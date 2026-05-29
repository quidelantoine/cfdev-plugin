# Quick Start

[← README](../../readme.md) · [Français](../fr/demarrage-rapide.md)

---

## 0. Create `cfdev-fields.php`

All the code below goes in **`your-theme/cfdev-fields.php`** — CFDev loads it automatically.

```
your-theme/
└── cfdev-fields.php   ← create this file, CFDev does the rest
```

Every declaration must be wrapped in an `init` action:

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    // → your declarations here
});
```

> For larger projects, see [how to split into multiple files](installation.md#splitting-into-multiple-files).

---

## 1. Register a post type with fields

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {

    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Product Info', [
            ['id' => 'price',    'type' => 'number', 'label' => 'Price',        'required' => true],
            ['id' => 'photo',    'type' => 'image',  'label' => 'Photo'],
            ['id' => 'gallery',  'type' => 'gallery','label' => 'Gallery'],
            ['id' => 'brochure', 'type' => 'file',   'label' => 'Brochure PDF'],
            ['id' => 'cta',      'type' => 'link',   'label' => 'CTA Button'],
        ]);

});
```

## 2. Add a taxonomy

```php
register_cfdev_post_type(['product', 'products'])
    ->addTaxonomy(['category', 'categories'])
    ->addMetaBox('product_info', 'Product Info', $fields);
```

## 3. Read data in a template

### Without cache (direct meta)

```php
$price   = get_post_meta(get_the_ID(), 'price', true);
$imageId = get_post_meta(get_the_ID(), 'photo', true);

echo esc_html($price);
echo wp_get_attachment_image($imageId, 'medium');
```

### With cache (recommended — resolved, no extra queries)

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

// Scalar field
echo esc_html($product['price'] ?? '');

// Image (all sizes resolved)
$photo = $product['photo'] ?? [];
echo '<img src="' . esc_url($photo['medium'] ?? '') . '" alt="' . esc_attr($photo['alt'] ?? '') . '">';

// Gallery
foreach ($product['gallery'] ?? [] as $img) {
    echo '<img src="' . esc_url($img['medium']) . '" alt="' . esc_attr($img['alt']) . '">';
}

// File
$pdf = $product['brochure'] ?? [];
echo '<a href="' . esc_url($pdf['url'] ?? '') . '">' . esc_html($pdf['filename'] ?? '') . '</a>';

// Link
$cta = $product['cta'] ?? [];
if (!empty($cta['url'])) {
    echo '<a href="' . esc_url($cta['url']) . '" target="' . esc_attr($cta['target'] ?? '_self') . '">'
        . esc_html($cta['text'] ?: $cta['url']) . '</a>';
}
```

## 4. Fields with options and validation

Every field key in action — `description`, `explanation`, `default_value`, `required`, `show_admin_column`, `rules`, and more:

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;
use Weblitzer\CFDev\Validation\Rules\FileExtension;
use Weblitzer\CFDev\Validation\Rules\Email;
use Weblitzer\CFDev\Validation\Rules\Url;
use Weblitzer\CFDev\Validation\Rules\Regex;
use Weblitzer\CFDev\Validation\Rules\DateAfterToday;

add_action('init', static function (): void {

    $product = register_cfdev_post_type(['product', 'products'], ['public' => true]);

    $product->addMetaBox('product_info', 'Product Info', [

        ['id' => 'title',
            'type'              => 'text',
            'label'             => 'Title',
            'description'       => 'Displayed in the product card header',
            'explanation'       => 'Keep under 60 characters for SEO',
            'required'          => true,
            'show_admin_column' => true,
            'admin_column_sortable' => true,
            'rules'             => [new MinLength(3), new MaxLength(60)]],

        ['id' => 'price',
            'type'          => 'number',
            'label'         => 'Price (€)',
            'description'   => 'Tax-inclusive price',
            'default_value' => '0',
            'required'      => true,
            'show_admin_column' => true,
            'admin_column_sortable' => true,
            'args'          => ['min' => 0, 'step' => 0.01],
            'rules'         => [new Min(0), new Max(99999)]],

        ['id' => 'contact_email',
            'type'        => 'email',
            'label'       => 'Contact Email',
            'description' => 'Displayed in the enquiry form',
            'required'    => true,
            'rules'       => [new Email()]],

        ['id' => 'website',
            'type'        => 'url',
            'label'       => 'Website',
            'explanation' => 'Must start with https://',
            'rules'       => [new Url()]],

        ['id' => 'phone',
            'type'  => 'tel',
            'label' => 'Phone',
            'rules' => [new Regex('/^\+?[\d\s\-\.]{7,15}$/')]],

        ['id' => 'launch_date',
            'type'        => 'date',
            'label'       => 'Launch Date',
            'description' => 'Must be in the future',
            'required'    => true,
            'args'        => ['date_format' => 'd/m/Y'],
            'rules'       => [new DateAfterToday()]],

        ['id' => 'cover',
            'type'          => 'image',
            'label'         => 'Cover Image',
            'description'   => 'Minimum 1200 × 630 px for social sharing',
            'show_admin_column' => true,
            'rules'         => [new Required(), new ImageMinDimensions(1200, 630)]],

        ['id' => 'brochure',
            'type'        => 'file',
            'label'       => 'Brochure PDF',
            'explanation' => 'PDF only, max 10 MB',
            'rules'       => [new FileExtension(['pdf'])]],

        ['id' => 'status',
            'type'          => 'select',
            'label'         => 'Status',
            'description'   => 'Controls front-end visibility',
            'default_value' => 'draft',
            'show_admin_column' => true,
            'options'       => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'],
            'args'          => ['show_option_none' => '— Choose —'],
            'required'      => true],

    ]);

});
```

