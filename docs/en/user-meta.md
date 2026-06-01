# User Meta

[← README](../../readme.md) · [Français](../fr/user-meta.md)

---

User meta lets you attach custom fields to user profiles. Fields appear on the **Your Profile** page (own profile) and/or the **Edit User** admin screen.

---

## 1. Quick registration — helper function

```php
add_action('init', static function (): void {

    register_cfdev_user_meta('profile', 'Profile', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
        ['id' => 'bio',       'type' => 'wysiwyg', 'label' => 'Bio'],
        ['id' => 'website',   'type' => 'url',   'label' => 'Website'],
    ]);

});
```

By default, the section appears on **both** the user's own profile page and the admin "Edit User" screen.

---

## 2. Standalone registration — `UserMeta` class

```php
use Weblitzer\CFDev\Meta\UserMeta;

add_action('init', static function (): void {

    new UserMeta('profile', 'Profile', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
    ]);

});
```

Both `register_cfdev_user_meta()` and `new UserMeta()` accept the same parameters.

---

## 3. Locations — where fields appear

Control which profile pages show the section via the fourth parameter:

```php
// Own profile only (the user editing themselves)
register_cfdev_user_meta('social', 'Social Links', $fields, ['show_user_profile']);

// Admin "Edit User" screen only (editing another user)
register_cfdev_user_meta('admin_notes', 'Admin Notes', $fields, ['edit_user_profile']);

// Both (default — same as omitting this parameter)
register_cfdev_user_meta('profile', 'Profile', $fields, ['show_user_profile', 'edit_user_profile']);
```

| Location key | Where it appears |
|---|---|
| `show_user_profile` | When the user edits their **own** profile |
| `edit_user_profile` | When an admin edits **another** user's profile |

---

## 4. Role restriction — `onlyForRole()`

Show the section only for users with a specific role. Useful to expose extra fields to administrators without cluttering the profile of regular users.

```php
// Only administrators see this section
register_cfdev_user_meta('admin_meta', 'Admin Data', $fields)
    ->onlyForRole('administrator');

// Multiple roles accepted
register_cfdev_user_meta('editor_meta', 'Editor Data', $fields)
    ->onlyForRole(['editor', 'author']);
```

> The restriction applies to the **profile being edited**, not the user doing the editing.

---

## 5. Display order — `priority`

When several user meta sections exist, control their order with the fifth parameter (default: `10`):

```php
// Shown first
register_cfdev_user_meta('basics', 'Basic Info', $fields, [], 5);

// Shown second (default position)
register_cfdev_user_meta('contact', 'Contact', $fields, [], 10);

// Shown last
register_cfdev_user_meta('advanced', 'Advanced', $fields, [], 20);
```

---

## 6. Layouts

### 6.1 Flat fields

```php
register_cfdev_user_meta('profile', 'Profile', [
    ['id' => 'avatar',     'type' => 'image',   'label' => 'Avatar'],
    ['id' => 'job_title',  'type' => 'text',    'label' => 'Job Title'],
    ['id' => 'department', 'type' => 'select',  'label' => 'Department',
        'options' => ['dev' => 'Development', 'design' => 'Design', 'marketing' => 'Marketing']],
    ['id' => 'bio',        'type' => 'wysiwyg', 'label' => 'Bio'],
    ['id' => 'twitter',    'type' => 'url',     'label' => 'Twitter / X'],
    ['id' => 'linkedin',   'type' => 'url',     'label' => 'LinkedIn'],
]);
```

### 6.2 Bundle — repeatable rows

```php
register_cfdev_user_meta('certifications', 'Certifications', [
    'bundle',
    '_certs',
    [
        ['id' => 'title',  'type' => 'text',  'label' => 'Certification',  'required' => true],
        ['id' => 'issuer', 'type' => 'text',  'label' => 'Issued by'],
        ['id' => 'year',   'type' => 'number','label' => 'Year'],
        ['id' => 'file',   'type' => 'file',  'label' => 'Certificate PDF'],
    ],
]);
```

### 6.3 Tabs

