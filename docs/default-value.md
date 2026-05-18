# Valeur par défaut — `default_value`

`default_value` est la valeur affichée dans le champ quand **aucune valeur n'a encore été sauvegardée** pour cet objet (post, terme, utilisateur). Une fois le formulaire soumis, la valeur sauvegardée prend le dessus — la valeur par défaut n'est plus utilisée.

> La valeur par défaut est affichée uniquement dans le formulaire. Elle n'est **pas persistée** automatiquement en base — seulement quand l'utilisateur soumet.

---

## Selon le type de champ

### Scalaire — `text`, `textarea`, `wysiwyg`, `hidden`

```php
['id' => 'title', 'type' => 'text', 'label' => 'Titre', 'default_value' => 'Sans titre']
```

### Dates — `date`, `time`, `datetime`

Chaîne affichée telle quelle dans l'input (doit correspondre au `date_format` / `time_format` défini dans `args`).

```php
['id' => 'start', 'type' => 'date', 'default_value' => '01/01/2026', 'args' => ['date_format' => 'd/m/Y']]
```

### `checkbox` et `toggle`

`'on'` = coché par défaut. Toute autre valeur (ou vide) = décoché.

```php
['id' => 'active', 'type' => 'checkbox', 'default_value' => 'on']
['id' => 'visible', 'type' => 'toggle',   'default_value' => 'on']
```

### `select`, `radios`, `yesno`

La clé de l'option à sélectionner par défaut (doit correspondre à une clé de `options`).

```php
['id' => 'status', 'type' => 'select', 'options' => ['draft' => 'Brouillon', 'published' => 'Publié'], 'default_value' => 'draft']
['id' => 'gender', 'type' => 'radios', 'options' => ['m' => 'Homme', 'f' => 'Femme'], 'default_value' => 'm']
```

### `checkboxes`, `multi_select`

Tableau des clés pré-cochées.

```php
['id' => 'tags', 'type' => 'checkboxes', 'options' => ['php' => 'PHP', 'js' => 'JS', 'css' => 'CSS'], 'default_value' => ['php', 'css']]
```

### `post_select`, `term_select`, `user_select`

ID du post / terme / utilisateur sélectionné par défaut.

```php
['id' => 'author', 'type' => 'post_select', 'default_value' => 42]
['id' => 'genre',  'type' => 'term_select', 'default_value' => 7]
```

### `post_checkboxes`, `term_checkboxes`

Tableau d'IDs pré-cochés.

```php
['id' => 'related', 'type' => 'post_checkboxes', 'default_value' => [12, 34]]
```

### `bundle`

Tableau de lignes pré-remplies. Chaque ligne est un tableau de valeurs dans l'ordre des champs du bundle.

```php
[
    'bundle',
    [
        ['id' => 'name',  'type' => 'text', 'label' => 'Nom'],
        ['id' => 'score', 'type' => 'text', 'label' => 'Score'],
    ],
    'default_value' => [
        ['Jean', '10'],
        ['Marie', '20'],
    ],
]
```

---

## Résumé des types

| Type | Format de `default_value` |
|---|---|
| `text`, `textarea`, `wysiwyg`, `hidden` | `string` |
| `date`, `time`, `datetime` | `string` (au format affiché) |
| `checkbox`, `toggle` | `'on'` ou `''` |
| `select`, `radios`, `yesno` | clé d'une option |
| `checkboxes`, `multi_select` | `array` de clés |
| `post_select`, `term_select`, `user_select` | ID (int) |
| `post_checkboxes`, `term_checkboxes` | `array` d'IDs |
| `bundle` | `array` de lignes |
| `image`, `file`, `color` | non utilisé |