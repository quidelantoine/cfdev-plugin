# Registry & Conditions

## Vue d'ensemble

Chaque meta box, user meta et term meta déclaré via CFDev est automatiquement enregistré dans `CFDev\Registry`. Aucune action de ta part — l'enregistrement se fait dans le constructeur de chaque classe.

Le Registry permet :
- d'avoir une vue d'ensemble de tous les champs déclarés dans le code
- de détecter les IDs dupliqués sur le même post type / taxonomie / contexte
- d'alimenter une page d'administration récapitulative (à venir)

---

## Conditions d'affichage

Les conditions restreignent l'affichage (et la sauvegarde) d'un meta box à un contexte précis.
Elles se chaînent après la déclaration et n'ont aucun effet sur le code existant sans conditions.

### MetaBox — par ID de post

```php
$pages = new \CFDev\PostType('page');

$pages->addMetaBox('home_hero', 'Bloc Hero', $fields)
    ->onlyForId(42);
```

Le meta box `home_hero` ne s'affiche que lorsqu'on édite le post dont l'ID est `42`.
La sauvegarde est aussi bloquée pour tout autre ID — même si le formulaire envoie des données.

---

### MetaBox — par template de page

```php
$pages->addMetaBox('contact_map', 'Carte', $fields)
    ->onlyForTemplate('template-contact.php');
```

Le meta box s'affiche uniquement sur les pages qui utilisent le template `template-contact.php`.
Le slug correspond à ce que retourne `get_page_template_slug()`.

> Fonctionne sur tout post type qui supporte `page-attributes` (pas seulement `page`).

---

### Combiner les deux conditions

```php
$pages->addMetaBox('home_seo', 'SEO Accueil', $fields)
    ->onlyForId(42)
    ->onlyForTemplate('template-home.php');
```

Les deux conditions doivent être vraies simultanément.

---

### Chaînage avec d'autres meta boxes

Le chaînage sur `ContentType` est préservé :

```php
$pages
    ->addMetaBox('home_hero', 'Hero', $hero_fields)
        ->onlyForId(42)
    ->addMetaBox('seo', 'SEO', $seo_fields)   // pas de condition : affiché partout
    ->addSupport('thumbnail');                  // toujours fonctionnel
```

> `onlyForId()` et `onlyForTemplate()` s'appliquent au **dernier** `addMetaBox()` appelé.

---

### MetaBox instancié directement (sans ContentType)

```php
$mb = new \CFDev\Meta\MetaBox('home_hero', 'Hero', 'page', $fields);
$mb->onlyForId(42);
```

---

### UserMeta — par rôle utilisateur

```php
$userMeta = new \CFDev\Meta\UserMeta('admin_extra', 'Options admin', $fields);
$userMeta->onlyForRole('administrator');

// Plusieurs rôles autorisés :
$userMeta->onlyForRole(['editor', 'author']);
```

Les champs s'affichent uniquement sur le profil d'un utilisateur ayant au moins un des rôles listés.

---

### TermMeta — par terme parent

```php
$termMeta = new \CFDev\Meta\TermMeta('formation', $fields);
$termMeta->onlyIfParent(12);  // ID du terme parent
```

- **Formulaire d'édition** : affiche les champs uniquement si `$term->parent === 12`.
- **Formulaire d'ajout** : lit `$_GET['parent']` — les champs apparaissent si l'URL contient `?parent=12`.

---

## Consulter le Registry

```php
// Tous les meta boxes enregistrés
$entries = \CFDev\Registry::all();

// Structure d'une entrée :
// [
//   'id'         => 'home_hero',
//   'title'      => 'Hero',
//   'meta_type'  => 'post',                        // 'post' | 'user' | 'term'
//   'targets'    => ['page'],                       // post types / locations / taxonomies
//   'layout'     => 'flat',                         // 'flat' | 'tabs' | 'accordion' | 'bundle'
//   'conditions' => ['post_id' => 42],              // vide si aucune condition
//   'source'     => 'unknown',                      // 'theme' | 'demo' à venir
//   'fields'     => [
//       // champs plats uniquement (headings et champs in_bundle exclus)
//       'hero_title' => ['type' => 'text', 'label' => 'Titre', 'required' => false],
//   ],
//   'bundles'    => [
//       // un Bundle par clé (id du bundle) — vide si layout 'flat'
//       '_slides' => [
//           'fields' => [
//               'slide_title' => ['type' => 'text', 'label' => 'Titre', 'required' => true],
//               'slide_image' => ['type' => 'image', 'label' => 'Image', 'required' => false],
//           ],
//       ],
//   ],
// ]

// Détecter les doublons d'ID de champ
$dups = \CFDev\Registry::duplicates();
// Ex. : ['hero_title' => ['home_hero', 'page_intro']]
// → le champ 'hero_title' est déclaré deux fois sur le même post type
```

---

## Résumé des conditions par type

| Condition | MetaBox | UserMeta | TermMeta |
|---|---|---|---|
| `onlyForId(int $id)` | ✅ | — | — |
| `onlyForTemplate(string $tpl)` | ✅ | — | — |
| `onlyForRole(string\|array $roles)` | — | ✅ | — |
| `onlyIfParent(int $parent_id)` | — | — | ✅ |