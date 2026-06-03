# Interface admin

[← README](../../readme.md) · [English](../en/admin.md)

CFDev ajoute un menu **CFDev** dans la barre latérale WordPress. Toutes les pages nécessitent la capacité `manage_options` (administrateurs uniquement).

---

## Pages disponibles

| URL | Rôle |
|---|---|
| `?page=cfdev` | Tableau de bord — registre des groupes + inspecteur |
| `?page=cfdev-cache` | Cache — activation, liste des fichiers, vidage |
| `?page=cfdev-rest` | REST API — toggles + champs exposés |

---

## Tableau de bord

### Organisation

Les groupes sont répartis en onglets par contexte :

- **Un onglet par post type** déclaré (`Page`, `Article`, `Livre`…)
- **Termes** — groupes assignés aux taxonomies
- **Utilisateurs** — groupes assignés aux profils

Chaque groupe est un bloc rétractable affichant :

| Élément | Description |
|---|---|
| Titre + ID | Nom humain et identifiant machine |
| Badge de layout | `flat` / `tabs` / `accordion` / `bundle` |
| "Aussi dans" | Autres post types ciblés (si multi-cible) |
| Conditions | Badges `ID : 1`, `Template : …`, `Rôle : editor`… |
| Nb de champs | Total flat + champs de bundles |
| ⚙ Inspecter | Lance l'inspecteur de données pour ce groupe |
| </> Code | Ouvre le snippet PHP pour ce groupe |

### Tableau des champs

En dépliant un groupe, on voit un tableau par section / bundle :

| ID | Type | Label | Validation |
|---|---|---|---|
| `hero_title` | `text` | Titre hero | `requis` `min-length: 3` |
| `hero_image` | `image` | Image | `requis` |

La colonne **Validation** affiche un badge par règle active.

### Détection des ID de champs en double

CFDev détecte automatiquement les ID de champs qui apparaissent dans plus d'un groupe ciblant le même post type, la même taxonomie ou le même contexte utilisateur.

Lorsque des doublons sont détectés :
- Une **notice d'avertissement** apparaît en haut du tableau de bord, listant tous les ID en conflit et les groupes concernés
- Chaque champ en double est marqué d'un badge **⚠** dans sa ligne

```
⚠ ID de champs en double :
  `price`  déclaré dans  product_info, product_pricing
```

> **Note :** La détection ne s'applique qu'aux champs plats. Les champs dans un bundle sont isolés par l'ID du bundle — deux bundles partageant un nom de champ (`title`, `image`…) sur le même post type ne créent aucun conflit en base de données ni en cache.

---

## Exporter les définitions de champs

Deux boutons apparaissent sous l'en-tête du Tableau de bord :

| Bouton | Résultat |
|---|---|
| **Exporter JSON** | Fichier `.json` — lisible par machine, idéal pour outils, documentation ou migrations |
| **Exporter PHP** | Fichier `.php` — tableau PHP avec `return [...]`, prêt à coller dans `cfdev-fields.php` |

Le nom du fichier est horodaté automatiquement : `cfdev-export-YYYYMMDD-HHmmss.json`.

### Structure JSON

```json
{
  "version": "1.0.6",
  "exported_at": "2026-06-03T14:30:00+00:00",
  "groups": [
    {
      "id": "product_info",
      "title": "Info produit",
      "meta_type": "post",
      "targets": ["product"],
      "layout": "flat",
      "fields": [
        { "id": "price", "type": "number", "label": "Prix", "required": true, "args": { "min": 0 } },
        { "id": "photo", "type": "image",  "label": "Photo" }
      ]
    }
  ]
}
```

Seules les propriétés non-défaut sont incluses par champ (`required`, `repeatable`, `ajax`, `rest`, `args`, `options`, `description`, `default_value`). Un champ sans extras n'affiche que `id`, `type`, `label`.

Les groupes avec bundles incluent une clé `bundles` :

```json
"bundles": {
  "_specs": [
    { "id": "material", "type": "text", "label": "Matière" }
  ]
}
```

### Structure PHP

```php
<?php
// CFDev — Field definitions export
// Generated : 2026-06-03 14:30:00

return [
    [
        'id'        => 'product_info',
        'title'     => 'Info produit',
        'meta_type' => 'post',
        'targets'   => ['product'],
        'layout'    => 'flat',
        'fields'    => [
            ['id' => 'price', 'type' => 'number', 'label' => 'Prix', 'required' => true],
            ['id' => 'photo', 'type' => 'image',  'label' => 'Photo'],
        ],
    ],
];
```

> L'export PHP est un **snapshot de données**, pas du code `register_cfdev_*` exécutable. Utile pour documenter un projet, copier des définitions vers un autre site, ou servir de base à une migration.

---

## L'inspecteur — outil de développement

Le bouton **⚙ Inspecter** sur chaque groupe ouvre une modale sombre affichant les données réelles d'un objet choisi, directement depuis l'admin.

### À quoi ça sert

- Vérifier qu'un champ est bien sauvegardé après une saisie
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

Pour les groupes avec `onlyForId(42)` : le select est masqué et les données de l'objet #42 se chargent directement.

