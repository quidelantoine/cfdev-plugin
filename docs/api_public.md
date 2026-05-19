# CFDev — API publique

CFDev est une API **code-first** : tout se déclare en PHP, sans interface d'administration.
Tous les éléments ci-dessous s'enregistrent automatiquement dans le [Registry](registry.md).

---

## Vue d'ensemble rapide

```
PostType
 ├── addTaxonomy()      → crée une Taxonomy liée au CPT
 ├── addMetaBox()       → ajoute des champs sur les posts du CPT
 │    ├── onlyForId()         → condition : ID de post
 │    └── onlyForTemplate()   → condition : template de page
 └── addSupport()       → add_post_type_support()

Taxonomy (standalone ou via PostType)
 └── addTermMeta()      → ajoute des champs sur les termes
      └── onlyIfParent()     → condition : terme parent

UserMeta (standalone)
 └── onlyForRole()      → condition : rôle utilisateur
```

---

## 1. Custom Post Type

### Déclaration minimale

```php
register_cfdev_post_type('livre');
```

### Avec nom pluriel et arguments WP

```php
register_cfdev_post_type(['livre', 'livres'], [
    'public'       => true,
    'menu_icon'    => 'dashicons-book',
    'supports'     => ['title', 'editor', 'thumbnail'],
    'has_archive'  => true,
]);
```

**Paramètres**

| Paramètre | Type | Description |
|---|---|---|
| `$name` | `string\|array` | Nom singulier, ou `['singulier', 'pluriel']` |
| `$args` | `array` | Arguments passés à `register_post_type()` |
| `$labels` | `array` | Labels WP à surcharger |

> Sans `['singulier', 'pluriel']`, le pluriel est généré automatiquement (`livre` → `livres`).

---

## 2. Ajouter une taxonomie à un CPT

### Via chaînage sur PostType

```php
register_cfdev_post_type(['livre', 'livres'])
    ->addTaxonomy(['genre', 'genres'])
    ->addTaxonomy(['auteur', 'auteurs'], ['hierarchical' => false]);
```

### Standalone (pour accéder à `addTermMeta`)

```php
$genre = register_cfdev_taxonomy(['genre', 'genres'], 'livre', [
    'show_admin_column' => true,
]);
```

**Paramètres de `addTaxonomy()` / `register_cfdev_taxonomy()`**

| Paramètre | Type | Description |
|---|---|---|
| `$name` | `string\|array` | Nom singulier, ou `['singulier', 'pluriel']` |
| `$post_type` | `string\|array` | Slug(s) du CPT associé |
| `$args` | `array` | Arguments passés à `register_taxonomy()` |
| `$labels` | `array` | Labels WP à surcharger |

---

## 3. Meta boxes sur un CPT

```php
$champs_livre = [
    ['type' => 'text',     'name' => 'isbn',       'label' => 'ISBN'],
    ['type' => 'number',   'name' => 'pages',      'label' => 'Nombre de pages'],
    ['type' => 'image',    'name' => 'couverture', 'label' => 'Couverture'],
    ['type' => 'textarea', 'name' => 'resume',     'label' => 'Résumé'],
];

register_cfdev_post_type(['livre', 'livres'])
    ->addTaxonomy('genre')
    ->addMetaBox('livre_details', 'Détails du livre', $champs_livre)
    ->addSupport('thumbnail');
```

### Plusieurs meta boxes

```php
register_cfdev_post_type(['livre', 'livres'])
    ->addMetaBox('livre_details', 'Détails',    $champs_details)
    ->addMetaBox('livre_seo',     'SEO',         $champs_seo, 'normal', 'low')
    ->addMetaBox('livre_sidebar', 'Sidebar',     $champs_sidebar, 'side');
```

**Paramètres de `addMetaBox()`**

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `$id` | `string` | — | Identifiant unique |
| `$title` | `string` | — | Titre affiché |
| `$fields` | `array` | `[]` | Tableau de champs |
| `$context` | `string` | `'normal'` | `'normal'` \| `'side'` \| `'advanced'` |
| `$priority` | `string` | `'default'` | `'default'` \| `'high'` \| `'low'` |

---

## 4. Conditions sur les meta boxes

### Par ID de post (ex : page d'accueil uniquement)

```php
register_cfdev_post_type('page')
    ->addMetaBox('home_hero', 'Bloc Hero', $fields_hero)
        ->onlyForId(42)
    ->addMetaBox('page_seo', 'SEO', $fields_seo);   // sans condition : toutes les pages
```

### Par template de page

