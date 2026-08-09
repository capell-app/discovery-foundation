<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static ?string run(string $query, array<int|string, string|int|float> $dictionary, int $maxDistance = 1) */
final class ResolveTypoCorrectionAction
{
    use AsFake;
    use AsObject;

    /** @param array<int|string, string|int|float> $dictionary */
    public function handle(string $query, array $dictionary, int $maxDistance = 1): ?string
    {
        $query = NormalizeTextAction::run($query);
        $terms = array_values(array_filter(array_unique(array_map(
            static fn (string|int|float $term): string => NormalizeTextAction::run((string) $term),
            array_filter($dictionary, static fn (mixed $term): bool => is_string($term) || is_numeric($term)),
        )), static fn (string $term): bool => $term !== ''));

        if ($query === '' || $terms === []) {
            return null;
        }

        $maxDistance = max(0, min(3, $maxDistance));
        $changed = false;
        $tokens = preg_split('/\\s+/u', $query) ?: [];

        foreach ($tokens as $index => $token) {
            if (! is_string($token) || mb_strlen($token) < 4) {
                continue;
            }

            $correction = $this->nearestTerm($token, $terms, $maxDistance);
            if ($correction !== null && $correction !== $token) {
                $tokens[$index] = $correction;
                $changed = true;
            }
        }

        return $changed ? NormalizeTextAction::run(implode(' ', $tokens)) : null;
    }

    /** @param list<string> $terms */
    private function nearestTerm(string $token, array $terms, int $maxDistance): ?string
    {
        $nearest = null;
        $distance = $maxDistance + 1;

        foreach ($terms as $term) {
            if (abs(mb_strlen($term) - mb_strlen($token)) > $maxDistance) {
                continue;
            }

            $candidateDistance = $this->unicodeDistance($token, $term);
            if ($candidateDistance <= $maxDistance && $candidateDistance < $distance) {
                $nearest = $term;
                $distance = $candidateDistance;
            }
        }

        return $nearest;
    }

    private function unicodeDistance(string $left, string $right): int
    {
        $leftCharacters = mb_str_split($left);
        $rightCharacters = mb_str_split($right);
        $rightLength = count($rightCharacters);
        $previous = range(0, $rightLength);

        foreach ($leftCharacters as $leftIndex => $leftCharacter) {
            $current = [$leftIndex + 1];

            foreach ($rightCharacters as $rightIndex => $rightCharacter) {
                $current[] = min(
                    $current[$rightIndex] + 1,
                    $previous[$rightIndex + 1] + 1,
                    $previous[$rightIndex] + ($leftCharacter === $rightCharacter ? 0 : 1),
                );
            }

            $previous = $current;
        }

        return $previous[$rightLength] ?? count($leftCharacters);
    }
}