```php
register_cfdev_user_meta('full_profile', 'Full Profile', [
    'tabs',
    [
        'Identity' => [
            ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
            ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
            ['id' => 'bio',       'type' => 'wysiwyg','label' => 'Bio'],
        ],
        'Social' => [
            ['id' => 'twitter',  'type' => 'url', 'label' => 'Twitter / X'],
            ['id' => 'linkedin', 'type' => 'url', 'label' => 'LinkedIn'],
            ['id' => 'github',   'type' => 'url', 'label' => 'GitHub'],
        ],
        'Media' => [
            ['id' => 'avatar',  'type' => 'image',   'label' => 'Avatar'],
            ['id' => 'banner',  'type' => 'image',   'label' => 'Banner'],
            ['id' => 'gallery', 'type' => 'gallery', 'label' => 'Gallery'],
        ],
    ],
]);
```

### 6.4 Multiple sections for different roles

```php
add_action('init', static function (): void {

    // Section for all users (own profile only)
    register_cfdev_user_meta('public_profile', 'Public Profile', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title'],
    ], ['show_user_profile'], 10);

    // Section for administrators only, both profile pages, shown last
    register_cfdev_user_meta('admin_notes', 'Admin Notes', [
        ['id' => 'internal_note', 'type' => 'textarea', 'label' => 'Internal note'],
        ['id' => 'account_type',  'type' => 'select',   'label' => 'Account type',
            'options' => ['standard' => 'Standard', 'premium' => 'Premium', 'vip' => 'VIP']],
    ], ['show_user_profile', 'edit_user_profile'], 30)
    ->onlyForRole('administrator');

});
```

---

## 7. Reading user meta

### Without cache — direct meta

```php
$job_title = get_user_meta($user_id, 'job_title', true);

// Image — stored as attachment ID
$avatar_id = get_user_meta($user_id, 'avatar', true);
$avatar    = \Weblitzer\CFDev\Field::decodeMetaValue($avatar_id);
// $avatar is now an array with 'url', 'medium', 'thumbnail', 'alt', etc.
```

> For scalar fields (text, number, url…), `get_user_meta($id, 'key', true)` returns the value directly.
> For complex types (image, file, link…), the value is a JSON-encoded object — use `Field::decodeMetaValue()` or the cache.

### With cache (recommended)

```php
$cache    = new \Weblitzer\CFDev\Cache\CacheManager();
$data     = $cache->user(get_current_user_id());
$profile  = $data['groups']['profile'] ?? [];
$admin_n  = $data['groups']['admin_notes'] ?? [];

// Scalar field
echo esc_html($profile['job_title'] ?? '');

// Image (all sizes resolved)
$avatar = $profile['avatar'] ?? [];
echo '<img src="' . esc_url($avatar['medium'] ?? '') . '" alt="' . esc_attr($avatar['alt'] ?? '') . '">';

// Bundle rows
$certs = $data['groups']['certifications']['_certs'] ?? [];
foreach ($certs as $cert) {
    echo '<strong>' . esc_html($cert['title']) . '</strong> — ' . esc_html($cert['issuer']);
}
```

> `groups` keys match the `$id` first parameter of `register_cfdev_user_meta()` / `new UserMeta()`.
> Cache is invalidated automatically on `profile_update`.

---

## 8. REST API

Mark fields with `'rest' => true` to expose them via the REST endpoint:

```php
register_cfdev_user_meta('profile', 'Profile', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar',    'rest' => true],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Job Title', 'rest' => true],
    ['id' => 'private',   'type' => 'text',  'label' => 'Private data'], // not exposed
]);
```

Access the data (authentication required):

```
GET /wp-json/cfdev/v1/user/{id}
Authorization: Bearer <token>   (or cookie + nonce)
```

```json
{
    "id": 1,
    "groups": {
        "profile": {
            "avatar":    { "url": "...", "medium": "...", "alt": "..." },
            "job_title": "CTO"
        }
    }
}
```

> The user REST endpoint always requires authentication. The REST API must be enabled globally in **WordPress Admin → CFDev → REST API**.

---

## Next

→ [Term Meta](term-meta.md) · [Field Types](fields.md) · [Layouts](layouts.md) · [Cache](cache.md) · [REST API](rest-api.md)