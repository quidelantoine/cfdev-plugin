# WooCommerce

[← README](../../readme.md) · [Français](../fr/woocommerce.md)

CFDev works with WooCommerce — you can add custom fields to products and product categories using the same PHP API as any other post type.

> **Tested with:** WooCommerce 10.x · Classic Editor plugin · PHP 8.2 · WordPress 6.5

---

## Product fields

Register a meta box on the `product` post type exactly as you would any other CPT:

```php
use Weblitzer\CFDev\Meta\MetaBox;

new MetaBox(
    'my_product_extra',
    'Extra product info',
    'product',
    [
        ['id' => 'badge',    'type' => 'text',   'label' => 'Badge label',
         'description' => 'e.g. New, Sale, Exclusive'],
        ['id' => 'note',     'type' => 'textarea','label' => 'Promo note'],
        ['id' => 'featured', 'type' => 'toggle', 'label' => 'Highlight this product'],
        ['id' => 'priority', 'type' => 'select', 'label' => 'Priority',
         'options' => ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'],
         'args'    => ['show_option_none' => '-- Choose --']],
    ]
);
```

The metabox appears below WooCommerce's "Product data" panel in the classic editor.

Read the saved values in a template:

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['my_product_extra'] ?? [];

echo esc_html($product['badge'] ?? '');
```

---

## Product category fields

Use `TermMeta` on `product_cat` (or `product_tag`):

```php
use Weblitzer\CFDev\Meta\TermMeta;

new TermMeta(
    'product_cat',
    'Category extra',
    [
        ['id' => 'cat_banner', 'type' => 'text', 'label' => 'Banner text'],
    ]
);
```

Fields appear on the **Edit category** form (not on the Add form — the default location is `edit_form` only).

Read the values:

```php
$cache    = new \Weblitzer\CFDev\Cache\CacheManager();
$data     = $cache->term($term->term_id, $term->taxonomy);
$category = $data['groups']['product_cat'] ?? [];

echo esc_html($category['cat_banner'] ?? '');
```

---

## Block product editor (WooCommerce 8+)

WooCommerce 8+ ships a React-based product editor that **does not support classic meta boxes**.

To use CFDev fields on products you have two options:

**Option A — keep the classic editor** (recommended for CFDev):

```php
add_filter('woocommerce_admin_features', static function (array $features): array {
    $features['product-block-editor'] = false;
    return $features;
});
```

Add this filter in your `cfdev-fields.php` file or in a mu-plugin. The classic editor is then used for all products.

**Option B — disable the block editor via WooCommerce settings:**

WooCommerce → Settings → Features → uncheck **"New product editor"**.

> **Note:** `product_cat` and `product_tag` are not affected — the term editor is always classic.

---

## HPOS (High-Performance Order Storage)

HPOS moves WooCommerce orders out of the `wp_posts` table into dedicated tables. As a result:

- `shop_order` is no longer a real WordPress post type when HPOS is enabled
- CFDev's `MetaBox` relies on `save_post` and WP meta functions — **not compatible with HPOS orders**
- Products (`product`) are unaffected — they remain standard posts

If you need custom fields on orders, use WooCommerce's own order meta API or a dedicated WC extension.

---

## REST API

CFDev registers meta fields via `show_in_rest` on the **WP REST API** endpoint (`/wp/v2/products`), not on WooCommerce's own REST API (`/wc/v3/products`). These are two separate endpoints:

| Endpoint | What it returns |
|---|---|
| `/wp/v2/products/{id}` | WP post object + CFDev meta in `meta` key |
| `/wc/v3/products/{id}` | Full WooCommerce product data (price, stock, etc.) |

To expose CFDev fields on a product field, set `'rest' => true` in the field definition — they will appear in the WP REST response only.

---

## Summary

| Scenario | Supported |
|---|---|
| Custom fields on `product` (classic editor) | ✅ |
| Custom fields on `product_cat` / `product_tag` | ✅ |
| WooCommerce block product editor | ❌ (disable it or use Option A above) |
| Custom fields on `shop_order` with HPOS | ❌ |
| Custom fields on `shop_order` without HPOS | ✅ (standard post meta) |
| CFDev cache on products | ✅ |
| REST API via `/wp/v2/products` | ✅ |
| REST API via `/wc/v3/products` | ❌ (different endpoint) |
