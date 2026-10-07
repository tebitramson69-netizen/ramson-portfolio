<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Content\ContentList;

final class ProcessController extends ContentListController
{
    protected function list(): ContentList
    {
        return ContentList::ProcessSteps;
    }

    protected function path(): string
    {
        return '/admin/process';
    }

    protected function intro(): string
    {
        return 'Roughly four steps, in your own words. For a client this answers the real '
            . 'question — what is working with you actually like? Numbers come from the order '
            . 'below, so there is nothing to renumber when you move a step.';
    }
}
