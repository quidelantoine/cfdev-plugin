# Installation

[← README](../../readme.md) · [English](../en/installation.md)

---

## Prérequis

| | Minimum | Recommandé |
|---|---|---|
| PHP | 8.2 | 8.3+ |
| WordPress | 6.5 | dernière version |

PHP 8.2 minimum : propriétés `readonly` utilisées dans `FileMime`.  
WordPress 6.5 minimum : cible les installations activement maintenues.

---

## Installation via Composer

```bash
composer require weblitzer/cfdev-plugin
```

Le plugin s'enregistre automatiquement une fois activé dans WordPress.

---

## Build de production

Supprimer les dépendances dev et optimiser l'autoloader :

```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

Ou ajouter à `composer.json` de façon permanente :

```json
"config": {
    "optimize-autoloader": true,
    "classmap-authoritative": true
}
```

---

## Déclarer des champs — `cfdev-fields.php`

Créez un fichier nommé **`cfdev-fields.php`** à la racine de votre thème actif. CFDev le détecte et le charge automatiquement — aucun `require` nécessaire.

```
your-theme/
└── cfdev-fields.php   ← chargé automatiquement par CFDev
```

Toutes les déclarations doivent être enveloppées dans un hook `init` :

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price', 'type' => 'number', 'label' => 'Prix', 'required' => true],
            ['id' => 'photo', 'type' => 'image',  'label' => 'Photo'],
        ]);
});
```

---

## Séparer en plusieurs fichiers

Quand le projet grandit, séparez les déclarations par type de contenu. `cfdev-fields.php` devient un simple point d'entrée :

```
your-theme/
├── cfdev-fields.php       ← point d'entrée
└── cfdev/
    ├── post-types.php     ← CPTs et leurs meta boxes
    ├── taxonomies.php     ← taxonomies et term meta
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
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price',   'type' => 'number', 'label' => 'Prix'],
            ['id' => 'photo',   'type' => 'image',  'label' => 'Photo'],
        ]);

    register_cfdev_post_type(['event', 'events'], ['public' => true])
        ->addMetaBox('event_info', 'Infos événement', [
            ['id' => 'date',     'type' => 'date',   'label' => 'Date'],
            ['id' => 'location', 'type' => 'text',   'label' => 'Lieu'],
        ]);
});
```

```php
// your-theme/cfdev/taxonomies.php

add_action('init', static function (): void {
    register_cfdev_taxonomy('category', 'product')
        ->addTermMeta([
            ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
            ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
        ]);
});
```

```php
// your-theme/cfdev/users.php

add_action('init', static function (): void {
    register_cfdev_user_meta('profile', 'Profil', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
    ])->onlyForRole('administrator');
});
```

Si vous préférez charger le fichier vous-même (depuis un plugin par exemple) :

```php
// functions.php ou un plugin custom
require_once get_template_directory() . '/cfdev-fields.php';
```

---

## Vérifier l'installation

Après avoir activé le plugin et ajouté au moins une déclaration de champ, rendez-vous dans **WordPress Admin → CFDev**. Vous devriez voir la liste de tous les groupes de champs enregistrés.

---

## Suivant

→ [Démarrage rapide](demarrage-rapide.md)
