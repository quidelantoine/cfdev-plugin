# Field Types

[← README](../../readme.md) · [Français](../fr/champs.md)

Every field requires at minimum `id`, `type` and `label`.

```php
['id' => 'my_field', 'type' => 'text', 'label' => 'My Field']
```

**Common keys** available on all types: `description`, `explanation`, `default_value`, `required`, `repeatable`, `ajax`, `show_admin_column`, `admin_column_sortable`, `css_classes`, `rules`.

---

## Text & Input

### `text`
Single-line text field.

```php
['id' => 'title', 'type' => 'text', 'label' => 'Title']
```
Stored as: `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `textarea`
Multi-line text, no rich editor.

```php
['id' => 'summary', 'type' => 'textarea', 'label' => 'Summary']
```
Stored as: `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `wysiwyg`
Full TinyMCE editor (same as WP post content). Accepts all `wp_editor()` args.

```php
['id' => 'content', 'type' => 'wysiwyg', 'label' => 'Content']
['id' => 'excerpt', 'type' => 'wysiwyg', 'label' => 'Excerpt', 'args' => [
    'media_buttons' => false, 'teeny' => true, 'editor_height' => 200,
]]
```
Stored as: `string` (HTML) | repeatable ❌ | ajax ✅ | bundle ✅

---

### `number`
Numeric field. Accepts integers and decimals.

```php
['id' => 'price', 'type' => 'number', 'label' => 'Price', 'args' => [
    'min' => 0, 'max' => 9999, 'step' => 0.01,
]]
```
`args`: `min`, `max`, `step` (all optional). Stored as: numeric `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `range`
Slider with live value display.

```php
['id' => 'opacity', 'type' => 'range', 'label' => 'Opacity (%)', 'args' => [
    'min' => 0, 'max' => 100, 'step' => 5,
], 'default_value' => '50']
```
`args`: `min` (0), `max` (100), `step` (1). Stored as: numeric `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `email`
Email field. Auto-validates format if value is non-empty.

```php
['id' => 'contact', 'type' => 'email', 'label' => 'Email', 'required' => true]
```
Stored as: `string` (sanitized via `sanitize_email()`) | repeatable ✅ | ajax ✅ | bundle ✅

---

### `url`
URL field. Auto-validates format if value is non-empty.

```php
['id' => 'website', 'type' => 'url', 'label' => 'Website']
```
Stored as: `string` (sanitized via `esc_url_raw()`) | repeatable ✅ | ajax ✅ | bundle ✅

---

### `tel`
Phone number field. No format validation (formats vary by country).

```php
['id' => 'phone', 'type' => 'tel', 'label' => 'Phone']
```
Stored as: `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `color`
WordPress color picker. Stores a hex value.

```php
['id' => 'bg_color', 'type' => 'color', 'label' => 'Background', 'default_value' => '#3a86ff']
```
Stored as: `string` (e.g. `#3a86ff`) | repeatable ✅ | ajax ✅ | bundle ✅

---

### `hidden`
Hidden input. Useful for storing a fixed or computed value.

```php
['id' => 'source', 'type' => 'hidden', 'default_value' => 'import']
```
Stored as: `string` | repeatable ❌ | ajax ❌ | bundle ❌

---

## Dates

### `date`
Date picker (jQuery UI). Stores a Unix timestamp.

```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Date', 'args' => ['date_format' => 'd/m/Y']]
```
`args`: `date_format` (PHP format, default `'m/d/Y'`). Stored as: Unix timestamp `string` | repeatable ✅ | bundle ✅

---

### `time`
Time picker. Stores a Unix timestamp.

```php
['id' => 'start_time', 'type' => 'time', 'label' => 'Start Time', 'args' => ['time_format' => 'H:i']]
```

---

### `datetime`
Combined date + time picker. Stores a Unix timestamp.

```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Published At', 'args' => [
    'date_format' => 'd/m/Y', 'time_format' => 'H:i',
]]
```

---

## Media

### `image`
Image picker via the WordPress media library. Stores the attachment ID.

```php
['id' => 'thumbnail', 'type' => 'image', 'label' => 'Thumbnail']
['id' => 'thumbnail', 'type' => 'image', 'args' => ['preview_size' => 'medium']]
```
Cache returns: `['id', 'alt', 'full', 'medium', 'thumbnail', …]` | repeatable ✅ | bundle ✅