Validation errors survive the POST → redirect cycle and appear **inline in the edit form** — no data is lost.

## 5. Add term meta and user meta

```php
// Term meta — appears on both the Add and Edit term forms (default)
register_cfdev_taxonomy('genre', 'product')
    ->addTermMeta([
        ['id' => 'color', 'type' => 'color', 'label' => 'Color'],
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ]);

// Term meta — Edit form only (use TermMeta directly, default is ['edit_form'])
// Add form only:  ['add_form']
// Both forms:     ['add_form', 'edit_form']
new \Weblitzer\CFDev\Meta\TermMeta('genre', 'Genre Info', $fields, ['add_form', 'edit_form']);

// Term meta — restricted to child terms of a given parent
register_cfdev_taxonomy('genre', 'product')
    ->addTermMeta([/* fields */])
    ->onlyIfParent(12);

// User meta — visible to all users (default: show_user_profile + edit_user_profile)
register_cfdev_user_meta('profile', 'Profile', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
])->onlyForRole('administrator');

// User meta — custom locations and display order
register_cfdev_user_meta(
    'social',
    'Social Links',
    $fields,
    ['show_user_profile'],   // only on own profile page, not on "Edit user"
    20                       // priority — controls order when multiple sections exist
);
```

---

## 6. Real-world example — CPT with taxonomy and meta box

A complete registration without method chaining, useful when you need to keep a reference to each object:

```php
add_action('init', static function (): void {

    $lessons = register_cfdev_post_type('lessons', [
        'public'       => true,
        'menu_icon'    => 'dashicons-welcome-learn-more',
        'has_archive'  => true,
        'supports'     => ['title', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ], [
        'name'          => 'Lessons',
        'singular_name' => 'Lesson',
    ]);

    $lessons->addMetaBox('lesson_details', 'Lesson Details', [
        ['id' => 'duration',   'type' => 'number', 'label' => 'Duration (min)', 'required' => true],
        ['id' => 'level',      'type' => 'select', 'label' => 'Level',
            'options' => ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'],
            'args'    => ['show_option_none' => '— Choose —']],
        ['id' => 'video',      'type' => 'url',    'label' => 'Video URL'],
        ['id' => 'attachment', 'type' => 'file',   'label' => 'PDF Resource'],
    ]);

    // Taxonomy registered independently — allows reuse across post types
    register_cfdev_taxonomy('courses', 'lessons', [
        'show_admin_column'    => true,
        'admin_column_filter'  => true,
    ])
    ->addTermMeta([
        ['id' => 'color',       'type' => 'color',    'label' => 'Color'],
        ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
    ]);

});
```

---

## 7. Layouts

> **Important — unique IDs per post type:** every `addMetaBox()` call on the same post type must use a **different ID**. Two calls with the same ID cause the second to silently overwrite the first (native WordPress behaviour). All examples below use distinct IDs.

### Bundle — repeatable rows of fields

