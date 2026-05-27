# WooCommerce

[← README](../../readme.md) · [English](../en/woocommerce.md)

CFDev fonctionne avec WooCommerce — vous pouvez ajouter des champs personnalisés aux produits et catégories de produits avec la même API PHP que pour n'importe quel autre post type.

> **Testé avec :** WooCommerce 10.x · Classic Editor plugin · PHP 8.2 · WordPress 6.5

---

## Champs produit

Enregistrez un meta box sur le post type `product` comme pour n'importe quel autre CPT :

```php
use Weblitzer\CFDev\Meta\MetaBox;

new MetaBox(
    'my_product_extra',
    'Infos produit supplémentaires',
    'product',
    [
        ['id' => 'badge',    'type' => 'text',    'label' => 'Badge',
         'description' => 'Ex : Nouveau, Promo, Exclusif'],
        ['id' => 'note',     'type' => 'textarea', 'label' => 'Note promotionnelle'],
        ['id' => 'featured', 'type' => 'toggle',   'label' => 'Mettre en avant'],
        ['id' => 'priority', 'type' => 'select',   'label' => 'Priorité',
         'options' => ['normal' => 'Normale', 'high' => 'Haute', 'urgent' => 'Urgente'],
         'args'    => ['show_option_none' => '-- Choisir --']],
    ]
);
```

Le meta box apparaît sous le panneau "Données produit" de WooCommerce dans l'éditeur classique.

Lecture dans un template :

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['my_product_extra'] ?? [];

echo esc_html($product['badge'] ?? '');
```

---

## Champs de catégorie produit

Utilisez `TermMeta` sur `product_cat` (ou `product_tag`) :

```php
use Weblitzer\CFDev\Meta\TermMeta;

new TermMeta(
    'product_cat',
    'Catégorie — extras',
    [
        ['id' => 'cat_banner', 'type' => 'text', 'label' => 'Texte bannière'],
    ]
);
```

Les champs apparaissent sur le formulaire **Modifier la catégorie** (pas sur le formulaire d'ajout — l'emplacement par défaut est `edit_form` uniquement).

Lecture des valeurs :

```php
$cache    = new \Weblitzer\CFDev\Cache\CacheManager();
$data     = $cache->term($term->term_id, $term->taxonomy);
$category = $data['groups']['product_cat'] ?? [];

echo esc_html($category['cat_banner'] ?? '');
```

---

## Éditeur produit en bloc (WooCommerce 8+)

WooCommerce 8+ intègre un éditeur produit React qui **ne supporte pas les meta boxes classiques**.

Pour utiliser les champs CFDev sur les produits, deux options :

**Option A — garder l'éditeur classique** (recommandé avec CFDev) :

```php
add_filter('woocommerce_admin_features', static function (array $features): array {
    $features['product-block-editor'] = false;
    return $features;
});
```

Ajoutez ce filtre dans votre `cfdev-fields.php` ou dans un mu-plugin. L'éditeur classique est alors utilisé pour tous les produits.

**Option B — désactiver via les réglages WooCommerce :**

WooCommerce → Réglages → Fonctionnalités → décocher **"Nouvel éditeur de produits"**.

> **Note :** `product_cat` et `product_tag` ne sont pas affectés — le formulaire de termes est toujours classique.

---

## HPOS (High-Performance Order Storage)

HPOS déplace les commandes WooCommerce hors de la table `wp_posts` vers des tables dédiées. En conséquence :

- `shop_order` n'est plus un vrai post type WordPress quand HPOS est activé
- CFDev's `MetaBox` repose sur `save_post` et les fonctions WP meta — **incompatible avec les commandes HPOS**
- Les produits (`product`) ne sont pas concernés — ils restent des posts standard

Si vous avez besoin de champs personnalisés sur les commandes, utilisez l'API meta de WooCommerce ou une extension WC dédiée.

---

## REST API

CFDev enregistre les champs meta via `show_in_rest` sur l'endpoint **WP REST API** (`/wp/v2/products`), pas sur l'API REST de WooCommerce (`/wc/v3/products`). Ce sont deux endpoints distincts :

| Endpoint | Ce qu'il retourne |
|---|---|
| `/wp/v2/products/{id}` | Objet post WP + meta CFDev dans la clé `meta` |
| `/wc/v3/products/{id}` | Données WooCommerce complètes (prix, stock, etc.) |

Pour exposer un champ CFDev, ajoutez `'rest' => true` dans la définition du champ — il apparaîtra uniquement dans la réponse WP REST.

---

## Récapitulatif

| Scénario | Supporté |
|---|---|
| Champs sur `product` (éditeur classique) | ✅ |
| Champs sur `product_cat` / `product_tag` | ✅ |
| Éditeur produit en bloc WooCommerce | ❌ (le désactiver — voir Option A ci-dessus) |
| Champs sur `shop_order` avec HPOS | ❌ |
| Champs sur `shop_order` sans HPOS | ✅ (post meta standard) |
| Cache CFDev sur les produits | ✅ |
| REST API via `/wp/v2/products` | ✅ |
| REST API via `/wc/v3/products` | ❌ (endpoint distinct) |
