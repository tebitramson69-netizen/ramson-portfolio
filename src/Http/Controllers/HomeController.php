<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Seo;

final class HomeController extends Controller
{
    /** @param array<string, string> $params */
    public function index(Request $request, array $params = []): Response
    {
        $profile = $this->profiles->current();

        $meta = new Seo(
            title:       '',
            description: $this->settings->string('meta_description'),
            canonical:   absolute_url('/'),
            ogType:      'website',
            jsonLd:      $profile === null ? [] : [$this->personSchema($profile)],
        );

        return $this->page('pages/home', $meta, [], isHome: true);
    }

    /**
     * schema.org/Person, generated ENTIRELY from stored profile data.
     *
     * Building it from the same values the visitor sees is what stops the
     * structured data drifting from the page — and it means no claim can
     * appear here that is not already published and verified.
     *
     * @return array<string, mixed>
     */
    private function personSchema(\App\Domain\Profile\Profile $profile): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Person',
            'name'     => $profile->fullName,
            'jobTitle' => $profile->professionalTitle,
            'url'      => absolute_url('/'),
        ];

        if ($profile->location !== '') {
            $schema['address'] = [
                '@type'          => 'PostalAddress',
                'addressCountry' => $profile->location,
            ];
        }

        if ($profile->institution !== '') {
            $schema['alumniOf'] = [
                '@type' => 'EducationalOrganization',
                'name'  => $profile->institution,
            ];
        }

        $sameAs = array_values(array_filter([$profile->githubUrl, $profile->linkedinUrl]));

        if ($sameAs !== []) {
            $schema['sameAs'] = $sameAs;
        }

        if ($profile->hasPhoto()) {
            $url = $profile->photo?->url('og', 'jpeg') ?? $profile->photo?->url('hero', 'jpeg');
            if ($url !== null) {
                $schema['image'] = absolute_url($url);
            }
        }

        return $schema;
    }
}
