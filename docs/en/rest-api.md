# REST API

[← README](../../readme.md) · [Français](../fr/rest-api.md)

Add `'rest' => true` to any field to expose it in **both** REST modes:

| Mode | Posts | Terms | Users | Options | Values |
|---|---|---|---|---|---|
| WP native REST | `/wp/v2/{rest_base}/{id}` | `/wp/v2/{rest_base}/{id}` | `/wp/v2/users/{id}` | `/wp/v2/settings` | Raw (image ID, JSON string…) |
| CFDev API | `/cfdev/v1/post/{id}` | `/cfdev/v1/term/{slug}/{id}` | `/cfdev/v1/user/{id}` | `/cfdev/v1/options/{page_id}` | Resolved (enriched image, decoded bundle…) |

A group only appears in the CFDev API if it contains at least one `rest: true` field. Only those fields are included in the response.

---

## Enable a field

Add `'rest' => true` to the field definition:

```php
register_cfdev_post_type(['book', 'books'])
    ->addMetaBox('details', 'Details', [
        ['type' => 'text',   'id' => 'subtitle', 'label' => 'Subtitle', 'rest' => true],
        ['type' => 'number', 'id' => 'pages',    'label' => 'Pages',    'rest' => true],
        ['type' => 'image',  'id' => 'cover',    'label' => 'Cover',    'rest' => true],
        ['type' => 'text',   'id' => 'note',     'label' => 'Internal note'],  // never exposed
    ]);
```

Works the same for taxonomies and users:

```php
// Term (taxonomy must be registered with show_in_rest: true for native REST)
new TermMeta('genre', '', [
    ['type' => 'color', 'id' => 'color', 'label' => 'Color', 'rest' => true],
]);

// User
register_cfdev_user_meta('profile', 'Profile', [
    ['type' => 'text', 'id' => 'bio', 'label' => 'Bio', 'rest' => true],
]);
```

---

## Enable a Bundle

A Bundle stores its data under a **single meta key** as JSON.

- **Native REST** → returns the raw JSON string, parse client-side
- **CFDev API** → returns the decoded array with resolved values (enriched images, etc.)

Add `['rest' => true]` as the last element of the bundle array:

```php
->addMetaBox('chapters', 'Chapters', [
    'bundle',
    'chapters_bundle',  // explicit bundle ID
    [/* fields */],
    ['rest' => true],
]);
```

`rest: true` is placed on the bundle itself — exposure is all-or-nothing. You cannot select individual fields inside a bundle.

---

## WP native REST — raw values

### Post

```ts
const res  = await fetch('/wp-json/wp/v2/books/42?_fields=id,title,meta');
const post = await res.json();

post.meta.subtitle                      // "My subtitle"  (string)
post.meta.pages                         // "42"           (string — cast Number() if needed)
post.meta.cover                         // "61"           (raw attachment ID)
JSON.parse(post.meta.chapters_bundle)   // [{ title: "…" }, ...]
```

### Term

The taxonomy must be registered with `show_in_rest: true`. The URL uses the taxonomy's `rest_base`.

```ts
const res  = await fetch('/wp-json/wp/v2/genres/5?_fields=id,name,meta');
const term = await res.json();

term.meta.color   // "red"  (raw string)
```

### User

Authentication required.

```ts
const res = await fetch('/wp-json/wp/v2/users/1?_fields=id,name,meta', {
    headers: { Authorization: 'Basic ' + btoa('user:app-password') },
});
const user = await res.json();

user.meta.bio   // "My intro"  (raw string)
```

---

## CFDev API — resolved values

### Post

```
GET /wp-json/cfdev/v1/post/{id}
```

```json
{
    "id": 42,
    "groups": {
        "details": {
            "subtitle": "My subtitle",
            "pages": 42,
            "cover": { "id": 61, "alt": "Cover", "full": "https://…/cover.jpg", "thumbnail": "https://…/cover-150x150.jpg" },
            "chapters_bundle": [
                { "title": "Chapter 1", "text": "…" },
                { "title": "Chapter 2", "text": "…" }
            ]
        }
    }
}
```

**Auth:** none for a `publish` post on a public CPT. Requires `read_post` for drafts / private posts.

### Term

```
GET /wp-json/cfdev/v1/term/{slug}/{id}
```

`{slug}` is the taxonomy slug (e.g. `genre`, `category`) — **not** the endpoint's `rest_base` (e.g. `genres`, `categories`).

```json
{
    "id": 5,
    "groups": {
        "genre-meta": {
            "color": "red"
        }
    }
}
```

**Auth:** none for a public taxonomy. Requires `manage_terms` otherwise.

### User

```
GET /wp-json/cfdev/v1/user/{id}
```

**Auth:** always required. Users can read their own data; admins can read any user.

