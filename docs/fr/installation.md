# Installation

[← README](../../readme.md) · [English](../en/installation.md)

---

## Prérequis

| | Minimum | Recommandé |
|---|---|---|
| PHP | 8.2 | 8.3+ |
| WordPress | 6.5 | dernière version |

PHP 8.2 minimum : propriétés `readonly` utilisées dans `FileMime`.  
WordPress 6.5 minimum : cible les installations activement maintenues.

### Classic Editor — recommandé

CFDev utilise les **meta boxes** WordPress pour afficher les champs sur l'écran de modification. Avec WordPress 6.x et l'éditeur de blocs (Gutenberg) actif, les meta boxes sont déplacées dans une **iframe**. Cela casse certaines fonctionnalités CFDev :

- Les champs AJAX (répétables, sélecteurs post/term/user) deviennent inaccessibles
- L'éditeur **Wysiwyg** (TinyMCE) peut ne pas s'initialiser correctement

**Installez et activez le plugin [Classic Editor](https://wordpress.org/plugins/classic-editor/)** pour éviter ces problèmes. CFDev affichera un avertissement dans l'admin s'il détecte que Classic Editor n'est pas actif.

> Si vous avez désactivé l'éditeur de blocs vous-même via `add_filter('use_block_editor_for_post_type', '__return_false')`, CFDev n'affichera pas l'avertissement.

---

## Installation

**Option 1 — Upload via WordPress Admin (recommandé)**

Téléchargez le dernier `cfdev-plugin-x.x.x.zip` depuis la page [GitHub Releases](https://github.com/quidelantoine/cfdev-plugin/releases), puis uploadez-le via **WordPress Admin → Extensions → Ajouter → Envoyer une extension**.

WordPress gère l'extraction automatiquement — aucun renommage nécessaire.

**Option 2 — Copie manuelle**

Extrayez le zip, renommez le dossier en `cfdev-plugin` si nécessaire, puis copiez-le :

```bash
cp -r cfdev-plugin /chemin/vers/wp-content/plugins/
```

> ⚠️ Certains extracteurs de zip renomment le dossier d'après le nom du fichier zip (ex. `cfdev-plugin-1.0.4`). Renommez-le en `cfdev-plugin` avant de le copier dans `wp-content/plugins/`.

Puis activez le plugin dans **WordPress Admin → Extensions**.

> Aucun Composer requis. Le plugin embarque un autoloader PSR-4 natif — pas de dossier `vendor/`, pas d'étape de build.

---

## Déclarer des champs — `cfdev-fields.php`

Créez un fichier nommé **`cfdev-fields.php`** à la racine de votre thème actif. CFDev le détecte et le charge automatiquement — aucun `require` nécessaire.

```
your-theme/
└── cfdev-fields.php   ← chargé automatiquement par CFDev
```

Toutes les déclarations doivent être enveloppées dans un hook `init` :

```php
// your-theme/cfdev-fields.php

add_action('init', static function (): void {
    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price', 'type' => 'number', 'label' => 'Prix', 'required' => true],
            ['id' => 'photo', 'type' => 'image',  'label' => 'Photo'],
        ]);
});
```

---

## Séparer en plusieurs fichiers

Quand le projet grandit, séparez les déclarations par type de contenu. `cfdev-fields.php` devient un simple point d'entrée :

```
your-theme/
├── cfdev-fields.php       ← point d'entrée
└── cfdev/
    ├── post-types.php     ← CPTs et leurs meta boxes
    ├── taxonomies.php     ← taxonomies et term meta
    └── users.php          ← user meta
```

```php
// your-theme/cfdev-fields.php

require_once __DIR__ . '/cfdev/post-types.php';
require_once __DIR__ . '/cfdev/taxonomies.php';
require_once __DIR__ . '/cfdev/users.php';
```

```php
// your-theme/cfdev/post-types.php

add_action('init', static function (): void {
    register_cfdev_post_type(['product', 'products'], ['public' => true])
        ->addMetaBox('product_info', 'Infos produit', [
            ['id' => 'price',   'type' => 'number', 'label' => 'Prix'],
            ['id' => 'photo',   'type' => 'image',  'label' => 'Photo'],
        ]);

    register_cfdev_post_type(['event', 'events'], ['public' => true])
        ->addMetaBox('event_info', 'Infos événement', [
            ['id' => 'date',     'type' => 'date',   'label' => 'Date'],
            ['id' => 'location', 'type' => 'text',   'label' => 'Lieu'],
        ]);
});
```

```php
// your-theme/cfdev/taxonomies.php

add_action('init', static function (): void {
    register_cfdev_taxonomy('category', 'product')
        ->addTermMeta([
            ['id' => 'color', 'type' => 'color', 'label' => 'Couleur'],
            ['id' => 'image', 'type' => 'image', 'label' => 'Image'],
        ]);
});
```

```php
// your-theme/cfdev/users.php

add_action('init', static function (): void {
    register_cfdev_user_meta('profile', 'Profil', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
    ])->onlyForRole('administrator');
});
```

Si vous préférez charger le fichier vous-même (depuis un plugin par exemple) :

```php
// functions.php ou un plugin custom
require_once get_template_directory() . '/cfdev-fields.php';
```

---

## Traductions

L'interface d'administration est traduite en 10 langues : 🇫🇷 🇪🇸 🇩🇪 🇧🇷 🇳🇱 🇮🇹 🇯🇵 🇨🇳 🇷🇺 🇵🇱

WordPress sélectionne automatiquement la bonne locale en fonction de la langue du site (**Réglages → Général → Langue du site**). Aucune configuration nécessaire.

Pour ajouter une nouvelle langue, copiez [`languages/cfdev.pot`](../../languages/cfdev.pot) et traduisez-le avec [Loco Translate](https://localise.biz/) ou [Poedit](https://poedit.net/).

---

## Vérifier l'installation

Après avoir activé le plugin et ajouté au moins une déclaration de champ, rendez-vous dans **WordPress Admin → CFDev**. Vous devriez voir la liste de tous les groupes de champs enregistrés.

---

## Suivant

→ [Démarrage rapide](demarrage-rapide.md)
