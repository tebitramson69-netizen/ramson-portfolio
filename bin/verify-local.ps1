<#
.SYNOPSIS
    Local environment verification for Windows + XAMPP (Phases 2-5).

.DESCRIPTION
    Discovers the local toolchain, checks configuration, creates the
    application database if it is missing, runs migrations and seeds, starts
    the development server, exercises every route, and performs a security
    sanity check.

    SAFE BY DESIGN. This script only ever:
      - reads files and queries information_schema
      - runs CREATE DATABASE IF NOT EXISTS (never DROP, never ALTER)
      - runs the project's own migration runner
      - starts and then stops a development server it started itself

    It never drops a database, never resets one, never touches student_db or
    any unrelated schema, and never prints a password.

.EXAMPLE
    cd C:\xampp1\htdocs\ramson-portfolio
    powershell -ExecutionPolicy Bypass -File bin\verify-local.ps1
#>

[CmdletBinding()]
param(
    [string] $XamppRoot = '',
    [switch] $SkipDatabase
)

$ErrorActionPreference = 'Continue'
$script:Results = @()
$script:Root = Split-Path -Parent $PSScriptRoot

function Section($Title) {
    Write-Host ''
    Write-Host ('=' * 70) -ForegroundColor DarkGray
    Write-Host "  $Title" -ForegroundColor Cyan
    Write-Host ('=' * 70) -ForegroundColor DarkGray
}

function Report($Area, $Status, $Detail) {
    $script:Results += [pscustomobject]@{ Area = $Area; Status = $Status; Detail = $Detail }
    $colour = switch ($Status) { 'PASS' { 'Green' } 'FAIL' { 'Red' } default { 'Yellow' } }
    $tag    = switch ($Status) { 'PASS' { '[PASS]' } 'FAIL' { '[FAIL]' } default { '[WARN]' } }
    Write-Host ("  {0,-7} {1,-34} {2}" -f $tag, $Area, $Detail) -ForegroundColor $colour
}

function Find-First([string[]] $Candidates) {
    foreach ($c in $Candidates) { if ($c -and (Test-Path -LiteralPath $c)) { return (Resolve-Path -LiteralPath $c).Path } }
    return $null
}

function Assert-PageContent {
    <#
        Runs every content assertion against ONE fetched page.

        The guard matters more than the assertions: if the body is empty, the
        checks are reported as FAIL rather than run. Running a "-notmatch"
        assertion against an empty string returns true, so without this guard
        a failed fetch silently reports "CareerForge absent: PASS" — a check
        that passed without ever seeing a page. A verification script that can
        pass vacuously is worse than no script.
    #>
    param(
        [Parameter(Mandatory)] [string] $Label,
        $Page
    )

    $code = if ($Page) { [int] $Page.Code } else { 0 }
    $body = if ($Page -and $Page.Body) { [string] $Page.Body } else { '' }

    # Two independent gates, because ONE is not enough. An emptiness check
    # alone still lets a transport failure through whenever the caller puts
    # something in Body; a status check alone still lets a 200-with-empty-body
    # through. Assertions run only when the response is genuinely usable.
    if ($code -lt 200 -or $code -ge 400) {
        $why = if ($Page -and $Page.Error) { $Page.Error } else { "HTTP $code" }
        Report "$Label content" 'FAIL' "no usable response ($why) - assertions NOT run, not passed"
        return
    }

    if ([string]::IsNullOrWhiteSpace($body)) {
        Report "$Label content" 'FAIL' "empty body (HTTP $code) - assertions NOT run, not passed"
        return
    }

    Report "$Label body fetched" 'PASS' "HTTP $code, $($body.Length) bytes"

    $must = [ordered]@{
        'full name'            = 'Tebit Ramson Titih'
        'value proposition'    = 'practical web systems'
        'Rendo'                = 'Rendo'
        'School Mgmt System'   = 'School Management System'
        'RT monogram fallback' = 'portrait__monogram[^>]*>RT<'
    }
    foreach ($k in $must.Keys) {
        Report "$Label : $k" $(if ($body -match $must[$k]) { 'PASS' } else { 'FAIL' }) ''
    }

    $mustNot = [ordered]@{
        'CareerForge absent'       = '(?i)careerforge'
        'bare "Ramson Titih" absent' = '(?<!Tebit )Ramson Titih'
        'no inline style attrs'    = 'style="'
        'no PHP warnings/notices'  = '(?i)(<b>Warning</b>|<b>Notice</b>|<b>Fatal error</b>|Deprecated:)'
    }
    foreach ($k in $mustNot.Keys) {
        Report "$Label : $k" $(if ($body -notmatch $mustNot[$k]) { 'PASS' } else { 'FAIL' }) ''
    }
}

