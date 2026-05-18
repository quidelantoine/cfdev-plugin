# Champs répétables — `repeatable`

`repeatable => true` transforme un champ en liste dynamique : l'utilisateur peut ajouter autant de valeurs qu'il veut, les réordonner par drag & drop, et en supprimer. La valeur sauvegardée en base est un tableau.

---

## Utilisation

```php
->addMetaBox('links', 'Liens', [
    [
        'id'         => 'external_urls',
        'type'       => 'text',
        'label'      => 'URL externes',
        'repeatable' => true,
    ],
]);
```

Fonctionne de la même façon dans **MetaBox**, **TermMeta** et **UserMeta**.

---

## Ce que le système génère

- Un bouton `+ Ajouter`
- Une liste `<ul>` triable (drag & drop)
- Chaque valeur dans un `<li>` avec poignée de déplacement et bouton de suppression
- Le `name` HTML devient `cfdev[field_id][]` (tableau)

---

## Deux conditions requises

Le système vérifie toujours les deux :

```php
if ($field->repeatable && $field->supports_repeatable)
```

| Propriété | Définie par |
|---|---|
| `repeatable` | Toi, dans la définition du champ |
| `supports_repeatable` | Le plugin, sur chaque classe de champ |

Si `repeatable => true` est déclaré sur un type qui ne le supporte pas, le champ s'affiche normalement sans effet.

---

## Types compatibles

| Type | Compatible |
|---|---|
| `text` | ✅ |
| `textarea` | ✅ |
| `select` | ✅ |
| `image` | ✅ |
| `post_select` | ✅ |
| `term_select` | ✅ |
| `user_select` | ✅ |
| `color`, `date`, `datetime`, `time` | ✅ |
| `checkbox`, `checkboxes`, `radios`, `yesno`, `toggle` | ❌ |
| `file` | ❌ |
| `wysiwyg` | ❌ |

---

## Rendu en colonne admin

Quand `show_admin_column => true` est combiné avec `repeatable => true`, les valeurs sont affichées jointes par `, ` dans la colonne.

```php
[
    'id'                => 'tags',
    'type'              => 'text',
    'label'             => 'Tags',
    'repeatable'        => true,
    'show_admin_column' => true,
]
// Colonne : "PHP, WordPress, MySQL"
```

---

## Lire les valeurs en front-end

La valeur retournée est un tableau — toujours traiter comme tel :

```php
$urls = get_post_meta($post_id, 'external_urls', true);
// $urls = ['https://example.com', 'https://autre.com']

foreach ((array) $urls as $url) {
    echo esc_url($url);
}
```