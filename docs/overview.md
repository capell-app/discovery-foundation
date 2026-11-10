# Discovery Core

<!-- prettier-ignore-start -->

## What It Does

Discovery Core provides the shared public URL registry and deterministic matching primitives used by Capell discovery features.

First-party packages can contribute scoped, indexable URLs without duplicating registry or text-matching logic.

- **Price and availability:** Check current availability and price on the [Capell Marketplace listing](https://capell.app/extensions/discovery-foundation).
- **Licence:** Check the licence terms on the [Capell Marketplace listing](https://capell.app/extensions/discovery-foundation).
- **Requires:** `PHP ^8.4`, `capell-app/core ^1.0.76`, `lorisleiva/laravel-actions ^2.8`, `spatie/laravel-data ^4.5`, `spatie/laravel-package-tools ^1.14.1`.
- **Install:** `composer require capell-app/discovery-foundation`.

## Who It Is For

**For developers:** Typed contracts keep URL discovery neutral while compatibility shims let existing Site Discovery consumers migrate across the current major version.

**For teams:** Teams get consistent sitemap, search, SEO, and 404 discovery inputs without analytics, AI calls, or public authoring data.

## Quick Start

1. Install the package: `composer require capell-app/discovery-foundation`.
2. Verify the package provider and manifest contributions are registered in the host app.

## Configure

This extension works without configuration. There is nothing to set after install.

## Limits And Pitfalls

- Install the required Capell packages at the versions in `composer.json`: `capell-app/core ^1.0.76`.
- Custom write integrations must preserve invalidation for `discovery-foundation` cache tags.

## Screens And Workflow

Docs gap: add `docs/screenshots.json` before promoting this package with visual workflow claims.

- Admin index screen if the package has a Filament resource.
- Create/edit screen if editors create records.
- Settings/configuration screen when settings exist.
- Frontend output when the package renders public pages.
- Package detail or install intent screen when marketplace-owned.

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

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |

## Next Steps

- [Package docs index](README.md)
- [Worked extension examples](extension-contracts.md)
- [Developer troubleshooting](../README.md#troubleshooting)
- [Developer reference](reference.md)
- [Capell content language plan](../../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../../docs/erd/capell-and-package-erds.md)

<!-- prettier-ignore-end -->
