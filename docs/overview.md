# Discovery Foundation

<!-- prettier-ignore-start -->

## What This Plugin Adds

Discovery Foundation is an **Available**, **No schema impact** Capell package in the **Capell Foundation** product group. It ships as `capell-app/discovery-foundation` and extends these surfaces: shared.

Discovery Foundation provides the shared public URL registry and deterministic matching primitives used by Capell discovery features.

First-party packages can contribute scoped, indexable URLs without duplicating registry or text-matching logic.

Evidence: [`src/Actions/BuildPublicUrlRegistryAction.php`](../src/Actions/BuildPublicUrlRegistryAction.php), [`src/Actions/ScorePublicUrlCandidateAction.php`](../src/Actions/ScorePublicUrlCandidateAction.php), [`src/Contracts/PublicUrlContributor.php`](../src/Contracts/PublicUrlContributor.php), [`src/Health/DiscoveryFoundationHealthCheck.php`](../src/Health/DiscoveryFoundationHealthCheck.php), [`src/Actions/DiscoverPublicPagesAction.php`](../src/Actions/DiscoverPublicPagesAction.php), [`tests/Integration/Actions/BuildPublicUrlRegistryActionTest.php`](../tests/Integration/Actions/BuildPublicUrlRegistryActionTest.php).

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/discovery-foundation`
- Namespace: `Capell\DiscoveryFoundation`
- Theme key: not applicable

## Why It Matters

**For developers:** Typed contracts keep URL discovery neutral while compatibility shims let existing Site Discovery consumers migrate across the current major version.

**For teams:** Teams get consistent sitemap, search, SEO, and 404 discovery inputs without analytics, AI calls, or public authoring data.

Evidence: [`src/Data/PublicUrlRegistryEntryData.php`](../src/Data/PublicUrlRegistryEntryData.php), [`src/Contracts/PublicUrlContributor.php`](../src/Contracts/PublicUrlContributor.php), [`src/Actions/BuildPublicUrlRegistryAction.php`](../src/Actions/BuildPublicUrlRegistryAction.php), [`src/Providers/DiscoveryFoundationServiceProvider.php`](../src/Providers/DiscoveryFoundationServiceProvider.php).

## Screens And Workflow

Docs gap: add `docs/screenshots.json` before promoting this package with visual workflow claims.

- Admin index screen if the package has a Filament resource.
- Create/edit screen if editors create records.
- Settings/configuration screen when settings exist.
- Frontend output when the package renders public pages.
- Package detail or install intent screen when marketplace-owned.

## Technical Shape

### Service providers

- `Capell\DiscoveryFoundation\Providers\DiscoveryFoundationServiceProvider`

### Extension contracts

- `PublicUrlContributor`

### Actions

- `BuildPublicUrlRegistryAction`
- `DiscoverPublicPagesAction`
- `NormalizeSearchTextAction`
- `NormalizeTextAction`
- `ReplacePhraseAction`
- `ResolveTypoCorrectionAction`
- `ScorePublicUrlCandidateAction`

### Data objects

- `DiscoverablePageData`
- `PublicUrlCandidateScoreData`
- `PublicUrlData`
- `PublicUrlRegistryEntryData`

### Manifest action API

- `buildPublicUrlRegistry: Capell\DiscoveryFoundation\Actions\BuildPublicUrlRegistryAction`
- `discoverPublicPages: Capell\DiscoveryFoundation\Actions\DiscoverPublicPagesAction`
- `replacePhrase: Capell\DiscoveryFoundation\Actions\ReplacePhraseAction`

### Manifest contributions

- `health-check: Capell\DiscoveryFoundation\Health\DiscoveryFoundationHealthCheck`

### Health checks

- `Capell\DiscoveryFoundation\Health\DiscoveryFoundationHealthCheck`

### Cache tags

- `discovery-foundation`


## Data Model

- Required tables: `pages`, `sites`, `site_domains`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: Docs gap: migrations and manifest contributions do not prove a cascade, pruning command, or timed retention policy.

## Install Impact

- Required packages: `capell-app/core`.
- Admin navigation: no admin page or resource contribution is declared.
- Admin/editor extensions: none declared.
- Permissions: no package permission declarations or Shield gates detected; host access rules still apply.
- Public routes: none declared.
- Database changes: no package migrations declared.
- Config: no package config files.
- Settings: no package settings declared.
- Queues or schedules: none declared.
- Cache tags: `discovery-foundation`.
- Commands: none declared.

## Common Pitfalls

- Keep required Capell packages on compatible v4 releases: `capell-app/core`.
- Custom write integrations must preserve invalidation for `discovery-foundation` cache tags.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |

## Quick Start

1. Install the package: `composer require capell-app/discovery-foundation`.
2. Verify the package provider and manifest contributions are registered in the host app.

## Next Steps

- [Package docs index](README.md)
- [Worked extension examples](extension-contracts.md)
- [Developer troubleshooting](../README.md#troubleshooting)
- [Screenshot contract](screenshots.json)
- [Capell content language plan](../../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../../docs/erd/capell-and-package-erds.md)
- Focused tests: `vendor/bin/pest packages/discovery-foundation/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
