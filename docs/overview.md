# CFDev — Custom Fields For Developers

> Un système de champs personnalisés **code-first** pour WordPress. Déclarez vos champs en PHP, récupérez des données prêtes à l'emploi, sans jamais toucher à l'interface d'administration pour configurer.

---

## Pourquoi CFDev ?

Les plugins de champs personnalisés traditionnels (ACF, Pods, Meta Box) reposent sur une configuration via l'admin WordPress. C'est pratique pour démarrer, mais en équipe ou en production cela pose des problèmes concrets :

- La configuration est stockée en base de données, pas en code — elle ne passe pas en git
- Un mauvais clic peut supprimer des groupes de champs en production
- Les données retournées sont brutes : les images donnent un ID, pas une URL utilisable
- Pas de validation serveur native sur les champs

**CFDev prend le parti inverse :** tout est déclaré en PHP, versionnable, prévisible, et les données sont servies pré-résolues.

---

## Ce que CFDev apporte

### 1. Déclaration code-first

Les groupes de champs sont enregistrés en PHP, dans des fichiers que vous committez. Aucune donnée de configuration en base.

```php
register_cfdev_post_type('lessons', [...])
    ->addMetaBox('lesson_details', 'Détails de la leçon', [
        ['id' => 'lesson_duration', 'type' => 'text',  'label' => 'Durée',   'required' => true],
        ['id' => 'lesson_cover',    'type' => 'image', 'label' => 'Couverture'],
        ['id' => 'lesson_files',    'type' => 'gallery', 'label' => 'Documents'],
    ]);
```

### 2. Données prêtes à l'emploi

Sans CFDev, récupérer une image demande 4 appels WordPress. Avec CFDev et son cache, une image devient directement :

```php
$image = $data['groups']['lesson_details']['lesson_cover'];
// ['id' => 15, 'alt' => 'Couverture', 'full' => '…', 'medium' => '…', 'thumbnail' => '…']
```

Les URLs de toutes les tailles générées par WordPress sont incluses. Fini les `wp_get_attachment_image_src()` dans les templates.

### 3. Cache fichier auto-invalidé

Un fichier `.tmp` par objet (post, terme, utilisateur). Généré une fois, réutilisé à chaque requête. Invalidé automatiquement à chaque sauvegarde. Zéro configuration, zéro maintenance.

### 4. Validation serveur intégrée

Plus de 25 règles de validation disponibles, directement sur les champs. Les erreurs survivent à la redirection POST → GET et s'affichent inline dans le formulaire d'édition.

### 5. Couverture complète de WordPress

Posts, pages, CPT, termes de taxonomie, profils utilisateur — tous couverts avec la même API cohérente.

---

## Installation rapide

```php
// cfdev-fields.php à la racine du thème — chargé automatiquement par le plugin

add_action('init', static function (): void {

    register_cfdev_post_type('product', ['public' => true])
        ->addMetaBox('product_info', 'Informations produit', [
            ['id' => 'price',       'type' => 'number', 'label' => 'Prix',        'required' => true],
            ['id' => 'main_image',  'type' => 'image',  'label' => 'Photo principale'],
            ['id' => 'gallery',     'type' => 'gallery','label' => 'Galerie'],
            ['id' => 'brochure',    'type' => 'file',   'label' => 'Brochure PDF'],
            ['id' => 'cta',         'type' => 'link',   'label' => 'Bouton CTA'],
        ]);
});
```

Dans le template :

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

$image   = $product['main_image'] ?? [];
$gallery = $product['gallery']    ?? [];
$cta     = $product['cta']        ?? [];

echo '<img src="' . esc_url($image['medium'] ?? $image['full'] ?? '') . '" alt="' . esc_attr($image['alt'] ?? '') . '">';

foreach ($gallery as $img) {
    echo '<img src="' . esc_url($img['medium']) . '" alt="' . esc_attr($img['alt']) . '">';
}

