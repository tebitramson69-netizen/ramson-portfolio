<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\HttpNotFoundException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;

/**
 * Case-study pages.
 *
 * PHASE 2 SCOPE: the projects tables land in Phase 3, so the case-study
 * content is still the approved static Phase 1 markup, selected by slug from
 * the table below. The ROUTING and the 404 path are real — an unknown slug
 * returns a genuine 404 — which is what Phase 2 needs to establish.
 *
 * In Phase 3 this table is replaced by
 *     $project = $this->projects->findPublishedBySlug($slug);
 * and the unpublished-project check moves into the repository, where a
 * forgotten template condition cannot leak a draft.
 */
final class WorkController extends Controller
{
    /** @var array<string, array{template:string, title:string, description:string}> */
    private const CASE_STUDIES = [
        'rendo' => [
            'template'    => 'pages/work/rendo',
            'title'       => 'Rendo',
            'description' => 'Rendo: a business and booking automation platform helping clinics, '
                           . 'dental practices and beauty studios handle client messaging, bookings '
                           . 'and appointment reminders through WhatsApp.',
        ],
    ];

    /** @param array<string, string> $params */
    public function show(Request $request, array $params = []): Response
    {
        $slug = $params['slug'] ?? '';

        if (!isset(self::CASE_STUDIES[$slug])) {
            throw new HttpNotFoundException('No case study for slug: ' . $slug);
        }

        $study = self::CASE_STUDIES[$slug];

        $meta = new Seo(
            title:       $study['title'],
            description: $study['description'],
            canonical:   absolute_url('/work/' . $slug),
            ogType:      'article',
        );

        return $this->page($study['template'], $meta, ['slug' => $slug]);
    }
}
