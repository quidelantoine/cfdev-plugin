# Validation

[← README](../../readme.md) · [English](../en/validation.md)

Les règles de validation s'attachent directement aux champs via la clé `rules`. Elles s'évaluent lors de la sauvegarde. Les erreurs survivent au cycle POST → redirection et s'affichent inline dans le formulaire d'édition.

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\MinLength;

['id' => 'title', 'type' => 'text', 'label' => 'Titre', 'rules' => [
    new Required(),
    new MinLength(3),
]]
```

Les champs `email` et `url` injectent automatiquement leurs règles de format si la valeur est non vide — inutile de les ajouter manuellement.

---

## Required

Le champ ne peut pas être vide. Deux approches possibles :

```php
// Astérisque visuel + validation serveur (recommandé)
['id' => 'title', 'type' => 'text', 'required' => true]

// Validation serveur uniquement, sans astérisque
['id' => 'title', 'type' => 'text', 'rules' => [new Required()]]
```

`required => true` injecte automatiquement la règle `Required` — pas besoin de la déclarer dans `rules` en plus.

Fonctionne sur tous les types :

```php
['id' => 'cover',  'type' => 'image',  'required' => true]
['id' => 'status', 'type' => 'select', 'required' => true]
```

---

## Min / Max

Valeur numérique minimale / maximale.

```php
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\Max;

['id' => 'price', 'type' => 'number', 'rules' => [new Min(0), new Max(9999)]]
```

---

## Between

Valeur numérique comprise entre `$min` et `$max` (inclus).

```php
use Weblitzer\CFDev\Validation\Rules\Between;

['id' => 'year',     'type' => 'number', 'rules' => [new Between(1900, 2100)]]
['id' => 'discount', 'type' => 'number', 'rules' => [new Between(0, 100)]]
```

---

## MinLength / MaxLength

Longueur minimale / maximale en caractères (mbstring).

```php
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\MaxLength;

['id' => 'title',   'type' => 'text',     'rules' => [new MinLength(3), new MaxLength(100)]]
['id' => 'excerpt', 'type' => 'textarea', 'rules' => [new MaxLength(300)]]
```

---

## ExactLength

Exactement `$length` caractères.

```php
use Weblitzer\CFDev\Validation\Rules\ExactLength;

['id' => 'zip',  'type' => 'text', 'rules' => [new ExactLength(5)]]
['id' => 'iban', 'type' => 'text', 'rules' => [new ExactLength(34)]]
```

---

## MinItems / MaxItems

Nombre minimal / maximal d'éléments dans un tableau (galerie, checkboxes). Les valeurs vides sont ignorées dans le comptage.

```php
use Weblitzer\CFDev\Validation\Rules\MinItems;
use Weblitzer\CFDev\Validation\Rules\MaxItems;

['id' => 'gallery',   'type' => 'gallery',        'rules' => [new MinItems(1), new MaxItems(10)]]
['id' => 'reviewers', 'type' => 'user_checkboxes', 'rules' => [new MaxItems(3)]]
```

---

## IsNumeric

La valeur doit être un nombre (entier ou décimal).

```php
use Weblitzer\CFDev\Validation\Rules\IsNumeric;

['id' => 'latitude', 'type' => 'text', 'rules' => [new IsNumeric()]]
```

---

## Positive

La valeur doit être strictement positive (> 0).

```php
use Weblitzer\CFDev\Validation\Rules\Positive;

['id' => 'stock',    'type' => 'number', 'rules' => [new Positive()]]
['id' => 'duration', 'type' => 'number', 'rules' => [new Positive()]]
```

---

## Alpha

Lettres uniquement (a–z, A–Z).

```php
use Weblitzer\CFDev\Validation\Rules\Alpha;

['id' => 'country_code', 'type' => 'text', 'rules' => [new Alpha(), new ExactLength(2)]]
```

---

## AlphaNumeric

Lettres et chiffres uniquement.

```php
use Weblitzer\CFDev\Validation\Rules\AlphaNumeric;

['id' => 'promo_code', 'type' => 'text', 'rules' => [new AlphaNumeric()]]
```

---

## Slug

Format slug WordPress : lettres minuscules, chiffres, tirets.

```php
use Weblitzer\CFDev\Validation\Rules\Slug;

['id' => 'template_key', 'type' => 'text', 'rules' => [new Slug()]]
```

---

## Email

Format email valide (via `is_email()`). Injectée automatiquement sur les champs `email` — utile sur les champs `text` qui collectent des emails.

```php
use Weblitzer\CFDev\Validation\Rules\Email;

['id' => 'contact', 'type' => 'text', 'rules' => [new Required(), new Email()]]
```

---

## Url

Format URL valide. Injectée automatiquement sur les champs `url`.

```php
use Weblitzer\CFDev\Validation\Rules\Url;

['id' => 'website', 'type' => 'text', 'rules' => [new Url()]]
```

---

## Uuid

Format UUID v4.

```php
use Weblitzer\CFDev\Validation\Rules\Uuid;

