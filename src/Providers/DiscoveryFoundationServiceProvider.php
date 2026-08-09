<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Providers;

use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\DiscoveryFoundation\Contracts\PublicUrlContributor;
use Capell\DiscoveryFoundation\Support\PublicUrls\CmsPagePublicUrlContributor;
use Spatie\LaravelPackageTools\Package;

final class DiscoveryFoundationServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-discovery-foundation';

    public static string $packageName = 'capell-app/discovery-foundation';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasTranslations();
    }

    protected function bootInstalledPackage(): self
    {
        $this->app->singleton(CmsPagePublicUrlContributor::class);
        $this->app->tag([CmsPagePublicUrlContributor::class], PublicUrlContributor::TAG);

        return $this;
    }
}
