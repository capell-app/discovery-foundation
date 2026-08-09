<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\DiscoveryFoundation\Contracts\PublicUrlContributor;
use Capell\DiscoveryFoundation\Support\PublicUrls\CmsPagePublicUrlContributor;
use Illuminate\Support\Collection;

final class DiscoveryFoundationHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    /** @return Collection<int, DoctorCheckResultData> */
    public static function runDiagnostics(): Collection
    {
        $contributors = collect(app()->tagged(PublicUrlContributor::TAG));
        $registered = $contributors->contains(static fn (mixed $contributor): bool => $contributor instanceof CmsPagePublicUrlContributor);

        return collect([new DoctorCheckResultData(
            label: (string) __('capell-discovery-foundation::package.health.public_urls.label'),
            passed: $registered,
            message: $registered
                ? (string) __('capell-discovery-foundation::package.health.public_urls.passed', ['count' => $contributors->count()])
                : (string) __('capell-discovery-foundation::package.health.public_urls.failed'),
            remediation: $registered ? null : (string) __('capell-discovery-foundation::package.health.public_urls.remediation'),
        )]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }
}