function Get-Status([string] $Url, [string] $Method = 'GET') {
    <#
        Deliberately NOT Invoke-WebRequest.

        A redirect has to be observable, and -MaximumRedirection 0 does not
        report one identically across PowerShell versions or hosts. On Windows
        PowerShell 5.1 an admin route that answers 302 came back as code 0 -
        indistinguishable from a connection failure, which is the same class of
        bug as the vacuous assertions fixed above.

        HttpWebRequest with AllowAutoRedirect disabled returns a 3xx as an
        ordinary response on every version, so "the guard redirects" is proven
        rather than inferred. Any genuine transport failure now carries the
        exception type AND message, so a future 0 says why instead of leaving
        it to be guessed.
    #>
    try {
        $request = [System.Net.WebRequest]::Create($Url)
    } catch {
        return [pscustomobject]@{ Code = 0; Body = ''; Headers = $null; Error = "bad URL: $($_.Exception.Message)" }
    }

    $request.Method            = $Method
    $request.AllowAutoRedirect = $false
    $request.Timeout           = 15000
    $request.UserAgent         = 'verify-local.ps1'

    $response = $null
    try {
        if ($Method -eq 'POST') {
            # An empty body still needs the stream opened and closed, or the
            # request is never sent.
            $request.ContentType   = 'application/x-www-form-urlencoded'
            $request.ContentLength = 0
            $request.GetRequestStream().Close()
        }
        $response = $request.GetResponse()
    } catch [System.Net.WebException] {
        $response = $_.Exception.Response
        if (-not $response) {
            return [pscustomobject]@{ Code = 0; Body = ''; Headers = $null; Error = "WebException/$($_.Exception.Status): $($_.Exception.Message)" }
        }
    } catch {
        return [pscustomobject]@{ Code = 0; Body = ''; Headers = $null; Error = "$($_.Exception.GetType().Name): $($_.Exception.Message)" }
    }

    $body = ''
    try {
        $stream = $response.GetResponseStream()
        if ($stream) {
            $reader = New-Object System.IO.StreamReader($stream)
            $body = $reader.ReadToEnd()
            $reader.Close()
        }
    } catch { }

    $code    = [int] $response.StatusCode
    $headers = $response.Headers
    $response.Close()

    return [pscustomobject]@{ Code = $code; Body = $body; Headers = $headers; Error = '' }
}

Write-Host ''
Write-Host '  Local verification (Phases 2-5) - Tebit Ramson Titih portfolio' -ForegroundColor White
Write-Host "  Project: $script:Root"

# =====================================================================
Section 'STEP 1 - ENVIRONMENT'
# =====================================================================

Write-Host "  PowerShell     : $($PSVersionTable.PSVersion)"
Write-Host "  OS             : $([System.Environment]::OSVersion.VersionString)"
Write-Host "  Working dir    : $(Get-Location)"

# --- XAMPP discovery: search, do not assume -------------------------------
if (-not $XamppRoot) {
    $guesses = @('C:\xampp1', 'C:\xampp', 'D:\xampp1', 'D:\xampp', 'C:\XAMPP')
    # Derive from the project path too: ...\xampp1\htdocs\ramson-portfolio
    $fromProject = Split-Path -Parent (Split-Path -Parent $script:Root)
    if ($fromProject) { $guesses = @($fromProject) + $guesses }
    foreach ($g in $guesses) {
        if ((Test-Path (Join-Path $g 'php\php.exe')) -or (Test-Path (Join-Path $g 'mysql\bin'))) { $XamppRoot = $g; break }
    }
}

if ($XamppRoot) { Report 'XAMPP root' 'PASS' $XamppRoot }
else            { Report 'XAMPP root' 'FAIL' 'Not found. Re-run with -XamppRoot C:\path\to\xampp' }

