<?php

declare(strict_types=1);

use Capell\DiscoveryFoundation\Actions\NormalizeTextAction;
use Capell\DiscoveryFoundation\Actions\ResolveTypoCorrectionAction;
use Capell\DiscoveryFoundation\Actions\ScorePublicUrlCandidateAction;

it('normalizes punctuation separators whitespace casing and unicode text deterministically', function (): void {
    $input = "  Café / Site_URL—Guide!!\n  v2  ";

    expect(NormalizeTextAction::run($input))->toBe('café site url guide v2')
        ->and(NormalizeTextAction::run($input))->toBe(NormalizeTextAction::run($input));
});

it('corrects sufficiently long query tokens to their nearest dictionary terms', function (): void {
    $correction = ResolveTypoCorrectionAction::run(
        query: 'The capel suport 404',
        dictionary: ['Capell', 'support', '404'],
    );

    expect($correction)->toBe('the capell support 404')
        ->and(ResolveTypoCorrectionAction::run('Capell support', ['Capell', 'support']))->toBeNull()
        ->and(ResolveTypoCorrectionAction::run('cat', ['cut']))->toBeNull();
});

it('gives an exact candidate a perfect score and keeps score components bounded', function (): void {
    $score = ScorePublicUrlCandidateAction::run(
        missingPath: '/docs/getting-started',
        missingTitle: 'Getting Started',
        candidatePath: '/docs/getting-started/',
        candidateTitle: 'getting-started',
    );

    expect($score->score)->toBe(1.0)
        ->and($score->editSimilarity)->toBe(1.0)
        ->and($score->tokenDiceSimilarity)->toBe(1.0);
});

it('ranks a close candidate above an unrelated candidate using both similarity signals', function (): void {
    $close = ScorePublicUrlCandidateAction::run(
        missingPath: '/docs/getting-started',
        missingTitle: 'Getting Started',
        candidatePath: '/docs/getting-starred',
        candidateTitle: 'Getting Started',
    );
    $unrelated = ScorePublicUrlCandidateAction::run(
        missingPath: '/docs/getting-started',
        missingTitle: 'Getting Started',
        candidatePath: '/pricing',
        candidateTitle: 'Pricing',
    );

    expect($close->score)->toBeGreaterThan($unrelated->score)
        ->and($close->editSimilarity)->toBeGreaterThan($unrelated->editSimilarity)
        ->and($close->tokenDiceSimilarity)->toBeGreaterThan($unrelated->tokenDiceSimilarity)
        ->and($close->score)->toBeGreaterThanOrEqual(0.0)
        ->and($close->score)->toBeLessThanOrEqual(1.0)
        ->and($unrelated->score)->toBeGreaterThanOrEqual(0.0)
        ->and($unrelated->score)->toBeLessThanOrEqual(1.0);
});
