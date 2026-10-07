<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Settings\SettingsWriter;

/**
 * Site settings — the handful of scalars the templates read by name.
 *
 * One form, saved whole. There are few enough of these that a per-field save
 * would be more chrome than content, and they are read together anyway.
 *
 * The screen offers no "add setting" button on purpose: a key is part of the
 * code, because a template reads it by name. See SettingsWriter.
 *
 * Every route carries the kernel's 'auth' guard, declared in routes/web.php.
 */
final class SettingsController extends AdminController
{
    private const HERE = '/admin/settings';

    /** @param array<string, string> $params */
    public function edit(Request $request, array $params = []): Response
    {
        return $this->adminPage('admin/settings', 'Settings', [
            'csrf'     => Csrf::token(),
            'settings' => (new SettingsWriter())->rows(),
        ]);
    }

    /** @param array<string, string> $params */
    public function update(Request $request, array $params = []): Response
    {
        if ($response = $this->requireCsrf($request, self::HERE)) {
            return $response;
        }

        $submitted = (array) ($request->post['settings'] ?? []);

        $changed = (new SettingsWriter())->updateMany($submitted);

        $this->flash('success', $changed === 0
            ? 'Nothing changed.'
            : sprintf('Saved %d setting%s.', $changed, $changed === 1 ? '' : 's'));

        return Response::redirect(route_url(self::HERE));
    }
}
