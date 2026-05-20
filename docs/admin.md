# CFDev — Interface d'administration

CFDev ajoute un menu **CFDev** dans la barre latérale WordPress. Toutes les pages requièrent la capacité `manage_options`.

---

## Pages disponibles

| URL | Rôle |
|-----|------|
| `?page=cfdev` | Tableau de bord (vue d'ensemble) |
| `?page=cfdev-fields` | Groupes de champs — registre complet + inspecteur |
| `?page=cfdev-cache` | Cache — activation, liste des fichiers, vidage |
| `?page=cfdev-settings` | Réglages globaux |

---

## Page "Groupes de champs"

### Organisation

Les groupes sont répartis en onglets par contexte :

- **Un onglet par post type** déclaré (`Page`, `Article`, `Book`…)
- **Termes** — groupes assignés à des taxonomies
- **Utilisateurs** — groupes assignés aux profils

Chaque groupe est un bloc rétractable affichant :

| Élément | Description |
|---------|-------------|
| Titre + ID | Nom humain et identifiant machine (`code`) |
| Badge de layout | `flat` / `tabs` / `accordion` / `bundle` |
| "Aussi dans" | Autres post types ciblés (si multi-cible) |
| Conditions | Badges `ID : 1`, `Template : …`, `Rôle : editor`… |
| Nb de champs | Total flat + champs de bundles |
| ⚙ Inspecter | Lance l'inspecteur de données pour ce groupe |

### Tableau des champs

En dépliant un groupe, on voit un tableau par section / bundle :

| ID | Type | Label | Validation |
|----|------|-------|------------|
| `hero_title` | `text` | Titre hero | `requis` `min-length: 3` |
| `hero_image` | `image` | Image | `requis` |
| `hero_qty`   | `text` | Quantité | `min: 1` `max: 99` |

La colonne **Validation** affiche un badge par règle active sur le champ :

- `requis` (vert) — champ obligatoire
- `min: 5`, `max: 100` — valeur numérique min/max
- `min-length: 3`, `max-length: 255` — longueur de chaîne
- `between: 1, 10` — intervalle
- `email`, `url`, `slug`, `numeric`, `alpha`, `alpha-numeric`
- `regex: /^[a-z]+$/` — expression régulière avec son pattern
- `file-extension: jpg|png|webp`, `file-mime: image/jpeg`
- `max-items: 5`, `min-items: 1` — pour les bundles / galeries
- `date-after: 2024-01-01`, `date-before-today`
- `image-min-dimensions: 800, 600`, `image-exact-dimensions: 1920, 1080`

---

## L'inspecteur — outil dev clé

Le bouton **⚙ Inspecter** sur chaque groupe ouvre une modale sombre affichant les données réelles du groupe pour un objet choisi, directement depuis l'admin.

### À quoi ça sert

- Vérifier qu'un champ est bien enregistré après une saisie
- Voir exactement la structure PHP retournée par `CacheManager` (images résolues, bundles, etc.)
- Copier le chemin d'accès à un champ en un clic pour l'utiliser dans un template
- Diagnostiquer rapidement un champ vide, une galerie mal résolue, un bundle cassé
- Observer l'état du cache (HIT / GENERATED / OFF) sans fouiller les fichiers `.tmp`

### Sélection de l'objet

Quand le groupe n'est pas lié à un objet fixe, un `<select>` apparaît dans la barre de la modale.

Il est **pré-filtré selon les conditions du groupe** :

| Condition déclarée | Ce qui s'affiche dans le select |
|---|---|
| Aucune | Tous les objets du post type / taxonomie / utilisateurs (max 100) |
| `onlyForTemplate('tpl-about.php')` | Uniquement les pages avec ce template |
| `onlyForRoles('editor')` | Uniquement les éditeurs |
| `onlyIfParent(5)` (TermMeta) | Uniquement les termes enfants du terme #5 |

Pour les groupes avec `onlyForId(42)` : le select est masqué et les données de la page #42 sont chargées directement — pas de choix à faire.

**Changer la sélection** recharge automatiquement les données sans délai.

### Lecture des données

Les données s'affichent sous forme d'arbre interactif (style Symfony Profiler) :

```
▼ array(3)
    ⎘  hero_title   ⇒  "Bienvenue sur CFDev"  (22)
    ⎘  hero_image   ⇒  ▶ object(5)
    ⎘  hero_slides  ⇒  ▼ array(2)
                          ⎘  0  ⇒  ▶ object(2)
                          ⎘  1  ⇒  ▶ object(2)
```

- **▶ / ▼** : cliquer sur le badge ouvre/ferme le niveau
- **(22)** : longueur de la chaîne
- **Couleurs** : clés en violet, strings en vert, nombres en bleu, null en gris…

### Copier un chemin

Chaque ligne a un bouton ⎘ qui copie le chemin PHP complet dans le presse-papiers :

```
⎘ → $group['hero_image']['medium']
⎘ → $group['hero_slides'][0]['slide_title']
```

Un snippet d'accès global est aussi disponible en haut de la modale :

```php
$data  = (new \Weblitzer\CFDev\Cache\CacheManager())->post(42);
$group = $data['groups']['home_hero'] ?? [];
```

Le bouton ⎘ à côté du snippet copie ces deux lignes d'un seul clic.

### Badge de cache

En haut à droite de la modale :

| Badge | Signification |
|-------|---------------|
| `CACHE HIT — il y a 3min` | Les données viennent du fichier `.tmp` |
| `GENERATED` | Données générées en direct (cache OFF ou fichier absent/expiré) |
| `CACHE OFF` | Le cache est désactivé dans les réglages |

### ↺ Régénérer

Force la régénération des données (équivalent à `force: true` dans `CacheManager`). Utile pour voir les données fraîches après une modification sans vider tout le cache.

---

## Page "Cache"

### Toggle activer/désactiver

| État | Comportement |
|------|-------------|
| **Actif** | Données lues depuis `.tmp` si présent et non expiré (TTL 24 h). Fichier écrit après génération. |
| **Inactif** | Données lues en direct depuis la base. Aucun fichier créé ni lu. |

> **Recommandation :** désactiver en développement, activer en production.

L'invalidation est automatique sur `save_post`, `edited_term`, `delete_term`, `profile_update`.

### Tableau des fichiers

| Colonne | Description |
|---------|-------------|
| Objet | Titre / nom / display name + nom du fichier `.tmp` |
| Type | Post type réel (`Page`, `Leçon`…), taxonomie, ou `Utilisateur` |
| Groupes | Tags avec les titres des groupes présents dans ce fichier cache |
| Taille | Taille du fichier JSON |
| Âge | Temps écoulé depuis la génération |
| Modifié | Date et heure de dernière écriture |
| Action | Bouton **Supprimer** pour invalider un fichier individuel |

Les lignes dont l'âge dépasse 24 h affichent un badge **Expiré**.

> La colonne "Groupes" ne liste que les groupes **dont les conditions correspondent** à cet objet. Un article standard n'affiche pas un groupe conditionné à la page d'accueil.

---

## Utilisation dans les templates

### Récupérer les données d'un post

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$data  = $cache->post(get_the_ID());

$hero  = $data['groups']['home_hero'] ?? [];
$titre = $hero['hero_title'] ?? '';
$image = $hero['hero_image'] ?? [];

echo '<img src="' . esc_url($image['medium'] ?? $image['full'] ?? '') . '"
          alt="' . esc_attr($image['alt'] ?? '') . '">';
```

### Terme / taxonomie

```php
$cache  = new \Weblitzer\CFDev\Cache\CacheManager();
$data   = $cache->term($term->term_id, 'courses');
$module = $data['groups']['courses'] ?? [];

$resume = $module['m_term_resume_module'] ?? '';
$image  = $module['m_term_img_module']    ?? [];
```

### Utilisateur

```php
$cache  = new \Weblitzer\CFDev\Cache\CacheManager();
$data   = $cache->user(get_current_user_id());
$profil = $data['groups']['profile'] ?? [];

$poste  = $profil['job_title'] ?? '';
$avatar = $profil['avatar']    ?? [];
```

### Bundle (lignes répétables)

```php
$slides = $data['groups']['home_hero']['hero_slides'] ?? [];

// $slides = [
//   ['slide_title' => 'Slide 1', 'slide_image' => ['id' => 16, 'full' => '…', 'alt' => '…']],
//   ['slide_title' => 'Slide 2', 'slide_image' => ['id' => 17, 'full' => '…', 'alt' => '…']],
// ]

foreach ($slides as $slide) {
    $img = $slide['slide_image'] ?? [];
    echo '<h4>' . esc_html($slide['slide_title'] ?? '') . '</h4>';
    echo '<img src="' . esc_url($img['medium'] ?? $img['full'] ?? '') . '"
              alt="' . esc_attr($img['alt'] ?? '') . '">';
}
```

### Forcer la régénération

```php
$data = $cache->post(42, force: true);
$data = $cache->term(7, 'courses', force: true);
$data = $cache->user(1, force: true);
```

### Invalidation manuelle

```php
$cache->invalidatePost(42);
$cache->invalidateTerm(7, 'courses');
$cache->invalidateUser(1);
$cache->invalidateAll();  // retourne le nombre de fichiers supprimés
```

---

## Structure des données résolues

| Type CFDev | Structure retournée |
|------------|---------------------|
| `text`, `textarea`, `select`, `toggle`, `yesno`… | Valeur brute (`string`) |
| `image` | `['id' => int, 'alt' => string, 'full' => string, 'medium' => string, 'thumbnail' => string, …]` |
| `gallery` | `[ ['id' => …, 'alt' => …, 'full' => …], … ]` |
| `file` | `['id' => int, 'url' => string, 'filename' => string]` |
| `link` | `['url' => string, 'text' => string, 'target' => string]` |
| `checkboxes` / `multi_select` | `['val1', 'val2', …]` |
| `bundle` | `[ ['field_a' => val, 'field_b' => val], … ]` — tableau de lignes |

> L'`alt` d'une image est lu depuis `_wp_attachment_image_alt`. Si vide, fallback sur le titre du fichier dans la médiathèque.

---

## Structure des fichiers cache

Chaque fichier est un JSON dans `wp-content/uploads/cfdev-cache/`.

**Convention de nommage :**

| Objet | Clé fichier |
|-------|-------------|
| Post ID 42 | `post_42.tmp` |
| Term ID 7, taxonomie `courses` | `term_courses_7.tmp` |
| User ID 1 | `user_1.tmp` |

**Exemple** `post_42.tmp` — seuls les groupes dont les conditions correspondent à ce post sont présents :

```json
{
    "post_id": 42,
    "generated_at": 1747612800,
    "groups": {
        "home_hero": {
            "hero_title": "Bienvenue",
            "hero_image": {
                "id": 15,
                "alt": "Photo hero",
                "full": "https://example.com/wp-content/uploads/hero.jpg",
                "medium": "https://example.com/wp-content/uploads/hero-300x200.jpg",
                "thumbnail": "https://example.com/wp-content/uploads/hero-150x150.jpg"
            },
            "hero_slides": [
                { "slide_title": "Slide 1", "slide_image": { "id": 16, "full": "…" } },
                { "slide_title": "Slide 2", "slide_image": { "id": 17, "full": "…" } }
            ]
        }
    }
}
```

Un groupe conditionné à une autre page (ex. `onlyForId(1)` sur la page d'accueil) **n'apparaît pas** dans ce fichier si le post #42 n'est pas la page d'accueil.