A Bundle groups multiple fields into repeatable rows. Perfect for team members, sessions, pricing tiers, etc.

```php
$lessons->addMetaBox('team', 'Team Members', [
    'bundle',
    '_members',
    [
        ['id' => 'name',  'type' => 'text',     'label' => 'Name',  'required' => true],
        ['id' => 'role',  'type' => 'text',     'label' => 'Role'],
        ['id' => 'photo', 'type' => 'image',    'label' => 'Photo'],
        ['id' => 'bio',   'type' => 'textarea', 'label' => 'Bio'],
    ],
]);
```

Read bundle data:
```php
$data    = (new \Weblitzer\CFDev\Cache\CacheManager())->post(get_the_ID());
$members = $data['groups']['team']['_members'] ?? [];

foreach ($members as $member) {
    echo '<h3>' . esc_html($member['name']) . '</h3>';
    echo '<p>'  . esc_html($member['role']) . '</p>';
    echo wp_get_attachment_image($member['photo'], 'thumbnail');
}
```

---

### Tabs — fields organized in tabs

Tab labels are the array keys. Each tab contains a flat list of fields.

```php
$product->addMetaBox('product_tabs', 'Product', [
    'tabs',
    [
        'General' => [
            ['id' => 'price',       'type' => 'number',  'label' => 'Price'],
            ['id' => 'stock',       'type' => 'number',  'label' => 'Stock'],
            ['id' => 'description', 'type' => 'wysiwyg', 'label' => 'Description'],
        ],
        'Media' => [
            ['id' => 'photo',   'type' => 'image',   'label' => 'Main Photo'],
            ['id' => 'gallery', 'type' => 'gallery', 'label' => 'Gallery'],
        ],
        'SEO' => [
            ['id' => 'seo_title', 'type' => 'text',     'label' => 'SEO Title'],
            ['id' => 'seo_desc',  'type' => 'textarea', 'label' => 'Meta Description'],
        ],
    ],
]);
```

---

### Accordion — fields organized in collapsible sections

Same structure as Tabs, displayed as collapsible sections.

```php
$page->addMetaBox('faq', 'FAQ', [
    'accordion',
    [
        'Shipping' => [
            ['id' => 'shipping_delay', 'type' => 'text', 'label' => 'Delivery time'],
            ['id' => 'shipping_price', 'type' => 'text', 'label' => 'Shipping cost'],
        ],
        'Returns' => [
            ['id' => 'return_policy', 'type' => 'wysiwyg', 'label' => 'Return policy'],
        ],
    ],
]);
```

---

### Bundle inside a Tab

One tab contains flat fields, another contains a repeatable bundle.

```php
$formation->addMetaBox('formation', 'Formation', [
    'tabs',
    [
        'Info' => [
            ['id' => 'intro',    'type' => 'wysiwyg', 'label' => 'Introduction'],
            ['id' => 'duration', 'type' => 'number',  'label' => 'Total duration (h)'],
        ],
        'Sessions' => [
            ['bundle', '_sessions', [
                ['id' => 'title',    'type' => 'text',   'label' => 'Session title', 'required' => true],
                ['id' => 'date',     'type' => 'date',   'label' => 'Date'],
                ['id' => 'seats',    'type' => 'number', 'label' => 'Available seats'],
                ['id' => 'location', 'type' => 'text',   'label' => 'Location'],
            ]],
        ],
    ],
]);
```

---

### Bundle inside an Accordion section

One section contains flat fields, another contains a repeatable bundle.

```php
$product->addMetaBox('specs', 'Technical Specs', [
    'accordion',
    [
        'Dimensions' => [
            ['id' => 'weight', 'type' => 'number', 'label' => 'Weight (kg)'],
            ['id' => 'width',  'type' => 'number', 'label' => 'Width (cm)'],
            ['id' => 'height', 'type' => 'number', 'label' => 'Height (cm)'],
        ],
        'Components' => [
            ['bundle', '_components', [
                ['id' => 'name',     'type' => 'text',   'label' => 'Component'],
                ['id' => 'ref',      'type' => 'text',   'label' => 'Reference'],
                ['id' => 'quantity', 'type' => 'number', 'label' => 'Qty'],
            ]],
        ],
    ],
]);
```

---

## Next

→ [Field Types](fields.md) · [Layouts](layouts.md) · [Validation](validation.md) · [Cache](cache.md)