['id' => 'external_id', 'type' => 'text', 'rules' => [new Uuid()]]
```

---

## Regex

Expression régulière personnalisée.

```php
use Weblitzer\CFDev\Validation\Rules\Regex;

['id' => 'phone',     'type' => 'tel',  'rules' => [new Regex('/^(\+33|0)[1-9](\d{8})$/')]]
['id' => 'hex_color', 'type' => 'text', 'rules' => [new Regex('/^#[0-9a-fA-F]{6}$/')]]
```

---

## Contains

La valeur doit contenir la sous-chaîne donnée.

```php
use Weblitzer\CFDev\Validation\Rules\Contains;

['id' => 'internal_link', 'type' => 'url', 'rules' => [new Contains('monsite.fr')]]
```

---

## StartsWith / EndsWith

La valeur doit commencer / finir par une chaîne donnée.

```php
use Weblitzer\CFDev\Validation\Rules\StartsWith;
use Weblitzer\CFDev\Validation\Rules\EndsWith;

['id' => 'api_endpoint', 'type' => 'url',  'rules' => [new StartsWith('https://')]]
['id' => 'brochure',     'type' => 'file', 'rules' => [new EndsWith('.pdf')]]
```

---

## DateAfter / DateBefore

La date doit être après / avant la date donnée. Format par défaut : `Y-m-d`.

```php
use Weblitzer\CFDev\Validation\Rules\DateAfter;
use Weblitzer\CFDev\Validation\Rules\DateBefore;

['id' => 'publish_date', 'type' => 'date', 'rules' => [new DateAfter('2020-01-01')]]
['id' => 'expires_at',   'type' => 'date', 'rules' => [new DateBefore('2030-12-31')]]

// Fenêtre combinée
['id' => 'event_date', 'type' => 'date', 'rules' => [
    new DateAfter('2025-01-01'),
    new DateBefore('2026-12-31'),
]]
```

---

## DateAfterToday

La date doit être strictement dans le futur.

```php
use Weblitzer\CFDev\Validation\Rules\DateAfterToday;

['id' => 'event_date', 'type' => 'date', 'rules' => [new Required(), new DateAfterToday()]]

// Format personnalisé (si le datepicker retourne d/m/Y)
['id' => 'deadline', 'type' => 'date', 'rules' => [new DateAfterToday('d/m/Y')]]
```

---

## FileExtension

L'attachment doit avoir une extension autorisée. Reçoit l'ID de l'attachment.

```php
use Weblitzer\CFDev\Validation\Rules\FileExtension;

['id' => 'brochure', 'type' => 'file',  'rules' => [new Required(), new FileExtension(['pdf'])]]
['id' => 'banner',   'type' => 'image', 'rules' => [new FileExtension(['jpg', 'jpeg', 'png', 'webp'])]]
```

---

## FileMime

L'attachment doit avoir un type MIME autorisé. Plus précis que `FileExtension`.

```php
use Weblitzer\CFDev\Validation\Rules\FileMime;

['id' => 'video', 'type' => 'file',  'rules' => [new FileMime(['video/mp4', 'video/webm'])]]
['id' => 'photo', 'type' => 'image', 'rules' => [new FileMime(['image/jpeg', 'image/png', 'image/webp'])]]
```

---

## ImageExactDimensions

L'image doit avoir exactement les dimensions spécifiées. `width` et `height` sont optionnels.

```php
use Weblitzer\CFDev\Validation\Rules\ImageExactDimensions;

['id' => 'og_image', 'type' => 'image', 'rules' => [new Required(), new ImageExactDimensions(1200, 630)]]

// Largeur uniquement
['id' => 'banner', 'type' => 'image', 'rules' => [new ImageExactDimensions(width: 1920)]]
```

---

## ImageMinDimensions

L'image doit avoir au minimum les dimensions spécifiées.

```php
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;

['id' => 'hero',  'type' => 'image', 'rules' => [new Required(), new ImageMinDimensions(1200, 600)]]

// Largeur minimale uniquement
['id' => 'cover', 'type' => 'image', 'rules' => [new ImageMinDimensions(width: 800)]]
```

---

## Récapitulatif

| Catégorie | Règles |
|---|---|
| Obligatoire | `Required` |
| Longueur | `MinLength`, `MaxLength`, `ExactLength` |
| Nombre | `Min`, `Max`, `Between`, `IsNumeric`, `Positive` |
| Items | `MinItems`, `MaxItems` |
| Format | `Alpha`, `AlphaNumeric`, `Slug`, `Email`, `Url`, `Uuid` |
| Chaîne | `Regex`, `Contains`, `StartsWith`, `EndsWith` |
| Dates | `DateAfter`, `DateBefore`, `DateAfterToday` |
| Fichiers | `FileExtension`, `FileMime` |
| Images | `ImageExactDimensions`, `ImageMinDimensions` |
