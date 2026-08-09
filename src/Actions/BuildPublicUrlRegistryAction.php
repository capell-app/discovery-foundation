<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\DiscoveryFoundation\Contracts\PublicUrlContributor;
use Capell\DiscoveryFoundation\Data\PublicUrlData;
use Capell\DiscoveryFoundation\Data\PublicUrlRegistryEntryData;
use Capell\DiscoveryFoundation\Enums\PublicUrlContentType;
use Capell\DiscoveryFoundation\Enums\PublicUrlIndexability;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Collection<int, PublicUrlRegistryEntryData> run()
 */
final class BuildPublicUrlRegistryAction
{
    use AsFake;
    use AsObject;

    /**
     * @return Collection<int, PublicUrlRegistryEntryData>
     */
    public function handle(): Collection
    {
        return collect(app()->tagged(PublicUrlContributor::TAG))
            ->filter(fn (mixed $contributor): bool => $contributor instanceof PublicUrlContributor)
            ->flatMap(fn (PublicUrlContributor $contributor): Collection => $contributor->publicUrls())
            ->map(function (mixed $publicUrl): ?PublicUrlRegistryEntryData {
                $data = $this->toPublicUrlData($publicUrl);

                return $data instanceof PublicUrlData ? $this->normalize($data) : null;
            })
            ->filter(fn (?PublicUrlRegistryEntryData $entry): bool => $entry instanceof PublicUrlRegistryEntryData)
            ->reduce(function (Collection $registry, PublicUrlRegistryEntryData $entry): Collection {
                $key = $this->registryKey($entry);
                $existingEntry = $registry->get($key);

                $registry->put($key, $existingEntry instanceof PublicUrlRegistryEntryData
                    ? $this->mergeDuplicateEntry($existingEntry, $entry)
                    : $entry);

                return $registry;
            }, collect())
            ->values();
    }

    private function toPublicUrlData(mixed $publicUrl): ?PublicUrlData
    {
        if ($publicUrl instanceof PublicUrlData) {
            return $publicUrl;
        }

        if (! is_object($publicUrl)
            || ! isset($publicUrl->canonicalUrl, $publicUrl->sourcePackage, $publicUrl->site, $publicUrl->language)
            || ! $publicUrl->site instanceof Site
            || ! $publicUrl->language instanceof Language) {
            return null;
        }

        $indexability = $publicUrl->indexability ?? PublicUrlIndexability::Indexable;
        $contentType = $publicUrl->contentType ?? PublicUrlContentType::Page;
        $indexabilityValue = $indexability instanceof PublicUrlIndexability
            ? $indexability
            : (is_object($indexability) && is_string($indexability->value ?? null) ? $indexability->value : $indexability);
        $contentTypeValue = $contentType instanceof PublicUrlContentType
            ? $contentType
            : (is_object($contentType) && is_string($contentType->value ?? null) ? $contentType->value : $contentType);

        return new PublicUrlData(
            canonicalUrl: is_string($publicUrl->canonicalUrl) ? $publicUrl->canonicalUrl : '',
            sourcePackage: is_string($publicUrl->sourcePackage) ? $publicUrl->sourcePackage : '',
            site: $publicUrl->site,
            language: $publicUrl->language,
            routeName: is_string($publicUrl->routeName ?? null) ? $publicUrl->routeName : null,
            lastModified: isset($publicUrl->lastModified) && $publicUrl->lastModified instanceof CarbonInterface
                ? $publicUrl->lastModified
                : null,
            indexability: $indexabilityValue instanceof PublicUrlIndexability
                ? $indexabilityValue
                : (is_string($indexabilityValue) ? PublicUrlIndexability::tryFrom($indexabilityValue) : null) ?? PublicUrlIndexability::Indexable,
            robotsDirectives: is_array($publicUrl->robotsDirectives ?? null) ? $publicUrl->robotsDirectives : [],
            contentType: $contentTypeValue instanceof PublicUrlContentType
                ? $contentTypeValue
                : (is_string($contentTypeValue) ? PublicUrlContentType::tryFrom($contentTypeValue) : null) ?? PublicUrlContentType::Page,
            isSitemapEligible: ($publicUrl->isSitemapEligible ?? true) === true,
            isAiDiscoveryEligible: ($publicUrl->isAiDiscoveryEligible ?? true) === true,
            priority: is_string($publicUrl->priority ?? null) ? $publicUrl->priority : null,
            changeFrequency: is_string($publicUrl->changeFrequency ?? null) ? $publicUrl->changeFrequency : null,
            title: is_string($publicUrl->title ?? null) ? $publicUrl->title : null,
        );
    }

