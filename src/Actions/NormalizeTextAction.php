<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static string run(string $text) */
final class NormalizeTextAction
{
    use AsFake;
    use AsObject;

    public function handle(string $text): string
    {
        $text = trim(mb_strtolower($text));
        $text = (string) preg_replace('/[\\/_-]+/u', ' ', $text);
        $text = (string) preg_replace('/[^\\pL\\pN]+/u', ' ', $text);

        return trim((string) preg_replace('/\\s+/u', ' ', $text));
    }
}