```php
register_cfdev_post_type('page')
    ->addMetaBox('contact_map', 'Carte', $fields_map)
        ->onlyForTemplate('template-contact.php')
    ->addMetaBox('page_seo', 'SEO', $fields_seo);
```

> `onlyForId()` et `onlyForTemplate()` s'appliquent au **dernier** `addMetaBox()` appelé.
> Le chaînage des autres méthodes (`addMetaBox`, `addSupport`, `addTaxonomy`) continue normalement.

---

## 5. Champs sur les termes d'une taxonomie

La méthode `addTermMeta()` est disponible sur `Taxonomy` (retourné par `register_cfdev_taxonomy()`).

```php
$champs_genre = [
    ['type' => 'image',    'name' => 'genre_image',       'label' => 'Image du genre'],
    ['type' => 'textarea', 'name' => 'genre_description',  'label' => 'Description longue'],
    ['type' => 'color',    'name' => 'genre_couleur',      'label' => 'Couleur'],
];

$genre = register_cfdev_taxonomy(['genre', 'genres'], 'livre');
$genre->addTermMeta($champs_genre);
```

### Limiter à certains formulaires

```php
// Seulement sur le formulaire d'édition (pas à la création)
$genre->addTermMeta($champs_genre, ['edit_form']);

// Seulement à la création
$genre->addTermMeta($champs_genre, ['add_form']);
```

### Condition : terme parent

```php
$sous_genre = register_cfdev_taxonomy(['sous-genre', 'sous-genres'], 'livre');
$sous_genre->addTermMeta($champs_sous_genre)
           ->onlyIfParent(12);   // ID du terme parent requis
```

---

## 6. Champs sur les profils utilisateurs

```php
$champs_user = [
    ['type' => 'text',  'name' => 'telephone', 'label' => 'Téléphone'],
    ['type' => 'image', 'name' => 'avatar',     'label' => 'Avatar personnalisé'],
];

$userMeta = new \Weblitzer\CFDev\Meta\UserMeta('profil_extra', 'Informations complémentaires', $champs_user);
```

### Condition : rôle utilisateur

```php
$champs_admin = [
    ['type' => 'toggle', 'name' => 'beta_access', 'label' => 'Accès beta'],
];

$adminMeta = new \Weblitzer\CFDev\Meta\UserMeta('options_admin', 'Options admin', $champs_admin);
$adminMeta->onlyForRole('administrator');

// Plusieurs rôles
$editorMeta = new \Weblitzer\CFDev\Meta\UserMeta('options_edition', 'Options édition', $champs_editor);
$editorMeta->onlyForRole(['editor', 'author']);
```

---

## 7. Exemple complet — CPT Livre

```php
// Dans cfdev-fields.php (thème) ou dans un hook init
add_action('init', function () {

    // ── Champs ────────────────────────────────────────────────
    $champs_details = [
        ['type' => 'text',   'name' => 'isbn',    'label' => 'ISBN', 'required' => true],
        ['type' => 'number', 'name' => 'pages',   'label' => 'Pages'],
        ['type' => 'image',  'name' => 'couverture', 'label' => 'Couverture'],
    ];

    $champs_seo = [
        ['type' => 'text',     'name' => 'seo_title',       'label' => 'Titre SEO'],
        ['type' => 'textarea', 'name' => 'seo_description', 'label' => 'Description SEO'],
    ];

    $champs_genre = [
        ['type' => 'image', 'name' => 'genre_image', 'label' => 'Image du genre'],
        ['type' => 'color', 'name' => 'genre_color', 'label' => 'Couleur'],
    ];

    $champs_bio = [
        ['type' => 'textarea', 'name' => 'bio',    'label' => 'Biographie'],
        ['type' => 'url',      'name' => 'site',   'label' => 'Site web'],
    ];

    // ── Post Type ─────────────────────────────────────────────
    register_cfdev_post_type(['livre', 'livres'], ['menu_icon' => 'dashicons-book'])
        ->addTaxonomy(['genre', 'genres'])
        ->addTaxonomy(['auteur', 'auteurs'], ['hierarchical' => false])
        ->addSupport('thumbnail')
        ->addMetaBox('livre_details', 'Détails du livre', $champs_details)
        ->addMetaBox('livre_seo',     'SEO',              $champs_seo, 'normal', 'low');

    // ── Taxonomies avec champs ────────────────────────────────
    register_cfdev_taxonomy(['genre', 'genres'], 'livre')
        ->addTermMeta($champs_genre);

    register_cfdev_taxonomy(['auteur', 'auteurs'], 'livre', ['hierarchical' => false])
        ->addTermMeta($champs_bio);

    // ── User Meta ─────────────────────────────────────────────
    (new \Weblitzer\CFDev\Meta\UserMeta('editeur_options', 'Options éditeur', [
        ['type' => 'toggle', 'name' => 'peut_publier', 'label' => 'Peut publier sans validation'],
    ]))->onlyForRole(['administrator', 'editor']);
});
```

