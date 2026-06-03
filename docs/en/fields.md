# Field Types

[← README](../../readme.md) · [Français](../fr/champs.md)

Every field requires at minimum `id`, `type` and `label`.

```php
['id' => 'my_field', 'type' => 'text', 'label' => 'My Field']
```

**Common keys** available on all types:

| Key | Description |
|---|---|
| `label` | Field label displayed in the admin |
| `description` | Short help text displayed **below the label** (above the input) |
| `explanation` | Longer hint displayed **below the input** (not shown on repeatable fields) |
| `default_value` | Pre-filled value on empty forms |
| `required` | Visual asterisk + server-side `Required` rule |
| `repeatable` | Dynamic multi-value list (add / remove / reorder) |
| `ajax` | Loads editor assets on demand (reduces initial page weight) |
| `show_admin_column` | Adds a column in the admin list view |
| `admin_column_sortable` | Makes that column sortable |
| `css_classes` | Array of CSS classes added to the field wrapper |
| `rules` | Array of validation rule objects |
| `rest` | `true` to expose in the WP REST API and the CFDev API |

---

## MetaBox position

`addMetaBox()` accepts two extra parameters to control where the box appears:

```php
->addMetaBox('id', 'Title', $fields, 'side', 'high')
// context:  'normal' (default) | 'side' | 'advanced'
// priority: 'default'          | 'high' | 'low'
```

`'side'` places the box in the right sidebar. `'high'` makes it appear first in its column.

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
Hidden input (`<input type="hidden">`). The value is saved through the standard save cycle and goes through normal validation. Do not mark `required` without a `default_value`, or validation will silently fail.

**Common use cases:**

```php
// Tag the origin of a record (import, API, manual…)
['id' => 'source', 'type' => 'hidden', 'default_value' => 'import']

// Lock a computed value set at creation time (never overwrite it in the admin)
['id' => 'schema_version', 'type' => 'hidden', 'default_value' => '2']

// Store an external identifier without exposing it in the form
// The value is pre-filled programmatically via an upstream save_post hook
['id' => 'stripe_product_id', 'type' => 'hidden']

// Inside a Bundle: store an internal flag per row with no visible column
['id' => '_row_migrated', 'type' => 'hidden', 'default_value' => '0']
```

Stored as: `string` | repeatable ❌ | ajax ❌ | bundle ✅

---

## Dates

> All three date fields store a **Unix timestamp** (`string`).
> For `date` and `datetime`, `date_format` controls both the **display** in the picker
> and the **parsing** on save — they must be consistent.

### `date`
Date picker (jQuery UI). Stores a Unix timestamp (midnight UTC).

**Minimal — US default (`m/d/Y`)**
```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Event Date']
// displays / parses: 06/15/2024
```

**European format (`d/m/Y`)**
```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Event Date', 'args' => [
    'date_format' => 'd/m/Y',
]]
// displays / parses: 15/06/2024
```

**ISO 8601 (`Y-m-d`)**
```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Event Date', 'args' => [
    'date_format' => 'Y-m-d',
]]
// displays / parses: 2024-06-15
```

**Dot separator (`d.m.Y`)**
```php
['id' => 'event_date', 'type' => 'date', 'label' => 'Event Date', 'args' => [
    'date_format' => 'd.m.Y',
]]
// displays / parses: 15.06.2024
```

| Arg | Default | Description |
|---|---|---|
| `date_format` | `'m/d/Y'` | PHP format — controls both display **and** parsing on save |

**Reading the value:**
```php
$ts = (int) get_post_meta($post_id, 'event_date', true);
echo date('d/m/Y', $ts); // 15/06/2024  (free format on the read side)
```

Stored as: Unix timestamp `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `time`
Time picker. Stores a Unix timestamp.

**24h — default (`H:i`)**
```php
['id' => 'start_time', 'type' => 'time', 'label' => 'Start Time']
// displays / parses: 14:30
```

**24h with seconds (`H:i:s`)**
```php
['id' => 'start_time', 'type' => 'time', 'label' => 'Start Time', 'args' => [
    'time_format' => 'H:i:s',
]]
// displays / parses: 14:30:00
```

**12h AM/PM (`h:i a`)**
```php
['id' => 'start_time', 'type' => 'time', 'label' => 'Start Time', 'args' => [
    'time_format' => 'h:i a',
]]
// displays / parses: 02:30 pm
```

| Arg | Default | Description |
|---|---|---|
| `time_format` | `'H:i'` | PHP format for the picker display and field value |

> **Note:** The `time` field uses `strtotime()` to parse the value on save.
> Standard formats (`H:i`, `H:i:s`, `h:i a`) are all recognized.

**Reading the value:**
```php
$ts = (int) get_post_meta($post_id, 'start_time', true);
echo date('H:i', $ts); // 14:30
```

Stored as: Unix timestamp `string` | repeatable ✅ | ajax ✅ | bundle ✅

---

### `datetime`
Combined date + time picker. Stores a Unix timestamp.

**Minimal — defaults (`m/d/Y H:i`)**
```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Published At']
// displays / parses: 06/15/2024 14:30
```

**European date + 24h time**
```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Published At', 'args' => [
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
]]
// displays / parses: 15/06/2024 14:30
```

**ISO date + time with seconds**
```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Published At', 'args' => [
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i:s',
]]
// displays / parses: 2024-06-15 14:30:00
```

**Override date format only (time keeps its default)**
```php
['id' => 'published_at', 'type' => 'datetime', 'label' => 'Published At', 'args' => [
    'date_format' => 'd/m/Y',
    // time_format → defaults to 'H:i'
]]
// displays / parses: 15/06/2024 14:30
```

| Arg | Default | Description |
|---|---|---|
| `date_format` | `'m/d/Y'` | PHP format for the date portion |
| `time_format` | `'H:i'` | PHP format for the time portion |

> **Note:** If `time_format` does not include seconds (`s`), they are forced to `00` on save.
> Each arg is independent — you can change one without affecting the other.

**Reading the value:**
```php
$ts = (int) get_post_meta($post_id, 'published_at', true);
echo date('d/m/Y H:i', $ts); // 15/06/2024 14:30
```

Stored as: Unix timestamp `string` | repeatable ✅ | ajax ✅ | bundle ✅

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
User dropdown. Stores the user ID. Accepts all `wp_dropdown_users()` args, which are forwarded to `WP_User_Query` — including `role__in` to filter by multiple roles.

```php
// Single role
['id' => 'author', 'type' => 'user_select', 'label' => 'Author', 'args' => [
    'role' => 'editor', 'show_option_none' => '— Choose —',
]]

// Multiple roles (role__in, WP_User_Query)
['id' => 'assignee', 'type' => 'user_select', 'label' => 'Assigned to', 'args' => [
    'role__in' => ['editor', 'author'], 'show_option_none' => '— Choose —',
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
| `hidden` | string | — | ❌ | ❌ | ✅ |
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
