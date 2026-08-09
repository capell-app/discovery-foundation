<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\DiscoveryFoundation\Actions\BuildPublicUrlRegistryAction;
use Capell\DiscoveryFoundation\Contracts\PublicUrlContributor;
use Capell\DiscoveryFoundation\Data\PublicUrlData;
use Capell\DiscoveryFoundation\Data\PublicUrlRegistryEntryData;
use Capell\DiscoveryFoundation\Enums\PublicUrlIndexability;
use Carbon\CarbonImmutable;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->originalContainer = Container::getInstance();
    Container::setInstance(new Container);
    $this->language = (new Language)->forceFill([
        'id' => 1,
        'name' => 'English',
        'locale' => 'en',
        'code' => 'en',
        'flag' => 'gb-eng',
        'status' => true,
        'default' => true,
        'order' => 1,
    ]);
    $this->site = (new Site)->forceFill([
        'id' => 1,
        'language_id' => $this->language->getKey(),
    ]);
});

afterEach(function (): void {
    Container::setInstance($this->originalContainer);
});

it('rejects non-public canonical URLs and falls back for empty optional registry fields', function (): void {
    $urls = collect([
        new PublicUrlData(
            canonicalUrl: '/relative',
            sourcePackage: 'capell-app/test',
            site: $this->site,
            language: $this->language,
        ),
        new PublicUrlData(
            canonicalUrl: 'javascript:alert(1)',
            sourcePackage: 'capell-app/test',
            site: $this->site,
            language: $this->language,
        ),
        new PublicUrlData(
            canonicalUrl: 'https:///missing-host',
            sourcePackage: 'capell-app/test',
            site: $this->site,
            language: $this->language,
        ),
        new PublicUrlData(
            canonicalUrl: 'https://Example.TEST/',
            sourcePackage: ' ',
            site: $this->site,
            language: $this->language,
            routeName: ' ',
            priority: ' ',
            changeFrequency: ' ',
            title: ' ',
        ),
    ]);
    $contributor = new readonly class($urls) implements PublicUrlContributor
    {
        /** @param Collection<int, PublicUrlData> $urls */
        public function __construct(private Collection $urls) {}

        /**
         * @return Collection<int, PublicUrlData>
         */
        public function publicUrls(): Collection
        {
            return $this->urls;
        }
    };

    app()->instance('discovery-foundation-url-validation-contributor', $contributor);
    app()->tag(['discovery-foundation-url-validation-contributor'], PublicUrlContributor::TAG);

    $entries = (new BuildPublicUrlRegistryAction)->handle();

    expect($entries)->toHaveCount(1)
        ->and($entries->first())->toBeInstanceOf(PublicUrlRegistryEntryData::class)
        ->and($entries->first()?->canonicalUrl)->toBe('https://example.test')
        ->and($entries->first()?->sourcePackage)->toBe('unknown')
        ->and($entries->first()?->routeName)->toBeNull()
        ->and($entries->first()?->priority)->toBeNull()
        ->and($entries->first()?->changeFrequency)->toBeNull()
        ->and($entries->first()?->title)->toBeNull();
});

it('deduplicates within site and language scope and merges duplicate eligibility conservatively', function (): void {
    $secondLanguage = (new Language)->forceFill([
        'id' => 2,
        'name' => 'French',
        'locale' => 'fr',
        'code' => 'fr',
        'flag' => 'fr',
        'status' => true,
        'default' => false,
        'order' => 2,
    ]);
    $secondSite = (new Site)->forceFill([
        'id' => 2,
        'language_id' => $this->language->getKey(),
    ]);
    $older = CarbonImmutable::parse('2026-08-01 10:00:00', 'UTC');
    $newer = CarbonImmutable::parse('2026-08-08 10:00:00', 'UTC');
    $contributor = new readonly class($this->site, $this->language, $secondSite, $secondLanguage, $older, $newer) implements PublicUrlContributor
    {
        public function __construct(
            private Site $site,
            private Language $language,
            private Site $secondSite,
            private Language $secondLanguage,
            private CarbonImmutable $older,
            private CarbonImmutable $newer,
        ) {}

        /**
         * @return Collection<int, PublicUrlData>
         */
        public function publicUrls(): Collection
        {
            return collect([
                new PublicUrlData(
                    canonicalUrl: 'https://EXAMPLE.test/about/',
                    sourcePackage: 'capell-app/first',
                    site: $this->site,
                    language: $this->language,
                    lastModified: $this->older,
                    robotsDirectives: ['INDEX', 'follow'],
                    title: 'About',
                ),
                new PublicUrlData(
                    canonicalUrl: 'https://example.test/about',
                    sourcePackage: 'capell-app/second',
                    site: $this->site,
                    language: $this->language,
                    lastModified: $this->newer,
                    indexability: PublicUrlIndexability::NoIndex,
                    robotsDirectives: ['noindex', 'nofollow'],
                    isSitemapEligible: true,
                    isAiDiscoveryEligible: true,
                ),
                new PublicUrlData(
                    canonicalUrl: 'https://example.test/about',
                    sourcePackage: 'capell-app/french',
                    site: $this->site,
                    language: $this->secondLanguage,
                ),
                new PublicUrlData(
                    canonicalUrl: 'https://example.test/about',
                    sourcePackage: 'capell-app/other-site',
                    site: $this->secondSite,
                    language: $this->language,
                ),
            ]);
        }
    };

    app()->instance('discovery-foundation-dedupe-contributor', $contributor);
    app()->tag(['discovery-foundation-dedupe-contributor'], PublicUrlContributor::TAG);

    $entries = (new BuildPublicUrlRegistryAction)->handle();
    $merged = $entries->firstWhere('siteKey', $this->site->getKey());

    expect($entries)->toHaveCount(3)
        ->and($merged)->toBeInstanceOf(PublicUrlRegistryEntryData::class)
        ->and($merged?->sourcePackage)->toBe('capell-app/first')
        ->and($merged?->indexability)->toBe(PublicUrlIndexability::NoIndex)
        ->and($merged?->robotsDirectives)->toBe(['index', 'follow', 'noindex', 'nofollow'])
        ->and($merged?->lastModified)->toBe($newer)
        ->and($merged?->isSitemapEligible)->toBeFalse()
        ->and($merged?->isAiDiscoveryEligible)->toBeFalse()
        ->and($merged?->title)->toBe('About');
});
