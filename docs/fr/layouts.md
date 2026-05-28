# Layouts

[← README](../../readme.md) · [English](../en/layouts.md)

CFDev propose trois conteneurs de layout : **Bundle**, **Tabs** et **Accordion**. Ce ne sont pas des types de champs — ce sont des wrappers qui organisent les champs dans une MetaBox.

---

## Bundle — lignes répétables

Un Bundle crée des lignes ajoutables/supprimables dynamiquement. Chaque ligne contient les mêmes champs.

```php
->addMetaBox('team', 'Équipe', [
    'bundle',
    [
        ['id' => 'member_name',  'type' => 'text',  'label' => 'Nom',   'required' => true],
        ['id' => 'member_photo', 'type' => 'image', 'label' => 'Photo'],
        ['id' => 'member_role',  'type' => 'text',  'label' => 'Rôle'],
    ],
]);
```

### ID du bundle

Le bundle préfixe toujours son ID avec `_` — c'est à la fois la clé meta en base et la clé dans le cache.

Par défaut, le bundle utilise l'ID de la MetaBox :

```php
->addMetaBox('team', 'Équipe', [      // MetaBox ID = 'team'
    'bundle',
    [/* champs */],
]);
// Clé meta DB et cache → '_team'
```

Vous pouvez définir un ID explicite :

```php
->addMetaBox('team', 'Équipe', [
    'bundle',
    'team_members',    // ID explicite
    [/* champs */],
]);
// Clé meta DB et cache → '_team_members'
```

### Lire les données du bundle depuis le cache

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$members = $data['groups']['team']['_team'] ?? [];            // ID par défaut
// $members = $data['groups']['team']['_team_members'] ?? []; // ID explicite

// $members = [
//   ['member_name' => 'Alice', 'member_photo' => ['id'=>1,'full'=>'…'], 'member_role' => 'Dev'],
//   ['member_name' => 'Bob',   'member_photo' => ['id'=>2,'full'=>'…'], 'member_role' => 'Design'],
// ]

foreach ($members as $member) {
    $photo = $member['member_photo'] ?? [];
    echo '<h3>' . esc_html($member['member_name'] ?? '') . '</h3>';
    echo '<img src="' . esc_url($photo['medium'] ?? '') . '" alt="' . esc_attr($photo['alt'] ?? '') . '">';
}
```

---

## Tabs — onglets

Organise les champs en onglets cliquables. Chaque onglet peut contenir des champs plats ou un bundle.

```php
->addMetaBox('product_tabs', 'Produit', [
    'tabs',
    [
        'Général' => [
            ['id' => 'price',       'type' => 'number',   'label' => 'Prix'],
            ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
        ],
        'Médias' => [
            ['id' => 'cover',   'type' => 'image',   'label' => 'Couverture'],
            ['id' => 'gallery', 'type' => 'gallery', 'label' => 'Galerie'],
        ],
        'Livraison' => [
            ['bundle', '_delivery', [
                ['id' => 'country', 'type' => 'text',   'label' => 'Pays'],
                ['id' => 'delay',   'type' => 'number', 'label' => 'Délai (jours)'],
            ]],
        ],
    ],
]);
```

Les labels des onglets sont les clés du tableau. Les champs sont lus et sauvegardés de la même façon que les champs plats.

---

## Accordion — sections repliables

Même structure que les Tabs, affiché en accordéon.

```php
->addMetaBox('product_info', 'Produit', [
    'accordion',
    [
        'Détails' => [
            ['id' => 'weight',     'type' => 'number', 'label' => 'Poids (kg)'],
            ['id' => 'dimensions', 'type' => 'text',   'label' => 'Dimensions'],
        ],
        'Galerie' => [
            ['bundle', '_slides', [
                ['id' => 'slide_title', 'type' => 'text',  'label' => 'Titre'],
                ['id' => 'slide_image', 'type' => 'image', 'label' => 'Image'],
            ]],
        ],
    ],
]);
```

---

## Règles d'imbrication

| Conteneur | Peut contenir |
|---|---|
| MetaBox | champs plats, Bundle, Tabs, Accordion |
| Tabs | champs plats par onglet, Bundle par onglet |
| Accordion | champs plats par section, Bundle par section |
| Bundle | champs plats uniquement (pas d'imbrication) |

---

## Structure cache pour les bundles dans Tabs/Accordion

Les données du bundle sont accessibles de la même façon quelle que soit l'imbrication :

```php
$data    = $cache->post(get_the_ID());
$slides  = $data['groups']['product_info']['_slides'] ?? [];
```
