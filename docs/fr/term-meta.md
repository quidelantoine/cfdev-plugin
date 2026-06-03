# Term Meta

[← README](../../readme.md) · [English](../en/term-meta.md)

---

La term meta permet d'attacher des champs personnalisés aux termes de taxonomie — catégories, étiquettes, ou toute taxonomie personnalisée. Les champs apparaissent sur le formulaire **Ajouter un terme**, le formulaire **Modifier le terme**, ou les deux.

---

## 1. Enregistrement rapide — via le chaînage de taxonomie

La méthode la plus rapide quand vous créez déjà la taxonomie avec CFDev :

```php
add_action('init', static function (): void {

    register_cfdev_taxonomy('genre', 'product')
        ->addTermMeta([
            ['id' => 'color',       'type' => 'color',    'label' => 'Couleur'],
            ['id' => 'icon',        'type' => 'image',    'label' => 'Icône'],
            ['id' => 'description', 'type' => 'textarea', 'label' => 'Description'],
        ]);

});
```

`addTermMeta()` affiche les champs sur les formulaires Ajouter **et** Modifier par défaut.

---

## 2. Enregistrement standalone — sur les taxonomies existantes

Pour les taxonomies natives de WordPress (`category`, `post_tag`) ou toute taxonomie enregistrée par un autre plugin ou thème, instanciez `TermMeta` directement :

```php
use Weblitzer\CFDev\Meta\TermMeta;

add_action('init', static function (): void {

    new TermMeta('category', 'Options de catégorie', [
        ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
        ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
    ]);

});
```

---

## 3. Contrôler l'emplacement des champs

Le quatrième paramètre de `TermMeta` définit les formulaires concernés. Par défaut : `['edit_form']`.

```php
// Formulaire de modification uniquement (défaut)
new TermMeta('category', 'Options', $fields, ['edit_form']);

// Formulaire d'ajout uniquement
new TermMeta('category', 'Options', $fields, ['add_form']);

// Les deux formulaires
new TermMeta('category', 'Options', $fields, ['add_form', 'edit_form']);
```

> **Note :** les champs ajoutés via `register_cfdev_taxonomy()->addTermMeta()` utilisent `['add_form', 'edit_form']` par défaut.

---

## 4. Plusieurs taxonomies en une déclaration

Passez un tableau pour attacher les mêmes champs à plusieurs taxonomies en même temps :

```php
new TermMeta(['category', 'post_tag'], 'Apparence', [
    ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
    ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
]);
```

---

## 5. Restriction hiérarchique — `onlyIfParent()`

Limitez les champs aux termes qui sont **enfants directs** d'un terme parent donné. Utile pour les taxonomies hiérarchiques (ex. catégories avec sous-catégories).

### Via le chaînage de taxonomie

```php
register_cfdev_taxonomy('product_cat', 'product')
    ->addTermMeta([
        ['id' => 'badge', 'type' => 'text', 'label' => 'Badge sous-catégorie'],
    ])
    ->onlyIfParent(12); // uniquement pour les enfants directs du terme ID 12
```

### Via instanciation directe

```php
(new TermMeta('category', 'Champs sous-catégorie', $fields))
    ->onlyIfParent(12);
```

