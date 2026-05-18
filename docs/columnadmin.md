# Colonnes admin — `show_admin_column`, `admin_column_sortable`, `admin_column_filter`

Ces trois options permettent d'afficher et de manipuler des colonnes dans les listes de l'administration WordPress. Elles fonctionnent à deux niveaux distincts : sur une **taxonomie** ou sur un **champ**.

---

## Niveau 1 — Sur une taxonomie

Affiche les **termes assignés** à chaque post dans la liste des posts du type de contenu lié.

```php
// Via new Taxonomy()
new \CFDev\Taxonomy('genre', 'book', [
    'show_admin_column'     => true,
    'admin_column_sortable' => true,
    'admin_column_filter'   => true,
]);

// Via la fonction globale
register_cfdev_taxonomy('genre', 'book', [
    'show_admin_column'     => true,
    'admin_column_sortable' => true,
    'admin_column_filter'   => true,
]);

// Via addTaxonomy() sur un PostType
register_cfdev_post_type('book', 'books')
    ->addTaxonomy('genre', [
        'show_admin_column'     => true,
        'admin_column_sortable' => true,
        'admin_column_filter'   => true,
    ]);
```

| Option | Effet |
|---|---|
| `show_admin_column` | Ajoute une colonne dans la liste des posts avec les termes assignés |
| `admin_column_sortable` | Rend la colonne triable (en-tête cliquable) |
| `admin_column_filter` | Ajoute un dropdown de filtrage par terme au-dessus de la liste |

---

## Niveau 2 — Sur un champ (MetaBox, TermMeta, UserMeta)

Affiche la **valeur d'un champ meta** comme colonne dans les listes admin.
Les options se déclarent directement dans la définition du champ.

### MetaBox — liste des posts (`/wp-admin/edit.php?post_type=...`)

```php
register_cfdev_post_type('product', 'products')
    ->addMetaBox('details', 'Détails', [
        [
            'id'                    => 'price',
            'type'                  => 'text',
            'label'                 => 'Prix',
            'show_admin_column'     => true,
            'admin_column_sortable' => true,
        ],
        [
            'id'                => 'sku',
            'type'              => 'text',
            'label'             => 'SKU',
            'show_admin_column' => true,
        ],
    ]);
```

Résultat dans la liste des produits :

```
Titre       | Prix    | SKU      | Date
----------- | ------- | -------- | ----------
Produit A   | 29.90€  | SKU-001  | 2026-01-01
Produit B   | 14.50€  | SKU-002  | 2026-01-02
```

### TermMeta — liste des termes (`/wp-admin/edit-tags.php?taxonomy=...`)

```php
register_cfdev_taxonomy('genre', 'book')
    ->addTermMeta([
        [
            'id'                => 'color',
            'type'              => 'colorpicker',
            'label'             => 'Couleur',
            'show_admin_column' => true,
        ],
        [
            'id'                => 'icon',
            'type'              => 'image',
            'label'             => 'Icône',
            'show_admin_column' => true,
        ],
    ]);
```

### UserMeta — liste des utilisateurs (`/wp-admin/users.php`)

Signature : `__construct(string $id, $title, array $data = [], array|string $locations = [], int $priority = 10)`

`$locations` vaut `['show_user_profile', 'edit_user_profile']` par défaut — inutile de le préciser dans le cas général.
`$priority` contrôle l'ordre d'affichage des sections sur la page profil (même principe que la priorité des hooks WordPress).

```php
// Cas standard — locations par défaut
new \CFDev\Meta\UserMeta('profile', 'Profil', [
    [
        'id'                    => 'job_title',
        'type'                  => 'text',
        'label'                 => 'Poste',
        'show_admin_column'     => true,
        'admin_column_sortable' => true,
    ],
    [
        'id'                => 'avatar',
        'type'              => 'image',
        'label'             => 'Avatar',
        'show_admin_column' => true,
    ],
]);

// Contrôler l'ordre d'affichage de plusieurs sections
new \CFDev\Meta\UserMeta('section_a', 'Section A', $fields);          // priority 10 (défaut)
new \CFDev\Meta\UserMeta('section_b', 'Section B', $fields, [], 20);  // s'affiche après
new \CFDev\Meta\UserMeta('section_c', 'Section C', $fields, [], 5);   // s'affiche avant tout

// Restreindre à un seul onglet (visible sur son propre profil, pas éditable par l'admin)
new \CFDev\Meta\UserMeta('profile', 'Profil', $fields, ['show_user_profile']);
```

---

## Tableau de support

| Option | Taxonomie | MetaBox | TermMeta | UserMeta |
|---|---|---|---|---|
| `show_admin_column` | ✅ | ✅ | ✅ | ✅ |
| `admin_column_sortable` | ✅ | ✅ | ✅ | ✅ |
| `admin_column_filter` | ✅ | ❌ | ❌ | ❌ |

> `admin_column_filter` au niveau champ est déclaré dans `Field.php` mais pas encore implémenté. Seul le filtre au niveau taxonomie (niveau 1) fonctionne.

---

## Rendu automatique selon le type de champ

| Type de champ | Rendu dans la colonne |
|---|---|
| `image` | Miniature 100×100 |
| `text`, `select`, etc. | Valeur texte échappée |
| Champ répétable | Valeurs jointes par `, ` |
| `radios` | Libellé de l'option sélectionnée (MetaBox uniquement) |