# Cache

[← README](../../readme.md) · [Français](../fr/cache.md)

CFDev includes a file-based cache that stores **pre-resolved data**: images enriched with all their URLs, bundles unwrapped, JSON decoded. Templates only display — no WordPress logic in views.

---

## Enable / disable

Go to **WordPress Admin → CFDev → Cache** and toggle the switch.

| State | Behavior |
|---|---|
| **On** (production) | Reads `.tmp` file per object. Written once after generation, auto-invalidated on save. |
| **Off** (development) | Data read directly from the database. Changes visible immediately. |

---

## Usage

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();

// Post
$data = $cache->post(get_the_ID());

// Term
$data = $cache->term($term->term_id, 'genre');

// User
$data = $cache->user(get_current_user_id());

// Access data
$group = $data['groups']['product_info'] ?? [];
$price = $group['price'] ?? '';
$image = $group['photo'] ?? [];
```

---

## Returned structure by field type

| Type | Returned value |
|---|---|
| `text`, `select`, etc. | Raw `string` |
| `image` | `['id', 'alt', 'full', 'medium', 'thumbnail', …]` |
| `image_alt` | `['id', 'alt', 'full', 'medium', …]` (custom alt takes priority) |
| `gallery` | `[['id', 'alt', 'full', …], …]` |
| `file` | `['id', 'url', 'filename']` |
| `link` | `['url', 'text', 'target']` |
| `checkboxes` / `multi_select` | `['val1', 'val2', …]` |
| `bundle` | `[['field_a' => val, 'field_b' => val], …]` — keyed as `_bundle_id` |

---

## Performance

| Situation | DB queries | Estimated time |
|---|---|---|
| Cache on, valid file | 0 | ~1–2 ms (file read) |
| Cache on, after a save | N (regeneration) | ~5–30 ms |
| Cache off | N per field | same |

**Regeneration cost:** on the first access after a save, CFDev reads all post meta, resolves images (URL per size), unwraps bundles, and writes the file. This cost is **one-time and transparent** — the next request reads the file.

**Auto-expiration (TTL 24 h):** a file older than 24 hours is considered stale and regenerated on the next request.

---

## Manual invalidation

Automatic invalidation covers the standard cases (`save_post`, `edited_term`, `profile_update`). For out-of-WordPress cases (bulk imports, direct DB writes, migration scripts):

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();

$cache->invalidatePost(42);
$cache->invalidateTerm(7, 'category');
$cache->invalidateUser(1);

$count = $cache->invalidateAll(); // returns number of deleted files
```

**Force immediate regeneration** (invalidate + read in one pass):

```php
$data = $cache->post(42, force: true);
$data = $cache->term(7, 'genre', force: true);
$data = $cache->user(1, force: true);
```

---

## File naming convention

Files are stored in `wp-content/uploads/cfdev-cache/`.

| Object | File key |
|---|---|
| Post ID 42 | `post_42.tmp` |
| Term ID 7, taxonomy `genre` | `term_genre_7.tmp` |
| User ID 1 | `user_1.tmp` |

Only groups whose conditions match the object appear in the file. A group scoped to another page will not appear in an unrelated post's cache file.

---

## Security

**HTTP access blocked automatically:** CFDev generates a `.htaccess` in the cache directory on creation (Apache / LiteSpeed).

**On Nginx:** `.htaccess` is not read. Add this rule to your server config:

```nginx
location ~* /wp-content/uploads/cfdev-cache/ {
    deny all;
}
```

Cache files contain all field values as plain JSON. If fields store sensitive data, ensure:
- Uploads directory permissions are correct (`755` dirs, `644` files)
- SSH/FTP access to the server is restricted