$php = Find-First @(
    (Join-Path $XamppRoot 'php\php.exe'),
    (Get-Command php -ErrorAction SilentlyContinue | Select-Object -Expand Source -First 1)
)
$mysql = Find-First @(
    (Join-Path $XamppRoot 'mysql\bin\mysql.exe'),
    (Join-Path $XamppRoot 'mysql\bin\mariadb.exe'),
    (Get-Command mysql -ErrorAction SilentlyContinue | Select-Object -Expand Source -First 1)
)
$apacheExe = Find-First @((Join-Path $XamppRoot 'apache\bin\httpd.exe'))

if ($php)   { Report 'PHP executable' 'PASS' "$php  ($(& $php -r 'echo PHP_VERSION;'))" }
else        { Report 'PHP executable' 'FAIL' 'php.exe not found' }
if ($mysql) { Report 'MySQL client'   'PASS' $mysql }
else        { Report 'MySQL client'   'FAIL' 'mysql.exe not found' }
if ($apacheExe) { Report 'Apache binary' 'PASS' $apacheExe } else { Report 'Apache binary' 'WARN' 'httpd.exe not found' }

# --- running services ------------------------------------------------------
$mysqlProc  = Get-Process -Name 'mysqld','mariadbd' -ErrorAction SilentlyContinue
$apacheProc = Get-Process -Name 'httpd'             -ErrorAction SilentlyContinue
Report 'MySQL running'  $(if ($mysqlProc)  { 'PASS' } else { 'WARN' }) $(if ($mysqlProc)  { "PID $($mysqlProc[0].Id)" } else { 'not running - start it in XAMPP Control Panel' })
Report 'Apache running' $(if ($apacheProc) { 'PASS' } else { 'WARN' }) $(if ($apacheProc) { "PID $($apacheProc[0].Id)" } else { 'not running - start it in XAMPP Control Panel' })

foreach ($port in 3306, 80) {
    $inUse = $null -ne (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)
    Report "Port $port listening" $(if ($inUse) { 'PASS' } else { 'WARN' }) $(if ($inUse) { 'in use (expected)' } else { 'nothing listening' })
}

# --- required files --------------------------------------------------------
$required = @(
    'config\config.php', 'config\config.example.php', 'bin\migrate.php',
    'database\migrations', 'database\seeds', 'public\index.php',
    'public\.htaccess', 'public\uploads\.htaccess', '.htaccess'
)
$missing = @()
foreach ($rel in $required) {
    if (-not (Test-Path (Join-Path $script:Root $rel))) { $missing += $rel }
}
Report 'Required files' $(if ($missing.Count -eq 0) { 'PASS' } else { 'FAIL' }) $(if ($missing.Count -eq 0) { "$($required.Count) present" } else { "missing: $($missing -join ', ')" })

# =====================================================================
Section 'STEP 2 - CONFIGURATION (no secrets printed)'
# =====================================================================

$probe = Join-Path $script:Root 'storage\cache\_verify_probe.php'
@'
<?php
require dirname(__DIR__, 2) . "/src/Core/Autoloader.php";
App\Core\Autoloader::register("App", dirname(__DIR__, 2) . "/src");
App\Core\Config::load(dirname(__DIR__, 2) . "/config");
$pw = (string) App\Core\Config::get("database.password");
echo json_encode([
    "env"      => App\Core\Config::get("app.env"),
    "debug"    => App\Core\Config::get("app.debug") ? "true" : "false",
    "url"      => App\Core\Config::get("app.url"),
    "timezone" => App\Core\Config::get("app.timezone"),
    "db_host"  => App\Core\Config::get("database.host"),
    "db_port"  => App\Core\Config::get("database.port"),
    "db_name"  => App\Core\Config::get("database.database"),
    "db_user"  => App\Core\Config::get("database.username"),
    "db_pass"  => $pw === "" ? "(empty)" : "(set, " . strlen($pw) . " chars)",
    "charset"  => App\Core\Config::get("database.charset"),
]);
'@ | Set-Content -LiteralPath $probe -Encoding UTF8

$cfg = $null
if ($php) {
    $raw = & $php $probe 2>&1 | Out-String
    try { $cfg = $raw.Trim() | ConvertFrom-Json } catch { Report 'Config load' 'FAIL' $raw.Trim() }
}
Remove-Item -LiteralPath $probe -ErrorAction SilentlyContinue

