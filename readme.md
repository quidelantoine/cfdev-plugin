# CFDev — Code-First Custom Meta Fields for WordPress

> Declare custom fields in PHP. No admin UI configuration. No database config drift.

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net/)
[![WordPress 6.5+](https://img.shields.io/badge/WordPress-6.5%2B-blue)](https://wordpress.org/)
[![Works with WooCommerce](https://img.shields.io/badge/WooCommerce-works%20with-96588a)](docs/en/woocommerce.md)

**[English docs](docs/en/installation.md)** · **[Lire en français](docs/fr/readme-fr.md)**

---

## What is CFDev?

CFDev is a WordPress plugin for developers who want to declare custom fields in PHP and never touch the database config again. Post meta, term meta, user meta — everything is registered in code, versioned with your project, and deployed without migration scripts.

**What you get out of the box:**

- **30+ field types** — text, image, file, select, checkboxes, date, wysiwyg, color, gallery, link, and more
- **Post, term and user meta** — attach meta boxes to any CPT or taxonomy, and field groups to user profiles
- **Layout containers** — group fields into **Bundles** (repeatable row groups), **Tabs**, or **Accordions** for clean, structured edit screens
- **Repeatable fields** — any field can be made repeatable with AJAX-powered add/remove
- **25+ validation rules** — required, min/max, regex, email, URL… enforced server-side, errors survive the redirect
- **File cache** — resolved field data cached to disk, invalidated on save, ready for high-traffic reads
- **Admin helper** — a built-in back-office panel to inspect all registered fields, browse cached data, and flush the cache in one click
- **REST API** — expose any field via WP REST with a single `'rest' => true` flag

```php
register_cfdev_post_type(['book', 'books'], ['public' => true])
    ->addTaxonomy('genre')
    ->addMetaBox('book_details', 'Book Details', [
        ['id' => 'subtitle',  'type' => 'text',    'label' => 'Subtitle',  'required' => true],
        ['id' => 'cover',     'type' => 'image',   'label' => 'Cover Image'],
        ['id' => 'pages',     'type' => 'number',  'label' => 'Page Count'],
        ['id' => 'published', 'type' => 'date',    'label' => 'Published Date'],
    ]);
```

---

## Why CFDev?

| | CFDev | ACF |
|---|---|---|
| Configuration in code (versionable) | ✅ | ❌ |
| Server-side validation (25+ rules) | ✅ | ❌ |
| Built-in file cache (resolved data) | ✅ | ❌ |
| Zero DB config drift (dev→prod) | ✅ | ❌ |
| No-bloat (~60 KB) | ✅ | ❌ |

---

## Requirements

| | Minimum | Recommended |
|---|---|---|
| PHP | 8.2 | 8.3+ |
| WordPress | 6.5 | latest |

---

## Installation

```bash
composer require quidelantoine/cfdev
```

**Production build:**

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

---

## Quick Start

Create a file in your theme (e.g. `cfdev-fields.php`) and load it, or use `functions.php`:

```php
add_action('init', static function (): void {

    register_cfdev_post_type('product', ['public' => true])
        ->addMetaBox('product_info', 'Product Info', [
            ['id' => 'price',    'type' => 'number', 'label' => 'Price',    'required' => true],
            ['id' => 'photo',    'type' => 'image',  'label' => 'Photo'],
            ['id' => 'brochure', 'type' => 'file',   'label' => 'Brochure PDF'],
        ]);

});
```

Read data in your template:

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

echo esc_html($product['price'] ?? '');
echo '<img src="' . esc_url($product['photo']['medium'] ?? '') . '" alt="' . esc_attr($product['photo']['alt'] ?? '') . '">';
```

---

## Documentation

### English

| Guide | Description |
|---|---|
| [Installation](docs/en/installation.md) | Requirements, install, production build |
| [Quick Start](docs/en/quick-start.md) | First post type, meta box and template |
| [Field Types](docs/en/fields.md) | All 30+ field types with options |
| [Layouts](docs/en/layouts.md) | Bundle, Tabs, Accordion |
| [Validation](docs/en/validation.md) | 25+ built-in validation rules |
| [Cache](docs/en/cache.md) | File cache — setup, invalidation, performance |
| [Admin UI](docs/en/admin.md) | CFDev admin pages (Fields, Cache) |
| [REST API](docs/en/rest-api.md) | Expose fields via WP REST API |
| [Repeatable & AJAX](docs/en/repeatable.md) | Repeatable fields and AJAX loading |
| [Admin Columns](docs/en/admin-columns.md) | Custom columns in post/term/user lists |
| [WooCommerce](docs/en/woocommerce.md) | Custom fields on products and product categories |

### Français

| Guide | Description |
|---|---|
| [Installation](docs/fr/installation.md) | Prérequis, installation, build production |
| [Démarrage rapide](docs/fr/demarrage-rapide.md) | Premier post type, meta box et template |
| [Types de champs](docs/fr/champs.md) | Tous les types de champs avec options |
| [Layouts](docs/fr/layouts.md) | Bundle, Tabs, Accordion |
| [Validation](docs/fr/validation.md) | 25+ règles de validation intégrées |
| [Cache](docs/fr/cache.md) | Cache fichier — activation, invalidation, perf |
| [Interface admin](docs/fr/admin.md) | Pages admin CFDev (Champs, Cache) |
| [REST API](docs/fr/rest-api.md) | Exposer les champs via WP REST API |
| [Répétable & AJAX](docs/fr/repeatable.md) | Champs répétables et chargement AJAX |
| [Colonnes admin](docs/fr/colonnes-admin.md) | Colonnes dans les listes post/terme/user |
| [WooCommerce](docs/fr/woocommerce.md) | Champs personnalisés sur les produits et catégories |

---

## Development

```bash
# Tests
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration --bootstrap tests/Integration/bootstrap.php

# Code quality
vendor/bin/phpcs -s
vendor/bin/phpstan analyse src tests

# E2E (requires docker compose up -d)
npm run cy:run
npm run cy:open
```

---

## License

GPL-2.0-or-later
