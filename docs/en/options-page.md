# Options Pages

[← README](../../readme.md) · [Français](../fr/options-page.md)

---

An options page stores field values in `wp_options` — not attached to any post, term, or user. This is the right tool for global site settings: contact details, social links, maintenance mode, default colors, etc.

---

## 1. Quick registration

```php
add_action('init', static function (): void {

    register_cfdev_options_page('site_settings', 'Site Settings', [
        ['id' => '_site_name',  'type' => 'text',  'label' => 'Site Name',  'required' => true],
        ['id' => '_site_email', 'type' => 'email', 'label' => 'Contact E-mail'],
        ['id' => '_site_phone', 'type' => 'tel',   'label' => 'Phone'],
        ['id' => '_site_logo',  'type' => 'image', 'label' => 'Logo'],
    ]);

});
```

This creates a **top-level menu entry** in the WordPress admin sidebar.

---

## 2. Reading values

Each field is stored as a standalone `wp_options` record. Read it anywhere with the native WordPress function:

```php
$name  = get_option('_site_name');
$email = get_option('_site_email');
$logo  = \Weblitzer\CFDev\Field::decodeMetaValue(get_option('_site_logo'));
// $logo['url'], $logo['alt'], $logo['id'], $logo['sizes']['thumbnail']['url']
```

> `wp_options` is already cached by WordPress's object cache — no extra caching layer needed for flat fields.

---

## 3. Sub-pages

### Under your own top-level page

Chain `addSubPage()` to nest pages under the parent you just created:

```php
register_cfdev_options_page('brand', 'Brand', $brand_fields)
    ->addSubPage('social',  'Social Media', $social_fields)
    ->addSubPage('seo',     'SEO',          $seo_fields);
```

The admin sidebar shows:
```
Brand
 ├─ Social Media
 └─ SEO
```

### Under a native WordPress menu

Use `asSubmenu()` to attach to an existing WP admin menu:

```php
// Under Settings → ...
register_cfdev_options_page('contact_info', 'Contact Info', $fields)
    ->asSubmenu('options-general.php');

// Under a custom top-level menu slug
register_cfdev_options_page('brand_colors', 'Brand Colors', $fields)
    ->asSubmenu('my-theme-settings');
```

---

## 4. Layouts

Options pages support all the same layout containers as meta boxes.

### Flat fields

The default — just pass an array of field definitions:

```php
register_cfdev_options_page('contact', 'Contact', [
    ['id' => '_contact_email', 'type' => 'email', 'label' => 'E-mail'],
    ['id' => '_contact_phone', 'type' => 'tel',   'label' => 'Phone'],
]);
```

### Bundle — repeatable row group

```php
register_cfdev_options_page('team', 'Team Members', [
    'bundle', '_team_members', [
        ['id' => '_tm_name',  'type' => 'text',  'label' => 'Name'],
        ['id' => '_tm_role',  'type' => 'text',  'label' => 'Role'],
        ['id' => '_tm_photo', 'type' => 'image', 'label' => 'Photo'],
    ]
]);
```

Read the bundle:

```php
$rows = \Weblitzer\CFDev\Field::decodeMetaValue(get_option('_team_members')) ?: [];
foreach ($rows as $member) {
    echo esc_html($member['_tm_name'] ?? '');
}
```

### Tabs

```php
register_cfdev_options_page('global', 'Global Settings', [
    'tabs', [
        'General' => [
            ['id' => '_global_name',  'type' => 'text',  'label' => 'Site Name'],
            ['id' => '_global_email', 'type' => 'email', 'label' => 'E-mail'],
        ],
        'Social' => [
            ['id' => '_global_fb',  'type' => 'url', 'label' => 'Facebook'],
            ['id' => '_global_ig',  'type' => 'url', 'label' => 'Instagram'],
        ],
    ]
]);
```

### Accordion

```php
register_cfdev_options_page('theme', 'Theme Options', [
    'accordion', [
        'Typography' => [
            ['id' => '_theme_font',  'type' => 'select', 'label' => 'Font Family',
             'options' => ['serif' => 'Serif', 'sans' => 'Sans-serif']],
            ['id' => '_theme_size',  'type' => 'number', 'label' => 'Base Size (px)'],
        ],
        'Colors' => [
            ['id' => '_theme_primary',   'type' => 'color', 'label' => 'Primary'],
            ['id' => '_theme_secondary', 'type' => 'color', 'label' => 'Secondary'],
        ],
    ]
]);
```

---

## 5. Validation