---

## 8. Lire les champs enregistrés (Registry)

Après que tout est déclaré (après `init`) :

```php
// Tous les meta boxes, avec leurs champs et conditions
$all = \Weblitzer\CFDev\Registry::all();

// Doublons d'ID de champ sur le même scope
$dups = \Weblitzer\CFDev\Registry::duplicates();
// Ex. : ['seo_title' => ['livre_seo', 'page_seo']] si le même ID existe deux fois sur 'page'
```

→ Voir [registry.md](registry.md) pour le détail du format et des conditions.

---

---

## 9. Organisation des fichiers (pattern recommandé)

### Principe — séparer déclaration et définition

Inspiré d'ACF : un fichier dit **où** les champs sont assignés, des fichiers séparés disent **quels** champs.

```
theme/
  cfdev-fields.php          ← déclaration : qui est assigné où   (le "où")
  cfdev/
    fields/
      lessons.php           ← groupe : champs CPT Leçons
      courses.php           ← groupe : champs terme Module
      page-seo.php          ← groupe : champs SEO page
      user-profile.php      ← groupe : champs profil utilisateur
```

### `cfdev-fields.php` — le fichier de déclaration

Chargé **automatiquement** par le plugin si présent à la racine du thème actif.
Il contient uniquement la logique d'assignation.

```php
<?php
// theme/cfdev-fields.php

add_action('init', static function (): void {

    register_cfdev_post_type(['livre', 'livres'])
        ->addTaxonomy(['genre', 'genres'])
        ->addMetaBox('livre_details', 'Détails',
            require __DIR__ . '/cfdev/fields/book-details.php'
        )
        ->addMetaBox('livre_seo', 'SEO',
            require __DIR__ . '/cfdev/fields/book-seo.php',
            'normal', 'low'
        );

    register_cfdev_taxonomy(['genre', 'genres'], 'livre')
        ->addTermMeta(require __DIR__ . '/cfdev/fields/genre.php');

    (new \Weblitzer\CFDev\Meta\UserMeta(
        'profil_admin', 'Options admin',
        require __DIR__ . '/cfdev/fields/user-admin.php'
    ))->onlyForRole('administrator');
});
```

### Fichiers de champs — ils `return` un tableau

```php
<?php
// theme/cfdev/fields/book-details.php

return [
    ['id' => 'isbn',       'type' => 'text',     'label' => 'ISBN',     'required' => true],
    ['id' => 'pages',      'type' => 'number',   'label' => 'Pages'],
    ['id' => 'couverture', 'type' => 'image',    'label' => 'Couverture'],
    ['id' => 'resume',     'type' => 'textarea', 'label' => 'Résumé'],
];
```

> `require` évalue le fichier et retourne le tableau directement — pas de nom de fonction,
> pas de risque de conflit, réutilisable sur plusieurs post types.

### Réutiliser le même groupe sur plusieurs post types

```php
$seo = require __DIR__ . '/cfdev/fields/seo.php';

register_cfdev_post_type('livre')->addMetaBox('livre_seo', 'SEO', $seo);
register_cfdev_post_type('film') ->addMetaBox('film_seo',  'SEO', $seo);
```

### Champs de démo (plugin)

Le plugin embarque des champs de démo couvrant tous les layouts (flat, tabs, accordion, bundle)
sur post, page, term (`category`) et user meta.

Ils s'activent directement dans `Initializer::boot()` :

```php
// src/Initializer.php — dans boot()
$this->container->bind(Config::class, new Config(
    // ...
    demo: true,   // ← activer / désactiver ici
));
```

La valeur par défaut est `false`. Mettre `true` affiche des meta boxes préfixées `[DEMO]`
sur les types `post`, `page`, `category`, et les profils utilisateurs.
Remettre à `false` (ou supprimer la ligne) avant de déployer en production.

---

## Récupérer les valeurs en front

```php
// Meta de post
get_post_meta($post_id, 'isbn', true);

// Meta de terme (par ID ou slug)
get_cfdev_term_meta($term_id, 'genre', 'genre_image');
get_cfdev_term_meta('roman', 'genre', 'genre_color');

// Afficher directement
the_cfdev_term_meta($term_id, 'genre', 'genre_image');

// Meta utilisateur (WP standard)
get_user_meta($user_id, 'telephone', true);
```
