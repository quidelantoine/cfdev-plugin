quand il configure lerurs champ et meta ?# Types de champs

[← README](../../readme.md) · [English](../en/fields.md)

Chaque champ nécessite au minimum `id`, `type` et `label`.

```php
['id' => 'mon_champ', 'type' => 'text', 'label' => 'Mon champ']
```

**Clés communes** disponibles sur tous les types :

| Clé | Description |
|---|---|
| `label` | Libellé affiché dans l'administration |
| `description` | Texte d'aide court affiché **sous le label** (au-dessus du champ) |
| `explanation` | Aide plus longue affichée **sous le champ** (non affichée sur les champs répétables) |
| `default_value` | Valeur pré-remplie sur les formulaires vides |
| `required` | Astérisque visuel + règle `Required` côté serveur |
| `repeatable` | Liste dynamique multi-valeur (ajout / suppression / réordonnancement) |
| `ajax` | Charge les assets d'édition à la demande (réduit le poids initial de la page) |
| `show_admin_column` | Ajoute une colonne dans la liste admin |
| `admin_column_sortable` | Rend cette colonne triable |
| `css_classes` | Tableau de classes CSS ajoutées au wrapper du champ |
| `rules` | Tableau d'objets règles de validation |
| `rest` | `true` pour exposer dans le REST API WP et l'API CFDev |

---

## Position de la MetaBox

`addMetaBox()` accepte deux paramètres supplémentaires pour contrôler l'emplacement de la boîte :

```php
->addMetaBox('id', 'Titre', $fields, 'side', 'high')
// context:  'normal' (défaut) | 'side' | 'advanced'
// priority: 'default'         | 'high' | 'low'
```

`'side'` place la boîte dans la barre latérale droite. `'high'` la fait apparaître en premier dans sa colonne.

---

## Texte et saisie

### `text`
Champ texte monoligne.

```php
['id' => 'title', 'type' => 'text', 'label' => 'Titre']
```
Stocké comme : `string` | répétable ✅ | ajax ✅ | bundle ✅

---

### `textarea`
Texte multiligne, sans éditeur riche.

```php
['id' => 'summary', 'type' => 'textarea', 'label' => 'Résumé']
```
Stocké comme : `string` | répétable ✅ | ajax ✅ | bundle ✅

---

