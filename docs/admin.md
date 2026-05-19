# CFDev — Interface d'administration

CFDev ajoute un menu **CFDev** dans la barre latérale de WordPress. Les pages sont accessibles uniquement aux utilisateurs ayant la capacité `manage_options`.

---

## Pages disponibles

### Tableau de bord (`/wp-admin/admin.php?page=cfdev`)

Vue d'ensemble du plugin. Réservée à un usage futur (statistiques, état général, liens rapides).

---

### Champs (`/wp-admin/admin.php?page=cfdev-fields`)

Liste tous les groupes de champs enregistrés via `Registry`, organisés par contexte.

**Onglets :**

| Onglet | Contenu |
|--------|---------|
| Posts | Un sous-onglet par post type (ex. `Page`, `Article`, `Leçon`) |
| Terms | Groupes assignés aux taxonomies |
| Users | Groupes assignés aux profils utilisateur |

**Informations affichées par groupe :**

- **Titre** du groupe, badge de layout (`[flat]`, `[tabs]`, `[accordion]`, `[bundle]`)
- **Cibles** : tags affichant les post types / taxonomies / `all users`
- **Conditions** : badge `[Conditionnel]` si le groupe a un `onlyForTemplate` ou `onlyForPost`
- **Aussi dans :** liste des autres post types si le groupe est multi-cible
- **Champs** dans un tableau : nom, type, obligatoire
- **Sections** (tabs/accordion) : chaque onglet ou section affiché avec son titre ; les bundles imbriqués ont leur propre bloc

---

### Réglages (`/wp-admin/admin.php?page=cfdev-settings`)

Page réservée aux futurs réglages globaux du plugin.

---

### Cache (`/wp-admin/admin.php?page=cfdev-cache`)

Visualisation, gestion et activation du cache fichiers.

**Toggle en haut de page :** active ou désactive le système de cache `.tmp` dans `wp-content/uploads/cfdev-cache/`.

| État | Comportement |
|------|-------------|
| **Actif** | Données lues depuis le fichier si présent et non expiré (TTL 24 h). Nouveau fichier écrit après génération. |
| **Inactif** | Données toujours lues en direct depuis la base. Aucun fichier créé ni lu. |

> **Recommandation :** désactiver en développement, activer en production.

Le cache est invalidé automatiquement à chaque sauvegarde (article, terme, utilisateur).

**Tableau des fichiers :**

| Colonne | Description |
|---------|-------------|
| Objet | Titre de l'article / nom du terme / nom de l'utilisateur + nom du fichier `.tmp` |
| Type | Post type réel (ex. `Page`, `Leçon`) ou taxonomie, ou `Utilisateur` |
| Groupes | Tags avec les titres des groupes de champs présents dans ce fichier |
| Taille | Taille du fichier JSON |
| Âge | Temps écoulé depuis la génération |
| Modifié | Date et heure de dernière écriture |
| Action | Bouton **Supprimer** pour invalider un fichier individuel |

Les lignes dont l'âge dépasse 24 h affichent un badge **Expiré**.

---

### Get Data (`/wp-admin/admin.php?page=cfdev-getdata`)

Page de debug pour visualiser les données cachées. Réservée à un usage futur.

---

## Système de cache — Utilisation dans les templates

### Prérequis

Le cache doit être **activé** sur la page Cache de l'admin. Sans cela, `CacheManager` génère les données en direct sans les stocker (comportement identique, sans gain de performance).

> Même avec le cache désactivé, la syntaxe d'appel est identique — pas besoin de changer le code du template pour passer de dev à prod.

---

### Post — texte, image, fichier

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$data  = $cache->post(get_the_ID());

$hero     = $data['groups']['home_hero'] ?? [];
$titre    = $hero['hero_title']    ?? '';
$image    = $hero['hero_image']    ?? [];
$fichier  = $hero['hero_file']     ?? [];

// Image : toutes les tailles générées par WordPress sont disponibles
// $image = ['id' => 15, 'alt' => 'Photo hero', 'full' => '…', 'medium' => '…', 'thumbnail' => '…']
echo '<img src="' . esc_url($image['medium'] ?? $image['full'] ?? '') . '" alt="' . esc_attr($image['alt'] ?? '') . '">';

