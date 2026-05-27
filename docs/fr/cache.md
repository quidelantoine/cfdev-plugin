# Cache

[← README](../../readme.md) · [English](../en/cache.md)

CFDev inclut un cache fichier qui stocke des **données pré-résolues** : images enrichies de toutes leurs URLs, bundles déroulés, JSON décodés. Les templates n'ont plus qu'à afficher — pas de logique WordPress dans les vues.

---

## Activer / désactiver

Rendez-vous dans **WordPress Admin → CFDev → Cache** et basculez l'interrupteur.

| État | Comportement |
|---|---|
| **Actif** (production) | Lit le fichier `.tmp` par objet. Écrit une fois après génération, invalidé automatiquement à chaque sauvegarde. |
| **Inactif** (développement) | Données lues directement depuis la base de données. Les modifications sont visibles immédiatement. |

---

## Utilisation

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();

// Post
$data = $cache->post(get_the_ID());

// Terme
$data = $cache->term($term->term_id, 'genre');

// Utilisateur
$data = $cache->user(get_current_user_id());

// Accès aux données
$group = $data['groups']['product_info'] ?? [];
$price = $group['price'] ?? '';
$image = $group['photo'] ?? [];
```

---

## Structure retournée par type de champ

| Type | Valeur retournée |
|---|---|
| `text`, `select`, etc. | `string` brute |
| `image` | `['id', 'alt', 'full', 'medium', 'thumbnail', …]` |
| `image_alt` | `['id', 'alt', 'full', 'medium', …]` (alt personnalisé prioritaire) |
| `gallery` | `[['id', 'alt', 'full', …], …]` |
| `file` | `['id', 'url', 'filename']` |
| `link` | `['url', 'text', 'target']` |
| `checkboxes` / `multi_select` | `['val1', 'val2', …]` |
| `bundle` | `[['field_a' => val, 'field_b' => val], …]` — clé `_bundle_id` |

---

## Performance

| Situation | Requêtes DB | Temps estimé |
|---|---|---|
| Cache actif, fichier valide | 0 | ~1–2 ms (lecture fichier) |
| Cache actif, après une sauvegarde | N (régénération) | ~5–30 ms |
| Cache inactif | N par champ | identique |

**Coût de la régénération :** lors du premier accès après une sauvegarde, CFDev relit tous les meta, résout les images, déroule les bundles et écrit le fichier. Ce coût est **unique et transparent** — la requête suivante relit le fichier.

**Expiration automatique (TTL 24 h) :** un fichier de plus de 24 h est considéré périmé et régénéré à la prochaine requête.

---

## Invalidation manuelle

L'invalidation automatique couvre les cas standards (`save_post`, `edited_term`, `profile_update`). Pour les cas hors WordPress (imports en masse, modifications directes en DB, scripts de migration) :

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();

$cache->invalidatePost(42);
$cache->invalidateTerm(7, 'category');
$cache->invalidateUser(1);

$nb = $cache->invalidateAll(); // retourne le nombre de fichiers supprimés
```

**Forcer la régénération immédiate** (invalide + relit en une seule passe) :

```php
$data = $cache->post(42, force: true);
$data = $cache->term(7, 'genre', force: true);
$data = $cache->user(1, force: true);
```

---

## Convention de nommage des fichiers

Les fichiers sont stockés dans `wp-content/uploads/cfdev-cache/`.

| Objet | Clé fichier |
|---|---|
| Post ID 42 | `post_42.tmp` |
| Terme ID 7, taxonomie `genre` | `term_genre_7.tmp` |
| Utilisateur ID 1 | `user_1.tmp` |

Seuls les groupes dont les conditions correspondent à l'objet apparaissent dans le fichier. Un groupe conditionné à une autre page n'apparaîtra pas dans le cache d'un post non concerné.

---

## Sécurité

**Accès HTTP bloqué automatiquement :** CFDev génère un `.htaccess` dans le répertoire cache à la création (Apache / LiteSpeed).

**Sur Nginx :** le `.htaccess` n'est pas lu. Ajoutez cette règle dans votre configuration serveur :

```nginx
location ~* /wp-content/uploads/cfdev-cache/ {
    deny all;
}
```

Les fichiers cache contiennent toutes les valeurs de champs en JSON non chiffré. Si des champs stockent des données sensibles, vérifiez que :
- Les permissions du répertoire uploads sont correctes (`755` répertoires, `644` fichiers)
- L'accès SSH/FTP au serveur est restreint
