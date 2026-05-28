# Installation

[← Back to README](../../readme.md) · [Français](../fr/installation.md)

---

## Requirements

| | Minimum | Recommended |
|---|---|---|
| PHP | 8.2 | 8.3+ |
| WordPress | 6.5 | latest |

PHP 8.2 minimum: `readonly` properties used in `FileMime`.  
WordPress 6.5 minimum: targets actively maintained installs.

---

## Install

**Option 1 — Download the release zip**

Download the latest `cfdev-plugin.zip` from the [GitHub Releases](https://github.com/weblitzer/cfdev-plugin/releases) page, then upload it via **WordPress Admin → Plugins → Add New → Upload Plugin**.

**Option 2 — Copy the folder**

```bash
cp -r cfdev-plugin /path/to/wp-content/plugins/
```

Then activate the plugin in **WordPress Admin → Plugins**.

> No Composer required. The plugin ships with a built-in PSR-4 autoloader — no `vendor/` directory, no build step.

---

## Declaring fields — `cfdev-fields.php`

Create a file named **`cfdev-fields.php`** at the root of your active theme. CFDev detects and loads it automatically — no `require` needed.

```
your-theme/
└── cfdev-fields.php   ← auto-loaded by CFDev
```

Wrap all declarations in an `init` action:

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Product Info', [
            ['id' => 'price', 'type' => 'number', 'label' => 'Price', 'required' => true],
            ['id' => 'photo', 'type' => 'image',  'label' => 'Photo'],
        ]);
});
```

---

## Splitting into multiple files

As your project grows, split declarations by content type. `cfdev-fields.php` becomes a simple entry point:

```
your-theme/
├── cfdev-fields.php       ← entry point
└── cfdev/
    ├── post-types.php     ← CPTs and their meta boxes
    ├── taxonomies.php     ← taxonomies and term meta
    └── users.php          ← user meta
```

```php
// your-theme/cfdev-fields.php

require_once __DIR__ . '/cfdev/post-types.php';
require_once __DIR__ . '/cfdev/taxonomies.php';
require_once __DIR__ . '/cfdev/users.php';
```

```php
// your-theme/cfdev/post-types.php

add_action('init', static function (): void {
    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addTaxonomy('category')
        ->addMetaBox('product_info', 'Product Info', [
            ['id' => 'price',   'type' => 'number', 'label' => 'Price'],
            ['id' => 'photo',   'type' => 'image',  'label' => 'Photo'],
        ]);

    register_cfdev_post_type(['event', 'events'], ['public' => true])
        ->addMetaBox('event_info', 'Event Info', [
            ['id' => 'date',     'type' => 'date',   'label' => 'Date'],
            ['id' => 'location', 'type' => 'text',   'label' => 'Location'],
        ]);
});
```

```php
// your-theme/cfdev/taxonomies.php

add_action('init', static function (): void {
    register_cfdev_taxonomy('category', 'product')
        ->addTermMeta([
            ['id' => 'color', 'type' => 'color', 'label' => 'Color'],
            ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
        ]);
});
```

```php
// your-theme/cfdev/users.php

add_action('init', static function (): void {
    register_cfdev_user_meta('profile', 'Profile', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
    ])->onlyForRole('administrator');
});
```

If you prefer to load the file yourself (e.g. from a plugin), use:

```php
// functions.php or a custom plugin
require_once get_template_directory() . '/cfdev-fields.php';
```

---

## Verify installation

After activating the plugin and adding at least one field declaration, go to **WordPress Admin → CFDev**. You should see a list of all registered field groups.

---

## Next

→ [Quick Start](quick-start.md)