// Fichier
// $fichier = ['id' => 8, 'url' => '…/doc.pdf', 'filename' => 'doc.pdf']
echo '<a href="' . esc_url($fichier['url'] ?? '') . '">' . esc_html($fichier['filename'] ?? '') . '</a>';
```

---

### Post — galerie

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$gallery = $data['groups']['mon_groupe']['ma_gallery'] ?? [];

// $gallery = [
//   ['id' => 12, 'alt' => 'Photo 1', 'full' => '…', 'medium' => '…'],
//   ['id' => 14, 'alt' => 'Photo 2', 'full' => '…', 'medium' => '…'],
// ]

foreach ($gallery as $img) {
    echo '<img src="' . esc_url($img['medium'] ?? $img['full']) . '" alt="' . esc_attr($img['alt']) . '">';
}

// Taille préférée avec fallback sur full
$src = $img['large'] ?? $img['medium-large'] ?? $img['medium'] ?? $img['full'] ?? '';
```

---

### CPT Leçons — récupérer les meta d'une leçon

Champs déclarés dans `cfdev/fields/lessons.php` — groupe `meta_home_intro` :

```php
$cache       = new \Weblitzer\CFDev\Cache\CacheManager();
$lesson_data = $cache->post($lesson->ID);
$intro       = $lesson_data['groups']['meta_home_intro'] ?? [];

$texte3 = $intro['_text_home_partner_text3'] ?? '';  // text (obligatoire)
$texte1 = $intro['_text_home_partner_text1'] ?? '';  // textarea
```

---

### Taxonomie Modules — récupérer les meta d'un module

Champs déclarés dans `cfdev/fields/courses.php` — groupe `courses` :

```php
$cache       = new \Weblitzer\CFDev\Cache\CacheManager();
$module_data = $cache->term($term->term_id, 'courses');
$module      = $module_data['groups']['courses'] ?? [];

$ordre   = $module['m_term_order_module']           ?? '';  // select (1–15)
$image   = $module['m_term_img_module']              ?? [];  // image résolue
$resume  = $module['m_term_resume_module']           ?? '';  // textarea
$content = $module['_m_term_resume_module_wysiwyg']  ?? '';  // wysiwyg HTML
```

---

### Modules + Leçons — pattern archive taxonomy

Page d'archive d'un module : afficher les infos du module puis ses leçons avec leurs meta.

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$term  = get_queried_object();  // terme actuel (archive taxonomy)

// Meta du module
$module_data = $cache->term($term->term_id, 'courses');
$module      = $module_data['groups']['courses'] ?? [];

$image   = $module['m_term_img_module']   ?? [];
$resume  = $module['m_term_resume_module'] ?? '';

if (! empty($image)) {
    echo '<img src="' . esc_url($image['medium'] ?? $image['full']) . '" alt="' . esc_attr($image['alt']) . '">';
}
echo '<p>' . esc_html($resume) . '</p>';

// Leçons du module, triées par menu_order
$lessons = get_posts([
    'post_type'      => 'lessons',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'tax_query'      => [[
        'taxonomy' => 'courses',
        'field'    => 'term_id',
        'terms'    => $term->term_id,
    ]],
]);

foreach ($lessons as $lesson) {
    $lesson_data = $cache->post($lesson->ID);
    $intro       = $lesson_data['groups']['meta_home_intro'] ?? [];

    echo '<h3>' . esc_html($lesson->post_title) . '</h3>';
    echo '<p>'  . esc_html($intro['_text_home_partner_text3'] ?? '') . '</p>';
}
```

---

### User meta

Champs déclarés dans `cfdev/fields/user-profile.php` (groupe `profile`) et `cfdev/fields/user-formation.php` (groupe `meta_user_weblitzer`).

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$data  = $cache->user(get_current_user_id());

// Groupe profil
$profil = $data['groups']['profile'] ?? [];
$poste  = $profil['job_title'] ?? '';
$avatar = $profil['avatar']    ?? [];

// Afficher l'avatar
if (! empty($avatar)) {
    echo '<img src="' . esc_url($avatar['thumbnail'] ?? $avatar['full']) . '" alt="' . esc_attr($avatar['alt']) . '">';
}

// Groupe formation
$formation  = $data['groups']['meta_user_weblitzer'] ?? [];
$can_access = (bool) ($formation['user_pay_courses'] ?? false);
$user_poste = $formation['user_poste'] ?? '';

if ($can_access) {
    // Afficher les leçons accessibles
}
```