---

### `image_alt`
Image with custom alt text. Stores `{"id": int, "alt": string}` as JSON.

```php
['id' => 'hero', 'type' => 'image_alt', 'label' => 'Hero Image']
```
Cache alt priority: custom alt → WP attachment alt → attachment title. | bundle ✅

---

### `gallery`
Multiple image selection. Stores an array of attachment IDs.

```php
['id' => 'photos', 'type' => 'gallery', 'label' => 'Photo Gallery']
```
Cache returns: `[['id', 'alt', 'full', 'medium', …], …]` | repeatable ❌ | bundle ❌

---

### `file`
File picker via the media library. Stores the attachment ID.

```php
['id' => 'brochure', 'type' => 'file', 'label' => 'Brochure PDF']
```
Cache returns: `['id', 'url', 'filename']` | ajax ✅ | bundle ✅

---

## Choice

### `select`
Single-choice dropdown.

```php
['id' => 'status', 'type' => 'select', 'label' => 'Status', 'options' => [
    'draft'     => 'Draft',
    'published' => 'Published',
    'archived'  => 'Archived',
], 'args' => ['show_option_none' => '— Choose —']]
```
Stored as: `string` (option key) | repeatable ✅ | bundle ✅

---

### `multi_select`
Multi-choice dropdown (Ctrl+click).

```php
['id' => 'tags', 'type' => 'multi_select', 'label' => 'Tags', 'options' => [
    'php' => 'PHP', 'js' => 'JavaScript',
]]
```
Stored as: `array` of keys | bundle ✅

---

### `radios`
Radio button group (single choice).

```php
['id' => 'size', 'type' => 'radios', 'label' => 'Size', 'options' => [
    's' => 'Small', 'm' => 'Medium', 'l' => 'Large',
]]
```
Stored as: `string` (option key) | bundle ✅

---

### `checkboxes`
Checkbox group (multiple choice).

```php
['id' => 'amenities', 'type' => 'checkboxes', 'label' => 'Amenities', 'options' => [
    'wifi' => 'Wi-Fi', 'parking' => 'Parking', 'pool' => 'Pool',
]]
```
Stored as: `array` of keys (or `'-1'` if none) | bundle ✅

---

### `checkbox`
Single checkbox. Stores `'on'` or `'-1'`.

```php
['id' => 'featured', 'type' => 'checkbox', 'label' => 'Featured']
['id' => 'active',   'type' => 'checkbox', 'label' => 'Active', 'default_value' => 'on']
```
Stored as: `'on'` or `'-1'` | ajax ✅ | bundle ✅

---

### `yesno`
Yes / No radio buttons.

```php
['id' => 'available', 'type' => 'yesno', 'label' => 'Available?', 'default_value' => 'no']
```
Stored as: `'yes'` or `'no'` | bundle ✅

---

### `toggle`
On/off switch (CSS). Stores `'on'` or `'-1'`.

```php
['id' => 'visible', 'type' => 'toggle', 'label' => 'Visible', 'default_value' => 'on']
```
Stored as: `'on'` or `'-1'` | ajax ✅ | bundle ✅

---

## WordPress Relations

### `post_select`
Post dropdown. Stores the post ID. Accepts all `get_posts()` args.

```php
['id' => 'related', 'type' => 'post_select', 'label' => 'Related Post', 'args' => [
    'post_type' => 'post', 'orderby' => 'title', 'order' => 'ASC',
    'show_option_none' => '— None —',
]]
```
Stored as: `string` (post ID) | repeatable ✅ | bundle ✅

---

### `post_checkboxes`
Post checkbox list (multiple). Stores an array of IDs. Accepts all `get_posts()` args.

```php
['id' => 'related_posts', 'type' => 'post_checkboxes', 'label' => 'Related Posts', 'args' => [
    'post_type' => 'product', 'posts_per_page' => 20,
]]
```
Stored as: `array` of IDs (or `'-1'`) | bundle ✅

---

### `term_select`
Taxonomy term dropdown. Stores the term ID. Accepts all `wp_dropdown_categories()` args.

```php
['id' => 'genre', 'type' => 'term_select', 'label' => 'Genre', 'args' => [
    'taxonomy' => 'genre', 'show_option_none' => '— All —',
]]
```
Stored as: `string` (term ID) | repeatable ✅ | bundle ✅

