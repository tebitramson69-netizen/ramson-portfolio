<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Content\ContentList;

final class ServiceController extends ContentListController
{
    protected function list(): ContentList
    {
        return ContentList::Services;
    }

    protected function path(): string
    {
        return '/admin/services';
    }

    protected function intro(): string
    {
        return 'Three or four, written in business outcomes rather than technology names — '
            . 'this section is read by people who do not care what the stack is. '
            . 'Leave it empty and the section does not appear on the site at all.';
    }
}
