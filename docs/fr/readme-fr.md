# CFDev — Champs meta personnalisés code-first pour WordPress

> Déclarez vos champs en PHP. Pas d'interface admin à configurer. Pas de dérive de config en base de données.

[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net/)
[![WordPress 6.5+](https://img.shields.io/badge/WordPress-6.5%2B-blue)](https://wordpress.org/)

[![PHPStan](https://img.shields.io/badge/PHPStan-niveau%208-brightgreen)](../../phpstan.neon)
[![PHPCS](https://img.shields.io/badge/PHPCS-WordPress--VIP--Go-blue)](../../phpcs.xml)
[![Tests unitaires](https://img.shields.io/badge/tests%20unitaires-1360%20ok-brightgreen)](../../tests/Unit)
[![Tests intégration](https://img.shields.io/badge/tests%20intégration-228%20ok-brightgreen)](../../tests/Integration)
[![Cypress](https://img.shields.io/badge/Cypress-65%20specs%20%7C%2016%20fichiers-brightgreen)](../../cypress/e2e)

**[English](../../readme.md)** · **[Documentation française](installation.md)**

---

## C'est quoi CFDev ?

CFDev est un plugin WordPress pour les développeurs qui veulent déclarer leurs champs en PHP et ne plus jamais toucher à la config en base. Post meta, term meta, user meta — tout est enregistré dans le code, versionné avec le projet, déployé sans script de migration.

**Ce que vous obtenez :**

- **30+ types de champs** — texte, image, fichier, select, checkboxes, date, wysiwyg, couleur, galerie, lien, et bien d'autres
- **Post, term et user meta** — attachez des meta boxes à n'importe quel CPT ou taxonomie, et des groupes de champs aux profils utilisateurs
- **Conteneurs de layout** — organisez vos champs en **Bundles** (groupes de lignes répétables), **Tabs** ou **Accordéons** pour des écrans d'édition clairs et structurés
- **Champs répétables** — n'importe quel champ peut être rendu répétable avec ajout/suppression en AJAX
- **25+ règles de validation** — required, min/max, regex, email, URL… appliquées côté serveur, les erreurs survivent à la redirection
- **Cache fichier** — données résolues mises en cache sur disque, invalidées à la sauvegarde, prêtes pour la prod à fort trafic
- **Panel admin développeur** — un panneau intégré pour inspecter tous les champs enregistrés, parcourir les données en cache et vider le cache en un clic
- **REST API** — exposez n'importe quel champ via WP REST avec un simple flag `'rest' => true`

```php
register_cfdev_post_type(['book', 'books'], ['public' => true])
    ->addTaxonomy('genre')
    ->addMetaBox('book_details', 'Détails du livre', [
        ['id' => 'subtitle',  'type' => 'text',   'label' => 'Sous-titre',    'required' => true],
        ['id' => 'cover',     'type' => 'image',  'label' => 'Couverture'],
        ['id' => 'pages',     'type' => 'number', 'label' => 'Nombre de pages'],
        ['id' => 'published', 'type' => 'date',   'label' => 'Date de parution'],
    ]);
```

---

## Prérequis

| | Minimum | Recommandé |
|---|---|---|
| PHP | 8.2 | 8.3+ |
| WordPress | 6.5 | dernière version |

---

## Installation

**Option 1 — Upload via WordPress Admin (recommandé)**

Téléchargez le dernier `cfdev-plugin-x.x.x.zip` depuis [GitHub Releases](https://github.com/quidelantoine/cfdev-plugin/releases), puis uploadez-le via **WordPress Admin → Extensions → Ajouter → Envoyer une extension**.

**Option 2 — Copie manuelle**

Extrayez le zip, renommez le dossier en `cfdev-plugin` si nécessaire, puis copiez-le dans `wp-content/plugins/`.

> ⚠️ Certains extracteurs renomment le dossier d'après le zip (ex. `cfdev-plugin-1.0.4`). Renommez-le en `cfdev-plugin` avant de copier.

> Aucun Composer requis. Le plugin embarque un autoloader PSR-4 natif — pas de dossier `vendor/`, pas d'étape de build.

→ [Guide d'installation complet](installation.md)

---

## Démarrage rapide

Créez un fichier dans votre thème (ex. `cfdev-fields.php`) et chargez-le, ou utilisez `functions.php` :

```php
add_action('init', static function (): void {

    register_cfdev_post_type('product', ['public' => true])
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price',    'type' => 'number', 'label' => 'Prix',          'required' => true],
            ['id' => 'photo',    'type' => 'image',  'label' => 'Photo'],
            ['id' => 'brochure', 'type' => 'file',   'label' => 'Brochure PDF'],
        ]);

});
```

Lire les données dans votre template :

```php
$cache   = new \Weblitzer\CFDev\Cache\CacheManager();
$data    = $cache->post(get_the_ID());
$product = $data['groups']['product_info'] ?? [];

echo esc_html($product['price'] ?? '');
echo '<img src="' . esc_url($product['photo']['medium'] ?? '') . '" alt="' . esc_attr($product['photo']['alt'] ?? '') . '">';
```

---

## Documentation

| Guide | Description |
|---|---|
| [Installation](installation.md) | Prérequis, installation, build production |
| [Démarrage rapide](demarrage-rapide.md) | Premier post type, meta box et template |
| [Types de champs](champs.md) | Tous les types de champs avec options |
| [Layouts](layouts.md) | Bundle, Tabs, Accordion |
| [Validation](validation.md) | 25+ règles de validation intégrées |
| [Cache](cache.md) | Cache fichier — activation, invalidation, perf |
| [Interface admin](admin.md) | Pages admin CFDev (Champs, Cache) |
| [REST API](rest-api.md) | Exposer les champs via WP REST API |
| [Répétable & AJAX](repeatable.md) | Champs répétables et chargement AJAX |
| [Colonnes admin](colonnes-admin.md) | Colonnes dans les listes post/terme/user |
| [WooCommerce](woocommerce.md) | Champs personnalisés sur les produits et catégories |

---

## Licence

GPL-2.0-or-later
