<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Contracts;

use Capell\DiscoveryFoundation\Data\PublicUrlData;
use Illuminate\Support\Collection;

interface PublicUrlContributor
{
    public const string TAG = 'capell-discovery-foundation:public-url-contributors';

    /**
     * @return Collection<int, PublicUrlData>
     */
    public function publicUrls(): Collection;
}
