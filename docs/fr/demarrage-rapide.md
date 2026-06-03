# Démarrage rapide

[← README](../../readme.md) · [English](../en/quick-start.md)

---

## 0. Créer `cfdev-fields.php`

Tout le code ci-dessous va dans **`your-theme/cfdev-fields.php`** — CFDev le charge automatiquement.

```
your-theme/
└── cfdev-fields.php   ← créez ce fichier, CFDev s'occupe du reste
```

Chaque déclaration doit être enveloppée dans un hook `init` :

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    // → vos déclarations ici
});
```

> Pour les projets plus grands, voir [comment séparer en plusieurs fichiers](installation.md#séparer-en-plusieurs-fichiers).

---

## 1. Enregistrer un post type avec des champs

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {

    $product = register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price',    'type' => 'number', 'label' => 'Prix',          'required' => true],
            ['id' => 'photo',    'type' => 'image',  'label' => 'Photo'],
            ['id' => 'gallery',  'type' => 'gallery','label' => 'Galerie'],
            ['id' => 'brochure', 'type' => 'file',   'label' => 'Brochure PDF'],
            ['id' => 'cta',      'type' => 'link',   'label' => 'Bouton CTA'],
        ]);

});
```

## 2. Ajouter une taxonomie

```php
register_cfdev_post_type(['product', 'products'])
    ->addTaxonomy(['category', 'categories'])
    ->addMetaBox('product_info', 'Infos produit', $fields);
```

## 3. Lire les données dans un template

### Sans cache (méta directe)

```php
$price   = get_post_meta(get_the_ID(), 'price', true);
$imageId = get_post_meta(get_the_ID(), 'photo', true);

echo esc_html($price);
echo wp_get_attachment_image($imageId, 'medium');
```

### Avec cache (recommandé — données résolues, sans requêtes supplémentaires)

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

// Champ scalaire
echo esc_html($product['price'] ?? '');

// Image (toutes les tailles résolues)
$photo = $product['photo'] ?? [];
echo '<img src="' . esc_url($photo['medium'] ?? '') . '" alt="' . esc_attr($photo['alt'] ?? '') . '">';

// Galerie
foreach ($product['gallery'] ?? [] as $img) {
    echo '<img src="' . esc_url($img['medium']) . '" alt="' . esc_attr($img['alt']) . '">';
}

// Fichier
$pdf = $product['brochure'] ?? [];
echo '<a href="' . esc_url($pdf['url'] ?? '') . '">' . esc_html($pdf['filename'] ?? '') . '</a>';