---

### Bundle (lignes répétables)

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$data  = $cache->post(get_the_ID());

$slides = $data['groups']['home_hero']['hero_slides'] ?? [];

// $slides = [
//   ['slide_title' => 'Slide 1', 'slide_image' => ['id' => 16, 'full' => '…', 'alt' => '…']],
//   ['slide_title' => 'Slide 2', 'slide_image' => ['id' => 17, 'full' => '…', 'alt' => '…']],
// ]

foreach ($slides as $slide) {
    $img = $slide['slide_image'] ?? [];
    echo '<h4>' . esc_html($slide['slide_title'] ?? '') . '</h4>';
    echo '<img src="' . esc_url($img['medium'] ?? $img['full'] ?? '') . '" alt="' . esc_attr($img['alt'] ?? '') . '">';
}
```

---

### Forcer la régénération

```php
// Ignorer le cache existant et régénérer immédiatement
$data = $cache->post(42, force: true);
$data = $cache->term(7, 'courses', force: true);
$data = $cache->user(1, force: true);
```

---

### Invalidation manuelle

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();

$cache->invalidatePost(42);
$cache->invalidateTerm(7, 'courses');
$cache->invalidateUser(1);
$cache->invalidateAll();  // supprime tous les fichiers, retourne le nombre supprimé
```

L'invalidation automatique se déclenche sur `save_post`, `edited_term`, `delete_term` et `profile_update`.

---

## Structure des champs résolus

| Type CFDev | Structure retournée |
|------------|---------------------|
| `text`, `textarea`, `select`, `yesno`, etc. | Valeur brute (string) |
| `image` | `['id' => int, 'alt' => string, 'full' => string, 'medium' => string, 'thumbnail' => string, …]` |
| `gallery` | `[ ['id' => …, 'alt' => …, 'full' => …, …], … ]` |
| `file` | `['id' => int, 'url' => string, 'filename' => string]` |
| `link` | `['url' => string, 'text' => string, 'target' => string]` |
| `checkboxes` / `multi_select` | `['val1', 'val2', …]` |
| `bundle` | `[ ['field_a' => val, 'field_b' => val], … ]` — tableau de lignes |

> **Note :** l'`alt` d'une image est lu depuis la médiathèque WordPress (`_wp_attachment_image_alt`). Si non renseigné, fallback automatique sur le titre du fichier dans la médiathèque.

---

## Structure des fichiers cache

Chaque fichier est un JSON dans `wp-content/uploads/cfdev-cache/`.

**Convention de nommage :**

| Objet | Clé |
|-------|-----|
| Post ID 42 | `post_42` |
| Term ID 7, taxonomie `courses` | `term_courses_7` |
| User ID 1 | `user_1` |

**Exemple** `post_42.tmp` :

```json
{
    "post_id": 42,
    "generated_at": 1747612800,
    "groups": {
        "meta_home_intro": {
            "_text_home_partner_text3": "Texte 3",
            "_text_home_partner_text1": "Texte long ici"
        },
        "home_hero": {
            "hero_title": "Bienvenue",
            "hero_image": {
                "id": 15,
                "alt": "Photo hero",
                "full": "https://example.com/wp-content/uploads/hero.jpg",
                "medium": "https://example.com/wp-content/uploads/hero-300x200.jpg",
                "thumbnail": "https://example.com/wp-content/uploads/hero-150x150.jpg"
            },
            "hero_gallery": [
                { "id": 16, "alt": "Slide 1", "full": "…", "medium": "…" },
                { "id": 17, "alt": "Slide 2", "full": "…", "medium": "…" }
            ]
        }
    }
}
```