> Sur le formulaire d'ajout, CFDev lit le parent depuis `$_GET['parent']` si présent.
> Dans les réponses REST, les champs de ce groupe sont supprimés pour les termes dont le parent ne correspond pas.
>
> **Où le voir en back-office :** créer ou modifier un terme enfant direct du parent (ID 12 dans l'exemple). Le groupe est toujours visible dans le Dashboard CFDev avec le badge `Parent : 12 — Nom du terme`.

---

## 6. Restriction à un terme unique — `onlyForId()`

Limite les champs à **un seul terme précis**. Utile pour une catégorie "en vedette" ou "page d'accueil" qui a des champs supplémentaires qu'aucun autre terme ne partage.

```php
// Via la chaîne de taxonomie
register_cfdev_taxonomy('category', 'post')
    ->addTermMeta([
        ['id' => 'featured_color',  'type' => 'color', 'label' => 'Couleur vedette'],
        ['id' => 'featured_banner', 'type' => 'image', 'label' => 'Bannière'],
    ])
    ->onlyForId(7); // uniquement pour la catégorie "En vedette" (term ID 7)

// Via instanciation directe
(new TermMeta('category', 'Champs catégorie homepage', $fields))
    ->onlyForId(7);
```

> Le formulaire d'ajout est toujours masqué — l'ID du terme n'existe pas avant la sauvegarde.
> Dans les réponses REST, les champs sont supprimés pour tous les termes sauf celui-ci.
> Combinable avec `onlyIfParent()` — les deux conditions doivent passer simultanément.
>
> **Où le voir en back-office :** naviguer dans la liste de la taxonomie, puis cliquer **Modifier** sur le terme spécifique (ID 7 dans l'exemple). Le groupe est toujours visible dans le Dashboard CFDev avec le badge `ID : 7 — Nom du terme`, quel que soit le terme en cours d'édition.

---

## 7. Layouts

### 7.1 Champs plats

```php
new TermMeta('category', 'Méta catégorie', [
    ['id' => 'color',    'type' => 'color',    'label' => 'Couleur'],
    ['id' => 'image',    'type' => 'image',    'label' => 'Image'],
    ['id' => 'subtitle', 'type' => 'text',     'label' => 'Sous-titre'],
    ['id' => 'intro',    'type' => 'textarea', 'label' => 'Texte d\'intro'],
]);
```

### 7.2 Bundle — lignes répétables

```php
new TermMeta('category', 'Galerie', [
    'bundle',
    '_gallery_items',
    [
        ['id' => 'image',   'type' => 'image', 'label' => 'Image',   'required' => true],
        ['id' => 'caption', 'type' => 'text',  'label' => 'Légende'],
    ],
]);
```

### 7.3 Tabs

Les labels des onglets sont les clés du tableau :

```php
new TermMeta('category', 'Champs catégorie', [
    'tabs',
    [
        'Identité' => [
            ['id' => 'color',    'type' => 'color',  'label' => 'Couleur'],
            ['id' => 'image',    'type' => 'image',  'label' => 'Image'],
            ['id' => 'subtitle', 'type' => 'text',   'label' => 'Sous-titre'],
        ],
        'SEO' => [
            ['id' => 'seo_title', 'type' => 'text',     'label' => 'Titre SEO'],
            ['id' => 'seo_desc',  'type' => 'textarea', 'label' => 'Meta description'],
        ],
    ],
]);
```

### 7.4 Accordéon

Même structure que les Tabs, affiché en sections repliables :

```php
new TermMeta('category', 'Champs catégorie', [
    'accordion',
    [
        'Affichage' => [
            ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
            ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
        ],
        'Contenu' => [
            ['id' => 'intro', 'type' => 'wysiwyg', 'label' => 'Introduction'],
        ],
    ],
]);
```

### 7.5 Bundle dans une section d'Accordéon

```php
new TermMeta('category', 'Champs catégorie', [
    'accordion',
    [
        'Infos' => [
            ['id' => 'subtitle', 'type' => 'text',  'label' => 'Sous-titre'],
            ['id' => 'color',    'type' => 'color', 'label' => 'Couleur'],
        ],
        'Galerie' => [
            ['bundle', '_photos', [
                ['id' => 'image',   'type' => 'image', 'label' => 'Image',   'required' => true],
                ['id' => 'caption', 'type' => 'text',  'label' => 'Légende'],
            ]],
        ],
    ],
]);
```

---

## 8. Lire les term meta

### Sans cache — méta directe

```php
$color = get_term_meta($term->term_id, 'color', true);

// Pour les types complexes (image, fichier, lien…) décodez la valeur stockée :
$image_id = get_term_meta($term->term_id, 'image', true);
echo wp_get_attachment_image($image_id, 'medium');
```

**Fonctions helper** — CFDev fournit deux raccourcis qui gèrent le décodage automatiquement :

```php
// Retourne la valeur décodée (n'importe quel type)
$color = get_cfdev_term_meta($term->term_id, 'category', 'color');

// Le paramètre terme accepte aussi un slug
$image = get_cfdev_term_meta('news', 'category', 'image');

// Retourne toutes les méta du terme (tableau associatif)
$all = get_cfdev_term_meta($term->term_id, 'category');

// Affiche la valeur directement (champs scalaires uniquement)
the_cfdev_term_meta($term->term_id, 'category', 'color');
```

### Avec cache (recommandé)

```php
$cache = new \Weblitzer\CFDev\Cache\CacheManager();
$data  = $cache->term($term->term_id, 'category');
$group = $data['groups']['category'] ?? [];

// Champ scalaire
echo esc_html($group['color'] ?? '');

// Image (toutes les tailles résolues)
$img = $group['image'] ?? [];
echo '<img src="' . esc_url($img['medium'] ?? '') . '" alt="' . esc_attr($img['alt'] ?? '') . '">';

// Lignes de bundle
$photos = $group['_photos'] ?? [];
foreach ($photos as $photo) {
    echo '<img src="' . esc_url($photo['image']['medium'] ?? '') . '">';
}
```

> La clé du groupe correspond à l'id de `TermMeta`, qui vaut par défaut le nom de la première taxonomie.
> Le cache est invalidé automatiquement sur `edited_term` et `delete_term`.

---

## 9. REST API

Exposez des champs individuels ou des bundles entiers via `'rest' => true` :

```php
new TermMeta('category', 'Méta catégorie', [
    ['id' => 'color', 'type' => 'color', 'label' => 'Couleur', 'rest' => true],
    ['id' => 'image', 'type' => 'image', 'label' => 'Image',   'rest' => true],
    ['id' => 'notes', 'type' => 'text',  'label' => 'Notes'],  // non exposé
]);
```

Accédez aux données :

```
GET /wp-json/cfdev/v1/term/category/{id}
```

```json
{
    "id": 5,
    "taxonomy": "category",
    "groups": {
        "category": {
            "color": "#e74c3c",
            "image": { "url": "...", "medium": "...", "alt": "..." }
        }
    }
}
```

> L'API REST doit être activée globalement dans **WordPress Admin → CFDev → REST API**.

---

## 10. Colonnes admin

Affichez une valeur de term meta comme colonne dans la liste des termes :

```php
new TermMeta('category', 'Méta catégorie', [
    ['id' => 'color', 'type' => 'color', 'label' => 'Couleur', 'show_admin_column' => true],
]);
```

---

## Suivant

→ [User Meta](user-meta.md) · [Types de champs](champs.md) · [Layouts](layouts.md) · [Cache](cache.md) · [REST API](rest-api.md)