# Démarrage rapide

[← README](../../readme.md) · [English](../en/quick-start.md)

---

## 0. Créer `cfdev-fields.php`

Tout le code ci-dessous va dans **`your-theme/cfdev-fields.php`** — CFDev le charge automatiquement.

```
your-theme/
└── cfdev-fields.php   ← créez ce fichier, CFDev s'occupe du reste
```

Chaque déclaration doit être enveloppée dans un hook `init` :

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    // → vos déclarations ici
});
```

> Pour les projets plus grands, voir [comment séparer en plusieurs fichiers](installation.md#séparer-en-plusieurs-fichiers).

---

## 1. Enregistrer un post type avec des champs

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {

    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price',    'type' => 'number', 'label' => 'Prix',          'required' => true],
            ['id' => 'photo',    'type' => 'image',  'label' => 'Photo'],
            ['id' => 'gallery',  'type' => 'gallery','label' => 'Galerie'],
            ['id' => 'brochure', 'type' => 'file',   'label' => 'Brochure PDF'],
            ['id' => 'cta',      'type' => 'link',   'label' => 'Bouton CTA'],
        ]);

});
```

## 2. Ajouter une taxonomie

```php
register_cfdev_post_type(['product', 'products'])
    ->addTaxonomy(['category', 'categories'])
    ->addMetaBox('product_info', 'Infos produit', $fields);
```

## 3. Lire les données dans un template

### Sans cache (méta directe)

```php
$price   = get_post_meta(get_the_ID(), 'price', true);
$imageId = get_post_meta(get_the_ID(), 'photo', true);

echo esc_html($price);
echo wp_get_attachment_image($imageId, 'medium');
```

### Avec cache (recommandé — données résolues, sans requêtes supplémentaires)

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

// Champ scalaire
echo esc_html($product['price'] ?? '');

// Image (toutes les tailles résolues)
$photo = $product['photo'] ?? [];
echo '<img src="' . esc_url($photo['medium'] ?? '') . '" alt="' . esc_attr($photo['alt'] ?? '') . '">';

// Galerie
foreach ($product['gallery'] ?? [] as $img) {
    echo '<img src="' . esc_url($img['medium']) . '" alt="' . esc_attr($img['alt']) . '">';
}

// Fichier
$pdf = $product['brochure'] ?? [];
echo '<a href="' . esc_url($pdf['url'] ?? '') . '">' . esc_html($pdf['filename'] ?? '') . '</a>';

// Lien
$cta = $product['cta'] ?? [];
if (!empty($cta['url'])) {
    echo '<a href="' . esc_url($cta['url']) . '" target="' . esc_attr($cta['target'] ?? '_self') . '">'
        . esc_html($cta['text'] ?: $cta['url']) . '</a>';
}
```

## 4. Ajouter de la validation

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;

->addMetaBox('product_info', 'Infos produit', [
    ['id' => 'price', 'type' => 'number', 'label' => 'Prix', 'rules' => [
        new Required(),
        new Min(0),
    ]],
    ['id' => 'photo', 'type' => 'image', 'label' => 'Photo', 'rules' => [
        new ImageMinDimensions(800, 600),
    ]],
]);
```

Les erreurs de validation survivent au cycle POST → redirection et s'affichent inline dans le formulaire d'édition.

## 5. Ajouter des méta de terme et d'utilisateur

```php
// Méta de terme
register_cfdev_taxonomy('genre', 'product')
    ->addTermMeta([
        ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ]);

// Méta d'utilisateur
(new \Weblitzer\CFDev\Meta\UserMeta('profile', 'Profil', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
]))->onlyForRole('administrator');
```

---

## Suivant

→ [Types de champs](champs.md) · [Layouts](layouts.md) · [Validation](validation.md) · [Cache](cache.md)
