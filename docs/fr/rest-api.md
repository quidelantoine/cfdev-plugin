# REST API

[← README](../../readme.md) · [English](../en/rest-api.md)

Ajoutez `'rest' => true` sur un champ pour l'exposer dans **les deux modes** :

| Mode | Posts | Termes | Utilisateurs | Options | Valeurs |
|---|---|---|---|---|---|
| REST WP natif | `/wp/v2/{rest_base}/{id}` | `/wp/v2/{rest_base}/{id}` | `/wp/v2/users/{id}` | `/wp/v2/settings` | Brutes (ID d'image, JSON string…) |
| API CFDev | `/cfdev/v1/post/{id}` | `/cfdev/v1/term/{slug}/{id}` | `/cfdev/v1/user/{id}` | `/cfdev/v1/options/{page_id}` | Résolues (image enrichie, bundle décodé…) |

Un groupe n'apparaît dans l'API CFDev que s'il contient au moins un champ `rest: true`. Seuls ces champs sont inclus dans la réponse.

---

## Activer un champ

Ajoutez `'rest' => true` dans la définition du champ :

```php
register_cfdev_post_type(['book', 'books'])
    ->addMetaBox('details', 'Détails', [
        ['type' => 'text',   'id' => 'subtitle', 'label' => 'Sous-titre', 'rest' => true],
        ['type' => 'number', 'id' => 'pages',    'label' => 'Pages',      'rest' => true],
        ['type' => 'image',  'id' => 'cover',    'label' => 'Couverture', 'rest' => true],
        ['type' => 'text',   'id' => 'note',     'label' => 'Note interne'],  // jamais exposé
    ]);
```

Fonctionne de la même façon pour les taxonomies et les utilisateurs :

```php
// Terme (la taxonomie doit être enregistrée avec show_in_rest: true pour le REST natif)
new TermMeta('genre', '', [
    ['type' => 'color', 'id' => 'color', 'label' => 'Couleur', 'rest' => true],
]);

// Utilisateur
register_cfdev_user_meta('profile', 'Profil', [
    ['type' => 'text', 'id' => 'bio', 'label' => 'Bio', 'rest' => true],
]);
```

---

## Activer un Bundle

Un Bundle stocke ses données sous une **unique clé meta** en JSON.

- **REST natif** → retourne la chaîne JSON brute, à parser côté client
- **API CFDev** → retourne le tableau décodé avec les valeurs résolues

Ajoutez `['rest' => true]` comme dernier élément du tableau bundle :

```php
->addMetaBox('chapters', 'Chapitres', [
    'bundle',
    'chapters_bundle',  // ID du bundle explicite
    [/* champs */],
    ['rest' => true],
]);
```

`rest: true` se place uniquement sur le bundle — l'exposition est tout-ou-rien. Il n'est pas possible de sélectionner des champs individuels à l'intérieur.

---

## REST WP natif — valeurs brutes

### Post

```ts
const res  = await fetch('/wp-json/wp/v2/books/42?_fields=id,title,meta');
const post = await res.json();

post.meta.subtitle                      // "Mon sous-titre"  (string)
post.meta.pages                         // "42"              (string — cast Number() si besoin)
post.meta.cover                         // "61"              (ID brut)
JSON.parse(post.meta.chapters_bundle)   // [{ title: "…" }, ...]
```

### Terme

La taxonomie doit être enregistrée avec `show_in_rest: true`. L'URL utilise le `rest_base` de la taxonomie.

```ts
const res  = await fetch('/wp-json/wp/v2/genres/5?_fields=id,name,meta');
const term = await res.json();

term.meta.color   // "red"  (string brut)
```

### Utilisateur

Authentification requise.

```ts
const res = await fetch('/wp-json/wp/v2/users/1?_fields=id,name,meta', {
    headers: { Authorization: 'Basic ' + btoa('user:app-password') },
});
const user = await res.json();

user.meta.bio   // "Mon intro"  (string brut)
```

---

## API CFDev — valeurs résolues

### Post

```
GET /wp-json/cfdev/v1/post/{id}
```

```json
{
    "id": 42,
    "groups": {
        "details": {
            "subtitle": "Mon sous-titre",
            "pages": 42,
            "cover": { "id": 61, "alt": "Couverture", "full": "https://…/cover.jpg", "thumbnail": "https://…/cover-150x150.jpg" },
            "chapters_bundle": [
                { "title": "Chapitre 1", "text": "…" },
                { "title": "Chapitre 2", "text": "…" }
            ]
        }
    }
}
```

**Auth :** aucune pour un post `publish` sur un CPT public. Requiert `read_post` pour les drafts / privés.

### Terme

```
GET /wp-json/cfdev/v1/term/{slug}/{id}
```

`{slug}` est le slug de la taxonomie (ex. `genre`, `category`) — **pas** le `rest_base` de l'endpoint natif (ex. `genres`, `categories`).

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

**Auth :** aucune pour une taxonomie publique. Requiert `manage_terms` sinon.

### Utilisateur

```
GET /wp-json/cfdev/v1/user/{id}
```

**Auth :** toujours requise. Un utilisateur peut lire ses propres données ; un administrateur peut lire n'importe quel utilisateur.

```json
{
    "id": 1,
    "groups": {
        "profile": {
            "bio": "Mon intro"
        }
    }
}
```

### Options

```
GET /wp-json/cfdev/v1/options/{page_id}
```

`{page_id}` est le premier argument de `register_cfdev_options_page()`.

**Auth :** authentification requise. L'utilisateur courant doit avoir la capacité `manage_options` (administrateurs par défaut).

```json
{
    "page": "marque",
    "groups": {
        "marque": {
            "_marque_nom": "Acme Corp",
            "_marque_couleur": "#e63946",
            "_marque_logo": {
                "id": 42,
                "alt": "Logo Acme",
                "full": "https://exemple.com/wp-content/uploads/logo.png",
                "thumbnail": "https://exemple.com/wp-content/uploads/logo-150x150.png",
                "medium": "https://exemple.com/wp-content/uploads/logo-300x100.png"
            },
            "_equipe": [
                {
                    "_eq_nom": "Alice",
                    "_eq_poste": "CEO",
                    "_eq_photo": { "id": 5, "alt": "Alice", "full": "https://…/alice.jpg", "medium": "https://…/alice-300x300.jpg" }
                }
            ]
        }
    }
}
```

Seuls les champs et bundles avec `rest: true` apparaissent dans la réponse.

Pour l'endpoint natif WP settings, les mêmes champs sont disponibles à `/wp/v2/settings` en valeurs brutes :

```ts
const res = await fetch('/wp-json/wp/v2/settings', {
    headers: { Authorization: 'Basic ' + btoa('user:app-password') },
});
// → { "_marque_nom": "Acme Corp", "_marque_logo": "42", "_equipe": "[{…}]" }
```

> Les deux endpoints `/wp/v2/settings` et `/cfdev/v1/options/…` requièrent `manage_options`. Utilisez l'endpoint CFDev pour les valeurs résolues (bundles décodés, images enrichies) ; `/wp/v2/settings` pour les valeurs brutes.

---

### Depuis Next.js

```ts
// Options — manage_options requis
const marque = await fetch('https://exemple.com/wp-json/cfdev/v1/options/marque', {
    headers: { Authorization: 'Basic ' + Buffer.from(process.env.CFDEV_WP_TOKEN!).toString('base64') },
}).then(r => r.json());
marque.groups.marque._marque_nom           // "Acme Corp"
marque.groups.marque._marque_logo.full     // "https://…/logo.png"
marque.groups.marque._marque_logo.medium   // "https://…/logo-300x100.png"
marque.groups.marque._equipe               // [{ _eq_nom: "Alice", _eq_photo: { full: "…" } }]

// Post
const post = await fetch('https://exemple.com/wp-json/cfdev/v1/post/42').then(r => r.json());
post.groups.details.subtitle          // "Mon sous-titre"
post.groups.details.cover             // { id: 61, alt: '...', full: '...', thumbnail: '...' }
post.groups.details.chapters_bundle   // [{ title: 'Ch. 1', ... }]

// Terme — slug de la taxonomie dans l'URL, pas le rest_base
const term = await fetch('https://exemple.com/wp-json/cfdev/v1/term/genre/5').then(r => r.json());
term.groups['genre-meta'].color       // "red"

// Utilisateur — auth toujours requise
const user = await fetch('https://exemple.com/wp-json/cfdev/v1/user/1', {
    headers: {
        Authorization: 'Basic ' + Buffer.from(process.env.CFDEV_WP_TOKEN!).toString('base64'),
    },
}).then(r => r.json());
user.groups.profile.bio               // "Mon intro"
```

---

## Brut vs résolu

| Champ | REST natif (brut) | API CFDev (résolu) |
|---|---|---|
| Image | `"61"` (ID) | `{ id, alt, full, thumbnail, … }` |
| Bundle | `"[{...}]"` (JSON string) | `[{ title: "Ch. 1", … }]` |
| Checkboxes | `"[\"a\",\"b\"]"` (JSON string) | `["a", "b"]` |
| Nombre | `"42"` (string) | `42` (number) |
| Texte simple | `"Bonjour"` | `"Bonjour"` |

---

## Visibilité et authentification

| Cas | Code HTTP |
|---|---|
| Post `publish` sur CPT public | 200 — pas d'auth requise |
| Post `private`/`draft`, non authentifié | 401 |
| Post `private`/`draft`, authentifié sans droits | 403 |
| Taxonomie privée, non authentifié | 401 |
| Taxonomie privée, authentifié sans `manage_terms` | 403 |
| Endpoint user, non authentifié | 401 |
| Endpoint user, authentifié mais pas l'utilisateur concerné ni admin | 403 |
| Endpoint options, non authentifié | 401 |
| Endpoint options, authentifié sans `manage_options` | 403 |
| Endpoint options, authentifié avec `manage_options` | 200 |
| Page options introuvable ou aucun champ `rest: true` | 404 |

---

## Filtrage conditionnel

Les champs `rest: true` d'une MetaBox conditionnelle ne sont visibles que pour l'objet correspondant — dans les deux modes.

```php
// Exposé uniquement pour le post ID 42
->addMetaBox('hero', 'Hero', [
    ['type' => 'text', 'id' => 'hero_title', 'rest' => true],
])->onlyForId(42);
```

---

## Interrupteur global

L'exposition REST (natif et CFDev) peut être désactivée depuis **CFDev → Réglages** sans modifier le code.

---

## Voir les champs exposés

**CFDev → REST API** dans le back-office liste tous les champs actuellement exposés avec leur clé meta, type WP REST, groupe d'appartenance et endpoints correspondants.
