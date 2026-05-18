# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this plugin is

CFDev ("Custom Field For Dev") is a WordPress plugin that provides a code-first API for registering custom post types, taxonomies, and meta fields. Developers declare fields and meta boxes in PHP rather than through an admin UI.

## Commands

```bash
# Run all tests
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/Unit/Fields/TextTest.php

# Run tests with deprecation warnings
./vendor/bin/phpunit --display-deprecations

# Lint (check only)
vendor/bin/phpcs

# Lint (auto-fix)
vendor/bin/phpcbf

# Install dependencies (development)
composer install

# Install dependencies (production — no dev packages, optimized autoloader)
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

## Architecture

### Boot sequence

`cfdev-plugin.php` → `CFDev\Initializer::instance()` → creates a `Container`, registers `Config` and `AssetLoader`, then requires the two public function files from `src/functions/`.

### Public API (entry points for plugin users)

`src/functions/post_type_function.php` and `src/functions/taxonomy_function.php` expose the two global functions users call:

```php
register_cfdev_post_type(['book', 'books'], $args)
    ->addTaxonomy('genre')
    ->addSupport('thumbnail')
    ->addMetaBox('details', 'Book Details', $fields);
```

Both functions return instances of `PostType` / `Taxonomy` which support method chaining. The chain calls `addMetaBox()` (defined on the `ContentType` abstract), which instantiates `CFDev\Meta\MetaBox`.

### Class hierarchy

```
Abstracts\ContentType (implements Registerable, Supportable, HasMetaBox)
├── PostType      — registers CPTs, delegates taxonomy via new Taxonomy()
└── Taxonomy      — registers taxonomies, handles admin column filters

Meta              — base: renders/saves/validates all field layouts
├── Meta\MetaBox  — hooks into save_post, add_meta_boxes
├── Meta\UserMeta — hooks into user profile pages
└── Meta\TermMeta — hooks into term add/edit forms

Field             — base field: HTML attribute helpers, repeatable/ajax output, validation
└── Fields\*      — one class per field type (Text, Select, Image, Bundle, Tabs, etc.)
```

### Field type → class mapping

`Meta::build()` converts a `type` string to a class name via:
```
CFDev\Fields\{TypeInCamelCase}
```
e.g. `post_select` → `CFDev\Fields\PostSelect`. If the class doesn't exist, the field is silently skipped.

### Form data shape

All field values are posted under `cfdev[field_id]`. Bundle fields use `cfdev[bundle_id][row_index][field_id]`. The nonce key is `cfdev_nonce` with action `cfdev_meta`.

### Validation / ErrorBag

Validation errors survive the POST → redirect → GET cycle through WordPress transients (60 s TTL). The flow:

1. `MetaBox::savePost()` → `validateFields()` → `ErrorBag::push(meta_type, object_id, errors)`
2. On the next page load, `Meta::callback()` → `ErrorBag::load()` (reads transient into static `$runtime`, then deletes it)
3. During render, `ErrorBag::forField(field_id)` returns errors for individual fields
4. `admin_notices` hook calls `ErrorBag::peek()` (non-destructive) to show a summary banner

Bundle field error keys use dot notation: `bundle_id.row_index.field_id`.

### Layout containers (Tabs, Accordion, Bundle)

`Fields\Tabs`, `Fields\Accordion`, and `Fields\Bundle` are not field types — they are layout wrappers. `Meta::build()` detects them by checking `$data[0]` for the string `'tabs'`, `'accordion'`, or `'bundle'`. Fields inside a Bundle set `$field->in_bundle = true` and are skipped during flat-field iteration.

### Coding standards

PHPCS uses `WordPress-VIP-Go` + `PSR12` (see `phpcs.xml`). Maximum line length is 160 characters. `vendor/`, `node_modules/`, `tests/`, CSS, and JS are excluded from linting. The only active exclusion is `Squiz.PHP.CommentedOutCode.Found`.

### Tests

PHPUnit 13 + Brain/Monkey for WordPress function mocking. All tests extend `CFDev\Tests\Unit\CFDev_Test_Case`, which calls `Monkey\setUp()` / `Monkey\tearDown()` and stubs common WP i18n functions. Tests live under `tests/Unit/` and are organized by `Fields/`.
