# Pages d'options

[← README](../../readme.md) · [English](../en/options-page.md)

---

Une page d'options stocke les valeurs des champs dans `wp_options` — sans lien avec un article, un terme ou un utilisateur. C'est l'outil pour les réglages globaux du site : coordonnées, réseaux sociaux, mode maintenance, couleurs par défaut, etc.

---

## 1. Déclaration rapide

```php
add_action('init', static function (): void {

    register_cfdev_options_page('site_settings', 'Réglages du site', [
        ['id' => '_site_nom',       'type' => 'text',  'label' => 'Nom du site', 'required' => true],
        ['id' => '_site_email',     'type' => 'email', 'label' => 'E-mail de contact'],
        ['id' => '_site_telephone', 'type' => 'tel',   'label' => 'Téléphone'],
        ['id' => '_site_logo',      'type' => 'image', 'label' => 'Logo'],
    ]);

});
```

Cela crée une **entrée de menu principale** dans la barre latérale de l'administration WordPress.

---

## 2. Lire les valeurs

Chaque champ est stocké comme une entrée `wp_options` indépendante. La lecture se fait avec la fonction WordPress native :

```php
$nom   = get_option('_site_nom');
$email = get_option('_site_email');
$logo  = \Weblitzer\CFDev\Field::decodeMetaValue(get_option('_site_logo'));
// $logo['url'], $logo['alt'], $logo['id'], $logo['sizes']['thumbnail']['url']
```

> `wp_options` est déjà mis en cache par l'object cache de WordPress — aucune couche de cache supplémentaire n'est nécessaire pour les champs plats.

---

## 3. Sous-pages

### Sous votre propre page autonome

Chaînez `addSubPage()` pour imbriquer des pages sous la page parente que vous venez de créer :

```php
register_cfdev_options_page('marque', 'Marque', $champs_marque)
    ->addSubPage('reseaux',  'Réseaux sociaux', $champs_reseaux)
    ->addSubPage('seo',      'SEO',             $champs_seo);
```

La barre latérale admin affiche :
```
Marque
 ├─ Réseaux sociaux
 └─ SEO
```

### Sous un menu WordPress natif

Utilisez `asSubmenu()` pour attacher la page à un menu admin existant :

```php
// Sous Réglages → ...
register_cfdev_options_page('contact', 'Infos contact', $champs)
    ->asSubmenu('options-general.php');

// Sous un slug de menu personnalisé
register_cfdev_options_page('couleurs', 'Couleurs', $champs)
    ->asSubmenu('mon-theme-reglages');
```

---

## 4. Layouts

Les pages d'options supportent les mêmes conteneurs de layout que les meta boxes.

### Champs plats

Le mode par défaut — passez simplement un tableau de définitions de champs :

```php
register_cfdev_options_page('contact', 'Contact', [
    ['id' => '_contact_email', 'type' => 'email', 'label' => 'E-mail'],
    ['id' => '_contact_tel',   'type' => 'tel',   'label' => 'Téléphone'],
]);
```

### Bundle — groupe de lignes répétables

```php
register_cfdev_options_page('equipe', 'Équipe', [
    'bundle', '_membres_equipe', [
        ['id' => '_me_nom',   'type' => 'text',  'label' => 'Nom'],
        ['id' => '_me_poste', 'type' => 'text',  'label' => 'Poste'],
        ['id' => '_me_photo', 'type' => 'image', 'label' => 'Photo'],
    ]
]);
```

Lire le bundle :

```php
$lignes = \Weblitzer\CFDev\Field::decodeMetaValue(get_option('_membres_equipe')) ?: [];
foreach ($lignes as $membre) {
    echo esc_html($membre['_me_nom'] ?? '');
}
```

### Tabs (onglets)

```php
register_cfdev_options_page('global', 'Réglages globaux', [
    'tabs', [
        'Général' => [
            ['id' => '_global_nom',   'type' => 'text',  'label' => 'Nom du site'],
            ['id' => '_global_email', 'type' => 'email', 'label' => 'E-mail'],
        ],
        'Réseaux' => [
            ['id' => '_global_fb', 'type' => 'url', 'label' => 'Facebook'],
            ['id' => '_global_ig', 'type' => 'url', 'label' => 'Instagram'],
        ],
    ]
]);
```

### Accordion

```php
register_cfdev_options_page('theme', 'Options du thème', [
    'accordion', [
        'Typographie' => [
            ['id' => '_theme_police', 'type' => 'select', 'label' => 'Police',
             'options' => ['serif' => 'Serif', 'sans' => 'Sans-serif']],
            ['id' => '_theme_taille', 'type' => 'number', 'label' => 'Taille de base (px)'],
        ],
        'Couleurs' => [
            ['id' => '_theme_primaire',    'type' => 'color', 'label' => 'Primaire'],
            ['id' => '_theme_secondaire',  'type' => 'color', 'label' => 'Secondaire'],
        ],
    ]
]);
```

---

## 5. Validation

La validation fonctionne exactement comme pour les meta boxes — déclarez les règles en ligne :

