# Term Meta

[← README](../../readme.md) · [Français](../fr/term-meta.md)

---

Term meta lets you attach custom fields to taxonomy terms — categories, tags, or any custom taxonomy. Fields appear on the **Add term** form, the **Edit term** form, or both.

---

## 1. Quick registration — via taxonomy chain

The fastest way when you are already creating the taxonomy with CFDev:

```php
add_action('init', static function (): void {

    register_cfdev_taxonomy('genre', 'product')
        ->addTermMeta([
            ['id' => 'color',       'type' => 'color',    'label' => 'Color'],
            ['id' => 'icon',        'type' => 'image',    'label' => 'Icon'],
            ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
        ]);

});
```

`addTermMeta()` defaults to showing the fields on **both** the Add and Edit forms.

---

## 2. Standalone registration — on existing taxonomies

For WordPress built-in taxonomies (`category`, `post_tag`) or any taxonomy registered by another plugin or theme, instantiate `TermMeta` directly:

```php
use Weblitzer\CFDev\Meta\TermMeta;

add_action('init', static function (): void {

    new TermMeta('category', 'Category Options', [
        ['id' => 'color', 'type' => 'color', 'label' => 'Color'],
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ]);

});
```

---

## 3. Controlling where fields appear

The fourth parameter of `TermMeta` sets the form locations. Default is `['edit_form']`.

```php
// Edit form only (default)
new TermMeta('category', 'Options', $fields, ['edit_form']);

// Add form only
new TermMeta('category', 'Options', $fields, ['add_form']);

// Both forms
new TermMeta('category', 'Options', $fields, ['add_form', 'edit_form']);
```

> **Note:** fields added via `register_cfdev_taxonomy()->addTermMeta()` default to `['add_form', 'edit_form']`.

---

## 4. Multiple taxonomies in one declaration

Pass an array to attach the same fields to several taxonomies at once:

```php
new TermMeta(['category', 'post_tag'], 'Appearance', [
    ['id' => 'color', 'type' => 'color', 'label' => 'Color'],
    ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
]);
```

---

## 5. Hierarchical restriction — `onlyIfParent()`

Limit fields to terms that are **direct children** of a given parent term. Useful for hierarchical taxonomies (e.g. categories with sub-categories).

### Via taxonomy chain

```php
register_cfdev_taxonomy('product_cat', 'product')
    ->addTermMeta([
        ['id' => 'badge', 'type' => 'text', 'label' => 'Sub-category badge'],
    ])
    ->onlyIfParent(12); // only for direct children of term ID 12
```

### Via direct instantiation

```php
(new TermMeta('category', 'Sub-category fields', $fields))
    ->onlyIfParent(12);
```

> On the Add term form, CFDev reads the parent from `$_GET['parent']` when present.
> In REST responses, fields from this group are stripped for terms whose parent does not match.
>
> **Where to see it in admin:** create or edit a direct child of the parent term (ID 12 in the example). The group is always visible in the CFDev Dashboard with its `Parent : 12 — Term name` badge.

---

## 6. Single-term restriction — `onlyForId()`

Limit fields to **one specific term**. Useful for a "featured" or "homepage" category that has unique extra fields no other term needs.

```php
// Via taxonomy chain
register_cfdev_taxonomy('category', 'post')
    ->addTermMeta([
        ['id' => 'featured_color',  'type' => 'color', 'label' => 'Featured color'],
        ['id' => 'featured_banner', 'type' => 'image', 'label' => 'Banner image'],
    ])
    ->onlyForId(7); // only for the "Featured" category (term ID 7)

// Via direct instantiation
(new TermMeta('category', 'Homepage category fields', $fields))
    ->onlyForId(7);
```

> The Add term form is always hidden — the term ID does not exist before the term is saved.
> In REST responses, fields are stripped for all terms except the one with this ID.
> Combine with `onlyIfParent()` if needed — both conditions must pass.
>
> **Where to see it in admin:** navigate to the taxonomy list, then click **Edit** on the specific term (ID 7 in the example). The group is always visible in the CFDev Dashboard with its `ID : 7 — Term name` badge, regardless of which term you are currently editing.

---

## 7. Layouts

### 7.1 Flat fields

```php
new TermMeta('category', 'Category Meta', [
    ['id' => 'color',    'type' => 'color',    'label' => 'Color'],
    ['id' => 'image',    'type' => 'image',    'label' => 'Image'],
    ['id' => 'subtitle', 'type' => 'text',     'label' => 'Subtitle'],
    ['id' => 'intro',    'type' => 'textarea', 'label' => 'Intro text'],
]);
```

### 7.2 Bundle — repeatable rows

