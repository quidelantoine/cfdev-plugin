# Admin UI

[← README](../../readme.md) · [Français](../fr/admin.md)

CFDev adds a **CFDev** menu to the WordPress admin sidebar. All pages require the `manage_options` capability (administrators only).

---

## Pages

| URL | Role |
|---|---|
| `?page=cfdev` | Dashboard — field groups registry + inspector |
| `?page=cfdev-cache` | Cache — toggle, file list, flush |
| `?page=cfdev-rest` | REST API — toggles + exposed fields |

---

## Dashboard

### Organization

Groups are organized in tabs by context:

- **One tab per post type** declared (`Page`, `Post`, `Book`…)
- **Terms** — groups assigned to taxonomies
- **Users** — groups assigned to user profiles

Each group is a collapsible block showing:

| Element | Description |
|---|---|
| Title + ID | Human name and machine identifier |
| Layout badge | `flat` / `tabs` / `accordion` / `bundle` |
| "Also in" | Other post types targeted (if multi-target) |
| Conditions | `ID: 1`, `Template: …`, `Role: editor`… badges |
| Field count | Total flat + bundle fields |
| ⚙ Inspect | Opens the data inspector for this group |
| </> Code | Opens the PHP code snippet for this group |

### Field table

Expanding a group shows a table per section / bundle:

| ID | Type | Label | Validation |
|---|---|---|---|
| `hero_title` | `text` | Hero title | `required` `min-length: 3` |
| `hero_image` | `image` | Image | `required` |

The **Validation** column shows one badge per active rule.

### Duplicate field ID detection

CFDev automatically detects field IDs that appear in more than one group targeting the same post type, taxonomy, or user context.

When duplicates are found:
- A **warning notice** appears at the top of the Dashboard listing all conflicting IDs and the groups they belong to
- Each duplicate field is highlighted with a **⚠** badge in its row

```
⚠ Duplicate field IDs:
  `price`  declared in  product_info, product_pricing
```

> **Note:** Duplicate detection applies to flat fields only. Fields inside bundles are scoped to their bundle ID, so two bundles sharing a field name (`title`, `image`…) on the same post type is safe — they never collide in the database or the cache.

---

## Export field definitions

Two buttons appear below the Dashboard header:

| Button | Output |
|---|---|
| **Export JSON** | `.json` file — machine-readable, suitable for tools, documentation or migrations |
| **Export PHP** | `.php` file — PHP array with `return [...]`, ready to paste into `cfdev-fields.php` |

The filename is timestamped automatically: `cfdev-export-YYYYMMDD-HHmmss.json`.

### JSON structure

```json
{
  "version": "1.0.6",
  "exported_at": "2026-06-03T14:30:00+00:00",
  "groups": [
    {
      "id": "product_info",
      "title": "Product Info",
      "meta_type": "post",
      "targets": ["product"],
      "layout": "flat",
      "fields": [
        { "id": "price", "type": "number", "label": "Price", "required": true, "args": { "min": 0 } },
        { "id": "photo", "type": "image",  "label": "Photo" }
      ]
    }
  ]
}
```

Only non-default properties are included per field (`required`, `repeatable`, `ajax`, `rest`, `args`, `options`, `description`, `default_value`). A field with no extras only shows `id`, `type`, `label`.

Groups with bundles include a `bundles` key:

```json
"bundles": {
  "_specs": [
    { "id": "material", "type": "text", "label": "Material" }
  ]
}
```

### PHP structure

```php
<?php
// CFDev — Field definitions export
// Generated : 2026-06-03 14:30:00

return [
    [
        'id'        => 'product_info',
        'title'     => 'Product Info',
        'meta_type' => 'post',
        'targets'   => ['product'],
        'layout'    => 'flat',
        'fields'    => [
            ['id' => 'price', 'type' => 'number', 'label' => 'Price', 'required' => true],
            ['id' => 'photo', 'type' => 'image',  'label' => 'Photo'],
        ],
    ],
];
```

> The PHP export is a **data snapshot**, not executable `register_cfdev_*` code. Use it to document a project, copy field definitions to another site, or as a starting point for a migration.

---

## The Inspector — developer tool

The **⚙ Inspect** button on each group opens a dark modal showing the live data for a chosen object, straight from the admin.

### What it's for

- Verify a field was saved correctly after editing
- See exactly the PHP structure returned by `CacheManager` (resolved images, bundles, etc.)
- Copy a field access path in one click to use in a template
- Quickly diagnose an empty field, broken gallery, or corrupted bundle
- Check cache state (HIT / GENERATED / OFF) without inspecting `.tmp` files

### Object selection

