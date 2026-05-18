# Sauvegarde AJAX — `ajax`

`ajax => true` ajoute un bouton **Save** directement sous le champ. Un clic sauvegarde ce champ seul en AJAX, sans soumettre toute la page.

---

## Utilisation

```php
->addMetaBox('details', 'Détails', [
    [
        'id'    => 'status',
        'type'  => 'select',
        'label' => 'Statut',
        'ajax'  => true,
        'options' => ['draft' => 'Brouillon', 'published' => 'Publié'],
    ],
]);
```

Résultat :

```
[select ▼]  [Save]
```

Fonctionne de la même façon dans **MetaBox**, **TermMeta** et **UserMeta**.

---

## Comment ça marche

1. `Field::output()` détecte `ajax && supports_ajax` → appelle `ajaxOutput()` → ajoute le bouton `.js-cfdev-ajax-save`
2. Clic JS → lit `field_id`, `value`, `meta_type`, `object_id` depuis le DOM
3. POST vers `admin-ajax.php` avec l'action `cfdev_field_ajax_save`
4. `Field::ajaxSave()` vérifie le nonce + les permissions (`edit_post`, `edit_user`, `edit_term`) → sauvegarde le meta

La valeur est sauvegardée immédiatement, sans rechargement de page. Le bouton clignote en vert pour confirmer.

---

## Deux conditions requises

```php
if ($this->ajax && $this->supports_ajax)
```

| Propriété | Définie par |
|---|---|
| `ajax` | Toi, dans la définition du champ |
| `supports_ajax` | Le plugin, sur chaque classe de champ |

---

## Types compatibles

| Type | Compatible |
|---|---|
| `text`, `textarea`, `wysiwyg` | ✅ |
| `select` | ✅ |
| `image`, `file` | ✅ |
| `checkbox`, `toggle` | ✅ |
| `color`, `date`, `datetime`, `time` | ✅ |
| `post_select`, `term_select`, `user_select` | ✅ |
| `checkboxes`, `radios`, `yesno` | ❌ |
| `multi_select`, `post_checkboxes`, `term_checkboxes` | ❌ |
| `bundle`, `tabs`, `accordion` | ❌ (layouts) |

---

## Limitations

**Désactivé de force dans les bundles.** À l'intérieur d'un `bundle`, `$field->ajax` est forcé à `false` — un bundle sauvegarde une ligne entière, pas champ par champ.

**Une seule valeur scalaire.** Le handler AJAX fait un `sanitize_text_field` sur la valeur — les champs multi-valeurs (`checkboxes`, `multi_select`) ne sont donc pas supportés.

**Pas de validation.** La sauvegarde AJAX bypasse le système de validation (`ErrorBag`). À utiliser pour des champs simples où la valeur brute est acceptable.

---

## Cas d'usage typiques

```php
// Champ de statut modifiable à la volée dans la liste
['id' => 'featured', 'type' => 'toggle',  'label' => 'Mis en avant', 'ajax' => true]

// Note interne rapide sur un utilisateur
['id' => 'admin_note', 'type' => 'text', 'label' => 'Note admin', 'ajax' => true]

// Priorité d'affichage
['id' => 'sort_order', 'type' => 'text', 'label' => 'Ordre', 'ajax' => true]
```