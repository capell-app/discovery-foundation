<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Support\PublicUrls;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\DiscoveryFoundation\Actions\DiscoverPublicPagesAction;
use Capell\DiscoveryFoundation\Contracts\PublicUrlContributor;
use Capell\DiscoveryFoundation\Data\DiscoverablePageData;
use Capell\DiscoveryFoundation\Data\PublicUrlData;
use Capell\DiscoveryFoundation\Enums\PublicUrlContentType;
use Illuminate\Support\Collection;

final class CmsPagePublicUrlContributor implements PublicUrlContributor
{
    /**
     * @return Collection<int, PublicUrlData>
     */
    public function publicUrls(): Collection
    {
        return SiteDomain::query()
            ->with(['site', 'language'])
            ->get()
            ->filter(fn (SiteDomain $domain): bool => $domain->site instanceof Site && $domain->language instanceof Language)
            ->flatMap(fn (SiteDomain $domain): Collection => $this->publicUrlsForDomain($domain))
            ->unique(fn (PublicUrlData $url): string => $this->modelIdentifier($url->site) . '|' . $this->modelIdentifier($url->language) . '|' . $url->canonicalUrl)
            ->values();
    }

    /**
     * @return Collection<int, PublicUrlData>
     */
    private function publicUrlsForDomain(SiteDomain $domain): Collection
    {
        $site = $domain->site;
        $language = $domain->language;

        if (! $site instanceof Site || ! $language instanceof Language) {
            return collect();
        }

        return DiscoverPublicPagesAction::run($site, $language)
            ->map(fn (DiscoverablePageData $page): PublicUrlData => new PublicUrlData(
                canonicalUrl: $page->url,
                sourcePackage: 'capell-app/discovery-foundation',
                site: $site,
                language: $language,
                routeName: 'capell.pages.show',
                lastModified: $page->lastModified,
                contentType: PublicUrlContentType::Page,
                isSitemapEligible: true,
                isAiDiscoveryEligible: true,
                priority: is_numeric($page->priority) ? number_format((float) $page->priority, 1, '.', '') : null,
                changeFrequency: $page->changeFrequency,
                title: $page->title,
            ));
    }

    private function modelIdentifier(Site|Language $model): int|string
    {
        $key = $model->getKey();

        return is_int($key) || is_string($key) ? $key : (string) spl_object_id($model);
    }
}
