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
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class CmsPagePublicUrlContributor implements PublicUrlContributor
{
    /**
     * @return Collection<int, PublicUrlData>
     */
    public function publicUrls(): Collection
    {
        // Page discovery depends only on site and language, so each pair is discovered once however many domains share it.
        // Eager-loading the site's enabled domains lets each discovered page resolve its domain without a query per page.
        return SiteDomain::query()
            ->enabled()
            ->whereHas('site', fn (Builder $query): Builder => $query->enabled())
            ->whereHas('language', fn (Builder $query): Builder => $query->enabled())
            ->with([
                'language',
                'site.siteDomains' => fn (BuilderContract $query): BuilderContract => $query
                    ->enabled()
                    ->whereHas('language', fn (Builder $query): Builder => $query->enabled())
                    ->orderBy('id'),
            ])
            ->orderBy('id')
            ->get()
            ->unique(fn (SiteDomain $domain): string => $domain->site_id . '|' . $domain->language_id)
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