if ($cfg) {
    Write-Host "    env=$($cfg.env)  debug=$($cfg.debug)  timezone=$($cfg.timezone)"
    Write-Host "    url=$($cfg.url)"
    Write-Host "    db=$($cfg.db_user)@$($cfg.db_host):$($cfg.db_port)/$($cfg.db_name)  password=$($cfg.db_pass)  charset=$($cfg.charset)"
    Report 'Config load' 'PASS' 'config/config.php parsed'
    Report 'Charset utf8mb4' $(if ($cfg.charset -eq 'utf8mb4') { 'PASS' } else { 'FAIL' }) $cfg.charset
    if ($cfg.env -ne 'local')  { Report 'APP_ENV' 'WARN' "$($cfg.env) - expected 'local' on your machine" } else { Report 'APP_ENV' 'PASS' 'local' }
}

# =====================================================================
Section 'STEP 3-5 - DATABASE'
# =====================================================================

$dbName = if ($cfg) { $cfg.db_name } else { 'ramson_portfolio' }
$dbUser = if ($cfg) { $cfg.db_user } else { 'root' }

function Invoke-MySql([string] $Sql, [string] $Database = '') {
    $a = @('-u', $dbUser, '--batch', '--skip-column-names')
    if ($cfg -and $cfg.db_host) { $a += @('-h', $cfg.db_host) }
    if ($cfg -and $cfg.db_port) { $a += @('-P', "$($cfg.db_port)") }
    if ($Database) { $a += $Database }
    $a += @('-e', $Sql)
    return (& $mysql @a 2>&1 | Out-String).Trim()
}

if (-not $SkipDatabase -and $mysql -and $mysqlProc) {

    $exists = Invoke-MySql "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$dbName';"

    if ($exists -match 'Access denied') {
        Report 'Database connect' 'FAIL' 'Access denied - the DB user/password in config/config.php is wrong'
    }
    elseif ($exists -eq '0') {
        Report 'Database exists' 'WARN' "$dbName absent - creating it"
        # IF NOT EXISTS: cannot clobber or reset an existing database.
        Invoke-MySql "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | Out-Null
        $exists = Invoke-MySql "SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$dbName';"
        Report 'Database created' $(if ($exists -eq '1') { 'PASS' } else { 'FAIL' }) $dbName
    }
    else {
        Report 'Database exists' 'PASS' "$dbName already present - left untouched"
    }

    $meta = Invoke-MySql "SELECT CONCAT(DEFAULT_CHARACTER_SET_NAME,'/',DEFAULT_COLLATION_NAME) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$dbName';"
    Report 'Charset / collation' $(if ($meta -eq 'utf8mb4/utf8mb4_unicode_ci') { 'PASS' } else { 'WARN' }) $meta

} elseif (-not $mysqlProc) {
    Report 'Database' 'WARN' 'MySQL not running - skipped. Start it in XAMPP Control Panel.'
}

# =====================================================================
Section 'STEP 6-8 - MIGRATIONS, STATUS, SEED'
# =====================================================================

if ($php -and $mysqlProc) {
    Push-Location $script:Root
    Write-Host '  > php bin\migrate.php --seed' -ForegroundColor DarkGray
    $out = & $php 'bin\migrate.php' '--seed' 2>&1 | Out-String
    Write-Host ($out.TrimEnd())
    Report 'Migrations' $(if ($LASTEXITCODE -eq 0) { 'PASS' } else { 'FAIL' }) "exit $LASTEXITCODE"

    Write-Host '  > php bin\migrate.php --status' -ForegroundColor DarkGray
    $status = & $php 'bin\migrate.php' '--status' 2>&1 | Out-String
    Write-Host ($status.TrimEnd())

    $fileCount = (Get-ChildItem (Join-Path $script:Root 'database\migrations') -Filter '*.sql').Count
    $appliedOk = $status -match "Applied \((\d+)\)"
    $applied   = if ($appliedOk) { [int] $Matches[1] } else { -1 }
    $pendingOk = $status -match "Pending \((\d+)\)"
    $pending   = if ($pendingOk) { [int] $Matches[1] } else { -1 }
    Report 'Migration status' $(if ($applied -eq $fileCount -and $pending -eq 0) { 'PASS' } else { 'FAIL' }) "applied=$applied of $fileCount files, pending=$pending"

    $profileRow = Invoke-MySql "SELECT CONCAT_WS('|', full_name, monogram, availability_note) FROM profile;" $dbName
    Write-Host "    profile: $profileRow"
    $seedOk = ($profileRow -like '*Tebit Ramson Titih*') -and ($profileRow -like '*RT*') -and ($profileRow -like '*Open to select projects*')
    Report 'Seeded profile' $(if ($seedOk) { 'PASS' } else { 'FAIL' }) $(if ($seedOk) { 'name, monogram and availability correct' } else { 'unexpected values' })

    $settings = Invoke-MySql "SELECT COUNT(*) FROM settings;" $dbName
    Report 'Seeded settings' $(if ([int] $settings -ge 3) { 'PASS' } else { 'FAIL' }) "$settings rows"
    Pop-Location
}