Validation works exactly like meta boxes — declare rules inline:

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Url;

register_cfdev_options_page('seo', 'SEO', [
    ['id' => '_seo_title',    'type' => 'text', 'label' => 'Default Title',
     'required' => true, 'rules' => [new MinLength(5), new MaxLength(60)]],
    ['id' => '_seo_site_url', 'type' => 'url',  'label' => 'Canonical URL',
     'rules' => [new Url()]],
]);
```

Errors survive the POST → redirect → GET cycle via a short-lived transient. Invalid fields are highlighted inline; a banner lists all errors at the top of the page.

---

## 6. REST API

Mark any field (or bundle) with `'rest' => true` to expose it via the native WordPress settings endpoint.

```php
register_cfdev_options_page('brand', 'Brand', [
    ['id' => '_brand_name',  'type' => 'text',  'label' => 'Brand Name',  'rest' => true],
    ['id' => '_brand_color', 'type' => 'color', 'label' => 'Brand Color', 'rest' => true],
    ['id' => '_brand_logo',  'type' => 'image', 'label' => 'Logo'],   // not exposed
]);
```

For a bundle:

```php
register_cfdev_options_page('team', 'Team', [
    'bundle', '_team', $fields, ['rest' => true]
]);
```

Two endpoints are available simultaneously.

### Native WP settings endpoint

```
GET /wp-json/wp/v2/settings
→ { "_brand_name": "Acme", "_brand_color": "#ff0000", "_team": "[{...}]" }
```

Returns **raw values** — image attachment IDs, bundles as JSON strings. Requires `manage_options` even for reading (not suitable for public-facing use).

### CFDev options endpoint

```
GET /wp-json/cfdev/v1/options/{page_id}
```

Returns **resolved values** — images enriched with URL/alt/sizes, bundles decoded as arrays:

```json
{
    "page": "brand",
    "groups": {
        "brand": {
            "_brand_name": "Acme",
            "_brand_color": "#ff0000",
            "_brand_logo": {
                "id": 42, "alt": "Acme logo",
                "full": "https://…/logo.png",
                "medium": "https://…/logo-300x100.png"
            },
            "_team": [
                { "_tm_name": "Alice", "_tm_photo": { "full": "…", "medium": "…" } }
            ]
        }
    }
}
```

**Auth:** none — publicly readable. Prefer this endpoint for headless/Next.js frontends.

The `cfdev_rest_enabled` toggle in **CFDev → REST API** applies to both endpoints — disabling it stops all CFDev REST registration globally.

---

## 7. Admin integration

Registered options pages appear in the **CFDev Dashboard** under the **Options** tab, showing:

- Page ID and title
- Layout badge (flat / bundle / tabs / accordion)
- Field count and field list (expandable)
- **✎ Edit** button — links directly to the options page

Fields marked `rest: true` appear in **CFDev → REST API** under the **Options** tab with the native `/wp/v2/settings` endpoint.

---

## 8. Page configuration

```php
$page = register_cfdev_options_page('settings', 'Settings', $fields);

// Change the required capability (default: 'manage_options')
$page->capability = 'edit_theme_options';

// Change the dashicon (top-level pages only)
$page->icon = 'dashicons-admin-customizer';

// Change the menu position (top-level pages only, default: 83)
$page->menu_position = 60;
```

---

## 9. Limits — what does not apply

| Property | Why it does not apply |
|---|---|
| `show_admin_column` | Admin list columns are for post types, not options |
| `admin_column_sortable` | Same |
| `ajax => true` | Works — AJAX save uses `update_option()` and requires `manage_options` |
| `rest => true` via `register_meta()` | Options use `register_setting()` instead — see [section 6](#6-rest-api) |

Options pages do **not** support:
- The **Inspect** modal in the admin (no object ID to inspect — use ✎ Edit instead)
- `onlyForId()` / `onlyForTemplate()` conditions (those target post objects)
- CFDev's file cache (`CacheManager`) — use `get_option()` directly; WP's object cache already handles it

---

## 10. Tips

**One page per concern.** Prefer several focused sub-pages over one giant flat list. Fields are easier to find, and the Code modal generates cleaner snippets.

**Prefix all field IDs.** `wp_options` is a global table shared by WordPress core, themes, and all plugins. Always prefix to avoid collisions: `_mytheme_site_name`, not just `_site_name`.

**Avoid storing large blobs in flat fields.** Large bundles serialize to a single `wp_options` row. For very large datasets, consider a CPT with a single post instead.