# Layouts

[← README](../../readme.md) · [Français](../fr/layouts.md)

CFDev supports three layout containers: **Bundle**, **Tabs**, and **Accordion**. These are not field types — they are wrappers that organize fields inside a MetaBox.

---

## Bundle — repeatable rows

A Bundle creates dynamically addable/removable rows. Each row contains the same set of fields.

```php
->addMetaBox('team', 'Team', [
    'bundle',
    [
        ['id' => 'member_name',  'type' => 'text',  'label' => 'Name',  'required' => true],
        ['id' => 'member_photo', 'type' => 'image', 'label' => 'Photo'],
        ['id' => 'member_role',  'type' => 'text',  'label' => 'Role'],
    ],
]);
```

### Bundle ID

Bundle always prefixes its ID with `_` — this becomes both the database meta key and the cache key.

By default the bundle uses the MetaBox ID:

```php
->addMetaBox('team', 'Team', [      // MetaBox ID = 'team'
    'bundle',
    [/* fields */],
]);
// DB meta key and cache key → '_team'
```

You can set an explicit ID:

```php
->addMetaBox('team', 'Team', [
    'bundle',
    'team_members',    // explicit ID
    [/* fields */],
]);
// DB meta key and cache key → '_team_members'
```

### Reading bundle data from cache

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$members = $data['groups']['team']['_team'] ?? [];         // default ID
// $members = $data['groups']['team']['_team_members'] ?? []; // explicit ID

// $members = [
//   ['member_name' => 'Alice', 'member_photo' => ['id'=>1,'full'=>'…'], 'member_role' => 'Dev'],
//   ['member_name' => 'Bob',   'member_photo' => ['id'=>2,'full'=>'…'], 'member_role' => 'Design'],
// ]

foreach ($members as $member) {
    $photo = $member['member_photo'] ?? [];
    echo '<h3>' . esc_html($member['member_name'] ?? '') . '</h3>';
    echo '<img src="' . esc_url($photo['medium'] ?? '') . '" alt="' . esc_attr($photo['alt'] ?? '') . '">';
}
```

---

## Tabs — tabbed sections

Organizes fields into clickable tabs. Each tab can contain flat fields or a bundle.

```php
->addMetaBox('product_tabs', 'Product', [
    'tabs',
    [
        'General' => [
            ['id' => 'price',       'type' => 'number', 'label' => 'Price'],
            ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
        ],
        'Media' => [
            ['id' => 'cover',   'type' => 'image',   'label' => 'Cover'],
            ['id' => 'gallery', 'type' => 'gallery', 'label' => 'Gallery'],
        ],
        'Delivery' => [
d            ['bundle', '_delivery', [
                ['id' => 'country', 'type' => 'text',   'label' => 'Country'],
                ['id' => 'delay',   'type' => 'number', 'label' => 'Days'],
            ]],
        ],
    ],
]);
```

Tab labels are the array keys. Fields are read and saved the same way as flat fields.

---

## Accordion — collapsible sections

Same structure as Tabs, displayed as collapsible sections instead.

```php
->addMetaBox('product_info', 'Product', [
    'accordion',
    [
        'Details' => [
            ['id' => 'weight',     'type' => 'number', 'label' => 'Weight (kg)'],
            ['id' => 'dimensions', 'type' => 'text',   'label' => 'Dimensions'],
        ],
        'Gallery' => [
            ['bundle', '_slides', [
                ['id' => 'slide_title', 'type' => 'text',  'label' => 'Title'],
                ['id' => 'slide_image', 'type' => 'image', 'label' => 'Image'],
            ]],
        ],
    ],
]);
```

---

## Nesting rules

| Container | Can contain |
|---|---|
| MetaBox | flat fields, Bundle, Tabs, Accordion |
| Tabs | flat fields per tab, Bundle per tab |
| Accordion | flat fields per section, Bundle per section |
| Bundle | flat fields only (no nesting) |

---

## Cache structure for bundles inside Tabs/Accordion

Bundle data is accessed the same way regardless of nesting:

```php
$data   = $cache->post(get_the_ID());
$slides = $data['groups']['product_info']['_slides'] ?? [];
```
