# Discovery Core reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/discovery-foundation`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Discovery Core is an **Available**, **No schema impact** Capell package in the **Capell Essentials** product group. It ships as `capell-app/discovery-foundation` and extends these surfaces: shared.

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/discovery-foundation`
- Namespace: `Capell\DiscoveryFoundation`
- Theme key: not applicable

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

<!-- prettier-ignore-end -->