# =====================================================================
Section 'STEP 9 - APPLICATION (PHP development server)'
# =====================================================================

$server = $null
if ($php) {
    $server = Start-Process -FilePath $php `
        -ArgumentList @('-S', '127.0.0.1:8123', '-t', 'public', 'bin\dev-server.php') `
        -WorkingDirectory $script:Root -PassThru -WindowStyle Hidden
    Start-Sleep -Seconds 3

    $cases = @(
        @{ Path = '/';                     Want = 200 },
        @{ Path = '/work/rendo';           Want = 200 },
        @{ Path = '/work/does-not-exist';  Want = 404 },
        @{ Path = '/definitely-not-a-page';Want = 404 },
        @{ Path = '/assets/css/main.css';  Want = 200 },
        @{ Path = '/assets/js/app.js';      Want = 200 },
        @{ Path = '/assets/js/admin.js';     Want = 200 },
        # The route guard: every admin path must send an anonymous visitor to
        # the login form, which is a 302. A 200 here would mean the guard is
        # not running.
        @{ Path = '/admin';                  Want = 302 },
        @{ Path = '/admin/profile';          Want = 302 },
        @{ Path = '/admin/login';            Want = 200 }
    )
    foreach ($c in $cases) {
        $r = Get-Status "http://127.0.0.1:8123$($c.Path)"
        $why = if ($r.Error) { " - $($r.Error)" } else { '' }
        Report "dev $($c.Path)" $(if ($r.Code -eq $c.Want) { 'PASS' } else { 'FAIL' }) "got $($r.Code), want $($c.Want)$why"
    }

    # NOTE: the variable below must NOT be called $home. $HOME is a read-only
    # PowerShell automatic variable; assigning to it fails, leaves a path
    # string in place, and every ".Body" then evaluates to $null. That made
    # each -match report FAIL and — far worse — each -notmatch report PASS
    # vacuously, so the "CareerForge absent" and "no inline styles" checks
    # were passing without ever looking at a page. Guarded properly below.
    $homePage = Get-Status 'http://127.0.0.1:8123/'
    Assert-PageContent -Label 'dev server' -Page $homePage

    # The guarded POST routes. A guard that only covers GET would leave the
    # upload and remove endpoints wide open, so each is probed separately
    # rather than assumed to be covered.
    foreach ($p in @('/admin/profile', '/admin/profile/photo', '/admin/profile/photo/alt', '/admin/profile/photo/remove')) {
        $r   = Get-Status "http://127.0.0.1:8123$p" 'POST'
        $why = if ($r.Error) { " - $($r.Error)" } else { '' }
        Report "POST $p guarded" $(if ($r.Code -eq 302) { 'PASS' } else { 'FAIL' }) "got $($r.Code), want 302 (redirect to login)$why"
    }
}

# =====================================================================
Section 'STEP 10 - APACHE'
# =====================================================================

$apacheBase = $null
if ($apacheProc) {
    # Subdirectory install is the XAMPP default: htdocs\<project>\public
    $leaf = Split-Path -Leaf $script:Root
    foreach ($candidate in @("http://localhost/$leaf/public", 'http://localhost')) {
        $probeR = Get-Status "$candidate/"
        if ($probeR.Code -eq 200 -and $probeR.Body -match 'Tebit Ramson Titih') { $apacheBase = $candidate; break }
    }
}

