# Admin Columns

[← README](../../readme.md) · [Français](../fr/colonnes-admin.md)

Custom columns can be added to the admin list view (posts, terms, users) directly from the field declaration.

---

## On a field (MetaBox, TermMeta, UserMeta)

Add `show_admin_column` and optionally `admin_column_sortable` to the field definition:

```php
['id' => 'price',     'type' => 'number', 'label' => 'Price',
 'show_admin_column' => true, 'admin_column_sortable' => true],

['id' => 'avatar',    'type' => 'image',  'label' => 'Avatar',
 'show_admin_column' => true],
```

### MetaBox — post list (`/wp-admin/edit.php?post_type=…`)

```php
register_cfdev_post_type('product', 'products')
    ->addMetaBox('details', 'Details', [
        [
            'id'                    => 'price',
            'type'                  => 'number',
            'label'                 => 'Price',
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

### TermMeta — term list (`/wp-admin/edit-tags.php?taxonomy=…`)

```php
register_cfdev_taxonomy('genre', 'book')
    ->addTermMeta([
        [
            'id'                => 'color',
            'type'              => 'color',
            'label'             => 'Color',
            'show_admin_column' => true,
        ],
    ]);
```

### UserMeta — user list (`/wp-admin/users.php`)

```php
new \Weblitzer\CFDev\Meta\UserMeta('profile', 'Profile', [
    [
        'id'                    => 'job_title',
        'type'                  => 'text',
        'label'                 => 'Job Title',
        'show_admin_column'     => true,
        'admin_column_sortable' => true,
    ],
]);
```

---

## On a taxonomy

Displays the **assigned terms** for each post in the post type list.

```php
register_cfdev_post_type('book', 'books')
    ->addTaxonomy('genre', [
        'show_admin_column'     => true,
        'admin_column_sortable' => true,
        'admin_column_filter'   => true,
    ]);
```

| Option | Effect |
|---|---|
| `show_admin_column` | Adds a column with the assigned terms |
| `admin_column_sortable` | Makes the column header clickable for sorting |
| `admin_column_filter` | Adds a filter dropdown above the post list |

---

## Support matrix

| Option | Taxonomy | MetaBox | TermMeta | UserMeta |
|---|---|---|---|---|
| `show_admin_column` | ✅ | ✅ | ✅ | ✅ |
| `admin_column_sortable` | ✅ | ✅ | ✅ | ✅ |
| `admin_column_filter` | ✅ | ❌ | ❌ | ❌ |

---

## Column rendering by field type

| Field type | Rendered output |
|---|---|
| `image` | 100×100 thumbnail |
| `text`, `select`, etc. | Escaped text value |
| Repeatable field | Values joined by `, ` |
| `radios` | Label of the selected option (MetaBox only) |
