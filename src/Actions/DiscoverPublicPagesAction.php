<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Capell\Core\Actions\Content\ExtractTextContentAction;
use Capell\Core\Actions\PublicRead\PageUrlsArePubliclyReadableAction;
use Capell\Core\Data\Database\SqlFragment;
use Capell\Core\Enums\BlueprintGroupEnum;
use Capell\Core\Facades\CapellDatabase;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\TableName;
use Capell\DiscoveryFoundation\Data\DiscoverablePageData;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use RuntimeException;

/**
 * @method static Collection<int, DiscoverablePageData> run(Site $site, Language $language, array<int, int>|null $pageIds = null, ?SiteDomain $publishingDomain = null)
 */
final class DiscoverPublicPagesAction
{
    use AsFake;
    use AsObject;

    private const int BODY_TEXT_LIMIT = 1000;

    /**
     * @param  array<int, int>|null  $pageIds
     * @param  SiteDomain|null  $publishingDomain  the domain the output is served from; every address is built, and judged for public readability, on it
     * @return Collection<int, DiscoverablePageData>
     */
    public function handle(Site $site, Language $language, ?array $pageIds = null, ?SiteDomain $publishingDomain = null): Collection
    {
        // Compared directly rather than via isEnabled(), which throws for an unsaved model whose status is unset.
        if ($site->getAttribute('status') === false || $language->getAttribute('status') === false || $pageIds === []) {
            return collect();
        }

        $query = Page::query();

        if ($pageIds !== null) {
            $query->whereKey($pageIds);
        }

        $priority = CapellDatabase::for($query->getModel())->queryDialect()->jsonExtract(
            SqlFragment::raw($query->getQuery()->getGrammar()->wrap(TableName::resolve('pages') . '.meta')),
            '$.priority',
        );
        new SqlFragment(
            $priority->sql . ' AS meta_priority',
            $priority->bindings,
        )->applySelect($query->getQuery());

        $pages = $query->addSelect(TableName::resolve('pages') . '.*')
            ->with([
                'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language->id),
                // Every language's address travels with the page: the sitemap's hreflang cluster needs them.
                'pageUrls' => fn (BuilderContract $query): BuilderContract => $query
                    ->where('status', true)
                    ->whereNull('type'),
                'pageUrls.language',
                'pageUrls.siteDomain',
                'translations',
            ])
            // whereHas, not withWhereHas: its constraint would also narrow the eager load above to one language.
            ->whereHas(
                'pageUrls',
                fn (BuilderContract $query): BuilderContract => $query
                    ->where('language_id', $language->id)
                    ->where('status', true)
                    ->whereNull('type'),
            )
            ->withWhereHas(
                'blueprint',
                fn (BuilderContract $query): BuilderContract => $query
                    ->where(
                        fn (Builder $query): Builder => $query->whereNull('group')
                            ->orWhereIn('group', config('capell.core.sitemap.type_groups', [BlueprintGroupEnum::Default->value])),
                    )
                    ->enabled()
                    ->visible()
                    ->accessible(),
            )
            ->where($query->qualifyColumn('site_id'), $site->id)
            ->where(
                fn (Builder $query): Builder => $query->whereNull(TableName::resolve('pages') . '.meta')
                    ->orWhereJsonDoesntContain(TableName::resolve('pages') . '.meta->hidden', true),
            )
            ->where(
                fn (Builder $query): Builder => $query->whereNull(TableName::resolve('pages') . '.meta->robots')
                    ->orWhereJsonDoesntContain(TableName::resolve('pages') . '.meta->robots', 'noindex'),
            )
            ->publishedDate()
            ->ordered()
            ->get()
            ->filter(fn (Page $page): bool => ! $this->hasNoIndexDirective($page->meta ?? [])
                && ! $this->hasNoIndexDirective($page->translation->meta ?? []));

        // Publish only an address an anonymous visitor may read; a page behind a
        // restricted route is left out of every discovery output. Every address
        // is checked in one batch.
        $candidates = $pages->flatMap(static fn (Page $page): array => $page->pageUrls->filter(static fn (PageUrl $pageUrl): bool => $pageUrl->language_id === $language->id)->all())->values()->all();

        // The default-domain match is only a fallback: the output is served from, and judged on, the publishing domain.
        // Either way the address is settled before it is judged, so the page is published and checked at the same URL.
        foreach ($pages as $page) {
            foreach ($page->pageUrls as $candidate) {
                if ($candidate->language_id !== $language->id) {
                    continue;
                }

                if ($publishingDomain instanceof SiteDomain) {
                    $candidate->setRelation('siteDomain', $publishingDomain);

                    continue;
                }

                $page->setRelation('pageUrl', $candidate);
                $page->setRelation('site', $site);
                Page::setResolvedPageUrlSiteDomain($page, $site);
            }
        }

        $decisions = PageUrlsArePubliclyReadableAction::run($candidates);
        $readable = [];

        foreach ($candidates as $position => $candidate) {
            if ($decisions[$position]) {
                $readable[spl_object_id($candidate)] = true;
            }
        }

        return $pages
            ->map(function (Page $page) use ($site, $readable, $publishingDomain): ?DiscoverablePageData {
                $pageUrl = $page->pageUrls->first(
                    static fn (PageUrl $candidate): bool => isset($readable[spl_object_id($candidate)]),
                );

                if (! $pageUrl instanceof PageUrl) {
                    return null;
                }

                $page->setRelation('pageUrl', $pageUrl);
                $page->setRelation('site', $site);
                Page::setResolvedPageUrlSiteDomain($page, $site);

                if ($publishingDomain instanceof SiteDomain) {
                    $pageUrl->setRelation('siteDomain', $publishingDomain);
                }

                $pageId = $page->getKey();
                $updatedAt = $page->getAttribute('updated_at');

                if (! is_int($pageId)) {
                    throw new RuntimeException('Expected the discoverable page to have an integer key.');
                }

                return new DiscoverablePageData(
                    pageId: $pageId,
                    title: trim(strip_tags($page->translation->title ?? $page->translation->label ?? $page->name ?? '')),
                    url: $pageUrl->full_url,
                    lastModified: $updatedAt instanceof CarbonInterface ? $updatedAt : null,
                    priority: is_numeric($page->meta['priority'] ?? null) ? (float) $page->meta['priority'] : null,
                    changeFrequency: is_string($page->meta['changefreq'] ?? null) ? $page->meta['changefreq'] : null,
                    page: $page,
                    bodyText: $this->bodyText($page),
                );
            })
            ->filter(fn (?DiscoverablePageData $page): bool => $page instanceof DiscoverablePageData)
            ->filter(fn (DiscoverablePageData $page): bool => $page->url !== '')
            ->values();
    }

    /**
     * Visible text of a page that is already public, bounded so a large site's registry stays small.
     */
    private function bodyText(Page $page): ?string
    {
        $content = $page->translation->content ?? null;

        if (! is_string($content) && ! is_array($content)) {
            return null;
        }

        $text = trim(ExtractTextContentAction::run($content, self::BODY_TEXT_LIMIT));

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function hasNoIndexDirective(array $meta): bool
    {
        $directives = $meta['robots'] ?? [];

        if (is_string($directives)) {
            return strtolower(trim($directives)) === 'noindex';
        }

        if (! is_array($directives)) {
            return false;
        }

        return collect($directives)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key) ? $value === true : is_string($value))
            ->map(fn (mixed $value, mixed $key): string => is_string($key) ? $key : (string) $value)
            ->map(fn (string $directive): string => strtolower(trim($directive)))
            ->contains('noindex');
    }
}
