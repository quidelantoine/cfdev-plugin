# REST API — CFDev

`rest: true` sur un champ l'expose dans **les deux modes** :

| Mode | Posts | Termes | Utilisateurs | Valeurs |
|------|-------|--------|--------------|---------|
| REST WP natif | `/wp/v2/{rest_base}/{id}` | `/wp/v2/{rest_base}/{id}` | `/wp/v2/users/{id}` | Brutes (ID d'image, JSON string…) |
| API CFDev | `/cfdev/v1/post/{id}` | `/cfdev/v1/term/{slug}/{id}` | `/cfdev/v1/user/{id}` | Résolues (image enrichie, bundle décodé…) |

> **Termes :** `{slug}` (endpoint CFDev) est le slug de la taxonomie (ex. `category`) ; `{rest_base}` (endpoint natif) est la base REST configurée sur la taxonomie (ex. `categories`). Ces deux valeurs peuvent différer.

Un groupe n'apparaît dans l'API CFDev que s'il contient au moins un champ `rest: true`. Seuls ces champs sont inclus dans la réponse.

## Activer l'exposition d'un champ plat

Ajoutez `'rest' => true` dans la définition du champ :

```php
register_cfdev_post_type(['book', 'books'])
    ->addMetaBox('details', 'Détails', [
        ['type' => 'text',   'id' => '_subtitle', 'label' => 'Sous-titre', 'rest' => true],
        ['type' => 'number', 'id' => '_pages',    'label' => 'Pages',      'rest' => true],
        ['type' => 'image',  'id' => '_cover',    'label' => 'Couverture', 'rest' => true],
        ['type' => 'text',   'id' => '_note',     'label' => 'Note interne'], // jamais exposé
    ]);
```

Sans `rest: true`, le champ n'apparaît ni dans le REST natif ni dans l'API CFDev.

Fonctionne de la même façon pour les taxonomies et les utilisateurs :

```php
// Terme (taxonomy avec show_in_rest: true requise pour le REST natif)
new TermMeta('genre', '', [
    ['type' => 'text', 'id' => '_color', 'label' => 'Couleur', 'rest' => true],
]);

// Utilisateur
new UserMeta('profile', 'Profil', [
    ['type' => 'text', 'id' => '_bio', 'label' => 'Bio', 'rest' => true],
]);
```

## Activer l'exposition d'un Bundle

Un Bundle stocke ses données sous une **unique clé meta** (l'ID du bundle) en JSON.

- **REST natif** → retourne la chaîne JSON brute, à parser côté client
- **API CFDev** → retourne le tableau décodé avec les valeurs résolues (images enrichies, etc.)

> **Tout-ou-rien.** `rest: true` se place uniquement sur le bundle lui-même, pas sur les champs individuels à l'intérieur. C'est le seul endroit possible — l'exposition est tout-ou-rien pour un bundle. Il n'est pas possible de sélectionner des champs individuels à l'intérieur.

Ajoutez `['rest' => true]` comme dernier élément du tableau :

```php
// Bundle avec ID explicite
->addMetaBox('chapters', 'Chapitres', [
    'bundle',
    'chapters_bundle',          // ID du bundle = clé meta
    [...fields...],
    ['rest' => true],
]);

// Bundle sans ID (utilise l'ID de la MetaBox)
->addMetaBox('chapters', 'Chapitres', [
    'bundle',
    [...fields...],
    ['rest' => true],
]);
```

Fonctionne aussi pour un Bundle imbriqué dans Tabs ou Accordion.

## REST WP natif — lire les valeurs brutes

### Post

```ts
const res  = await fetch('https://site.com/wp-json/wp/v2/books/42?_fields=id,title,meta');
const post = await res.json();

post.meta._subtitle                   // "Mon sous-titre"   (string)
post.meta._pages                      // "42"               (string — cast Number() si besoin)
post.meta._cover                      // "61"               (ID brut)
JSON.parse(post.meta.chapters_bundle) // [{ _title: "…" }, ...]
```

### Terme

La taxonomie doit être enregistrée avec `show_in_rest: true`. L'URL utilise le `rest_base` de la taxonomie (ex. `genres`, `categories`).

```ts
const res  = await fetch('https://site.com/wp-json/wp/v2/genres/5?_fields=id,name,meta');
const term = await res.json();

term.meta._color   // "red"   (string brut)
```

### Utilisateur

Auth requise. L'endpoint retourne les métadonnées dans l'objet `meta`.

```ts
const res  = await fetch('https://site.com/wp-json/wp/v2/users/1?_fields=id,name,meta', {
    headers: { Authorization: 'Basic ' + btoa('user:app-password') },
});
const user = await res.json();

user.meta._bio     // "Mon intro"   (string brut)
```

## API CFDev — valeurs résolues

### Post

```
GET /wp-json/cfdev/v1/post/{id}
```

Retourne uniquement les groupes et champs marqués `rest: true`, avec valeurs résolues :

```json
{
    "id": 42,
    "groups": {
        "details": {
            "_subtitle": "Mon sous-titre",
            "_pages": 42,
            "_cover": { "id": 61, "alt": "Couverture", "full": "https://…/cover.jpg", "thumbnail": "https://…/cover-150x150.jpg" },
            "chapters_bundle": [
                { "_title": "Chapitre 1", "_text": "…" },
                { "_title": "Chapitre 2", "_text": "…" }
            ]
        }
    }
}
```

**Auth** : aucune pour un post `publish` sur un CPT public. Requiert `read_post` pour les drafts / privés.

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
            "_color": "red"
        }
    }
}
```

**Auth** : aucune pour une taxonomie publique. Requiert `manage_terms` sinon.

### Utilisateur

```
GET /wp-json/cfdev/v1/user/{id}
```

```json
{
    "id": 1,
    "groups": {
        "profile": {
            "_bio": "Mon intro"
        }
    }
}
```

**Auth** : toujours requise. L'utilisateur peut lire ses propres données ; un administrateur peut lire n'importe quel utilisateur.

### Depuis Next.js

```ts
// Post
const post = await fetch('https://site.com/wp-json/cfdev/v1/post/42').then(r => r.json());
post.groups.details._subtitle          // "Mon sous-titre"
post.groups.details._cover             // { id: 61, alt: '...', full: '...', thumbnail: '...' }
post.groups.details.chapters_bundle    // [{ _title: 'Ch. 1', ... }]