```php
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\Url;

register_cfdev_options_page('seo', 'SEO', [
    ['id' => '_seo_titre',   'type' => 'text', 'label' => 'Titre par défaut',
     'required' => true, 'rules' => [new MinLength(5), new MaxLength(60)]],
    ['id' => '_seo_url_cano', 'type' => 'url', 'label' => 'URL canonique',
     'rules' => [new Url()]],
]);
```

Les erreurs survivent au cycle POST → redirection → GET via un transient de courte durée. Les champs invalides sont surlignés en rouge ; une bannière liste toutes les erreurs en haut de la page.

---

## 6. API REST

Marquez n'importe quel champ (ou bundle) avec `'rest' => true` pour l'exposer via l'endpoint de réglages natif de WordPress.

```php
register_cfdev_options_page('marque', 'Marque', [
    ['id' => '_marque_nom',    'type' => 'text',  'label' => 'Nom de marque', 'rest' => true],
    ['id' => '_marque_color',  'type' => 'color', 'label' => 'Couleur',       'rest' => true],
    ['id' => '_marque_logo',   'type' => 'image', 'label' => 'Logo'],   // non exposé
]);
```

Pour un bundle :

```php
register_cfdev_options_page('equipe', 'Équipe', [
    'bundle', '_equipe', $champs, ['rest' => true]
]);
```

Deux endpoints sont disponibles simultanément.

### Endpoint natif WP settings

```
GET /wp-json/wp/v2/settings
→ { "_marque_nom": "Acme", "_marque_color": "#ff0000", "_equipe": "[{...}]" }
```

Retourne des **valeurs brutes** — IDs d'attachement pour les images, bundles en JSON string. Requiert `manage_options` même en lecture (pas adapté à un usage public).

### Endpoint CFDev options

```
GET /wp-json/cfdev/v1/options/{page_id}
```

Retourne des **valeurs résolues** — images enrichies avec URL/alt/sizes, bundles décodés en tableaux :

```json
{
    "page": "marque",
    "groups": {
        "marque": {
            "_marque_nom": "Acme",
            "_marque_couleur": "#ff0000",
            "_marque_logo": {
                "id": 42, "alt": "Logo Acme",
                "full": "https://…/logo.png",
                "medium": "https://…/logo-300x100.png"
            },
            "_equipe": [
                { "_eq_nom": "Alice", "_eq_photo": { "full": "…", "medium": "…" } }
            ]
        }
    }
}
```

**Auth :** aucune — lecture publique. Préférez cet endpoint pour les frontends headless/Next.js.

Le toggle `cfdev_rest_enabled` dans **CFDev → REST API** s'applique aux deux endpoints — le désactiver coupe toutes les registrations REST de CFDev globalement.

---

## 7. Intégration dans l'admin

Les pages d'options déclarées apparaissent dans le **Dashboard CFDev** sous l'onglet **Options** avec :

- L'ID et le titre de la page
- Le badge de layout (plat / bundle / tabs / accordion)
- Le nombre de champs et la liste dépliable
- Le bouton **✎ Edit** — lien direct vers la page d'options

Les champs marqués `rest: true` apparaissent dans **CFDev → REST API** sous l'onglet **Options** avec l'endpoint natif `/wp/v2/settings`.

---

## 8. Configuration de la page

```php
$page = register_cfdev_options_page('reglages', 'Réglages', $champs);

// Capacité requise (défaut : 'manage_options')
$page->capability = 'edit_theme_options';

// Icône dashicon (pages de premier niveau uniquement)
$page->icon = 'dashicons-admin-customizer';

// Position dans le menu (pages de premier niveau, défaut : 83)
$page->menu_position = 60;
```

---

## 9. Limites — ce qui ne s'applique pas

| Propriété | Pourquoi elle ne s'applique pas |
|---|---|
| `show_admin_column` | Les colonnes de liste concernent les CPT, pas les options |
| `admin_column_sortable` | Idem |
| `ajax => true` | Fonctionne — le save AJAX utilise `update_option()` avec la capacité `manage_options` |
| `rest => true` via `register_meta()` | Les options utilisent `register_setting()` — voir [section 6](#6-api-rest) |

Les pages d'options **ne supportent pas** :
- La modale **Inspect** de l'admin (pas d'ID d'objet à inspecter — utilisez ✎ Edit à la place)
- Les conditions `onlyForId()` / `onlyForTemplate()` (celles-ci ciblent des objets post)
- Le cache fichier CFDev (`CacheManager`) — utilisez `get_option()` directement ; l'object cache de WordPress s'en charge

---

## 10. Conseils

**Une page par préoccupation.** Préférez plusieurs sous-pages ciblées plutôt qu'une seule grande liste plate. Les champs sont plus faciles à trouver, et la modale Code génère des snippets plus lisibles.

**Préfixez tous les IDs de champs.** `wp_options` est une table globale partagée par WordPress, les thèmes et tous les plugins. Préfixez toujours pour éviter les collisions : `_montheme_site_nom`, pas juste `_site_nom`.

**Évitez les gros blobs dans les champs plats.** Les bundles volumineux se sérialisent en une seule ligne `wp_options`. Pour des datasets très larges, envisagez un CPT avec un post unique.