### `wysiwyg`
Éditeur TinyMCE complet (identique au contenu d'un article WP). Accepte tous les args de `wp_editor()`.

```php
['id' => 'content', 'type' => 'wysiwyg', 'label' => 'Contenu']
['id' => 'excerpt', 'type' => 'wysiwyg', 'label' => 'Extrait', 'args' => [
    'media_buttons' => false, 'teeny' => true, 'editor_height' => 200,
]]
```
Stocké comme : `string` (HTML) | répétable ❌ | ajax ✅ | bundle ✅

---

### `number`
Champ numérique. Accepte entiers et décimaux.

```php
['id' => 'price', 'type' => 'number', 'label' => 'Prix', 'args' => [
    'min' => 0, 'max' => 9999, 'step' => 0.01,
]]
```
`args` : `min`, `max`, `step` (tous optionnels). Stocké comme : `string` numérique | répétable ✅ | ajax ✅ | bundle ✅

---

### `range`
Curseur avec affichage de la valeur en direct.

```php
['id' => 'opacity', 'type' => 'range', 'label' => 'Opacité (%)', 'args' => [
    'min' => 0, 'max' => 100, 'step' => 5,
], 'default_value' => '50']
```
`args` : `min` (0), `max` (100), `step` (1). Stocké comme : `string` numérique | répétable ✅ | ajax ✅ | bundle ✅

---

### `email`
Champ email. Valide automatiquement le format si la valeur est non vide.

```php
['id' => 'contact', 'type' => 'email', 'label' => 'Email', 'required' => true]
```
Stocké comme : `string` (via `sanitize_email()`) | répétable ✅ | ajax ✅ | bundle ✅

---

### `url`
Champ URL. Valide automatiquement le format si la valeur est non vide.

```php
['id' => 'website', 'type' => 'url', 'label' => 'Site web']
```
Stocké comme : `string` (via `esc_url_raw()`) | répétable ✅ | ajax ✅ | bundle ✅

---

### `tel`
Champ numéro de téléphone. Pas de validation de format (les formats varient selon les pays).

```php
['id' => 'phone', 'type' => 'tel', 'label' => 'Téléphone']
```
Stocké comme : `string` | répétable ✅ | ajax ✅ | bundle ✅

---

### `color`
Sélecteur de couleur WordPress. Stocke une valeur hexadécimale.

```php
['id' => 'bg_color', 'type' => 'color', 'label' => 'Arrière-plan', 'default_value' => '#3a86ff']
```
Stocké comme : `string` (ex. `#3a86ff`) | répétable ✅ | ajax ✅ | bundle ✅

---

### `hidden`
Champ caché. Utile pour stocker une valeur fixe ou calculée.

```php
['id' => 'source', 'type' => 'hidden', 'default_value' => 'import']
```
Stocké comme : `string` | répétable ❌ | ajax ❌ | bundle ❌

---

## Dates

### `date`
Sélecteur de date (jQuery UI). Stocke un timestamp Unix.

```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Date', 'args' => ['date_format' => 'd/m/Y']]
```
`args` : `date_format` (format PHP, défaut `'m/d/Y'`). Stocké comme : timestamp Unix `string` | répétable ✅ | bundle ✅

---

### `time`
Sélecteur d'heure. Stocke un timestamp Unix.

```php
['id' => 'start_time', 'type' => 'time', 'label' => 'Heure de début', 'args' => ['time_format' => 'H:i']]
```

---

### `datetime`
Sélecteur de date + heure combiné. Stocke un timestamp Unix.

```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Publié le', 'args' => [
    'date_format' => 'd/m/Y', 'time_format' => 'H:i',
]]
```

---

## Médias

### `image`
Sélecteur d'image via la médiathèque WordPress. Stocke l'ID de l'attachment.

```php
['id' => 'thumbnail', 'type' => 'image', 'label' => 'Miniature']
['id' => 'thumbnail', 'type' => 'image', 'args' => ['preview_size' => 'medium']]
```
Cache retourne : `['id', 'alt', 'full', 'medium', 'thumbnail', …]` | répétable ✅ | bundle ✅

---

### `image_alt`
Image avec texte alternatif personnalisé. Stocke `{"id": int, "alt": string}` en JSON.

```php
['id' => 'hero', 'type' => 'image_alt', 'label' => 'Image hero']
```
Priorité alt : alt personnalisé → alt WP → titre de l'attachment. | bundle ✅

---

### `gallery`
Sélection multiple d'images. Stocke un tableau d'IDs d'attachments.

```php
['id' => 'photos', 'type' => 'gallery', 'label' => 'Galerie photo']
```
Cache retourne : `[['id', 'alt', 'full', 'medium', …], …]` | répétable ❌ | bundle ❌

---

### `file`
Sélecteur de fichier via la médiathèque. Stocke l'ID de l'attachment.

```php
['id' => 'brochure', 'type' => 'file', 'label' => 'Brochure PDF']
```
Cache retourne : `['id', 'url', 'filename']` | ajax ✅ | bundle ✅

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
Stocké comme : `string` (clé de l'option) | répétable ✅ | bundle ✅

---

### `multi_select`
Liste déroulante à choix multiples (Ctrl+clic).

```php
['id' => 'tags', 'type' => 'multi_select', 'label' => 'Tags', 'options' => [
    'php' => 'PHP', 'js' => 'JavaScript',
]]
```
Stocké comme : `array` de clés | bundle ✅

---

### `radios`
Groupe de boutons radio (choix unique).

```php
['id' => 'size', 'type' => 'radios', 'label' => 'Taille', 'options' => [
    's' => 'Petit', 'm' => 'Moyen', 'l' => 'Grand',
]]
```
Stocké comme : `string` (clé de l'option) | bundle ✅

---

### `checkboxes`
Groupe de cases à cocher (choix multiples).

```php
['id' => 'amenities', 'type' => 'checkboxes', 'label' => 'Équipements', 'options' => [
    'wifi' => 'Wi-Fi', 'parking' => 'Parking', 'pool' => 'Piscine',
]]
```
Stocké comme : `array` de clés (ou `'-1'` si aucune) | bundle ✅

---

### `checkbox`
Case à cocher unique. Stocke `'on'` ou `'-1'`.

```php
['id' => 'featured', 'type' => 'checkbox', 'label' => 'En vedette']
['id' => 'active',   'type' => 'checkbox', 'label' => 'Actif', 'default_value' => 'on']
```
Stocké comme : `'on'` ou `'-1'` | ajax ✅ | bundle ✅

---

### `yesno`
Boutons radio Oui / Non.

```php
['id' => 'available', 'type' => 'yesno', 'label' => 'Disponible ?', 'default_value' => 'no']
```
Stocké comme : `'yes'` ou `'no'` | bundle ✅

---

### `toggle`
Interrupteur on/off (CSS). Stocke `'on'` ou `'-1'`.

```php
['id' => 'visible', 'type' => 'toggle', 'label' => 'Visible', 'default_value' => 'on']
```
Stocké comme : `'on'` ou `'-1'` | ajax ✅ | bundle ✅

---

## Relations WordPress

### `post_select`
Liste déroulante de posts. Stocke l'ID du post. Accepte tous les args de `get_posts()`.

```php
['id' => 'related', 'type' => 'post_select', 'label' => 'Article lié', 'args' => [
    'post_type' => 'post', 'orderby' => 'title', 'order' => 'ASC',
    'show_option_none' => '— Aucun —',
]]
```
Stocké comme : `string` (ID du post) | répétable ✅ | bundle ✅

---

### `post_checkboxes`
Liste de cases à cocher de posts (multiples). Stocke un tableau d'IDs.

```php
['id' => 'related_posts', 'type' => 'post_checkboxes', 'label' => 'Articles liés', 'args' => [
    'post_type' => 'product', 'posts_per_page' => 20,
]]
```
Stocké comme : `array` d'IDs (ou `'-1'`) | bundle ✅

---

### `term_select`
Liste déroulante de termes de taxonomie. Stocke l'ID du terme. Accepte tous les args de `wp_dropdown_categories()`.

```php
['id' => 'genre', 'type' => 'term_select', 'label' => 'Genre', 'args' => [
    'taxonomy' => 'genre', 'show_option_none' => '— Tous —',
]]
```
Stocké comme : `string` (ID du terme) | répétable ✅ | bundle ✅

---

### `term_checkboxes`
Liste de cases à cocher de termes. Stocke un tableau d'IDs. Accepte tous les args de `get_terms()`.

```php
['id' => 'categories', 'type' => 'term_checkboxes', 'label' => 'Catégories', 'args' => [
    'taxonomy' => 'category',
]]
```
Stocké comme : `array` d'IDs (ou `'-1'`) | bundle ✅

---

### `user_select`
Liste déroulante d'utilisateurs. Stocke l'ID de l'utilisateur. Accepte tous les args de `wp_dropdown_users()`.

```php
['id' => 'author', 'type' => 'user_select', 'label' => 'Auteur', 'args' => [
    'role' => 'editor', 'show_option_none' => '— Choisir —',
]]
```
Stocké comme : `string` (ID utilisateur) | répétable ✅ | bundle ✅

---

### `user_checkboxes`
Liste de cases à cocher d'utilisateurs. Stocke un tableau d'IDs. Accepte tous les args de `get_users()`.

```php
['id' => 'reviewers', 'type' => 'user_checkboxes', 'label' => 'Relecteurs', 'args' => [
    'role' => 'editor',
]]
```
Stocké comme : `array` d'IDs (ou `'-1'`) | bundle ✅

---

### `link`
Groupe URL + texte + cible. Stocke `['url', 'text', 'target']` en JSON.

```php
['id' => 'cta', 'type' => 'link', 'label' => 'Bouton CTA']
```
Stocké comme : `['url' => string, 'text' => string, 'target' => '_self'|'_blank']` | bundle ✅

---

## Organisation visuelle

### `heading`
Séparateur visuel — aucune donnée sauvegardée. Regroupe des champs dans une MetaBox sans utiliser Tabs/Accordion.

```php
['type' => 'heading', 'label' => 'Dimensions']
['type' => 'heading', 'label' => 'Médias', 'description' => 'Visuels du produit']
```
`id` est optionnel (auto-généré). | bundle ❌

---

## Tableau récapitulatif

| Type | Stocké comme | `options` | `repeatable` | `ajax` | `bundle` |
|---|---|---|---|---|---|
| `text` | string | — | ✅ | ✅ | ✅ |
| `textarea` | string | — | ✅ | ✅ | ✅ |
| `wysiwyg` | string HTML | — | ❌ | ✅ | ✅ |
| `number` | string numérique | — | ✅ | ✅ | ✅ |
| `range` | string numérique | — | ✅ | ✅ | ✅ |
| `email` | string | — | ✅ | ✅ | ✅ |
| `url` | string | — | ✅ | ✅ | ✅ |
| `tel` | string | — | ✅ | ✅ | ✅ |
| `color` | string hex | — | ✅ | ✅ | ✅ |
| `hidden` | string | — | ❌ | ❌ | ❌ |
| `date` / `time` / `datetime` | timestamp | — | ✅ | ✅ | ✅ |
| `image` | ID attachment | — | ✅ | ✅ | ✅ |
| `image_alt` | JSON {id, alt} | — | ❌ | ❌ | ✅ |
| `gallery` | tableau d'IDs | — | ❌ | ❌ | ❌ |
| `file` | ID attachment | — | ❌ | ✅ | ✅ |
| `select` | string | ✅ | ✅ | ✅ | ✅ |
| `multi_select` | array | ✅ | ❌ | ❌ | ✅ |
| `radios` | string | ✅ | ❌ | ❌ | ✅ |
| `checkboxes` | array | ✅ | ❌ | ❌ | ✅ |
| `checkbox` | `'on'`/`'-1'` | — | ❌ | ✅ | ✅ |
| `yesno` | `'yes'`/`'no'` | — | ❌ | ❌ | ✅ |
| `toggle` | `'on'`/`'-1'` | — | ❌ | ✅ | ✅ |
| `post_select` | ID post | — | ✅ | ✅ | ✅ |
| `post_checkboxes` | tableau d'IDs | — | ❌ | ❌ | ✅ |
| `term_select` | ID terme | — | ✅ | ✅ | ✅ |
| `term_checkboxes` | tableau d'IDs | — | ❌ | ❌ | ✅ |
| `user_select` | ID utilisateur | — | ✅ | ✅ | ✅ |
| `user_checkboxes` | tableau d'IDs | — | ❌ | ❌ | ✅ |
| `link` | array url/text/target | — | ❌ | ❌ | ✅ |
| `heading` | aucune | — | ❌ | ❌ | ❌ |
