# User Meta

[← README](../../readme.md) · [English](../en/user-meta.md)

---

La user meta permet d'attacher des champs personnalisés aux profils utilisateurs. Les champs apparaissent sur la page **Votre profil** (profil propre) et/ou l'écran admin **Modifier l'utilisateur**.

---

## 1. Enregistrement rapide — fonction helper

```php
add_action('init', static function (): void {

    register_cfdev_user_meta('profile', 'Profil', [
        ['id' => 'avatar',    'type' => 'image',   'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',    'label' => 'Poste'],
        ['id' => 'bio',       'type' => 'wysiwyg', 'label' => 'Bio'],
        ['id' => 'website',   'type' => 'url',     'label' => 'Site web'],
    ]);

});
```

Par défaut, la section apparaît sur la page de profil propre **et** sur l'écran admin "Modifier l'utilisateur".

---

## 2. Enregistrement standalone — classe `UserMeta`

```php
use Weblitzer\CFDev\Meta\UserMeta;

add_action('init', static function (): void {

    new UserMeta('profile', 'Profil', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
    ]);

});
```

`register_cfdev_user_meta()` et `new UserMeta()` acceptent les mêmes paramètres.

---

## 3. Emplacements — où apparaissent les champs

Contrôlez les pages de profil concernées via le quatrième paramètre :

```php
// Profil propre uniquement (l'utilisateur modifie son propre profil)
register_cfdev_user_meta('social', 'Réseaux sociaux', $fields, ['show_user_profile']);

// Écran admin "Modifier l'utilisateur" uniquement (modification d'un autre utilisateur)
register_cfdev_user_meta('admin_notes', 'Notes admin', $fields, ['edit_user_profile']);

// Les deux (défaut — identique à omettre ce paramètre)
register_cfdev_user_meta('profile', 'Profil', $fields, ['show_user_profile', 'edit_user_profile']);
```

| Clé d'emplacement | Où ça apparaît |
|---|---|
| `show_user_profile` | Quand l'utilisateur modifie son **propre** profil |
| `edit_user_profile` | Quand un admin modifie le profil d'**un autre** utilisateur |

---

## 4. Restriction par rôle — `onlyForRole()`

Affichez la section uniquement pour les utilisateurs ayant un rôle spécifique. Utile pour exposer des champs supplémentaires aux administrateurs sans alourdir le profil des utilisateurs ordinaires.

```php
// Seuls les administrateurs voient cette section
register_cfdev_user_meta('admin_meta', 'Données admin', $fields)
    ->onlyForRole('administrator');

// Plusieurs rôles acceptés
register_cfdev_user_meta('editor_meta', 'Données éditeur', $fields)
    ->onlyForRole(['editor', 'author']);
```

> La restriction s'applique au **profil en cours de modification**, pas à l'utilisateur qui fait la modification.

---

## 5. Ordre d'affichage — `priority`

Quand plusieurs sections user meta existent, contrôlez leur ordre avec le cinquième paramètre (défaut : `10`) :

```php
// Affiché en premier
register_cfdev_user_meta('basics', 'Infos de base', $fields, [], 5);

// Affiché en second (position par défaut)
register_cfdev_user_meta('contact', 'Contact', $fields, [], 10);

// Affiché en dernier
register_cfdev_user_meta('advanced', 'Avancé', $fields, [], 20);
```

---

## 6. Layouts

### 6.1 Champs plats

```php
register_cfdev_user_meta('profile', 'Profil', [
    ['id' => 'avatar',     'type' => 'image',   'label' => 'Avatar'],
    ['id' => 'job_title',  'type' => 'text',    'label' => 'Poste'],
    ['id' => 'department', 'type' => 'select',  'label' => 'Département',
        'options' => ['dev' => 'Développement', 'design' => 'Design', 'marketing' => 'Marketing']],
    ['id' => 'bio',        'type' => 'wysiwyg', 'label' => 'Bio'],
    ['id' => 'twitter',    'type' => 'url',     'label' => 'Twitter / X'],
    ['id' => 'linkedin',   'type' => 'url',     'label' => 'LinkedIn'],
]);
```

### 6.2 Bundle — lignes répétables

```php
register_cfdev_user_meta('certifications', 'Certifications', [
    'bundle',
    '_certs',
    [
        ['id' => 'title',  'type' => 'text',   'label' => 'Certification', 'required' => true],
        ['id' => 'issuer', 'type' => 'text',   'label' => 'Émetteur'],
        ['id' => 'year',   'type' => 'number', 'label' => 'Année'],
        ['id' => 'file',   'type' => 'file',   'label' => 'Certificat PDF'],
    ],
]);
```

### 6.3 Tabs