    private function normalize(PublicUrlData $publicUrl): ?PublicUrlRegistryEntryData
    {
        $canonicalUrl = $this->normalizeCanonicalUrl($publicUrl->canonicalUrl);

        if ($canonicalUrl === null) {
            return null;
        }

        $robotsDirectives = $this->normalizeRobotsDirectives($publicUrl->robotsDirectives);
        $isIndexable = $publicUrl->indexability->isIndexable()
            && ! in_array(PublicUrlIndexability::NoIndex->value, $robotsDirectives, true);

        return new PublicUrlRegistryEntryData(
            canonicalUrl: $canonicalUrl,
            sourcePackage: $this->normalizeRequiredString($publicUrl->sourcePackage, 'unknown'),
            siteKey: $this->normalizeModelKey($publicUrl->site),
            languageKey: $this->normalizeModelKey($publicUrl->language),
            siteId: $this->normalizeIntegerModelKey($publicUrl->site),
            languageId: $this->normalizeIntegerModelKey($publicUrl->language),
            languageCode: $this->normalizeOptionalString($publicUrl->language->code ?? $publicUrl->language->locale ?? null),
            routeName: $this->normalizeOptionalString($publicUrl->routeName),
            lastModified: $this->normalizeLastModified($publicUrl->lastModified),
            indexability: $isIndexable ? PublicUrlIndexability::Indexable : PublicUrlIndexability::NoIndex,
            robotsDirectives: $robotsDirectives,
            contentType: $publicUrl->contentType,
            isSitemapEligible: $publicUrl->isSitemapEligible && $isIndexable,
            isAiDiscoveryEligible: $publicUrl->isAiDiscoveryEligible && $isIndexable,
            priority: $this->normalizeOptionalString($publicUrl->priority),
            changeFrequency: $this->normalizeOptionalString($publicUrl->changeFrequency),
            title: $this->normalizeOptionalString($publicUrl->title),
        );
    }

    private function normalizeCanonicalUrl(string $canonicalUrl): ?string
    {
        $canonicalUrl = trim($canonicalUrl);

        if ($canonicalUrl === '') {
            return null;
        }

        $parts = parse_url($canonicalUrl);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        if ($host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = isset($parts['path']) ? '/' . ltrim($parts['path'], '/') : '';
        $path = $path === '/' ? '' : rtrim($path, '/');

        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $host . $port . $path . $query;
    }

    private function registryKey(PublicUrlRegistryEntryData $entry): string
    {
        return implode('|', [
            (string) $entry->siteKey,
            (string) $entry->languageKey,
            $entry->canonicalUrl,
        ]);
    }

    private function mergeDuplicateEntry(
        PublicUrlRegistryEntryData $existingEntry,
        PublicUrlRegistryEntryData $duplicateEntry,
    ): PublicUrlRegistryEntryData {
        $hasNoIndexEntry = $existingEntry->indexability === PublicUrlIndexability::NoIndex
            || $duplicateEntry->indexability === PublicUrlIndexability::NoIndex;

        return new PublicUrlRegistryEntryData(
            canonicalUrl: $existingEntry->canonicalUrl,
            sourcePackage: $existingEntry->sourcePackage,
            siteKey: $existingEntry->siteKey,
            languageKey: $existingEntry->languageKey,
            siteId: $existingEntry->siteId,
            languageId: $existingEntry->languageId,
            languageCode: $existingEntry->languageCode,
            routeName: $existingEntry->routeName,
            lastModified: $this->latestLastModified($existingEntry->lastModified, $duplicateEntry->lastModified),
            indexability: $hasNoIndexEntry ? PublicUrlIndexability::NoIndex : PublicUrlIndexability::Indexable,
            robotsDirectives: $this->mergeRobotsDirectives($existingEntry->robotsDirectives, $duplicateEntry->robotsDirectives),
            contentType: $existingEntry->contentType,
            isSitemapEligible: $existingEntry->isSitemapEligible && $duplicateEntry->isSitemapEligible,
            isAiDiscoveryEligible: $existingEntry->isAiDiscoveryEligible && $duplicateEntry->isAiDiscoveryEligible,
            priority: $existingEntry->priority,
            changeFrequency: $existingEntry->changeFrequency,
            title: $existingEntry->title ?? $duplicateEntry->title,
        );
    }

    /**
     * @param  array<int, string>  $existingDirectives
     * @param  array<int, string>  $duplicateDirectives
     * @return array<int, string>
     */
    private function mergeRobotsDirectives(array $existingDirectives, array $duplicateDirectives): array
    {
        return collect($existingDirectives)
            ->merge($duplicateDirectives)
            ->unique()
            ->values()
            ->all();
    }

    private function latestLastModified(?CarbonImmutable $existingLastModified, ?CarbonImmutable $duplicateLastModified): ?CarbonImmutable
    {
        if (! $existingLastModified instanceof CarbonImmutable) {
            return $duplicateLastModified;
        }

        if (! $duplicateLastModified instanceof CarbonImmutable) {
            return $existingLastModified;
        }

        return $duplicateLastModified->greaterThan($existingLastModified)
            ? $duplicateLastModified
            : $existingLastModified;
    }

    /**
     * @param  array<array-key, mixed>  $robotsDirectives
     * @return array<int, string>
     */
    private function normalizeRobotsDirectives(array $robotsDirectives): array
    {
        return collect($robotsDirectives)
            ->filter(fn (mixed $value, mixed $key): bool => is_string($key) ? $value === true : is_string($value))
            ->map(fn (mixed $value, mixed $key): string => is_string($key) ? $key : (string) $value)
            ->map(fn (string $directive): string => strtolower(trim($directive)))
            ->filter(fn (string $directive): bool => $directive !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeModelKey(Model $model): int|string
    {
        $key = $model->getKey();

        if (is_int($key) || is_string($key)) {
            return $key;
        }

        return $model::class . ':' . spl_object_id($model);
    }

    private function normalizeIntegerModelKey(Model $model): ?int
    {
        $key = $model->getKey();

        return is_numeric($key) ? (int) $key : null;
    }

    private function normalizeLastModified(?CarbonInterface $lastModified): ?CarbonImmutable
    {
        if (! $lastModified instanceof CarbonInterface) {
            return null;
        }

        return $lastModified instanceof CarbonImmutable
            ? $lastModified
            : CarbonImmutable::instance($lastModified);
    }

    private function normalizeRequiredString(string $value, string $fallback): string
    {
        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