Changer la sélection recharge les données instantanément.

### Arbre de données

Les données s'affichent sous forme d'arbre interactif (style Symfony Profiler) :

```
▼ array(3)
    ⎘  hero_title   ⇒  "Bienvenue sur CFDev"  (22)
    ⎘  hero_image   ⇒  ▶ object(5)
    ⎘  hero_slides  ⇒  ▼ array(2)
                          ⎘  0  ⇒  ▶ object(2)
                          ⎘  1  ⇒  ▶ object(2)
```

- **▶ / ▼** — cliquer ouvre/ferme le niveau
- **(22)** — longueur de la chaîne
- Couleurs : clés en violet, strings en vert, nombres en bleu, null en gris

### Copier un chemin

Chaque ligne a un bouton ⎘ qui copie le chemin PHP complet dans le presse-papiers :

```
⎘ → $group['hero_image']['medium']
⎘ → $group['hero_slides'][0]['slide_title']
```

Un snippet d'accès global est disponible en haut de la modale :

```php
$data  = (new \Weblitzer\CFDev\Cache\CacheManager())->post(42);
$group = $data['groups']['home_hero'] ?? [];
```

### Badge de cache

| Badge | Signification |
|---|---|
| `CACHE HIT — il y a 3 min` | Les données viennent du fichier `.tmp` |
| `GENERATED` | Données générées en direct (cache OFF ou fichier absent/expiré) |
| `CACHE OFF` | Le cache est désactivé dans les réglages |

### ↺ Régénérer

Force la régénération des données (équivalent à `force: true` dans `CacheManager`). Utile pour voir les données fraîches après une modification sans vider tout le cache.

---

## Page Cache

### Toggle activer/désactiver

| État | Comportement |
|---|---|
| **Actif** | Données lues depuis `.tmp` si présent et non expiré (TTL 24 h). Fichier écrit après génération. |
| **Inactif** | Données lues en direct depuis la base. Aucun fichier créé ni lu. |

Recommandé : inactif en développement, actif en production.

L'invalidation est automatique sur `save_post`, `edited_term`, `delete_term`, `profile_update`.

### Tableau des fichiers

| Colonne | Description |
|---|---|
| Objet | Titre / nom / display name + nom du fichier `.tmp` |
| Type | Post type réel, taxonomie, ou `Utilisateur` |
| Groupes | Tags des groupes présents dans ce fichier cache |
| Taille | Taille du fichier JSON |
| Âge | Temps écoulé depuis la génération |
| Modifié | Date et heure de dernière écriture |
| Action | Bouton **Supprimer** pour invalider un fichier individuel |

Les lignes dont l'âge dépasse 24 h affichent un badge **Expiré**.

> La colonne "Groupes" ne liste que les groupes **dont les conditions correspondent** à cet objet. Un article standard n'affiche pas un groupe conditionné à la page d'accueil.

---

## Page REST API

La page REST API (`?page=cfdev-rest`) fournit deux interrupteurs et une vue complète de tous les champs exposés à l'API REST.

### Toggles

| Toggle | Comportement quand actif |
|---|---|
| **WP REST natif** | Enregistre les champs via `register_meta()` pour qu'ils apparaissent dans les réponses `/wp-json/wp/v2/` sous forme de valeurs brutes (ID image, bundle encodé JSON) |
| **CFDev API** | Active les endpoints `/wp-json/cfdev/v1/`, qui renvoient des valeurs résolues (images enrichies, bundles décodés) |

Chaque toggle est une case à cocher qui soumet automatiquement le formulaire au changement. Les deux sont **actifs par défaut**.

### Champs exposés — vue par onglets

Les champs déclarés avec `'rest' => true` sont regroupés par contexte dans des onglets :

- **Un onglet par post type** — groupes ayant au moins un champ plat ou un bundle exposé REST
- **Termes** — groupes de term meta
- **Utilisateurs** — groupes de user meta
- **Options** — pages d'options

Le badge de total dans l'en-tête de la section indique le nombre de champs REST exposés.

### Cartes de groupes

Chaque groupe est rétractable. En le déroulant, on voit un tableau REST :

| Colonne | Contenu |
|---|---|
| Clé meta | L'`id` du champ ou l'ID du bundle, avec un badge `bundle` le cas échéant |
| Label | Libellé lisible du champ |
| Type REST | Type JSON — `string`, `number`, `boolean`, ou `array` |
| Endpoint CFDev | `/wp-json/cfdev/v1/…` — lien cliquable si un objet correspondant existe en base |
| Endpoint natif | `/wp-json/wp/v2/…` — lien cliquable si un objet correspondant existe |

Les badges de layout (`flat`, `tabs`, `accordion`) et de condition (`ID : 42`, `Template : …`, `Rôle : editor`) apparaissent dans l'en-tête du groupe, comme sur le Tableau de bord.

### Modale bundle

Les lignes de type bundle affichent un bouton **⊞ Voir les champs**. Un clic ouvre une modale listant tous les champs du bundle avec leur clé meta, leur label et leur type REST. Un bundle est exposé comme un seul objet JSON — les champs individuels ne peuvent pas être sélectionnés séparément.
