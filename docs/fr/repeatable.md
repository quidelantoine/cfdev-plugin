# Champs répétables & AJAX

[← README](../../readme.md) · [English](../en/repeatable.md)

`repeatable: true` transforme un champ en liste dynamique : l'utilisateur peut ajouter autant de valeurs qu'il veut, les réordonner par drag & drop, et en supprimer. La valeur sauvegardée en base est un tableau.

---

## Utilisation

```php
->addMetaBox('links', 'Liens', [
    [
        'id'         => 'external_urls',
        'type'       => 'text',
        'label'      => 'URLs externes',
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
| `repeatable` | Vous, dans la définition du champ |
| `supports_repeatable` | Le plugin, sur chaque classe de champ |

Si `repeatable: true` est déclaré sur un type qui ne le supporte pas, le champ s'affiche normalement sans effet.

---

## Types compatibles

| Type | Répétable |
|---|---|
| `text`, `textarea` | ✅ |
| `number`, `range` | ✅ |
| `email`, `url`, `tel` | ✅ |
| `color`, `date`, `datetime`, `time` | ✅ |
| `select` | ✅ |
| `image` | ✅ |
| `post_select`, `term_select`, `user_select` | ✅ |
| `checkbox`, `checkboxes`, `radios`, `yesno`, `toggle` | ❌ |
| `file` | ❌ |
| `wysiwyg` | ❌ |

---

## Lire les valeurs en front-end

La valeur retournée est toujours un tableau :

```php
$urls = get_post_meta($post_id, 'external_urls', true);
// $urls = ['https://exemple.com', 'https://autre.com']

foreach ((array) $urls as $url) {
    echo '<a href="' . esc_url($url) . '">' . esc_html($url) . '</a>';
}
```

---

## Répétable dans un Bundle

Un champ `repeatable: true` peut être placé à l'intérieur d'un Bundle. Chaque ligne du bundle stocke alors son propre tableau de valeurs dans le JSON global du bundle.

```php
->addMetaBox('articles', 'Articles', [
    'bundle', 'articles_bundle', [
        ['id' => 'title',  'type' => 'text', 'label' => 'Titre'],
        ['id' => 'tags',   'type' => 'text', 'label' => 'Tags', 'repeatable' => true],
    ],
]);
```

Structure stockée :

```json
[
  { "title": "Article A", "tags": ["php", "oop"] },
  { "title": "Article B", "tags": ["js", "react"] }
]
```

Lecture en front-end :

```php
$rows = json_decode(get_post_meta($post_id, '_articles_bundle', true), true);
foreach ($rows as $row) {
    echo esc_html($row['title']);
    foreach ((array) $row['tags'] as $tag) {
        echo '<span>' . esc_html($tag) . '</span>';
    }
}
```

---

## Colonne admin avec répétable

Quand `show_admin_column: true` est combiné avec `repeatable: true`, les valeurs sont affichées jointes par `, ` dans la colonne :

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

## Sauvegarde inline (AJAX)

Les champs marqués `ajax: true` affichent un bouton **Enregistrer** autonome. Un clic sur ce bouton sauvegarde uniquement ce champ via AJAX, sans soumettre tout le formulaire de la meta box.

```php
['id' => 'subtitle', 'type' => 'text', 'label' => 'Sous-titre', 'ajax' => true]
```

Deux conditions doivent être réunies pour que le bouton apparaisse :
- La config du champ contient `'ajax' => true` (opt-in développeur)
- Le type de champ le supporte (`supports_ajax = true` sur la classe) — ex. `Link` et `Gallery` ne le supportent pas

`ajax` et `repeatable` sont mutuellement exclusifs : si les deux sont définis, `repeatable` prend la priorité.

Les types compatibles sont listés dans le [tableau récapitulatif des types de champs](champs.md) (colonne `ajax`).
