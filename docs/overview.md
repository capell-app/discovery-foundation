# Discovery Foundation

<!-- prettier-ignore-start -->

Other packages, such as Search and Site Discovery, need to agree on which of your pages are public, and this package keeps the one list they all read. It comes along with those packages when they are installed.

Discovery Foundation builds a shared list of your public web addresses, separate for each site and language, whenever another package asks for it. A published page is left off the list if it's hidden, set to ask search engines not to list it or open only to signed-in visitors. Disabled sites and languages never appear. Other packages can add their own addresses to the list, and every address is checked again before it goes in.

It has no screens or settings of its own. Small helpers for search text come with it, including tidying spaces and correcting a typed word to its nearest match.

- **Price and availability:** Check current availability and price on the [Capell Marketplace listing](https://capell.app/extensions/discovery-foundation).
- **Licence:** Check the licence terms on the [Capell Marketplace listing](https://capell.app/extensions/discovery-foundation).
- **Requires:** `PHP ^8.4`, `capell-app/core ^1.0.76`, `lorisleiva/laravel-actions ^2.8`, `spatie/laravel-data ^4.5`, `spatie/laravel-package-tools ^1.14.1`.
- **Install:** `composer require capell-app/discovery-foundation`.

## Quick Start

1. Install the package: `composer require capell-app/discovery-foundation`.
2. Verify the package provider and manifest contributions are registered in the host app.

## For Developers

Typed contracts keep URL discovery neutral while compatibility shims let existing Site Discovery consumers migrate across the current major version.

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
