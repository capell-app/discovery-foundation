<?php

declare(strict_types=1);

use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\Publishing\PublishSentinel;
use Capell\DiscoveryFoundation\Actions\DiscoverPublicPagesAction;
use Capell\DiscoveryFoundation\Support\PublicUrls\CmsPagePublicUrlContributor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->language = Language::factory()->create(['status' => true]);
    $this->site = Site::factory()->language($this->language)->withTranslations($this->language)->create();
    $this->blueprint = Blueprint::factory()->page()->create([
        'status' => true,
        'meta' => ['hidden' => false, 'accessible' => true],
    ]);
    $this->firstPage = Page::factory()->site($this->site)->type($this->blueprint)
        ->withTranslations($this->language, ['title' => 'First public page'], slug: '/first')
        ->create(['meta' => ['hidden' => false], 'visible_from' => CarbonImmutable::now()->subDay()]);
    $this->secondPage = Page::factory()->site($this->site)->type($this->blueprint)
        ->withTranslations($this->language, ['title' => 'Second public page'], slug: '/second')
        ->create(['meta' => ['hidden' => false], 'visible_from' => CarbonImmutable::now()->subDay()]);
});

it('keeps discovering every public page when the page ID filter is omitted or null', function (): void {
    $expectedIds = [$this->firstPage->getKey(), $this->secondPage->getKey()];

    expect(DiscoverPublicPagesAction::run($this->site, $this->language)->pluck('pageId')->all())
        ->toEqualCanonicalizing($expectedIds)
        ->and(DiscoverPublicPagesAction::run($this->site, $this->language, null)->pluck('pageId')->all())
        ->toEqualCanonicalizing($expectedIds);
});

it('loads only the requested public pages before hydrating discovery data', function (): void {
    $retrieved = new Collection;
    Page::retrieved(function (Page $page) use ($retrieved): void {
        $retrieved->push($page->getKey());
    });

    $pages = DiscoverPublicPagesAction::run($this->site, $this->language, [$this->firstPage->getKey()]);

    expect($pages->pluck('pageId')->all())->toBe([$this->firstPage->getKey()]);

    expect($retrieved->all())->not->toBeEmpty()
        ->and($retrieved->all())->not->toContain($this->secondPage->getKey());
});

it('discovers no pages for an empty page ID filter', function (): void {
    expect(DiscoverPublicPagesAction::run($this->site, $this->language, []))->toBeEmpty();
});

it('discovers no pages for an unknown page ID', function (): void {
    expect(DiscoverPublicPagesAction::run($this->site, $this->language, [PHP_INT_MAX]))->toBeEmpty();
});

it('discovers no pages for a disabled site or language', function (): void {
    expect(DiscoverPublicPagesAction::run($this->site, $this->language))->toHaveCount(2);

    $this->site->update(['status' => false]);

    expect(DiscoverPublicPagesAction::run($this->site, $this->language))->toBeEmpty();

    $this->site->update(['status' => true]);
    $this->language->update(['status' => false]);

    expect(DiscoverPublicPagesAction::run($this->site, $this->language))->toBeEmpty();
});

it('contributes no CMS page URLs for a disabled site or through a disabled domain', function (): void {
    expect((new CmsPagePublicUrlContributor)->publicUrls())->toHaveCount(2);

    $this->site->update(['status' => false]);

    expect((new CmsPagePublicUrlContributor)->publicUrls())->toBeEmpty();

    $this->site->update(['status' => true]);
    SiteDomain::query()->where('site_id', $this->site->getKey())->update(['status' => false]);

    expect((new CmsPagePublicUrlContributor)->publicUrls())->toBeEmpty();
});

it('discovers CMS pages once per site and language with domain queries independent of page count', function (): void {
    $domain = SiteDomain::query()->where('site_id', $this->site->getKey())->firstOrFail();
    SiteDomain::factory()->create([
        'site_id' => $this->site->getKey(),
        'language_id' => $this->language->getKey(),
        'domain' => 'second-' . $domain->domain,
        'path' => $domain->path,
        'default' => false,
    ]);

    $countQueries = function (): array {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $urls = (new CmsPagePublicUrlContributor)->publicUrls();
            $queries = collect(DB::getQueryLog())->pluck('query');
        } finally {
            DB::disableQueryLog();
        }

        return [
            $urls,
            $queries->filter(fn (mixed $query): bool => is_string($query) && preg_match('/^select\b.*?\bfrom\s+["`]pages["`]/i', $query) === 1)->count(),
            $queries->filter(fn (mixed $query): bool => is_string($query) && preg_match('/\bfrom\s+["`]site_domains["`]/i', $query) === 1)->count(),
        ];
    };

    [$urls, $pageQueries, $domainQueries] = $countQueries();

    expect($urls)->toHaveCount(2)
        ->and($pageQueries)->toBe(1);

    Page::factory()->site($this->site)->type($this->blueprint)
        ->withTranslations($this->language, ['title' => 'Third public page'], slug: '/third')
        ->create(['meta' => ['hidden' => false], 'visible_from' => CarbonImmutable::now()->subDay()]);

    [$urls, , $domainQueriesWithMorePages] = $countQueries();

    expect($urls)->toHaveCount(3)
        ->and($domainQueriesWithMorePages)->toBe($domainQueries);
});

it('keeps site and publication eligibility when filtering by page IDs', function (): void {
    $otherSite = Site::factory()->language($this->language)->withTranslations($this->language)->create();
    $otherPage = Page::factory()->site($otherSite)->type($this->blueprint)
        ->withTranslations($this->language, slug: '/other')
        ->create(['meta' => ['hidden' => false], 'visible_from' => CarbonImmutable::now()->subDay()]);
    $this->secondPage->update(['visible_from' => PublishSentinel::draftValue()]);

    $pages = DiscoverPublicPagesAction::run($this->site, $this->language, [
        $this->firstPage->getKey(),
        $this->secondPage->getKey(),
        $otherPage->getKey(),
    ]);

    expect($pages->pluck('pageId')->all())->toBe([$this->firstPage->getKey()]);
});
