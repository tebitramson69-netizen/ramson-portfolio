<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * The two files crawlers ask for: sitemap.xml and robots.txt.
 *
 * A route rather than a file in public/, because the content is not static:
 * it has to reflect which projects are published right now. A file would be
 * correct the day it was written and wrong the first time a project was
 * published or unpublished from the admin, with nothing to notice it.
 *
 * It reads through findAllPublished(), the same published-only method the
 * public site uses. That is the point of the naming split in
 * ProjectRepository — a draft cannot leak into the sitemap here without
 * someone deliberately calling a findAll* method, and submitting a draft URL
 * to a search engine is exactly the kind of leak that is embarrassing and
 * hard to take back.
 */
final class SitemapController extends Controller
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        $urls = [[
            'loc'     => absolute_url('/'),
            'lastmod' => null,
            'changefreq' => 'weekly',
            'priority'   => '1.0',
        ]];

        foreach ($this->projects->findAllPublished() as $project) {
            $urls[] = [
                'loc'        => absolute_url($project->url()),
                'lastmod'    => $project->lastModified(),
                'changefreq' => 'monthly',
                'priority'   => '0.8',
            ];
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";

            if ($url['lastmod'] !== null) {
                $xml .= '    <lastmod>' . $url['lastmod'] . "</lastmod>\n";
            }

            $xml .= '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        return (new Response($xml))
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * robots.txt, generated for the same reason the sitemap is.
     *
     * It was a static file in public/ until Phase 9, with `Sitemap:
     * /sitemap.xml` in it. That is invalid: the robots.txt specification
     * requires the Sitemap directive to carry an ABSOLUTE URL, so crawlers
     * ignored the line — and it pointed at a 404 besides, because no sitemap
     * route existed. A static file cannot know the domain it is served from,
     * which is exactly why this is generated.
     *
     * The static file was deleted rather than left in place: public/.htaccess
     * serves any real file before consulting the front controller, so leaving
     * it would have silently kept the broken version winning.
     *
     * @param array<string, string> $params
     */
    public function robots(Request $request, array $params = []): Response
    {
        $lines = [
            'User-agent: *',
            // The admin is also guarded, 404s its drafts and is noindex'd on
            // every page. This line is courtesy to well-behaved crawlers, not
            // a security control - robots.txt is advisory and public.
            'Disallow: /admin',
            'Allow: /',
            '',
            'Sitemap: ' . absolute_url('/sitemap.xml'),
            '',
        ];

        return (new Response(implode("\n", $lines)))
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
