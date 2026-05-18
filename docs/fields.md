# Types de champs

Référence complète de tous les types disponibles. Chaque champ se déclare avec au minimum `id`, `type` et `label`.

```php
['id' => 'my_field', 'type' => 'text', 'label' => 'Mon champ']
```

Clés communes disponibles sur tous les types : `description`, `explanation`, `default_value`, `required`, `repeatable`, `ajax`, `show_admin_column`, `admin_column_sortable`, `css_classes`.

---

## Texte

### `text`

Champ texte sur une ligne.

```php
['id' => 'title', 'type' => 'text', 'label' => 'Titre']
```

| | |
|---|---|
| Valeur en base | `string` |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `textarea`

Zone de texte multi-lignes, sans éditeur riche.

```php
['id' => 'summary', 'type' => 'textarea', 'label' => 'Résumé']
```

| | |
|---|---|
| Valeur en base | `string` |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `wysiwyg`

Éditeur TinyMCE complet (même éditeur que le contenu WordPress).

```php
['id' => 'content', 'type' => 'wysiwyg', 'label' => 'Contenu']

// Avec options de l'éditeur
['id' => 'excerpt', 'type' => 'wysiwyg', 'label' => 'Extrait', 'args' => [
    'media_buttons' => false,
    'teeny'         => true,
    'editor_height' => 200,
]]
```

`args` accepte toutes les clés de `wp_editor()` : `media_buttons`, `teeny`, `quicktags`, `tinymce`, `editor_height`.

| | |
|---|---|
| Valeur en base | `string` (HTML) |
| `repeatable` | ❌ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `hidden`

Champ masqué. Utile pour stocker une valeur fixe ou calculée.

```php
['id' => 'source', 'type' => 'hidden', 'default_value' => 'import']
```

| | |
|---|---|
| Valeur en base | `string` |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ❌ |

---

## Choix

### `select`

Liste déroulante à choix unique.

```php
['id' => 'status', 'type' => 'select', 'label' => 'Statut', 'options' => [
    'draft'     => 'Brouillon',
    'published' => 'Publié',
    'archived'  => 'Archivé',
], 'args' => ['show_option_none' => '— Choisir —']]
```