When the group is not scoped to a fixed object, a `<select>` appears in the modal header.

It is **pre-filtered to match the group's conditions**:

| Declared condition | What appears in the select |
|---|---|
| None | All objects of the post type / taxonomy / users (max 100) |
| `onlyForTemplate('tpl-about.php')` | Only pages with that template |
| `onlyForRoles('editor')` | Only editors |
| `onlyIfParent(5)` (TermMeta) | Only child terms of term #5 |

For groups with `onlyForId(42)`: the select is hidden and post #42's data loads directly.

Changing the selection reloads data instantly.

### Data tree

Data is displayed as an interactive tree (Symfony Profiler style):

```
▼ array(3)
    ⎘  hero_title   ⇒  "Welcome to CFDev"  (17)
    ⎘  hero_image   ⇒  ▶ object(5)
    ⎘  hero_slides  ⇒  ▼ array(2)
                          ⎘  0  ⇒  ▶ object(2)
                          ⎘  1  ⇒  ▶ object(2)
```

- **▶ / ▼** — click the badge to expand/collapse
- **(17)** — string length
- Colors: keys in purple, strings in green, numbers in blue, null in grey

### Copy a path

Each line has a ⎘ button that copies the full PHP access path to the clipboard:

```
⎘ → $group['hero_image']['medium']
⎘ → $group['hero_slides'][0]['slide_title']
```

A global access snippet is shown at the top of the modal:

```php
$data  = (new \Weblitzer\CFDev\Cache\CacheManager())->post(42);
$group = $data['groups']['home_hero'] ?? [];
```

### Cache badge

| Badge | Meaning |
|---|---|
| `CACHE HIT — 3 min ago` | Data served from `.tmp` file |
| `GENERATED` | Data generated live (cache OFF or file missing/expired) |
| `CACHE OFF` | Cache is disabled in settings |

### ↺ Regenerate

Forces data regeneration (equivalent to `force: true` in `CacheManager`). Use after editing to see fresh data without flushing the whole cache.

---

## Cache page

### Toggle

| State | Behavior |
|---|---|
| **On** | Data read from `.tmp` if present and not expired (TTL 24 h). File written after generation. |
| **Off** | Data read directly from the database. No file created or read. |

Recommended: off during development, on in production.

Automatic invalidation runs on `save_post`, `edited_term`, `delete_term`, `profile_update`.

### File table

| Column | Description |
|---|---|
| Object | Title / name / display name + `.tmp` filename |
| Type | Real post type, taxonomy, or `User` |
| Groups | Tags listing the groups present in this cache file |
| Size | JSON file size |
| Age | Time since generation |
| Modified | Last write date and time |
| Action | **Delete** button to invalidate individual files |

Rows older than 24 h show an **Expired** badge.

> The "Groups" column only lists groups **whose conditions match** the object. A standard post won't show a group conditioned to the home page.

---

## REST API page

The REST API page (`?page=cfdev-rest`) provides two on/off controls and a full overview of every field exposed to the REST API.

### Toggles

| Toggle | Behavior when active |
|---|---|
| **Native WP REST** | Registers fields via `register_meta()` so they appear in `/wp-json/wp/v2/` responses as raw values (image ID, JSON-encoded bundle string) |
| **CFDev API** | Enables the `/wp-json/cfdev/v1/` endpoints, which return resolved values (expanded images, decoded bundles) |

Each toggle is a checkbox that auto-submits on change. Both default to **on**.

### Exposed fields — tab view

Fields declared with `'rest' => true` are grouped by context in tabs:

- **One tab per post type** — groups with at least one REST-exposed flat field or bundle
- **Terms** — term meta groups
- **Users** — user meta groups
- **Options** — options pages

The total count badge in the section header shows the aggregate number of REST-exposed fields.

### Group cards

Each group is collapsible. Expanding it reveals a REST table:

| Column | Content |
|---|---|
| Meta key | The field `id` or bundle ID, with a `bundle` badge where applicable |
| Label | Human-readable field label |
| REST type | JSON type — `string`, `number`, `boolean`, or `array` |
| CFDev endpoint | `/wp-json/cfdev/v1/…` — linked if a matching object exists in the database |
| Native endpoint | `/wp-json/wp/v2/…` — linked if a matching object exists |

Layout badges (`flat`, `tabs`, `accordion`) and condition badges (`ID : 42`, `Template : …`, `Role: editor`) appear in the group header, identical to the Dashboard.

### Bundle modal

Bundle rows show a **⊞ View fields** button. Clicking it opens a modal listing all fields inside the bundle with their meta key, label, and REST type. Bundles are exposed as a single JSON object — individual fields inside cannot be selected separately.