if ($apacheBase) {
    Report 'Apache serving project' 'PASS' $apacheBase
    foreach ($c in @(
        @{ P = '/';                    W = 200 },
        @{ P = '/work/rendo';          W = 200 },
        @{ P = '/work/does-not-exist'; W = 404 },
        @{ P = '/assets/css/main.css'; W = 200 }
    )) {
        $r = Get-Status "$apacheBase$($c.P)"
        $why = if ($r.Error) { " - $($r.Error)" } else { '' }
        Report "apache $($c.P)" $(if ($r.Code -eq $c.W) { 'PASS' } else { 'FAIL' }) "got $($r.Code), want $($c.W)$why"
    }

    $projectBase = $apacheBase -replace '/public$', ''
    Assert-PageContent -Label 'apache' -Page (Get-Status "$apacheBase/")

    # The root guard: these must NOT be served.
    foreach ($secret in @('/.git/config', '/config/config.php', '/config/config.example.php', '/src/Core/Database.php', '/composer.json')) {
        $r = Get-Status "$projectBase$secret"
        Report "apache blocks $secret" $(if ($r.Code -eq 403 -or $r.Code -eq 404) { 'PASS' } else { 'FAIL' }) "got $($r.Code) - must be 403/404"
    }
} elseif ($apacheProc) {
    Report 'Apache serving project' 'WARN' "Apache is running but the project was not found at http://localhost/$(Split-Path -Leaf $script:Root)/public"
} else {
    Report 'Apache' 'WARN' 'Apache not running - Apache checks skipped'
}

if ($server -and -not $server.HasExited) { Stop-Process -Id $server.Id -Force -ErrorAction SilentlyContinue }

# =====================================================================
Section 'STEP 11 - SECURITY'
# =====================================================================

Push-Location $script:Root
& git check-ignore -q 'config/config.php' 2>$null
Report 'config/config.php git-ignored' $(if ($LASTEXITCODE -eq 0) { 'PASS' } else { 'FAIL' }) ''

$tracked = & git ls-files 2>$null
Report 'config/config.php untracked' $(if ($tracked -notcontains 'config/config.php') { 'PASS' } else { 'FAIL' }) ''

$grepHits = & git grep -I -l -i -E "password\s*=>\s*'[^']+'" -- ':!config/config.example.php' ':!docs' 2>$null
Report 'No credentials in tracked files' $(if (-not $grepHits) { 'PASS' } else { 'FAIL' }) $(if ($grepHits) { "$grepHits" } else { 'clean' })

$src = Get-Content (Join-Path $script:Root 'src\Core\Database.php') -Raw
Report 'PDO emulation disabled' $(if ($src -match 'ATTR_EMULATE_PREPARES\s*=>\s*false') { 'PASS' } else { 'FAIL' }) ''
Report 'PDO exceptions on'      $(if ($src -match 'ERRMODE_EXCEPTION') { 'PASS' } else { 'FAIL' }) ''

$rawSql = & git grep -n -E '\$(_GET|_POST|_REQUEST)\[' -- 'src' 2>$null
Report 'No superglobals in src/' $(if (-not $rawSql) { 'PASS' } else { 'WARN' }) $(if ($rawSql) { 'review these' } else { 'request data flows through Request' })

$htRoot = Get-Content (Join-Path $script:Root '.htaccess') -Raw -ErrorAction SilentlyContinue
Report 'Root .htaccess denies all' $(if ($htRoot -match 'Require all denied') { 'PASS' } else { 'FAIL' }) ''
$htUp = Get-Content (Join-Path $script:Root 'public\uploads\.htaccess') -Raw -ErrorAction SilentlyContinue
Report 'Uploads deny executables' $(if ($htUp -match 'Require all denied') { 'PASS' } else { 'FAIL' }) ''
Pop-Location

# =====================================================================
Section 'STEP 12 - MEDIA PIPELINE (Phase 5)'
# =====================================================================

