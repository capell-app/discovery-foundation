<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\DiscoveryFoundation\Contracts\PublicUrlContributor;
use Capell\DiscoveryFoundation\Support\PublicUrls\CmsPagePublicUrlContributor;
use Override;
use Spatie\LaravelPackageTools\Package;

final class DiscoveryFoundationServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-discovery-foundation';

    public static string $packageName = 'capell-app/discovery-foundation';

    #[Override]
    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations();
    }

    #[Override]
    protected function bootInstalledRuntime(): void
    {
        $this->app->singleton(CmsPagePublicUrlContributor::class);
        $this->app->tag([CmsPagePublicUrlContributor::class], PublicUrlContributor::TAG);
    }
}
