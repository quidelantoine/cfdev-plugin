# CFDev – Code-First Custom Fields for WordPress

> Declare your custom fields in PHP arrays. No admin UI. No clicks. Just code.

---

## Why CFDev?

[ACF](https://www.advancedcustomfields.com/) is great — but managing field groups through the WordPress admin is a pain in real dev workflows :

- Fields live in the database, not in your repo
- No version control, no code review
- Switching environments (local → staging → prod) breaks everything

**CFDev** solves this by letting you declare your field groups directly in PHP, just like you register post types or taxonomies. Your fields live in your codebase, travel with your Git repo, and never need a database export.

---

## Requirements

- PHP `>= 8.0`
- WordPress `>= 6.0`

---

## Installation

### Via Composer

```bash
composer require yourvendor/cfdev
```

### Manuel

1. Télécharger le dépôt
2. Placer le dossier dans `/wp-content/plugins/cfdev`
3. Activer le plugin dans l'admin WordPress

---

## Quick Start

Déclare un groupe de champs dans ton `functions.php` ou dans ton plugin :

```php
add_action('cfdev/register_fields', function() {

    CFDev::register([
        'id'         => 'movie_details',
        'title'      => 'Movie Details',
        'post_types' => ['movie'],
        'fields'     => [
            [
                'id'    => 'release_year',
                'label' => 'Release Year',
                'type'  => 'number',
            ],
            [
                'id'      => 'genre',
                'label'   => 'Genre',
                'type'    => 'select',
                'choices' => [
                    'action'  => 'Action',
                    'drama'   => 'Drama',
                    'comedy'  => 'Comedy',
                ],
            ],
            [
                'id'    => 'synopsis',
                'label' => 'Synopsis',
                'type'  => 'textarea',
            ],
        ],
    ]);

});
```

---

## Getting Values

CFDev uses native WordPress meta functions under the hood, so retrieval is familiar :

```php
// Simple value
$year = cfdev_get('release_year', get_the_ID());

// Or use the native WP function directly
$year = get_post_meta(get_the_ID(), 'release_year', true);
```

---

## Field Types

| Type         | Description                        |
|--------------|------------------------------------|
| `text`       | Single line text input             |
| `textarea`   | Multi-line text input              |
| `number`     | Numeric input                      |
| `email`      | Email input with validation        |
| `url`        | URL input with validation          |
| `select`     | Dropdown select                    |
| `checkbox`   | Single checkbox (boolean)          |
| `radio`      | Radio button group                 |
| `image`      | WordPress media library (image)    |
| `file`       | WordPress media library (any file) |
| `date`       | Date picker                        |
| `wysiwyg`    | WordPress TinyMCE editor           |
| `repeater`   | Repeatable group of fields         |

---

## Field Options

Each field accepts the following keys :

```php
[
    'id'          => 'field_key',       // required — used as meta_key
    'label'       => 'Field Label',     // required — shown in admin
    'type'        => 'text',            // required — see field types above
    'description' => 'Helper text',     // optional
    'required'    => true,              // optional, default: false
    'default'     => 'Default value',   // optional
    'placeholder' => 'Placeholder',     // optional (text, textarea, number...)
    'choices'     => [],                // required for select, radio
    'conditions'  => [],                // optional — conditional logic
]
```

---

## Conditional Logic

Show or hide a field based on another field's value :

```php
[
    'id'    => 'subtitle',
    'label' => 'Subtitle',
    'type'  => 'text',
    'conditions' => [
        [
            'field'   => 'genre',
            'value'   => 'drama',
            'compare' => '==',
        ],
    ],
],
```

---

## Supported Locations

Attach field groups to different contexts :

```php
CFDev::register([
    'id'         => 'my_group',
    'title'      => 'My Group',

    // Attach to post types
    'post_types' => ['post', 'page', 'movie'],

    // Attach to taxonomies
    'taxonomies' => ['category', 'genre'],

    // Attach to user profile
    'user'       => true,

    // Attach to options page (requires CFDev Pro)
    'options'    => true,
]);
```

---

## Repeater

```php
[
    'id'     => 'team_members',
    'label'  => 'Team Members',
    'type'   => 'repeater',
    'fields' => [
        [
            'id'    => 'name',
            'label' => 'Name',
            'type'  => 'text',
        ],
        [
            'id'    => 'role',
            'label' => 'Role',
            'type'  => 'text',
        ],
    ],
],
```

Retrieve repeater values :

```php
$members = cfdev_get('team_members', get_the_ID());

foreach ($members as $member) {
    echo $member['name'] . ' — ' . $member['role'];
}
```

---

## Philosophy

CFDev follows a **code-first** approach inspired by how WordPress itself handles post types and taxonomies. The goal is simple :

- **Your fields belong in your repo**, not in the database
- **Zero admin dependency** — no export/import between environments
- **Readable and reviewable** — field declarations are just PHP arrays, easy to diff and PR

---

## Roadmap

- [ ] Core field types
- [ ] Repeater field
- [ ] Conditional logic
- [ ] Taxonomy & user meta support
- [ ] CLI command : `wp cfdev list`
- [ ] JSON import/export (ACF compatibility layer)
- [ ] Options page support

---

## Contributing

Pull requests are welcome. Please open an issue first to discuss what you'd like to change.

```bash
git clone https://github.com/yourname/cfdev.git
cd cfdev
composer install
```

---

## License

MIT © [Your Name]