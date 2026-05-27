# Repeatable Fields & AJAX

[← README](../../readme.md) · [Français](../fr/repeatable.md)

`repeatable: true` turns a field into a dynamic list: the user can add as many values as they want, reorder them by drag & drop, and delete them. The value saved to the database is an array.

---

## Usage

```php
->addMetaBox('links', 'Links', [
    [
        'id'         => 'external_urls',
        'type'       => 'text',
        'label'      => 'External URLs',
        'repeatable' => true,
    ],
]);
```

Works the same in **MetaBox**, **TermMeta**, and **UserMeta**.

---

## What the system generates

- An `+ Add` button
- A sortable `<ul>` list (drag & drop)
- Each value in a `<li>` with a drag handle and a delete button
- The HTML `name` becomes `cfdev[field_id][]` (array)

---

## Two conditions required

The system always checks both:

```php
if ($field->repeatable && $field->supports_repeatable)
```

| Property | Set by |
|---|---|
| `repeatable` | You, in the field definition |
| `supports_repeatable` | The plugin, on each field class |

If `repeatable: true` is declared on an unsupported type, the field renders normally with no effect.

---

## Compatible types

| Type | Repeatable |
|---|---|
| `text`, `textarea` | ✅ |
| `number`, `range` | ✅ |
| `email`, `url`, `tel` | ✅ |
| `color`, `date`, `datetime`, `time` | ✅ |
| `select` | ✅ |
| `image` | ✅ |
| `post_select`, `term_select`, `user_select` | ✅ |
| `checkbox`, `checkboxes`, `radios`, `yesno`, `toggle` | ❌ |
| `file` | ❌ |
| `wysiwyg` | ❌ |

---

## Reading values in templates

The returned value is always an array:

```php
$urls = get_post_meta($post_id, 'external_urls', true);
// $urls = ['https://example.com', 'https://other.com']

foreach ((array) $urls as $url) {
    echo '<a href="' . esc_url($url) . '">' . esc_html($url) . '</a>';
}
```

---

## Admin column with repeatable

When `show_admin_column: true` is combined with `repeatable: true`, values are joined by `, ` in the column:

```php
[
    'id'                => 'tags',
    'type'              => 'text',
    'label'             => 'Tags',
    'repeatable'        => true,
    'show_admin_column' => true,
]
// Column: "PHP, WordPress, MySQL"
```

---

## AJAX loading

Fields marked `ajax: true` load their editor assets on demand instead of on page load. This reduces initial page weight on posts with many media fields.

```php
['id' => 'photos', 'type' => 'image', 'label' => 'Photos', 'repeatable' => true, 'ajax' => true]
```

Compatible types are listed in the [Field Types](fields.md) summary table (`ajax` column).
