<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static string run(string $query, string $sourcePhrase, string $replacementPhrase) */
final class ReplacePhraseAction
{
    use AsFake;
    use AsObject;

    public function handle(string $query, string $sourcePhrase, string $replacementPhrase): string
    {
        $query = NormalizeSearchTextAction::run($query);
        $sourcePhrase = NormalizeSearchTextAction::run($sourcePhrase);
        $replacementPhrase = NormalizeSearchTextAction::run($replacementPhrase);

        if ($query === '' || $sourcePhrase === '' || $replacementPhrase === '') {
            return $query;
        }

        $replaced = preg_replace(
            '/(?<![\pL\pN])' . preg_quote($sourcePhrase, '/') . '(?![\pL\pN])/u',
            $replacementPhrase,
            $query,
        );

        return is_string($replaced) ? NormalizeSearchTextAction::run($replaced) : $query;
    }
}
