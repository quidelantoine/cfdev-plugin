# CLAUDE.md

CFDev ("Custom Field For Dev") is a WordPress plugin providing a code-first API for registering custom post types, taxonomies, and meta fields.

## Commands

```bash
# Unit tests (Brain/Monkey, no real WP)
./vendor/bin/phpunit --testsuite Unit

# Integration tests (real WP + Docker DB — run `docker compose up -d db` first)
./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php

# Single test file
./vendor/bin/phpunit tests/Unit/Fields/DateTest.php

# Coverage (PCOV via Docker — .mo file owned by root)
docker compose exec -w /app/public/wp-content/plugins/cfdev-plugin php \
  php -d pcov.enabled=1 vendor/bin/phpunit --testsuite Unit --coverage-text --no-progress

# Lint check / auto-fix
vendor/bin/phpcs
vendor/bin/phpcbf

# Pre-commit check (full)
./vendor/bin/phpunit --testsuite Unit && vendor/bin/phpcs && vendor/bin/phpstan analyse

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

## Date / Time fields

`Fields\Date`, `Fields\Datetime`, `Fields\Time` all store a Unix **timestamp string**.

- `date_format` (default `'m/d/Y'`) and `time_format` (default `'H:i'`) are PHP date format strings.
- `Date::saveValue()` and `Datetime::saveValue()` parse using `DateTime::createFromFormat($format, $value)` — the format must match exactly what the picker outputs. `Time::saveValue()` uses `strtotime()` instead.
- `DateFormatHelper::parse()` (`src/Support/DateFormatHelper.php`) converts a PHP format to jQuery UI datepicker format — called in constructors to set `data-date-format` / `data-time-format` attributes.
- Changing `date_format` affects both display and save parsing. Existing stored timestamps re-display correctly because `gmdate($format, $ts)` is format-agnostic.
- `Datetime`: if `time_format` has no `s` character, seconds are zeroed on save.

## CacheResolver — resolved shapes

`src/Cache/CacheResolver.php` enriches raw meta values:

- **image** → `['id' => int, 'alt' => string, 'full' => string, 'thumbnail' => string, 'medium' => string, …]` — `id` is always the first key, always present when the attachment exists.
- **gallery** → `[['id', 'alt', 'full', …], …]`
- **file** → `['id', 'url', 'filename']`
- **link** → `['url', 'text', 'target']`
- **bundle** → `[['field_id' => resolved_value, …], …]`
- Empty raw value (`''`, `null`, `false`) is returned as-is without resolution.

## i18n

Translation files live in `languages/`. Three files per locale: `.po` (source), `.mo` (binary), `.l10n.php` (PHP cache — **WordPress 6.5+ loads this with priority over `.mo`**).

Supported locales: `fr_FR`, `es_ES`, `de_DE`, `pt_BR`, `nl_NL`, `it_IT`, `ja`, `zh_CN`, `ru_RU`, `pl_PL`.

`msgfmt` is not available on the host — compile via Docker:

```bash
docker compose exec -w /app/public/wp-content/plugins/cfdev-plugin php \
  php -r "/* parse .po → write .mo + .l10n.php */"
```

The `.l10n.php` and `.mo` files are owned by `root` (created inside the container). Always recompile both when editing a `.po`. The `.pot` template is `languages/cfdev.pot`.

## Coding standards

PHPCS: `WordPress-VIP-Go` + `PSR12`, 160-char line max. `vendor/`, `node_modules/`, `tests/`, CSS, JS excluded. JS sniffs excluded explicitly in `phpcs.xml` (`WordPressVIPMinimum.JS.*`) — `<arg name="extensions" value="php"/>` alone is insufficient.

## Tests

**Unit:** PHPUnit 13 + Brain/Monkey. Base class: `CFDev\Tests\Unit\CFDev_Test_Case`. Use `#[DataProvider]` attribute (not `@dataProvider`). `Functions\expect()` doesn't count as a PHPUnit assertion — add `$this->addToAssertionCount(1)`. `WP_Error` and `HOUR_IN_SECONDS` stubbed in `tests/bootstrap.php`.

**Integration:** `wp-phpunit/wp-phpunit ^7.0`. `IntegrationTestCase` extends `WP_UnitTestCase` and overrides `expectDeprecated()` for PHPUnit 13 compat. Stubs for `Yoast\PHPUnitPolyfills` live in `tests/Integration/stubs/`. Reset `$wp_rest_server = null` in `set_up()` when testing REST. Call `Registry::reset()` after `do_action('init')`. CPTs must support `'custom-fields'` for meta to appear in REST responses.

## Known gotcha — Bundle + Wysiwyg

In `assets/js/functions.js`, `init_editors`: `var` hoisting inside `.each()` callbacks can shadow outer parameters. The internal settings variable must be named differently from any outer `settings` parameter, and `tinyMCEPreInit.mceInit[last_id]` must be deep-cloned (`$.extend(true, {}, …)`) to avoid mutating the original editor settings.