if ($php) {
    Push-Location $script:Root

    # The real assertions live in bin\verify-media.php so they run identically
    # on Windows and on a Linux host. This step runs it and reports its verdict
    # rather than duplicating the checks in PowerShell.
    $mediaOut = & $php 'bin\verify-media.php' 2>&1
    $mediaExit = $LASTEXITCODE
    $mediaOut | ForEach-Object { Write-Host "    $_" -ForegroundColor DarkGray }

    $summary = ($mediaOut | Where-Object { $_ -match '\d+ passed, \d+ failed' } | Select-Object -Last 1)
    Report 'Media pipeline self-check' $(if ($mediaExit -eq 0) { 'PASS' } else { 'FAIL' }) `
        $(if ($summary) { $summary.Trim() } else { "exit code $mediaExit" })

    Pop-Location
} else {
    Report 'Media pipeline self-check' 'WARN' 'php not found - skipped'
}

# Uploads directory rules, asserted against the file rather than guessed.
$htUp2 = Get-Content (Join-Path $script:Root 'public\uploads\.htaccess') -Raw -ErrorAction SilentlyContinue
Report 'Uploads cache is immutable' $(if ($htUp2 -match 'max-age=31536000, immutable') { 'PASS' } else { 'FAIL' }) `
    'safe only because every URL carries ?v=<updated_at>'
Report 'Uploads send nosniff'       $(if ($htUp2 -match 'X-Content-Type-Options') { 'PASS' } else { 'FAIL' }) ''
Report 'Uploads disable indexes'    $(if ($htUp2 -match 'Options -Indexes') { 'PASS' } else { 'FAIL' }) ''

# Under Apache, prove it: drop a .php file into public\uploads, request it, and
# delete it again. A directive that is present but not taking effect - because
# AllowOverride is None, say - passes a file-content check and fails this one.
if ($apacheBase) {
    $probeName = 'verify-probe.php'
    $probePath = Join-Path $script:Root ('public\uploads\' + $probeName)

    try {
        Set-Content -LiteralPath $probePath -Value '<?php echo "EXECUTED"; ?>' -Encoding ASCII
        $r = Get-Status "$apacheBase/uploads/$probeName"
        Report 'Apache refuses .php in uploads' $(if ($r.Code -eq 403 -or $r.Code -eq 404) { 'PASS' } else { 'FAIL' }) `
            "got $($r.Code) - must be 403/404, and the body must never contain EXECUTED"
        Report 'Uploaded .php is not executed' $(if ($r.Body -notmatch 'EXECUTED') { 'PASS' } else { 'FAIL' }) ''
    } finally {
        Remove-Item -LiteralPath $probePath -Force -ErrorAction SilentlyContinue
    }

    $r = Get-Status "$apacheBase/uploads/"
    Report 'Apache refuses an uploads listing' $(if ($r.Code -eq 403 -or $r.Code -eq 404) { 'PASS' } else { 'FAIL' }) "got $($r.Code)"
} else {
    Report 'Apache uploads rules' 'WARN' 'Apache not serving the project - upload directory rules not proven'
}

Write-Host ''
Write-Host '  The authenticated flow (upload, replace, remove) needs your admin' -ForegroundColor DarkGray
Write-Host '  password, so this script does not attempt it. Sign in at' -ForegroundColor DarkGray
Write-Host '  /admin/profile and check the three crops shown there.' -ForegroundColor DarkGray

# =====================================================================
Section 'STEP 13 - GIT'
# =====================================================================

Push-Location $script:Root
$branch = (& git rev-parse --abbrev-ref HEAD 2>$null)
$remote = (& git remote get-url origin 2>$null)
$dirty  = (& git status --porcelain 2>$null)
Write-Host "    branch=$branch"
Write-Host "    remote=$remote"
Report 'Working tree clean' $(if (-not $dirty) { 'PASS' } else { 'WARN' }) $(if ($dirty) { ($dirty -join '; ') } else { 'nothing uncommitted' })
Pop-Location

# =====================================================================
Section 'SUMMARY'
# =====================================================================

$pass = ($script:Results | Where-Object Status -eq 'PASS').Count
$warn = ($script:Results | Where-Object Status -eq 'WARN').Count
$fail = ($script:Results | Where-Object Status -eq 'FAIL').Count
Write-Host ''
Write-Host ("  PASS {0}    WARN {1}    FAIL {2}" -f $pass, $warn, $fail) -ForegroundColor White

if ($fail -gt 0) {
    Write-Host ''
    Write-Host '  Failures:' -ForegroundColor Red
    $script:Results | Where-Object Status -eq 'FAIL' | ForEach-Object { Write-Host "    - $($_.Area): $($_.Detail)" -ForegroundColor Red }
}
if ($warn -gt 0) {
    Write-Host ''
    Write-Host '  Needs attention:' -ForegroundColor Yellow
    $script:Results | Where-Object Status -eq 'WARN' | ForEach-Object { Write-Host "    - $($_.Area): $($_.Detail)" -ForegroundColor Yellow }
}

$reportPath = Join-Path $script:Root 'storage\logs\verify-local-report.txt'
$script:Results | Format-Table -AutoSize | Out-String -Width 200 | Set-Content -LiteralPath $reportPath -Encoding UTF8
Write-Host ''
Write-Host "  Full report written to: $reportPath" -ForegroundColor DarkGray
Write-Host '  Paste that file back into the conversation.' -ForegroundColor DarkGray
Write-Host ''