```php
new TermMeta('category', 'Gallery', [
    'bundle',
    '_gallery_items',
    [
        ['id' => 'image',   'type' => 'image', 'label' => 'Image',   'required' => true],
        ['id' => 'caption', 'type' => 'text',  'label' => 'Caption'],
    ],
]);
```

### 7.3 Tabs

Tab labels are the array keys:

```php
new TermMeta('category', 'Category Fields', [
    'tabs',
    [
        'Identity' => [
            ['id' => 'color',    'type' => 'color',  'label' => 'Color'],
            ['id' => 'image',    'type' => 'image',  'label' => 'Image'],
            ['id' => 'subtitle', 'type' => 'text',   'label' => 'Subtitle'],
        ],
        'SEO' => [
            ['id' => 'seo_title', 'type' => 'text',     'label' => 'SEO Title'],
            ['id' => 'seo_desc',  'type' => 'textarea', 'label' => 'Meta Description'],
        ],
    ],
]);
```

### 7.4 Accordion

Same structure as Tabs, displayed as collapsible sections:

```php
new TermMeta('category', 'Category Fields', [
    'accordion',
    [
        'Display' => [
            ['id' => 'color', 'type' => 'color', 'label' => 'Color'],
            ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
        ],
        'Content' => [
            ['id' => 'intro', 'type' => 'wysiwyg', 'label' => 'Intro'],
        ],
    ],
]);
```

### 7.5 Bundle inside an Accordion section

```php
new TermMeta('category', 'Category Fields', [
    'accordion',
    [
        'Info' => [
            ['id' => 'subtitle', 'type' => 'text',  'label' => 'Subtitle'],
            ['id' => 'color',    'type' => 'color', 'label' => 'Color'],
        ],
        'Gallery' => [
            ['bundle', '_photos', [
                ['id' => 'image',   'type' => 'image', 'label' => 'Image',   'required' => true],
                ['id' => 'caption', 'type' => 'text',  'label' => 'Caption'],
            ]],
        ],
    ],
]);
```

---

## 8. Reading term meta

### Without cache — direct meta

```php
$color = get_term_meta($term->term_id, 'color', true);

// For complex types (image, file, link…) decode the stored value:
$image_id = get_term_meta($term->term_id, 'image', true);
echo wp_get_attachment_image($image_id, 'medium');
```

**Helper functions** — CFDev provides two shortcuts that handle type decoding:

```php
// Returns the decoded value (any type)
$color = get_cfdev_term_meta($term->term_id, 'category', 'color');

// The term parameter also accepts a slug
$image = get_cfdev_term_meta('news', 'category', 'image');

// Returns all meta for the term (associative array)
$all = get_cfdev_term_meta($term->term_id, 'category');

// Echoes the value directly (scalar fields only)
the_cfdev_term_meta($term->term_id, 'category', 'color');
```

### With cache (recommended)

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$data  = $cache->term($term->term_id, 'category');
$group = $data['groups']['category'] ?? [];

// Scalar field
echo esc_html($group['color'] ?? '');

// Image (all sizes resolved)
$img = $group['image'] ?? [];
echo '<img src="' . esc_url($img['medium'] ?? '') . '" alt="' . esc_attr($img['alt'] ?? '') . '">';

// Bundle rows
$photos = $group['_photos'] ?? [];
foreach ($photos as $photo) {
    echo '<img src="' . esc_url($photo['image']['medium'] ?? '') . '">';
}
```

> The group key matches the `TermMeta` id, which defaults to the first taxonomy name.
> Cache is invalidated automatically on `edited_term` and `delete_term`.

---

## 9. REST API

Mark individual fields or entire bundles as REST-exposed with `'rest' => true`:

```php
new TermMeta('category', 'Category Meta', [
    ['id' => 'color', 'type' => 'color', 'label' => 'Color', 'rest' => true],
    ['id' => 'image', 'type' => 'image', 'label' => 'Image', 'rest' => true],
    ['id' => 'notes', 'type' => 'text',  'label' => 'Notes'], // not exposed
]);
```

Access the data:

```
GET /wp-json/cfdev/v1/term/category/{id}
```

```json
{
    "id": 5,
    "taxonomy": "category",
    "groups": {
        "category": {
            "color": "#e74c3c",
            "image": { "url": "...", "medium": "...", "alt": "..." }
        }
    }
}
```

> The REST API must be enabled globally in **WordPress Admin → CFDev → REST API**.

---

## 10. Admin columns

Show a term meta value as a column in the taxonomy term list:

```php
new TermMeta('category', 'Category Meta', [
    ['id' => 'color', 'type' => 'color', 'label' => 'Color', 'show_admin_column' => true],
]);
```

---

## Next

→ [User Meta](user-meta.md) · [Field Types](fields.md) · [Layouts](layouts.md) · [Cache](cache.md) · [REST API](rest-api.md)