```php
register_cfdev_user_meta('full_profile', 'Profil complet', [
    'tabs',
    [
        'Identité' => [
            ['id' => 'avatar',    'type' => 'image',   'label' => 'Avatar'],
            ['id' => 'job_title', 'type' => 'text',    'label' => 'Poste'],
            ['id' => 'bio',       'type' => 'wysiwyg', 'label' => 'Bio'],
        ],
        'Réseaux' => [
            ['id' => 'twitter',  'type' => 'url', 'label' => 'Twitter / X'],
            ['id' => 'linkedin', 'type' => 'url', 'label' => 'LinkedIn'],
            ['id' => 'github',   'type' => 'url', 'label' => 'GitHub'],
        ],
        'Médias' => [
            ['id' => 'avatar',  'type' => 'image',   'label' => 'Avatar'],
            ['id' => 'banner',  'type' => 'image',   'label' => 'Bannière'],
            ['id' => 'gallery', 'type' => 'gallery', 'label' => 'Galerie'],
        ],
    ],
]);
```

### 6.4 Plusieurs sections pour différents rôles

```php
add_action('init', static function (): void {

    // Section pour tous les utilisateurs (profil propre uniquement)
    register_cfdev_user_meta('public_profile', 'Profil public', [
        ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar'],
        ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste'],
    ], ['show_user_profile'], 10);

    // Section pour les administrateurs uniquement, les deux pages, affichée en dernier
    register_cfdev_user_meta('admin_notes', 'Notes admin', [
        ['id' => 'internal_note', 'type' => 'textarea', 'label' => 'Note interne'],
        ['id' => 'account_type',  'type' => 'select',   'label' => 'Type de compte',
            'options' => ['standard' => 'Standard', 'premium' => 'Premium', 'vip' => 'VIP']],
    ], ['show_user_profile', 'edit_user_profile'], 30)
    ->onlyForRole('administrator');

});
```

---

## 7. Lire les user meta

### Sans cache — méta directe

```php
$job_title = get_user_meta($user_id, 'job_title', true);

// Image — stockée comme ID de pièce jointe
$avatar_id = get_user_meta($user_id, 'avatar', true);
$avatar    = \Weblitzer\CFDev\Field::decodeMetaValue($avatar_id);
// $avatar est maintenant un tableau avec 'url', 'medium', 'thumbnail', 'alt', etc.
```

> Pour les champs scalaires (texte, nombre, url…), `get_user_meta($id, 'clé', true)` retourne la valeur directement.
> Pour les types complexes (image, fichier, lien…), la valeur est un objet JSON encodé — utilisez `Field::decodeMetaValue()` ou le cache.

### Avec cache (recommandé)

```php
$cache    = new \Weblitzer\CFDev\Cache\CacheManager();
$data     = $cache->user(get_current_user_id());
$profile  = $data['groups']['profile'] ?? [];
$admin_n  = $data['groups']['admin_notes'] ?? [];

// Champ scalaire
echo esc_html($profile['job_title'] ?? '');

// Image (toutes les tailles résolues)
$avatar = $profile['avatar'] ?? [];
echo '<img src="' . esc_url($avatar['medium'] ?? '') . '" alt="' . esc_attr($avatar['alt'] ?? '') . '">';

// Lignes de bundle
$certs = $data['groups']['certifications']['_certs'] ?? [];
foreach ($certs as $cert) {
    echo '<strong>' . esc_html($cert['title']) . '</strong> — ' . esc_html($cert['issuer']);
}
```

> Les clés de `groups` correspondent au paramètre `$id` de `register_cfdev_user_meta()` / `new UserMeta()`.
> Le cache est invalidé automatiquement sur `profile_update`.

---

## 8. REST API

Marquez les champs avec `'rest' => true` pour les exposer via l'endpoint REST :

```php
register_cfdev_user_meta('profile', 'Profil', [
    ['id' => 'avatar',    'type' => 'image', 'label' => 'Avatar',    'rest' => true],
    ['id' => 'job_title', 'type' => 'text',  'label' => 'Poste',     'rest' => true],
    ['id' => 'private',   'type' => 'text',  'label' => 'Données privées'], // non exposé
]);
```

Accédez aux données (authentification requise) :

```
GET /wp-json/cfdev/v1/user/{id}
Authorization: Bearer <token>   (ou cookie + nonce)
```

```json
{
    "id": 1,
    "groups": {
        "profile": {
            "avatar":    { "url": "...", "medium": "...", "alt": "..." },
            "job_title": "CTO"
        }
    }
}
```

> L'endpoint utilisateur requiert toujours une authentification. L'API REST doit être activée globalement dans **WordPress Admin → CFDev → REST API**.

---

## Suivant

→ [Term Meta](term-meta.md) · [Types de champs](champs.md) · [Layouts](layouts.md) · [Cache](cache.md) · [REST API](rest-api.md)