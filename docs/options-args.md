# `options` et `args`

Ces deux clés configurent le comportement d'un champ. Leur rôle est différent : `options` définit les **choix proposés à l'utilisateur**, `args` fournit une **configuration technique** propre à chaque type de champ.

---

## `options`

Liste de choix sous forme `['valeur' => 'Label']`.

Utilisé par : `select`, `multi_select`, `checkboxes`, `radios`.

```php
[
    'id'      => 'color',
    'type'    => 'select',
    'label'   => 'Couleur',
    'options' => [
        'red'   => 'Rouge',
        'green' => 'Vert',
        'blue'  => 'Bleu',
    ],
]
```

La clé (`'red'`) est la valeur sauvegardée en base. Le label (`'Rouge'`) est ce que l'utilisateur voit.

Pour les autres types, `options` est ignoré.

---

## `args`

Tableau de configuration technique. Son contenu **dépend du type de champ** — chaque type le consomme différemment.

### `image`

| Clé | Type | Défaut | Description |
|---|---|---|---|
| `preview_size` | `string\|array` | `'medium'` | Taille de la miniature dans le formulaire. String (`'thumbnail'`, `'medium'`, `'large'`) ou tableau `[width, height]` |

```php
['id' => 'photo', 'type' => 'image', 'args' => ['preview_size' => 'large']]
['id' => 'photo', 'type' => 'image', 'args' => ['preview_size' => [300, 200]]]
```

---

### `date`

| Clé | Type | Défaut | Description |
|---|---|---|---|
| `date_format` | `string` | `'m/d/Y'` | Format PHP d'affichage et de parsing |

```php
['id' => 'birthday', 'type' => 'date', 'args' => ['date_format' => 'd/m/Y']]
```

---

### `time`

| Clé | Type | Défaut | Description |
|---|---|---|---|
| `time_format` | `string` | `'H:i'` | Format PHP de l'heure |

```php
['id' => 'start_time', 'type' => 'time', 'args' => ['time_format' => 'g:i A']]
```

---

### `datetime`

Combine `date_format` et `time_format`.

```php
['id' => 'event_at', 'type' => 'datetime', 'args' => [
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
]]
```

---

### `select` et `multi_select`

| Clé | Type | Défaut | Description |
|---|---|---|---|
| `show_option_none` | `string` | — | Ajoute une option vide en tête de liste |

```php
['id' => 'status', 'type' => 'select', 'options' => [...], 'args' => [
    'show_option_none' => '— Choisir —',
]]
```

---

### `post_select` et `post_checkboxes`

`args` est passé directement à `get_posts()`. Toutes les clés de `WP_Query` sont acceptées.

| Clé fréquente | Défaut | Description |
|---|---|---|
| `post_type` | `'post'` | Type de contenu |
| `posts_per_page` | `-1` | Nombre de posts (-1 = tous) |
| `orderby` | `'date'` | Tri |
| `order` | `'DESC'` | Direction du tri |
| `post_status` | `'publish'` | Statut |
| `show_option_none` | — | Option vide en tête (`post_select` uniquement) |

```php
['id' => 'linked_product', 'type' => 'post_select', 'args' => [
    'post_type'      => 'product',
    'posts_per_page' => 50,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'show_option_none' => '— Aucun —',
]]
```

---

### `term_select`

`args` est passé à `wp_dropdown_categories()`. Toutes ses clés sont acceptées.

| Clé fréquente | Défaut | Description |
|---|---|---|
| `taxonomy` | `'category'` | Taxonomie à lister |
| `hide_empty` | `0` | Masquer les termes sans contenu |
| `orderby` | `'name'` | Tri |
| `show_option_none` | — | Option vide en tête |
| `hierarchical` | `1` | Affichage en arbre |

```php
['id' => 'genre', 'type' => 'term_select', 'args' => [
    'taxonomy'         => 'genre',
    'hide_empty'       => 0,
    'show_option_none' => '— Tous les genres —',
]]
```

---

### `term_checkboxes`

`args` est passé à `get_terms()`.

| Clé fréquente | Défaut | Description |
|---|---|---|
| `taxonomy` | `'category'` | Taxonomie à lister |
| `hide_empty` | — | Masquer les termes vides |
| `orderby` | — | Tri |

```php
['id' => 'tags', 'type' => 'term_checkboxes', 'args' => [
    'taxonomy'   => 'post_tag',
    'hide_empty' => false,
    'orderby'    => 'name',
]]
```

---

### `user_select`

`args` est passé à `wp_dropdown_users()`. Toutes ses clés sont acceptées.

| Clé fréquente | Défaut | Description |
|---|---|---|
| `orderby` | `'ID'` | Tri |
| `role` | — | Filtrer par rôle (`'editor'`, `'author'`, etc.) |
| `show_option_none` | — | Option vide en tête |

```php
['id' => 'author', 'type' => 'user_select', 'args' => [
    'role'             => 'editor',
    'orderby'          => 'display_name',
    'show_option_none' => '— Choisir un auteur —',
]]
```

---

### `wysiwyg`

`args` est passé à `wp_editor()`. Toutes ses clés sont acceptées.

| Clé fréquente | Défaut | Description |
|---|---|---|
| `media_buttons` | `true` | Afficher le bouton "Ajouter un média" |
| `teeny` | `false` | Barre d'outils réduite |
| `quicktags` | `true` | Activer l'onglet HTML |
| `editor_height` | — | Hauteur fixe en pixels |
| `tinymce` | `true` | Activer TinyMCE (passer `false` pour désactiver) |

```php
['id' => 'content', 'type' => 'wysiwyg', 'args' => [
    'media_buttons' => false,
    'teeny'         => true,
    'editor_height' => 200,
]]
```

---

## Résumé

| Type | `options` | `args` |
|---|---|---|
| `select`, `multi_select`, `checkboxes`, `radios` | ✅ choix | `show_option_none` |
| `image` | — | `preview_size` |
| `date` | — | `date_format` |
| `time` | — | `time_format` |
| `datetime` | — | `date_format` + `time_format` |
| `post_select`, `post_checkboxes` | — | args `WP_Query` |
| `term_select`, `term_checkboxes` | — | args `get_terms` / `wp_dropdown_categories` |
| `user_select` | — | args `wp_dropdown_users` |
| `wysiwyg` | — | args `wp_editor` |
| `text`, `textarea`, `checkbox`, etc. | — | ignoré |