<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Admin\DashboardStats;

final class DashboardController extends AdminController
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        $stats = new DashboardStats();

        return $this->adminPage('admin/dashboard', 'Dashboard', [
            'counts'    => $stats->counts(),
            'checklist' => $stats->checklist(),
            'health'    => $stats->health(),
            'csrf'      => Csrf::token(),
        ]);
    }
}