// Lien
$cta = $product['cta'] ?? [];
if (!empty($cta['url'])) {
    echo '<a href="' . esc_url($cta['url']) . '" target="' . esc_attr($cta['target'] ?? '_self') . '">'
        . esc_html($cta['text'] ?: $cta['url']) . '</a>';
}
```

## 4. Champs avec options et validation

Toutes les clés en action — `description`, `explanation`, `default_value`, `required`, `show_admin_column`, `rules`, et bien d'autres :

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;
use Weblitzer\CFDev\Validation\Rules\FileExtension;
use Weblitzer\CFDev\Validation\Rules\Email;
use Weblitzer\CFDev\Validation\Rules\Url;
use Weblitzer\CFDev\Validation\Rules\Regex;
use Weblitzer\CFDev\Validation\Rules\DateAfterToday;

add_action('init', static function (): void {

    $product = register_cfdev_post_type(['product', 'products'], ['public' => true]);

    $product->addMetaBox('product_info', 'Infos produit', [

        ['id' => 'title',
            'type'              => 'text',
            'label'             => 'Titre',
            'description'       => 'Affiché en en-tête de la fiche produit',
            'explanation'       => 'Moins de 60 caractères pour le SEO',
            'required'          => true,
            'show_admin_column' => true,
            'admin_column_sortable' => true,
            'rules'             => [new MinLength(3), new MaxLength(60)]],

        ['id' => 'price',
            'type'          => 'number',
            'label'         => 'Prix (€)',
            'description'   => 'Prix TTC',
            'default_value' => '0',
            'required'      => true,
            'show_admin_column' => true,
            'admin_column_sortable' => true,
            'args'          => ['min' => 0, 'step' => 0.01],
            'rules'         => [new Min(0), new Max(99999)]],

        ['id' => 'contact_email',
            'type'        => 'email',
            'label'       => 'E-mail de contact',
            'description' => 'Affiché dans le formulaire de demande',
            'required'    => true,
            'rules'       => [new Email()]],

        ['id' => 'website',
            'type'        => 'url',
            'label'       => 'Site web',
            'explanation' => 'Doit commencer par https://',
            'rules'       => [new Url()]],

        ['id' => 'phone',
            'type'  => 'tel',
            'label' => 'Téléphone',
            'rules' => [new Regex('/^\+?[\d\s\-\.]{7,15}$/')]],

        ['id' => 'launch_date',
            'type'        => 'date',
            'label'       => 'Date de lancement',
            'description' => 'Doit être dans le futur',
            'required'    => true,
            'args'        => ['date_format' => 'd/m/Y'],
            'rules'       => [new DateAfterToday()]],

        ['id' => 'cover',
            'type'          => 'image',
            'label'         => 'Image de couverture',
            'description'   => 'Minimum 1200 × 630 px pour le partage sur les réseaux sociaux',
            'show_admin_column' => true,
            'rules'         => [new Required(), new ImageMinDimensions(1200, 630)]],

        ['id' => 'brochure',
            'type'        => 'file',
            'label'       => 'Brochure PDF',
            'explanation' => 'PDF uniquement, 10 Mo max',
            'rules'       => [new FileExtension(['pdf'])]],

        ['id' => 'status',
            'type'          => 'select',
            'label'         => 'Statut',
            'description'   => 'Contrôle la visibilité en front-end',
            'default_value' => 'draft',
            'show_admin_column' => true,
            'options'       => ['draft' => 'Brouillon', 'published' => 'Publié', 'archived' => 'Archivé'],
            'args'          => ['show_option_none' => '— Choisir —'],
            'required'      => true],

    ]);

});
```

Les erreurs de validation survivent au cycle POST → redirection et s'affichent **inline dans le formulaire** — aucune donnée n'est perdue.

## 5. Articles et pages — champs sur les post types natifs

CFDev fonctionne sur les post types natifs de WordPress aussi bien que sur les types personnalisés. Utilisez `new PostType('post')` ou `new PostType('page')` pour attacher des meta boxes — aucun enregistrement de post type n'est nécessaire.

### Articles et pages standard

```php
use Weblitzer\CFDev\PostType;

add_action('init', static function (): void {

    // Champs sur tous les articles
    (new PostType('post'))->addMetaBox('post_extra', 'Détails de l\'article', [
        ['id' => 'subtitle', 'type' => 'text',   'label' => 'Sous-titre'],
        ['id' => 'source',   'type' => 'url',    'label' => 'URL source'],
        ['id' => 'gallery',  'type' => 'gallery','label' => 'Galerie photo'],
    ]);

    // Champs sur toutes les pages
    (new PostType('page'))->addMetaBox('page_hero', 'Section Hero', [
        ['id' => 'hero_image', 'type' => 'image', 'label' => 'Image hero'],
        ['id' => 'hero_title', 'type' => 'text',  'label' => 'Titre hero'],
        ['id' => 'hero_cta',   'type' => 'link',  'label' => 'Bouton CTA'],
    ]);

});
```

### Restreindre une meta box à un template de page spécifique

Les champs n'apparaissent que si la page utilise un template donné. Idéal pour les landing pages, pages d'accueil, etc.

```php
(new PostType('page'))
    ->addMetaBox('home_sections', 'Sections page d\'accueil', [
        ['id' => 'intro',    'type' => 'wysiwyg', 'label' => 'Introduction'],
        ['id' => 'features', 'type' => 'textarea','label' => 'Liste de fonctionnalités'],
        ['id' => 'banner',   'type' => 'image',   'label' => 'Bannière'],
    ])
    ->onlyForTemplate('template-home.php');
```

