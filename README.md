# Discovery Foundation

<!-- prettier-ignore-start -->

## What This Plugin Adds

Discovery Foundation is a free shared package that owns Capell's typed public URL registry, default CMS page contributor, and deterministic matching primitives.

- Status: Beta

Evidence: [`capell.json`](capell.json), [`src/Actions/BuildPublicUrlRegistryAction.php`](src/Actions/BuildPublicUrlRegistryAction.php), [`src/Actions/ScorePublicUrlCandidateAction.php`](src/Actions/ScorePublicUrlCandidateAction.php).

## Why It Matters

**For developers:** One site and language scoped registry serves Search, Site Discovery, SEO Suite, and Smart 404.

**For teams:** Consistent discovery works without analytics, AI providers, or public authoring data.

Evidence: [`src/Contracts/PublicUrlContributor.php`](src/Contracts/PublicUrlContributor.php), [`src/Providers/DiscoveryFoundationServiceProvider.php`](src/Providers/DiscoveryFoundationServiceProvider.php).

## Screens And Workflow

The authentic diagnostics and registry capture is deferred until this package can be installed in a clean host application. See [`docs/screenshots.json`](docs/screenshots.json).

## Technical Shape

- Provider: `Capell\DiscoveryFoundation\Providers\DiscoveryFoundationServiceProvider`.
- Actions: `BuildPublicUrlRegistryAction`, `DiscoverPublicPagesAction`, `NormalizeTextAction`, `NormalizeSearchTextAction`, `ResolveTypoCorrectionAction`, and `ScorePublicUrlCandidateAction`.
- Contract: `Capell\DiscoveryFoundation\Contracts\PublicUrlContributor`.
- No routes, migrations, settings, database tables, or external clients.

## Data Model

The package exposes immutable Data objects for contributed URLs, registry entries, discoverable pages, and candidate scores. Registry entries retain canonical URL, site, language, indexability, robots, sitemap, and title metadata.

## Install Impact

- Required package: `capell-app/core`.
- Runtime registration: the default CMS page contributor is tagged for the foundation registry.
- Public output: no package-owned route or Blade view.
- Compatibility: Site Discovery forwarding classes remain available for the current major version.

## Common Pitfalls

- Contributors must return typed public URL data and remain site and language scoped.
- Noindex entries stay authoritative when duplicate contributors disagree.
- Do not expose authoring fields or package metadata through a public consumer.

## Troubleshooting

If the health check cannot find the CMS contributor, confirm the foundation provider is installed and that the tagged contributor registry is booted before running discovery actions.

## Quick Start

1. Require `capell-app/discovery-foundation` alongside the consuming extension.
2. Register a contributor implementing `PublicUrlContributor` and tag it with `PublicUrlContributor::TAG`.
3. Run `BuildPublicUrlRegistryAction` from the consuming feature.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Screenshot contract](docs/screenshots.json)

<!-- prettier-ignore-end -->