---

### `term_checkboxes`
Term checkbox list. Stores an array of term IDs. Accepts all `get_terms()` args.

```php
['id' => 'categories', 'type' => 'term_checkboxes', 'label' => 'Categories', 'args' => [
    'taxonomy' => 'category',
]]
```
Stored as: `array` of IDs (or `'-1'`) | bundle ✅

---

### `user_select`
User dropdown. Stores the user ID. Accepts all `wp_dropdown_users()` args.

```php
['id' => 'author', 'type' => 'user_select', 'label' => 'Author', 'args' => [
    'role' => 'editor', 'show_option_none' => '— Choose —',
]]
```
Stored as: `string` (user ID) | repeatable ✅ | bundle ✅

---

### `user_checkboxes`
User checkbox list. Stores an array of user IDs. Accepts all `get_users()` args.

```php
['id' => 'reviewers', 'type' => 'user_checkboxes', 'label' => 'Reviewers', 'args' => [
    'role' => 'editor',
]]
```
Stored as: `array` of IDs (or `'-1'`) | bundle ✅

---

### `link`
URL + text + target group. Stores `['url', 'text', 'target']` as JSON.

```php
['id' => 'cta', 'type' => 'link', 'label' => 'CTA Button']
```
Stored as: `['url' => string, 'text' => string, 'target' => '_self'|'_blank']` | bundle ✅

---

## Visual Organization

### `heading`
Visual separator — no data saved. Groups fields inside a MetaBox without using Tabs/Accordion.

```php
['type' => 'heading', 'label' => 'Dimensions']
['type' => 'heading', 'label' => 'Media', 'description' => 'Product visuals']
```
`id` is optional (auto-generated). | bundle ❌

---

## Summary Table

| Type | Stored as | `options` | `repeatable` | `ajax` | `bundle` |
|---|---|---|---|---|---|
| `text` | string | — | ✅ | ✅ | ✅ |
| `textarea` | string | — | ✅ | ✅ | ✅ |
| `wysiwyg` | string HTML | — | ❌ | ✅ | ✅ |
| `number` | numeric string | — | ✅ | ✅ | ✅ |
| `range` | numeric string | — | ✅ | ✅ | ✅ |
| `email` | string | — | ✅ | ✅ | ✅ |
| `url` | string | — | ✅ | ✅ | ✅ |
| `tel` | string | — | ✅ | ✅ | ✅ |
| `color` | hex string | — | ✅ | ✅ | ✅ |
| `hidden` | string | — | ❌ | ❌ | ❌ |
| `date` / `time` / `datetime` | timestamp | — | ✅ | ✅ | ✅ |
| `image` | attachment ID | — | ✅ | ✅ | ✅ |
| `image_alt` | JSON {id, alt} | — | ❌ | ❌ | ✅ |
| `gallery` | array of IDs | — | ❌ | ❌ | ❌ |
| `file` | attachment ID | — | ❌ | ✅ | ✅ |
| `select` | string | ✅ | ✅ | ✅ | ✅ |
| `multi_select` | array | ✅ | ❌ | ❌ | ✅ |
| `radios` | string | ✅ | ❌ | ❌ | ✅ |
| `checkboxes` | array | ✅ | ❌ | ❌ | ✅ |
| `checkbox` | `'on'`/`'-1'` | — | ❌ | ✅ | ✅ |
| `yesno` | `'yes'`/`'no'` | — | ❌ | ❌ | ✅ |
| `toggle` | `'on'`/`'-1'` | — | ❌ | ✅ | ✅ |
| `post_select` | post ID | — | ✅ | ✅ | ✅ |
| `post_checkboxes` | array of IDs | — | ❌ | ❌ | ✅ |
| `term_select` | term ID | — | ✅ | ✅ | ✅ |
| `term_checkboxes` | array of IDs | — | ❌ | ❌ | ✅ |
| `user_select` | user ID | — | ✅ | ✅ | ✅ |
| `user_checkboxes` | array of IDs | — | ❌ | ❌ | ✅ |
| `link` | array url/text/target | — | ❌ | ❌ | ✅ |
| `heading` | none | — | ❌ | ❌ | ❌ |
