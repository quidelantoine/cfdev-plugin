# Quick Start

[← README](../../readme.md) · [Français](../fr/demarrage-rapide.md)

---

## 0. Create `cfdev-fields.php`

All the code below goes in **`your-theme/cfdev-fields.php`** — CFDev loads it automatically.

```
your-theme/
└── cfdev-fields.php   ← create this file, CFDev does the rest
```

Every declaration must be wrapped in an `init` action:

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    // → your declarations here
});
```

> For larger projects, see [how to split into multiple files](installation.md#splitting-into-multiple-files).

---

## 1. Register a post type with fields

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {

    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Product Info', [
            ['id' => 'price',    'type' => 'number', 'label' => 'Price',        'required' => true],
            ['id' => 'photo',    'type' => 'image',  'label' => 'Photo'],
            ['id' => 'gallery',  'type' => 'gallery','label' => 'Gallery'],
            ['id' => 'brochure', 'type' => 'file',   'label' => 'Brochure PDF'],
            ['id' => 'cta',      'type' => 'link',   'label' => 'CTA Button'],
        ]);

});
```

## 2. Add a taxonomy

```php
register_cfdev_post_type(['product', 'products'])
    ->addTaxonomy(['category', 'categories'])
    ->addMetaBox('product_info', 'Product Info', $fields);
```

## 3. Read data in a template

### Without cache (direct meta)

```php
$price   = get_post_meta(get_the_ID(), 'price', true);
$imageId = get_post_meta(get_the_ID(), 'photo', true);

echo esc_html($price);
echo wp_get_attachment_image($imageId, 'medium');
```

### With cache (recommended — resolved, no extra queries)

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

// Scalar field
echo esc_html($product['price'] ?? '');

// Image (all sizes resolved)
$photo = $product['photo'] ?? [];
echo '<img src="' . esc_url($photo['medium'] ?? '') . '" alt="' . esc_attr($photo['alt'] ?? '') . '">';

// Gallery
foreach ($product['gallery'] ?? [] as $img) {
    echo '<img src="' . esc_url($img['medium']) . '" alt="' . esc_attr($img['alt']) . '">';
}

// File
$pdf = $product['brochure'] ?? [];
echo '<a href="' . esc_url($pdf['url'] ?? '') . '">' . esc_html($pdf['filename'] ?? '') . '</a>';

// Link
$cta = $product['cta'] ?? [];
if (!empty($cta['url'])) {
    echo '<a href="' . esc_url($cta['url']) . '" target="' . esc_attr($cta['target'] ?? '_self') . '">'
        . esc_html($cta['text'] ?: $cta['url']) . '</a>';
}
```

## 4. Add validation

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;

->addMetaBox('product_info', 'Product Info', [
    ['id' => 'price', 'type' => 'number', 'label' => 'Price', 'rules' => [
        new Required(),
        new Min(0),
    ]],
    ['id' => 'photo', 'type' => 'image', 'label' => 'Photo', 'rules' => [
        new ImageMinDimensions(800, 600),
    ]],
]);
```

Validation errors survive the POST→redirect cycle and appear inline in the edit form.

## 5. Add term meta and user meta

```php
// Term meta
register_cfdev_taxonomy('genre', 'product')
    ->addTermMeta([
        ['id' => 'color', 'type' => 'color', 'label' => 'Color'],
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ]);

// User meta
(new \Weblitzer\CFDev\Meta\UserMeta('profile', 'Profile', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
]))->onlyForRole('administrator');
```

---

## Next

→ [Field Types](fields.md) · [Layouts](layouts.md) · [Validation](validation.md) · [Cache](cache.md)