```json
{
    "id": 1,
    "groups": {
        "profile": {
            "bio": "My intro"
        }
    }
}
```

### Options

```
GET /wp-json/cfdev/v1/options/{page_id}
```

`{page_id}` is the first argument of `register_cfdev_options_page()`.

**Auth:** none — options are publicly readable.

```json
{
    "page": "brand",
    "groups": {
        "brand": {
            "_brand_name": "Acme Corp",
            "_brand_color": "#e63946",
            "_brand_logo": {
                "id": 42,
                "alt": "Acme logo",
                "full": "https://example.com/wp-content/uploads/logo.png",
                "thumbnail": "https://example.com/wp-content/uploads/logo-150x150.png",
                "medium": "https://example.com/wp-content/uploads/logo-300x100.png"
            },
            "_team_members": [
                {
                    "_tm_name": "Alice",
                    "_tm_role": "CEO",
                    "_tm_photo": { "id": 5, "alt": "Alice", "full": "https://…/alice.jpg", "medium": "https://…/alice-300x300.jpg" }
                }
            ]
        }
    }
}
```

Only fields and bundles with `rest: true` appear in the response.

For the WP native settings endpoint, the same fields appear at `/wp/v2/settings` as raw values:

```ts
const res = await fetch('/wp-json/wp/v2/settings', {
    headers: { Authorization: 'Basic ' + btoa('user:app-password') },
});
// → { "_brand_name": "Acme Corp", "_brand_logo": "42", "_team_members": "[{…}]" }
```

> `/wp/v2/settings` requires `manage_options` even for reading. Prefer the CFDev endpoint for public-facing use.

---

### From Next.js

```ts
// Options — no auth required
const brand = await fetch('https://example.com/wp-json/cfdev/v1/options/brand').then(r => r.json());
brand.groups.brand._brand_name          // "Acme Corp"
brand.groups.brand._brand_logo.full     // "https://…/logo.png"
brand.groups.brand._brand_logo.medium   // "https://…/logo-300x100.png"
brand.groups.brand._team_members        // [{ _tm_name: "Alice", _tm_photo: { full: "…" } }]

// Post
const post = await fetch('https://example.com/wp-json/cfdev/v1/post/42').then(r => r.json());
post.groups.details.subtitle          // "My subtitle"
post.groups.details.cover             // { id: 61, alt: '...', full: '...', thumbnail: '...' }
post.groups.details.chapters_bundle   // [{ title: 'Ch. 1', ... }]

// Term — taxonomy slug in URL, not the rest_base
const term = await fetch('https://example.com/wp-json/cfdev/v1/term/genre/5').then(r => r.json());
term.groups['genre-meta'].color       // "red"

// User — auth always required
const user = await fetch('https://example.com/wp-json/cfdev/v1/user/1', {
    headers: {
        Authorization: 'Basic ' + Buffer.from(process.env.CFDEV_WP_TOKEN!).toString('base64'),
    },
}).then(r => r.json());
user.groups.profile.bio               // "My intro"
```

---

## Raw vs resolved

| Field | Native REST (raw) | CFDev API (resolved) |
|---|---|---|
| Image | `"61"` (ID) | `{ id, alt, full, thumbnail, … }` |
| Bundle | `"[{...}]"` (JSON string) | `[{ title: "Ch. 1", … }]` |
| Checkboxes | `"[\"a\",\"b\"]"` (JSON string) | `["a", "b"]` |
| Number | `"42"` (string) | `42` (number) |
| Plain text | `"Hello"` | `"Hello"` |

---

## Visibility and authentication

| Case | HTTP code |
|---|---|
| `publish` post on public CPT | 200 — no auth required |
| `private`/`draft`, unauthenticated | 401 |
| `private`/`draft`, authenticated without rights | 403 |
| Private taxonomy, unauthenticated | 401 |
| Private taxonomy, authenticated without `manage_terms` | 403 |
| User endpoint, unauthenticated | 401 |
| User endpoint, authenticated but not own user or admin | 403 |
| Options endpoint (`/cfdev/v1/options/…`) | 200 — always public |
| Options page not found or no `rest: true` fields | 404 |

---

## Conditional filtering

Fields marked `rest: true` in a conditional MetaBox are only visible for matching objects — in both modes.

```php
// Exposed only for post ID 42
->addMetaBox('hero', 'Hero', [
    ['type' => 'text', 'id' => 'hero_title', 'rest' => true],
])->onlyForId(42);
```

---

## Global switch

REST exposure (native and CFDev) can be disabled from **CFDev → Settings** without changing code.

---

## See exposed fields

**CFDev → REST API** in the back-office lists all currently exposed fields with their meta key, WP REST type, group, and corresponding endpoints.