### Restreindre une meta box à une page spécifique (par ID)

Les champs n'apparaissent que sur une page précise — utile pour une page de contact, une page "à propos", etc.

```php
(new PostType('page'))
    ->addMetaBox('contact_options', 'Options de contact', [
        ['id' => 'map_embed', 'type' => 'text', 'label' => 'URL embed Google Maps'],
        ['id' => 'phone',     'type' => 'tel',  'label' => 'Numéro de téléphone'],
    ])
    ->onlyForId(42); // uniquement pour la page avec l'ID 42
```

> L'ID doit correspondre à l'ID de la page en base de données (visible dans l'URL en modification : `post=42`).

### Condition personnalisée avec `onlyWhen()`

Pour tout cas que `onlyForTemplate()` et `onlyForId()` ne couvrent pas, utilisez `onlyWhen()`.
La méthode reçoit l'objet `WP_Post` et doit retourner un `bool`. Plusieurs appels sont **combinés en AND**.

La condition s'applique à l'**affichage**, à la **sauvegarde** et à la **sortie REST** — si le callable retourne `false`, la meta box est masquée, les champs ne sont pas sauvegardés, et les valeurs sont retirées des réponses REST.

```php
// Visible uniquement pour les administrateurs
// Le deuxième argument est un label optionnel affiché comme badge dans le Dashboard
(new PostType('page'))
    ->addMetaBox('admin_notes', 'Notes internes', [
        ['id' => 'internal_note', 'type' => 'textarea', 'label' => 'Note interne'],
    ])
    ->onlyWhen(fn(\WP_Post $p) => current_user_can('manage_options'), 'Admins uniquement');

// Visible uniquement quand le post est un brouillon
(new PostType('post'))
    ->addMetaBox('draft_tools', 'Outils brouillon', [
        ['id' => 'draft_checklist', 'type' => 'textarea', 'label' => 'Checklist avant publication'],
    ])
    ->onlyWhen(fn(\WP_Post $p) => $p->post_status === 'draft');

// Visible uniquement si un autre champ meta a une valeur
(new PostType('product'))
    ->addMetaBox('variant_details', 'Détails variante', [
        ['id' => 'variant_sku', 'type' => 'text', 'label' => 'SKU variante'],
    ])
    ->onlyWhen(fn(\WP_Post $p) => !empty(get_post_meta($p->ID, 'is_variable', true)));

// Combinaison avec onlyForTemplate — les deux conditions doivent passer
(new PostType('page'))
    ->addMetaBox('landing_admin', 'Landing (admins uniquement)', [
        ['id' => 'ab_variant', 'type' => 'select', 'label' => 'Variante A/B',
         'options' => ['a' => 'A', 'b' => 'B']],
    ])
    ->onlyForTemplate('template-landing.php')
    ->onlyWhen(fn(\WP_Post $p) => current_user_can('manage_options'));
```

> **Note sur le contexte :** le callable s'exécute dans `add_meta_boxes` (affichage), `save_post` (sauvegarde) et `rest_prepare_{type}` (REST). Les trois reçoivent l'objet `WP_Post` courant — lire `$p->post_status`, `$p->post_author` ou `get_post_meta()` est sûr. Évitez de lire `$_POST` dans le callable : utilisez l'objet post à la place.

### Pages avec plusieurs layouts via Tabs

```php
(new PostType('page'))
    ->addMetaBox('page_content', 'Contenu de la page', [
        'tabs',
        [
            'Hero' => [
                ['id' => 'hero_image', 'type' => 'image', 'label' => 'Image'],
                ['id' => 'hero_title', 'type' => 'text',  'label' => 'Titre'],
                ['id' => 'hero_cta',   'type' => 'link',  'label' => 'CTA'],
            ],
            'SEO' => [
                ['id' => 'seo_title', 'type' => 'text',     'label' => 'Titre SEO'],
                ['id' => 'seo_desc',  'type' => 'textarea', 'label' => 'Meta description'],
            ],
        ],
    ])
    ->onlyForTemplate('template-home.php');
```

---

## 6. Ajouter des méta de terme et d'utilisateur

```php
// Méta de terme — apparaît sur les formulaires Ajouter et Modifier (défaut)
register_cfdev_taxonomy('genre', 'product')
    ->addTermMeta([
        ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ]);

// Méta de terme — formulaire spécifique (TermMeta direct, défaut : ['edit_form'])
// Formulaire ajout uniquement :  ['add_form']
// Les deux formulaires :         ['add_form', 'edit_form']
new \Weblitzer\CFDev\Meta\TermMeta('genre', 'Infos genre', $fields, ['add_form', 'edit_form']);

// Méta de terme — restreint aux termes enfants d'un parent donné
register_cfdev_taxonomy('genre', 'product')
    ->addTermMeta([/* champs */])
    ->onlyIfParent(12);

// Méta d'utilisateur — visible par tous (défaut : show_user_profile + edit_user_profile)
register_cfdev_user_meta('profile', 'Profil', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
])->onlyForRole('administrator');

// Méta d'utilisateur — emplacements et ordre d'affichage personnalisés
register_cfdev_user_meta(
    'social',
    'Réseaux sociaux',
    $fields,
    ['show_user_profile'],   // uniquement sur la page de son propre profil
    20                       // priority — contrôle l'ordre si plusieurs sections
);
```

---

## 6. Exemple concret — CPT avec taxonomie et meta box

Enregistrement complet sans chaînage de méthodes, utile quand on a besoin de garder une référence à chaque objet :

```php
add_action('init', static function (): void {

    $lessons = register_cfdev_post_type('lessons', [
        'public'       => true,
        'menu_icon'    => 'dashicons-welcome-learn-more',
        'has_archive'  => true,
        'supports'     => ['title', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ], [
        'name'          => 'Leçons',
        'singular_name' => 'Leçon',
    ]);

    $lessons->addMetaBox('lesson_details', 'Détails de la leçon', [
        ['id' => 'duration',   'type' => 'number', 'label' => 'Durée (min)', 'required' => true],
        ['id' => 'level',      'type' => 'select', 'label' => 'Niveau',
            'options' => ['beginner' => 'Débutant', 'intermediate' => 'Intermédiaire', 'advanced' => 'Avancé'],
            'args'    => ['show_option_none' => '— Choisir —']],
        ['id' => 'video',      'type' => 'url',    'label' => 'URL vidéo'],
        ['id' => 'attachment', 'type' => 'file',   'label' => 'Ressource PDF'],
    ]);

    // Taxonomie enregistrée indépendamment — permet la réutilisation sur plusieurs post types
    register_cfdev_taxonomy('courses', 'lessons', [
        'show_admin_column'   => true,
        'admin_column_filter' => true,
    ])
    ->addTermMeta([
        ['id' => 'color',       'type' => 'color',    'label' => 'Couleur'],
        ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
    ]);

});
```

---

## 7. Layouts

> **Important — IDs uniques par post type :** chaque `addMetaBox()` sur un même post type doit avoir un **ID différent**. Deux appels avec le même ID entraîne l'écrasement du premier par le second (comportement natif WordPress). Tous les exemples ci-dessous utilisent des IDs distincts.

### Bundle — lignes répétables de champs

Un Bundle regroupe plusieurs champs en lignes répétables. Idéal pour des membres d'équipe, des sessions, des tarifs, etc.

```php
$lessons->addMetaBox('team', 'Membres de l\'équipe', [
    'bundle',
    '_members',
    [
        ['id' => 'name',  'type' => 'text',     'label' => 'Nom',    'required' => true],
        ['id' => 'role',  'type' => 'text',     'label' => 'Poste'],
        ['id' => 'photo', 'type' => 'image',    'label' => 'Photo'],
        ['id' => 'bio',   'type' => 'textarea', 'label' => 'Bio'],
    ],
]);
```

Lire les données d'un bundle :
```php
$data    = (new \Weblitzer\CFDev\Cache\CacheManager())->post(get_the_ID());
$members = $data['groups']['team']['_members'] ?? [];

foreach ($members as $member) {
    echo '<h3>' . esc_html($member['name']) . '</h3>';
    echo '<p>'  . esc_html($member['role']) . '</p>';
    echo wp_get_attachment_image($member['photo'], 'thumbnail');
}
```

---

### Tabs — champs organisés en onglets

Les labels des onglets sont les clés du tableau. Chaque onglet contient une liste de champs.

```php
$product->addMetaBox('product_tabs', 'Produit', [
    'tabs',
    [
        'Général' => [
            ['id' => 'price',       'type' => 'number',  'label' => 'Prix'],
            ['id' => 'stock',       'type' => 'number',  'label' => 'Stock'],
            ['id' => 'description', 'type' => 'wysiwyg', 'label' => 'Description'],
        ],
        'Médias' => [
            ['id' => 'photo',   'type' => 'image',   'label' => 'Photo principale'],
            ['id' => 'gallery', 'type' => 'gallery', 'label' => 'Galerie'],
        ],
        'SEO' => [
            ['id' => 'seo_title', 'type' => 'text',     'label' => 'Titre SEO'],
            ['id' => 'seo_desc',  'type' => 'textarea', 'label' => 'Meta description'],
        ],
    ],
]);
```

---

### Accordion — champs organisés en sections dépliables

Même structure que les Tabs, affiché en accordéon.

```php
$page->addMetaBox('faq', 'FAQ', [
    'accordion',
    [
        'Livraison' => [
            ['id' => 'shipping_delay', 'type' => 'text', 'label' => 'Délai de livraison'],
            ['id' => 'shipping_price', 'type' => 'text', 'label' => 'Frais de port'],
        ],
        'Retours' => [
            ['id' => 'return_policy', 'type' => 'wysiwyg', 'label' => 'Politique de retour'],
        ],
    ],
]);
```

---

### Bundle dans un Tab

Un onglet contient des champs plats, un autre contient un bundle répétable.

```php
$formation->addMetaBox('formation', 'Formation', [
    'tabs',
    [
        'Infos' => [
            ['id' => 'intro',    'type' => 'wysiwyg', 'label' => 'Introduction'],
            ['id' => 'duration', 'type' => 'number',  'label' => 'Durée totale (h)'],
        ],
        'Sessions' => [
            ['bundle', '_sessions', [
                ['id' => 'title',    'type' => 'text',   'label' => 'Titre de la session', 'required' => true],
                ['id' => 'date',     'type' => 'date',   'label' => 'Date'],
                ['id' => 'seats',    'type' => 'number', 'label' => 'Places disponibles'],
                ['id' => 'location', 'type' => 'text',   'label' => 'Lieu'],
            ]],
        ],
    ],
]);
```

---

### Bundle dans une section d'Accordion

Une section contient des champs plats, une autre contient un bundle répétable.

```php
$product->addMetaBox('specs', 'Fiche technique', [
    'accordion',
    [
        'Dimensions' => [
            ['id' => 'weight', 'type' => 'number', 'label' => 'Poids (kg)'],
            ['id' => 'width',  'type' => 'number', 'label' => 'Largeur (cm)'],
            ['id' => 'height', 'type' => 'number', 'label' => 'Hauteur (cm)'],
        ],
        'Composants' => [
            ['bundle', '_components', [
                ['id' => 'name',     'type' => 'text',   'label' => 'Composant'],
                ['id' => 'ref',      'type' => 'text',   'label' => 'Référence'],
                ['id' => 'quantity', 'type' => 'number', 'label' => 'Qté'],
            ]],
        ],
    ],
]);
```

---

## Suivant

→ [Types de champs](champs.md) · [Layouts](layouts.md) · [Validation](validation.md) · [Cache](cache.md) · [Term Meta](term-meta.md) · [User Meta](user-meta.md)
