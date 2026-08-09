<?php

declare(strict_types=1);

namespace Capell\DiscoveryFoundation\Data;

use Spatie\LaravelData\Data;

final class PublicUrlCandidateScoreData extends Data
{
    public function __construct(
        public readonly float $score,
        public readonly float $editSimilarity,
        public readonly float $tokenDiceSimilarity,
    ) {}
}
