<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static string run(string $text) */
final class NormalizeSearchTextAction
{
    use AsFake;
    use AsObject;

    public function handle(string $text): string
    {
        return trim((string) preg_replace('/\\s+/u', ' ', mb_strtolower($text)));
    }
}