// Terme — {slug} de la taxonomie dans l'URL, pas le rest_base
const term = await fetch('https://site.com/wp-json/cfdev/v1/term/genre/5').then(r => r.json());
term.groups['genre-meta']._color       // "red"

// Utilisateur — auth toujours requise
const user = await fetch('https://site.com/wp-json/cfdev/v1/user/1', {
    headers: { Authorization: 'Basic ' + Buffer.from(process.env.CFDEV_WP_TOKEN!).toString('base64') },
}).then(r => r.json());
user.groups.profile._bio               // "Mon intro"
```

Ces endpoints respectent le même interrupteur global **CFDev → Réglages → REST API**.

## Données brutes vs données résolues

| Champ | REST natif (brut) | API CFDev (résolu) |
|-------|-------------------|--------------------|
| Image | `"61"` (ID) | `{ id, alt, full, thumbnail, … }` |
| Bundle | `"[{...}]"` (JSON string) | `[{ _title: "Ch. 1", … }]` |
| Checkboxes | `"[\"a\",\"b\"]"` (JSON string) | `["a", "b"]` |
| Number | `"42"` (string) | `42` (number) |
| Texte simple | `"Bonjour"` | `"Bonjour"` |

## Types WP REST

| Type CFDev | Type REST | Note |
|------------|-----------|------|
| `number`   | `number`  | Valeur numérique |
| `bundle`   | `string`  | JSON à parser côté client (REST natif uniquement) |
| Tout autre | `string`  | Valeur brute de la meta |

## Filtrage par condition

Les champs `rest: true` d'une MetaBox conditionnelle ne sont visibles que pour l'objet correspondant — dans les deux modes.

```php
// Exposé uniquement pour la page d'ID 42
->addMetaBox('hero', 'Hero', [
    ['type' => 'text', 'id' => '_hero_title', 'rest' => true],
])->onlyForId(42);

// Exposé uniquement sur un template
->addMetaBox('home', 'Home', [
    ['type' => 'text', 'id' => '_headline', 'rest' => true],
])->onlyForTemplate('template-home.php');

// Terme : visible seulement si le term a ce parent
new TermMeta('category', '', [
    ['type' => 'text', 'id' => '_badge', 'rest' => true],
])->onlyIfParent(5);
```

## Visibilité et authentification

| Cas | Code HTTP |
|-----|-----------|
| Post `publish` sur CPT public | 200 — pas d'auth requise |
| Post `private`/`draft`, utilisateur non connecté | 401 — auth requise |
| Post `private`/`draft`, utilisateur connecté sans droits | 403 — droits insuffisants |
| Taxonomie privée, utilisateur non connecté | 401 |
| Taxonomie privée, connecté sans `manage_terms` | 403 |
| Endpoint user, non connecté | 401 |
| Endpoint user, connecté mais pas l'utilisateur concerné ni admin | 403 |

Depuis Next.js (server-side) avec Application Password :

```ts
// .env.local : CFDEV_WP_TOKEN=username:application-password

const res = await fetch('/wp-json/cfdev/v1/post/42', {
    headers: {
        Authorization: 'Basic ' + Buffer.from(process.env.CFDEV_WP_TOKEN!).toString('base64'),
    },
    cache: 'no-store',
});
```

## Interrupteur global

L'exposition REST (natif et CFDev) peut être désactivée depuis **CFDev → Réglages** sans modifier le code.

## Champs exclus automatiquement

- Champs sans `rest: true`
- Sous-champs individuels d'un Bundle — l'exposition est tout-ou-rien : marquez le bundle entier avec `['rest' => true]`, il n'est pas possible de sélectionner des champs individuels à l'intérieur
- Tout champ lorsque l'option globale est désactivée

## Voir les champs exposés

**CFDev → REST API** dans le back-office liste tous les champs actuellement exposés avec leur clé meta, type WP REST, groupe d'appartenance et endpoints correspondants.