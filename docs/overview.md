# Discovery Foundation

<!-- prettier-ignore-start -->

## What This Plugin Adds

The foundation package provides a neutral public URL registry and deterministic text matching for first-party discovery features.

## Why It Matters

**For developers:** Consumers share canonical URL, noindex, site, language, and title handling instead of maintaining separate public discovery implementations.

**For teams:** Discovery outputs remain consistent across sitemaps, search, SEO, and 404 suggestions.

## Screens And Workflow

Diagnostics and registry screenshots are intentionally unclaimed until an authentic host integration capture is available. The contract is recorded in [`screenshots.json`](screenshots.json).

## Technical Shape

`BuildPublicUrlRegistryAction` reads tagged `PublicUrlContributor` implementations. Normalisation, typo correction, edit similarity, and token Dice scoring are pure Actions with bounded inputs and deterministic ordering.

## Data Model

`PublicUrlData` is the contributor boundary. `PublicUrlRegistryEntryData` is the normalised consumer boundary. `PublicUrlCandidateScoreData` contains the combined score and its two bounded components.

## Install Impact

The package requires Core and registers the default CMS page contributor. It has no settings, migrations, public routes, public Blade, or external network clients.

## Common Pitfalls

Keep contributor output indexability authoritative, scope entries to the current site and language, and use the Foundation namespace for new consumers. Site Discovery aliases remain only for compatibility.

## Troubleshooting

If no URLs are returned, run the Foundation health check and inspect the tagged contributor registry before debugging a consumer package.

## Quick Start

1. Require `capell-app/discovery-foundation` alongside the consuming extension.
2. Register a contributor implementing `PublicUrlContributor` and tag it with `PublicUrlContributor::TAG`.
3. Call `BuildPublicUrlRegistryAction` from the consuming feature.

## Next Steps

- [Package README](../README.md)
- [Screenshot contract](screenshots.json)

<!-- prettier-ignore-end -->
