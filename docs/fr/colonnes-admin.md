# Colonnes admin

[← README](../../readme.md) · [English](../en/admin-columns.md)

Des colonnes personnalisées peuvent être ajoutées à la vue liste de l'administration WordPress (posts, termes, utilisateurs) directement depuis la déclaration du champ.

---

## Sur un champ (MetaBox, TermMeta, UserMeta)

Ajoutez `show_admin_column` et optionnellement `admin_column_sortable` dans la définition du champ :

```php
['id' => 'price',  'type' => 'number', 'label' => 'Prix',
 'show_admin_column' => true, 'admin_column_sortable' => true],

['id' => 'avatar', 'type' => 'image',  'label' => 'Avatar',
 'show_admin_column' => true],
```

### MetaBox — liste des posts (`/wp-admin/edit.php?post_type=…`)

```php
register_cfdev_post_type('product', 'products')
    ->addMetaBox('details', 'Détails', [
        [
            'id'                    => 'price',
            'type'                  => 'number',
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

### TermMeta — liste des termes (`/wp-admin/edit-tags.php?taxonomy=…`)

```php
register_cfdev_taxonomy('genre', 'book')
    ->addTermMeta([
        [
            'id'                => 'color',
            'type'              => 'color',
            'label'             => 'Couleur',
            'show_admin_column' => true,
        ],
    ]);
```

### UserMeta — liste des utilisateurs (`/wp-admin/users.php`)

```php
new \Weblitzer\CFDev\Meta\UserMeta('profile', 'Profil', [
    [
        'id'                    => 'job_title',
        'type'                  => 'text',
        'label'                 => 'Poste',
        'show_admin_column'     => true,
        'admin_column_sortable' => true,
    ],
]);
```

---

## Sur une taxonomie

Affiche les **termes assignés** à chaque post dans la liste des posts du type de contenu lié.

```php
register_cfdev_post_type('book', 'books')
    ->addTaxonomy('genre', [
        'show_admin_column'     => true,
        'admin_column_sortable' => true,
        'admin_column_filter'   => true,
    ]);
```

| Option | Effet |
|---|---|
| `show_admin_column` | Ajoute une colonne avec les termes assignés |
| `admin_column_sortable` | Rend l'en-tête cliquable pour le tri |
| `admin_column_filter` | Ajoute un dropdown de filtrage par terme au-dessus de la liste |

---

## Tableau de support

| Option | Taxonomie | MetaBox | TermMeta | UserMeta |
|---|---|---|---|---|
| `show_admin_column` | ✅ | ✅ | ✅ | ✅ |
| `admin_column_sortable` | ✅ | ✅ | ✅ | ✅ |
| `admin_column_filter` | ✅ | ❌ | ❌ | ❌ |

---

## Rendu automatique selon le type de champ

| Type de champ | Rendu dans la colonne |
|---|---|
| `image` | Miniature 100×100 |
| `text`, `select`, etc. | Valeur texte échappée |
| Champ répétable | Valeurs jointes par `, ` |
| `radios` | Libellé de l'option sélectionnée (MetaBox uniquement) |
