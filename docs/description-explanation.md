# `description` et `explanation`

Deux clés distinctes pour annoter les champs, affichées à des endroits différents.

---

## `description` — sous le label

Texte affiché **sous le label** du champ, dans la colonne de gauche du tableau. Sert à expliquer à quoi sert le champ.

Accepte du HTML simple (filtré par `wp_kses_post` — balises autorisées : `<a>`, `<strong>`, `<em>`, etc.).

```
Label du champ *
Texte de description ← ici
                        [ input .............. ]
```

```php
[
    'id'          => 'slug',
    'type'        => 'text',
    'label'       => 'Identifiant',
    'description' => 'Utilisé dans les URLs. Lettres minuscules et tirets uniquement.',
]
```

---

## `explanation` — inline après le champ

Texte affiché **directement après le champ** (inline), en italique. Sert à donner un exemple de format ou une contrainte courte.

Toujours en texte brut (échappé par `esc_html`).

```
Label      [ input .............. ] <em>Ex : 2026-01-31</em> ← ici
```

```php
[
    'id'          => 'start_date',
    'type'        => 'text',
    'label'       => 'Date de début',
    'explanation' => 'Format : YYYY-MM-DD',
]
```

> L'`explanation` est **masquée** quand `repeatable => true` — elle n'est pas affichée dans les items de la liste sortable.

---

## `description` sur une MetaBox entière

`description` peut aussi être passé au **titre de la MetaBox** (sous forme de tableau) pour afficher un texte d'introduction au-dessus de tous les champs.

```php
->addMetaBox('seo', ['SEO', 'Ces champs sont utilisés par les moteurs de recherche.'], $fields);
//                   ↑ titre   ↑ description de la box
```

Rendu :
```
┌─ SEO ───────────────────────────────────────────────────┐
│ Ces champs sont utilisés par les moteurs de recherche.  │
│ ─────────────────────────────────────────────────────── │
│ Titre       [ input ]                                   │
│ ...                                                     │
└─────────────────────────────────────────────────────────┘
```

---

## Résumé

| Clé | Niveau | Position | HTML | Masqué si repeatable |
|---|---|---|---|---|
| `description` | champ | Sous le label (colonne gauche) | ✅ `wp_kses_post` | Non |
| `explanation` | champ | Après l'input (inline) | ❌ texte brut | ✅ oui |
| `description` | MetaBox | En-tête de la box | ✅ `wp_kses_post` | — |