echo '<a href="' . esc_url($cta['url'] ?? '') . '" target="' . esc_attr($cta['target'] ?? '') . '">' . esc_html($cta['text'] ?? '') . '</a>';
```

---

## Tous les types de champs

### Texte et saisie

| Type | Description |
|------|-------------|
| `text` | Champ texte court |
| `textarea` | Texte long multi-lignes |
| `wysiwyg` | Éditeur WordPress TinyMCE |
| `number` | Nombre (avec `min`, `max`, `step`) |
| `range` | Curseur (avec `min`, `max`, `step`) |
| `email` | Adresse e-mail |
| `url` | URL |
| `tel` | Numéro de téléphone |
| `color` | Sélecteur de couleur (hex) |

### Médias

| Type | Description | Retour cache |
|------|-------------|--------------|
| `image` | Sélecteur d'image médiathèque | `{id, alt, full, medium, thumbnail, …}` |
| `gallery` | Sélection multiple d'images | `[{id, alt, full, …}, …]` |
| `file` | Sélecteur de fichier | `{id, url, filename}` |
| `link` | URL + texte + cible | `{url, text, target}` |

### Choix

| Type | Description |
|------|-------------|
| `checkbox` | Case à cocher unique (oui/non) |
| `yesno` | Bouton Oui / Non |
| `toggle` | Interrupteur on/off |
| `checkboxes` | Cases à cocher multiples (avec `options`) |
| `radios` | Boutons radio (avec `options`) |
| `select` | Liste déroulante (avec `options`) |
| `multi_select` | Liste déroulante multiple (avec `options`) |

### Dates

| Type | Description |
|------|-------------|
| `date` | Sélecteur de date |
| `datetime` | Date + heure |
| `time` | Heure |

### Relations WordPress

| Type | Description |
|------|-------------|
| `post_select` | Sélection d'un post (liste déroulante) |
| `post_checkboxes` | Sélection multiple de posts |
| `term_select` | Sélection d'un terme |
| `term_checkboxes` | Sélection multiple de termes |
| `user_select` | Sélection d'un utilisateur |
| `user_checkboxes` | Sélection multiple d'utilisateurs |

---

## Layouts avancés

### Tabs — onglets

Organise les champs en onglets cliquables. Un onglet peut contenir un bundle.

```php
->addMetaBox('product_tabs', 'Produit', [
    'tabs',
    [
        'Général'  => [/* champs */],
        'Médias'   => [/* champs */],
        'Livraison' => [
            ['bundle', [/* champs du bundle */]],
        ],
    ],
]);
```

### Accordion — sections repliables

Même structure que les tabs, affichage en accordéon.

```php
->addMetaBox('product_info', 'Produit', [
    'accordion',
    [
        'Informations' => [/* champs */],
        'Galerie'      => [
            ['bundle', [/* champs répétables */]],
        ],
    ],
]);
```

### Bundle — lignes répétables

Rangées ajoutables/supprimables dynamiquement. Chaque ligne contient les mêmes champs.

```php
->addMetaBox('team', 'Équipe', [
    'bundle',
    [
        ['id' => 'member_name',   'type' => 'text',  'label' => 'Nom',    'required' => true],
        ['id' => 'member_photo',  'type' => 'image', 'label' => 'Photo'],
        ['id' => 'member_role',   'type' => 'text',  'label' => 'Rôle'],
    ],
]);
```

Retour cache :

```php
$members = $data['groups']['team']['_team'] ?? [];
// [
//   ['member_name' => 'Alice', 'member_photo' => ['id'=>1,'full'=>'…'], 'member_role' => 'Dev'],
//   ['member_name' => 'Bob',   'member_photo' => ['id'=>2,'full'=>'…'], 'member_role' => 'Design'],
// ]
```

---

## Post Types et Taxonomies

### Enregistrement d'un CPT

```php
register_cfdev_post_type('book', ['public' => true], [
    'name'          => 'Livres',
    'singular_name' => 'Livre',
])
->addTaxonomy('genre')
->addSupport('thumbnail')
->addMetaBox('book_details', 'Détails', $fields);
```

### Enregistrement d'une taxonomie avec meta

```php
register_cfdev_taxonomy('genre', 'book', ['show_admin_column' => true])
    ->addTermMeta([
        ['id' => 'genre_color', 'type' => 'color', 'label' => 'Couleur'],
        ['id' => 'genre_image', 'type' => 'image', 'label' => 'Image'],
    ]);
```

### Meta utilisateurs

```php
(new \Weblitzer\CFDev\Meta\UserMeta('profile', 'Profil', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
]))->onlyForRole('administrator');
```

---

## Conditions d'affichage

Les groupes de champs peuvent être restreints à un contexte précis.

```php
// Visible uniquement sur un template de page spécifique
->addMetaBox('home_hero', 'Hero', $fields)
    ->onlyForTemplate('template-home.php');

// Visible uniquement sur un post précis (par ID)
->addMetaBox('about_content', 'Contenu', $fields)
    ->onlyForId(42);

// Visible uniquement pour un rôle utilisateur
(new UserMeta('admin_profile', 'Profil Admin', $fields))
    ->onlyForRole('administrator');
```

---

## Validation — 25+ règles intégrées

Les règles sont attachées directement aux champs. Les erreurs s'affichent inline dans l'éditeur sans rechargement.

```php
use Weblitzer\CFDev\Validation\Rules\{
    Required, MinLength, MaxLength, Regex,
    DateAfter, DateBefore, FileExtension, FileMime,
    ImageMinDimensions, MinItems, MaxItems
};

