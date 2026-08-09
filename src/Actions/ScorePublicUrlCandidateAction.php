<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Actions;

use Capell\DiscoveryFoundation\Data\PublicUrlCandidateScoreData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/** @method static PublicUrlCandidateScoreData run(string $missingPath, string $missingTitle, string $candidatePath, string $candidateTitle) */
final class ScorePublicUrlCandidateAction
{
    use AsFake;
    use AsObject;

    public function handle(
        string $missingPath,
        string $missingTitle,
        string $candidatePath,
        string $candidateTitle,
    ): PublicUrlCandidateScoreData {
        $missingSegment = $this->lastSegment($missingPath);
        $candidateSegment = $this->lastSegment($candidatePath);
        $editSimilarity = max(
            $this->editSimilarity($missingSegment, $candidateSegment),
            $this->editSimilarity($missingTitle, $candidateTitle),
        );
        $tokenDiceSimilarity = $this->tokenDice(
            NormalizeTextAction::run($missingPath . ' ' . $missingTitle),
            NormalizeTextAction::run($candidatePath . ' ' . $candidateTitle),
        );

        return new PublicUrlCandidateScoreData(
            score: $this->clamp((0.7 * $editSimilarity) + (0.3 * $tokenDiceSimilarity)),
            editSimilarity: $editSimilarity,
            tokenDiceSimilarity: $tokenDiceSimilarity,
        );
    }

    private function lastSegment(string $path): string
    {
        $path = trim((string) parse_url($path, PHP_URL_PATH), '/');

        return $path === '' ? '' : ltrim((string) strrchr('/' . $path, '/'), '/');
    }

    private function editSimilarity(string $left, string $right): float
    {
        $left = NormalizeTextAction::run($left);
        $right = NormalizeTextAction::run($right);

        if ($left === '' && $right === '') {
            return 1.0;
        }

        $leftChars = mb_str_split($left);
        $rightChars = mb_str_split($right);
        $leftLength = count($leftChars);
        $rightLength = count($rightChars);

        if ($leftLength === 0 || $rightLength === 0) {
            return 0.0;
        }

        $previous = range(0, $rightLength);
        for ($i = 1; $i <= $leftLength; $i++) {
            $current = [$i];
            for ($j = 1; $j <= $rightLength; $j++) {
                $current[$j] = min(
                    $current[$j - 1] + 1,
                    $previous[$j] + 1,
                    $previous[$j - 1] + ($leftChars[$i - 1] === $rightChars[$j - 1] ? 0 : 1),
                );
            }
            $previous = $current;
        }

        return $this->clamp(1 - ($previous[$rightLength] / max($leftLength, $rightLength)));
    }

    private function tokenDice(string $left, string $right): float
    {
        $leftTokens = array_values(array_filter(preg_split('/\\s+/u', $left) ?: []));
        $rightTokens = array_values(array_filter(preg_split('/\\s+/u', $right) ?: []));

        if ($leftTokens === [] && $rightTokens === []) {
            return 1.0;
        }

        if ($leftTokens === [] || $rightTokens === []) {
            return 0.0;
        }

        $leftCounts = array_count_values($leftTokens);
        $rightCounts = array_count_values($rightTokens);
        $intersection = 0;
        foreach ($leftCounts as $token => $count) {
            $intersection += min($count, $rightCounts[$token] ?? 0);
        }

        return $this->clamp((2 * $intersection) / (count($leftTokens) + count($rightTokens)));
    }

    private function clamp(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}
