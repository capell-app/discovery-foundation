<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Enums;

use Filament\Support\Contracts\HasLabel;

enum PublicUrlIndexability: string implements HasLabel
{
    case Indexable = 'indexable';
    case NoIndex = 'noindex';

    public function getLabel(): string
    {
        return (string) __('capell-discovery-foundation::generic.public_url_indexability.' . $this->value);
    }

    public function isIndexable(): bool
    {
        return $this === self::Indexable;
    }
}