['id' => 'title',   'type' => 'text',  'rules' => [new Required(), new MinLength(3), new MaxLength(100)]],
['id' => 'cover',   'type' => 'image', 'rules' => [new FileExtension(['jpg','png','webp']), new ImageMinDimensions(800, 600)]],
['id' => 'gallery', 'type' => 'gallery', 'rules' => [new MinItems(1), new MaxItems(12)]],
['id' => 'pdf',     'type' => 'file',  'rules' => [new FileMime(['application/pdf'])]],
['id' => 'event',   'type' => 'date',  'rules' => [new DateAfter('2024-01-01'), new DateBefore('2030-12-31')]],
```

**Toutes les règles disponibles :**

| Catégorie | Règles |
|-----------|--------|
| Obligatoire | `Required` |
| Longueur | `MinLength`, `MaxLength`, `ExactLength` |
| Nombre | `Min`, `Max`, `Between`, `Numeric`, `Positive` |
| Items | `MinItems`, `MaxItems` |
| Format | `Alpha`, `AlphaNumeric`, `Slug`, `Email`, `Url`, `Uuid` |
| Chaîne | `Regex`, `Contains`, `StartsWith`, `EndsWith` |
| Dates | `DateAfter`, `DateBefore`, `DateAfterToday` |
| Fichiers | `FileExtension`, `FileMime` |
| Images | `ImageExactDimensions`, `ImageMinDimensions` |

---

## Système de cache

### Pourquoi le cache CFDev est différent

Le cache WordPress classique (transients, object cache) stocke des données brutes. **Le cache CFDev stocke des données déjà résolues** : les images sont enrichies de toutes leurs URLs, les bundles sont déroulés, les JSON sont décodés.

Le template n'a plus qu'à afficher. Aucune logique WordPress dans les vues.

### Activation

Page **CFDev → Cache** dans l'admin — toggle on/off sans rechargement.

- **Désactivé** (développement) : données lues en direct, modifications visibles immédiatement
- **Activé** (production) : fichier `.tmp` par objet, lu en ~1 ms, invalidé automatiquement à chaque sauvegarde

### Utilisation

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();

// Post
$data = $cache->post(get_the_ID());

// Terme
$data = $cache->term($term->term_id, 'courses');

// Utilisateur
$data = $cache->user(get_current_user_id());

// Accès aux données
$titre  = $data['groups']['mon_groupe']['mon_champ']        ?? '';
$image  = $data['groups']['mon_groupe']['mon_image']        ?? [];
$slides = $data['groups']['mon_groupe']['mon_bundle']       ?? [];
```

### Structure retournée par type

| Type | Retour |
|------|--------|
| Texte, select, etc. | `string` brute |
| `image` | `['id', 'alt', 'full', 'medium', 'thumbnail', …]` |
| `gallery` | `[['id', 'alt', 'full', …], …]` |
| `file` | `['id', 'url', 'filename']` |
| `link` | `['url', 'text', 'target']` |
| `checkboxes` / `multi_select` | `['val1', 'val2', …]` |
| `bundle` | `[['field_a' => val, 'field_b' => val], …]` |

---

## Interface d'administration

CFDev ajoute un menu dédié dans la barre WordPress, dans l'esprit d'ACF.

### CFDev → Champs

Vue complète de tous les groupes de champs enregistrés. Un onglet par post type, un pour les termes, un pour les utilisateurs. Pour chaque groupe :

- Layout affiché (flat / tabs / accordion / bundle)
- Conditions (template, ID)
- Tableau des champs avec type et statut obligatoire
- Sections nommées pour les tabs et accordéons
- Badge "Aussi dans :" quand un groupe cible plusieurs post types

### CFDev → Cache

- Toggle d'activation du cache en haut de page
- Tableau de tous les fichiers `.tmp` : titre de l'objet, post type réel, groupes de champs présents, taille, âge, date
- Badge **Expiré** après 24 h
- Suppression fichier par fichier ou vidage total

---

## Colonnes d'administration

Des colonnes personnalisées peuvent être ajoutées à la liste des posts ou utilisateurs directement depuis la déclaration du champ.

```php
['id' => 'job_title', 'type' => 'text', 'label' => 'Poste',
 'show_admin_column' => true, 'admin_column_sortable' => true],

['id' => 'avatar', 'type' => 'image', 'label' => 'Avatar',
 'show_admin_column' => true],
```

---

## Champs répétables (AJAX)

N'importe quel champ peut devenir répétable — plusieurs valeurs du même champ sur un même objet.

```php
['id' => 'partner_logo', 'type' => 'image', 'label' => 'Logo partenaire', 'repeatable' => true],
```

---

## Pour aller plus loin

| Doc | Contenu |
|-----|---------|
| `docs/install.md` | Installation et configuration |
| `docs/api_public.md` | API publique complète (`register_cfdev_post_type`, etc.) |
| `docs/fields.md` | Référence de tous les types de champs et leurs options |
| `docs/validation.md` | Toutes les règles de validation avec exemples |
| `docs/admin.md` | Interface d'administration et exemples de templates |
| `docs/registry.md` | Structure du Registry et accès programmatique |
| `docs/repeatable.md` | Champs répétables et AJAX |
| `docs/columnadmin.md` | Colonnes d'administration |