| | |
|---|---|
| Valeur en base | `string` (clé de l'option) |
| `options` | ✅ requis |
| `args` | `show_option_none` |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `multi_select`

Liste déroulante à choix multiples (Ctrl+clic ou Cmd+clic).

```php
['id' => 'tags', 'type' => 'multi_select', 'label' => 'Tags', 'options' => [
    'php' => 'PHP', 'js' => 'JavaScript', 'css' => 'CSS',
]]
```

| | |
|---|---|
| Valeur en base | `array` de clés |
| `options` | ✅ requis |
| `args` | `show_option_none` |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ✅ |

---

### `checkboxes`

Groupe de cases à cocher (choix multiples).

```php
['id' => 'features', 'type' => 'checkboxes', 'label' => 'Fonctionnalités', 'options' => [
    'wifi'    => 'Wi-Fi',
    'parking' => 'Parking',
    'pool'    => 'Piscine',
]]
```

| | |
|---|---|
| Valeur en base | `array` de clés, ou `'-1'` si aucune sélection |
| `options` | ✅ requis |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ✅ |

---

### `radios`

Groupe de boutons radio (choix unique).

```php
['id' => 'gender', 'type' => 'radios', 'label' => 'Genre', 'options' => [
    'm' => 'Homme',
    'f' => 'Femme',
    'x' => 'Non précisé',
]]
```

| | |
|---|---|
| Valeur en base | `string` (clé de l'option) |
| `options` | ✅ requis |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ✅ |

---

### `checkbox`

Case à cocher unique. Sauvegarde `'on'` si cochée, `'-1'` si décochée.

```php
['id' => 'featured', 'type' => 'checkbox', 'label' => 'Mis en avant']

// Coché par défaut
['id' => 'active', 'type' => 'checkbox', 'label' => 'Actif', 'default_value' => 'on']
```

| | |
|---|---|
| Valeur en base | `'on'` ou `'-1'` |
| `repeatable` | ❌ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `yesno`

Deux boutons radio Oui / Non.

```php
['id' => 'available', 'type' => 'yesno', 'label' => 'Disponible ?']

// Non par défaut
['id' => 'available', 'type' => 'yesno', 'default_value' => 'no']
```

| | |
|---|---|
| Valeur en base | `'yes'` ou `'no'` |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ✅ |

---

### `toggle`

Interrupteur on/off (switch CSS). Sauvegarde `'on'` ou `'-1'`.

```php
['id' => 'visible', 'type' => 'toggle', 'label' => 'Visible']

// Activé par défaut
['id' => 'visible', 'type' => 'toggle', 'default_value' => 'on']
```

| | |
|---|---|
| Valeur en base | `'on'` ou `'-1'` |
| `repeatable` | ❌ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

## Date / Heure

### `date`

Sélecteur de date (jQuery UI datepicker). Sauvegarde un timestamp Unix.

```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Date']

// Format personnalisé
['id' => 'event_date', 'type' => 'date', 'label' => 'Date', 'args' => ['date_format' => 'd/m/Y']]
```

`args` : `date_format` (format PHP, défaut `'m/d/Y'`).

| | |
|---|---|
| Valeur en base | `string` (timestamp Unix) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `time`

Sélecteur d'heure. Sauvegarde un timestamp Unix.

```php
['id' => 'start_time', 'type' => 'time', 'label' => 'Heure de début', 'args' => ['time_format' => 'H:i']]
```

`args` : `time_format` (format PHP, défaut `'H:i'`).

| | |
|---|---|
| Valeur en base | `string` (timestamp Unix) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `datetime`

Sélecteur date + heure combinés. Sauvegarde un timestamp Unix.

```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Publié le', 'args' => [
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
]]
```

`args` : `date_format` + `time_format`.

| | |
|---|---|
| Valeur en base | `string` (timestamp Unix) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

## Médias

### `image`

Sélecteur d'image via la médiathèque WordPress. Sauvegarde l'ID de l'attachment.

```php
['id' => 'thumbnail', 'type' => 'image', 'label' => 'Vignette']

// Taille de prévisualisation personnalisée
['id' => 'thumbnail', 'type' => 'image', 'args' => ['preview_size' => 'medium']]
['id' => 'thumbnail', 'type' => 'image', 'args' => ['preview_size' => [300, 200]]]
```

`args` : `preview_size` (string ou `[width, height]`, défaut filtre `cfdev_preview_size`).

Récupération en front-end :
```php
$id = get_post_meta($post_id, 'thumbnail', true);
echo wp_get_attachment_image($id, 'large');
```

| | |
|---|---|
| Valeur en base | `string` (ID attachment) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `file`

Sélecteur de fichier via la médiathèque. Sauvegarde l'URL du fichier.

```php
['id' => 'brochure', 'type' => 'file', 'label' => 'Brochure PDF']
```

| | |
|---|---|
| Valeur en base | `string` (URL) |
| `repeatable` | ❌ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

## Couleur

### `color`

Sélecteur de couleur (WordPress color picker). Sauvegarde une valeur hexadécimale.

```php
['id' => 'bg_color', 'type' => 'color', 'label' => 'Couleur de fond']
['id' => 'bg_color', 'type' => 'color', 'default_value' => '#ff0000']
```

| | |
|---|---|
| Valeur en base | `string` (ex: `'#3a86ff'`) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

## Relations

### `post_select`

Liste déroulante de posts. Sauvegarde l'ID du post sélectionné.

```php
['id' => 'related_post', 'type' => 'post_select', 'label' => 'Article lié', 'args' => [
    'post_type'        => 'post',
    'posts_per_page'   => -1,
    'orderby'          => 'title',
    'order'            => 'ASC',
    'show_option_none' => '— Aucun —',
]]
```

`args` accepte tous les arguments de `get_posts()` / `WP_Query`.

| | |
|---|---|
| Valeur en base | `string` (ID post) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `post_checkboxes`

Cases à cocher de posts (choix multiples). Sauvegarde un tableau d'IDs.

```php
['id' => 'related_posts', 'type' => 'post_checkboxes', 'label' => 'Articles liés', 'args' => [
    'post_type'      => 'product',
    'posts_per_page' => 20,
]]
```

`args` accepte tous les arguments de `get_posts()`.

| | |
|---|---|
| Valeur en base | `array` d'IDs, ou `'-1'` si vide |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ✅ |

---

### `term_select`

Liste déroulante de termes d'une taxonomie. Sauvegarde l'ID du terme.

```php
['id' => 'genre', 'type' => 'term_select', 'label' => 'Genre', 'args' => [
    'taxonomy'         => 'genre',
    'hide_empty'       => 0,
    'show_option_none' => '— Tous —',
]]
```

`args` accepte tous les arguments de `wp_dropdown_categories()`.

| | |
|---|---|
| Valeur en base | `string` (ID terme) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

### `term_checkboxes`

Cases à cocher de termes (choix multiples). Sauvegarde un tableau d'IDs.

```php
['id' => 'categories', 'type' => 'term_checkboxes', 'label' => 'Catégories', 'args' => [
    'taxonomy'   => 'category',
    'hide_empty' => false,
]]
```

`args` accepte tous les arguments de `get_terms()`.

| | |
|---|---|
| Valeur en base | `array` d'IDs, ou `'-1'` si vide |
| `repeatable` | ❌ |
| `ajax` | ❌ |
| `bundle` | ✅ |

---

### `user_select`

Liste déroulante d'utilisateurs. Sauvegarde l'ID de l'utilisateur.

```php
['id' => 'author', 'type' => 'user_select', 'label' => 'Auteur', 'args' => [
    'role'             => 'editor',
    'orderby'          => 'display_name',
    'show_option_none' => '— Choisir —',
]]
```

`args` accepte tous les arguments de `wp_dropdown_users()`.

| | |
|---|---|
| Valeur en base | `string` (ID utilisateur) |
| `repeatable` | ✅ |
| `ajax` | ✅ |
| `bundle` | ✅ |

---

## Layouts

Ces trois types ne sont pas des champs — ce sont des **conteneurs** qui organisent d'autres champs. Ils se déclarent différemment (voir leur doc dédiée).

### `bundle`

Groupe de champs répétables en lignes. Chaque ligne contient les mêmes champs.

```php
['bundle', [
    ['id' => 'name',  'type' => 'text', 'label' => 'Nom'],
    ['id' => 'price', 'type' => 'text', 'label' => 'Prix'],
]]
```

Valeur en base : tableau de lignes `[['name' => '...', 'price' => '...'], ...]`.

---

### `tabs`

Organise les champs en onglets (navigation horizontale).

```php
['tabs', [
    'Général' => [
        ['id' => 'title', 'type' => 'text', 'label' => 'Titre'],
    ],
    'Médias' => [
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ],
]]
```

---

### `accordion`

Organise les champs en sections dépliables (même syntaxe que `tabs`).

```php
['accordion', [
    'Section A' => [
        ['id' => 'field_a', 'type' => 'text', 'label' => 'Champ A'],
    ],
    'Section B' => [
        ['id' => 'field_b', 'type' => 'text', 'label' => 'Champ B'],
    ],
]]
```

---

## Tableau récapitulatif

| Type | Valeur en base | `options` | `repeatable` | `ajax` | `bundle` |
|---|---|---|---|---|---|
| `text` | string | — | ✅ | ✅ | ✅ |
| `textarea` | string | — | ✅ | ✅ | ✅ |
| `wysiwyg` | string (HTML) | — | ❌ | ✅ | ✅ |
| `hidden` | string | — | ❌ | ❌ | ❌ |
| `select` | string | ✅ | ✅ | ✅ | ✅ |
| `multi_select` | array | ✅ | ❌ | ❌ | ✅ |
| `checkboxes` | array | ✅ | ❌ | ❌ | ✅ |
| `radios` | string | ✅ | ❌ | ❌ | ✅ |
| `checkbox` | `'on'`/`'-1'` | — | ❌ | ✅ | ✅ |
| `yesno` | `'yes'`/`'no'` | — | ❌ | ❌ | ✅ |
| `toggle` | `'on'`/`'-1'` | — | ❌ | ✅ | ✅ |
| `date` | timestamp | — | ✅ | ✅ | ✅ |
| `time` | timestamp | — | ✅ | ✅ | ✅ |
| `datetime` | timestamp | — | ✅ | ✅ | ✅ |
| `image` | ID attachment | — | ✅ | ✅ | ✅ |
| `file` | URL | — | ❌ | ✅ | ✅ |
| `color` | hex string | — | ✅ | ✅ | ✅ |
| `post_select` | ID post | — | ✅ | ✅ | ✅ |
| `post_checkboxes` | array d'IDs | — | ❌ | ❌ | ✅ |
| `term_select` | ID terme | — | ✅ | ✅ | ✅ |
| `term_checkboxes` | array d'IDs | — | ❌ | ❌ | ✅ |
| `user_select` | ID user | — | ✅ | ✅ | ✅ |