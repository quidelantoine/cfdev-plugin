# CLAUDE.md

CFDev ("Custom Field For Dev") is a WordPress plugin providing a code-first API for registering custom post types, taxonomies, and meta fields.

## Commands

```bash
# Unit tests (Brain/Monkey, no real WP)
./vendor/bin/phpunit --testsuite Unit

# Integration tests (real WP + Docker DB — run `docker compose up -d db` first)
./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php

# Lint check / auto-fix
vendor/bin/phpcs
vendor/bin/phpcbf

# Cypress — interactive / headless / single spec
npm run cy:open
npm run cy:run
npx cypress run --spec "cypress/e2e/02-flat-fields.cy.js" --browser chrome
```

## Architecture

**Boot:** `cfdev-plugin.php` → `CFDev\Initializer::instance()` → `Container` → registers `Config`, `AssetLoader`, loads `src/functions/`.

**Public API:**
```php
register_cfdev_post_type(['book', 'books'], $args)
    ->addTaxonomy('genre')
    ->addMetaBox('details', 'Book Details', $fields);
```

**Class hierarchy:**
```
Abstracts\ContentType
├── PostType
└── Taxonomy

Meta (renders/saves/validates)
├── Meta\MetaBox   — hooks: save_post, add_meta_boxes
├── Meta\UserMeta  — hooks: user profile pages
└── Meta\TermMeta  — hooks: term add/edit forms

Abstracts\CheckboxesBase  ← Checkboxes, PostCheckboxes, TermCheckboxes, UserCheckboxes
Abstracts\WpDropdownSelectBase ← PostSelect, TermSelect, UserSelect

Field (base: attrs, repeatable, validation)
└── Fields\*  — one class per type (Text, Select, Image, Bundle, Tabs, Wysiwyg, …)
```

**Field type → class:** `Meta::build()` maps `type` string to `CFDev\Fields\{TypeInCamelCase}`. Unknown types are silently skipped.

**Layout containers:** `Tabs`, `Accordion`, `Bundle` are detected by `$data[0]` being `'tabs'`/`'accordion'`/`'bundle'` — not by type. Fields inside Bundle have `$field->in_bundle = true` and are skipped in flat-field iteration.

**Form data:** `cfdev[field_id]`. Bundles: `cfdev[bundle_id][row_index][field_id]`. Nonce: `cfdev_nonce` / action `cfdev_meta`. Bundle IDs are prefixed with `_` by `Bundle::buildId()` — POST keys and meta keys must use `'_slug'`.

**Validation / ErrorBag:** Errors survive POST→redirect→GET via transients (60 s). `push()` on save → `load()` in render callback → `forField()` per field → `peek()` for admin notice banner. Bundle error keys use dot notation: `bundle.rowIndex.fieldId`.

## Coding standards

PHPCS: `WordPress-VIP-Go` + `PSR12`, 160-char line max. `vendor/`, `node_modules/`, `tests/`, CSS, JS excluded. JS sniffs excluded explicitly in `phpcs.xml` (`WordPressVIPMinimum.JS.*`) — `<arg name="extensions" value="php"/>` alone is insufficient.

## Tests

**Unit:** PHPUnit 13 + Brain/Monkey. Base class: `CFDev\Tests\Unit\CFDev_Test_Case`. Use `#[DataProvider]` attribute (not `@dataProvider`). `Functions\expect()` doesn't count as a PHPUnit assertion — add `$this->addToAssertionCount(1)`. `WP_Error` and `HOUR_IN_SECONDS` stubbed in `tests/bootstrap.php`.

**Integration:** `wp-phpunit/wp-phpunit ^7.0`. `IntegrationTestCase` extends `WP_UnitTestCase` and overrides `expectDeprecated()` for PHPUnit 13 compat. Stubs for `Yoast\PHPUnitPolyfills` live in `tests/Integration/stubs/`. Reset `$wp_rest_server = null` in `set_up()` when testing REST. Call `Registry::reset()` after `do_action('init')`. CPTs must support `'custom-fields'` for meta to appear in REST responses.

## Known gotcha — Bundle + Wysiwyg

In `assets/js/functions.js`, `init_editors`: `var` hoisting inside `.each()` callbacks can shadow outer parameters. The internal settings variable must be named differently from any outer `settings` parameter, and `tinyMCEPreInit.mceInit[last_id]` must be deep-cloned (`$.extend(true, {}, …)`) to avoid mutating the original editor settings.