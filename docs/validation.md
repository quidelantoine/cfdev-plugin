# Règles de validation

Les rules s'ajoutent via la clé `rules` du champ. Chaque rule implémente `Validatable` et est évaluée lors de la sauvegarde.

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\MinLength;

['id' => 'title', 'type' => 'text', 'label' => 'Titre', 'rules' => [
    new Required(),
    new MinLength(3),
]]
```

Les champs `email` et `url` injectent automatiquement leurs règles de format si la valeur est non vide — pas besoin de les ajouter manuellement.

---

### `Required`

Le champ ne peut pas être vide.

```php
// Texte obligatoire
['id' => 'title', 'type' => 'text', 'label' => 'Titre', 'rules' => [new Required()]]

// Image obligatoire
['id' => 'cover', 'type' => 'image', 'label' => 'Couverture', 'rules' => [new Required()]]

// Select obligatoire (ne doit pas rester sur "— Choisir —")
['id' => 'status', 'type' => 'select', 'label' => 'Statut', 'rules' => [new Required()]]
```

---

### `Min` / `Max`

Valeur numérique minimale / maximale.

```php
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\Max;

// Prix entre 0 et 9999
['id' => 'price', 'type' => 'number', 'label' => 'Prix', 'rules' => [
    new Min(0),
    new Max(9999),
]]

// Slider : note de 1 à 5 obligatoire
['id' => 'rating', 'type' => 'range', 'label' => 'Note', 'args' => ['min' => 1, 'max' => 5], 'rules' => [
    new Required(),
    new Min(1),
    new Max(5),
]]
```

---

### `Between`

Valeur numérique comprise entre `$min` et `$max` (inclus).

```php
use Weblitzer\CFDev\Validation\Rules\Between;

// Année de publication valide
['id' => 'year', 'type' => 'number', 'label' => 'Année', 'rules' => [new Between(1900, 2100)]]

// Pourcentage
['id' => 'discount', 'type' => 'number', 'label' => 'Remise (%)', 'rules' => [new Between(0, 100)]]
```

---

### `MinLength` / `MaxLength`

Longueur minimale / maximale en caractères (mbstring).

```php
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\MaxLength;

// Titre entre 3 et 100 caractères
['id' => 'title', 'type' => 'text', 'label' => 'Titre', 'rules' => [
    new MinLength(3),
    new MaxLength(100),
]]

// Résumé court
['id' => 'excerpt', 'type' => 'textarea', 'label' => 'Résumé', 'rules' => [
    new MaxLength(300),
]]
```

---

### `ExactLength`

Exactement `$length` caractères.

```php
use Weblitzer\CFDev\Validation\Rules\ExactLength;

// Code postal français
['id' => 'zip', 'type' => 'text', 'label' => 'Code postal', 'rules' => [new ExactLength(5)]]

// IBAN français (34 caractères)
['id' => 'iban', 'type' => 'text', 'label' => 'IBAN', 'rules' => [new ExactLength(34)]]
```

---

### `MinItems` / `MaxItems`

Nombre minimal / maximal d'éléments dans un tableau (galerie, checkboxes). Les valeurs vides sont ignorées dans le comptage.

```php
use Weblitzer\CFDev\Validation\Rules\MinItems;
use Weblitzer\CFDev\Validation\Rules\MaxItems;

// Galerie : entre 1 et 10 images
['id' => 'gallery', 'type' => 'gallery', 'label' => 'Galerie', 'rules' => [
    new MinItems(1),
    new MaxItems(10),
]]

// Au moins une catégorie cochée
['id' => 'categories', 'type' => 'post_checkboxes', 'label' => 'Catégories', 'rules' => [
    new MinItems(1),
]]

// Maximum 3 utilisateurs sélectionnés
['id' => 'reviewers', 'type' => 'user_checkboxes', 'label' => 'Relecteurs', 'rules' => [
    new MaxItems(3),
]]
```

---

### `Numeric`

La valeur doit être un nombre (entier ou décimal).

```php
use Weblitzer\CFDev\Validation\Rules\Numeric;

// Coordonnée GPS saisie en texte libre
['id' => 'latitude', 'type' => 'text', 'label' => 'Latitude', 'rules' => [new Numeric()]]
```

---

### `Positive`

La valeur doit être un nombre strictement positif (> 0).

```php
use Weblitzer\CFDev\Validation\Rules\Positive;

// Quantité en stock
['id' => 'stock', 'type' => 'number', 'label' => 'Stock', 'rules' => [new Positive()]]

// Durée en minutes
['id' => 'duration', 'type' => 'number', 'label' => 'Durée (min)', 'rules' => [new Positive()]]
```

---

### `Alpha`

Lettres uniquement (a–z, A–Z).

```php
use Weblitzer\CFDev\Validation\Rules\Alpha;

// Code pays ISO (FR, EN, DE…)
['id' => 'country_code', 'type' => 'text', 'label' => 'Code pays', 'rules' => [
    new Alpha(),
    new ExactLength(2),
]]
```

---

### `AlphaNumeric`

Lettres et chiffres uniquement.

```php
use Weblitzer\CFDev\Validation\Rules\AlphaNumeric;

// Code de promotion
['id' => 'promo_code', 'type' => 'text', 'label' => 'Code promo', 'rules' => [new AlphaNumeric()]]
```

---

### `Slug`

Format slug WordPress : lettres minuscules, chiffres, tirets.

```php
use Weblitzer\CFDev\Validation\Rules\Slug;

// Identifiant unique d'un template
['id' => 'template_key', 'type' => 'text', 'label' => 'Clé template', 'rules' => [new Slug()]]
```

---

### `Email`

Format email valide (via `is_email()`). Injectée automatiquement sur le champ `email` — utile pour les champs `text` qui collectent des emails.

```php
use Weblitzer\CFDev\Validation\Rules\Email;

// Email dans un champ texte
['id' => 'contact_email', 'type' => 'text', 'label' => 'Email de contact', 'rules' => [
    new Required(),
    new Email(),
]]
```

---

### `Url`

Format URL valide. Injectée automatiquement sur le champ `url` — utile pour les champs `text`.

```php
use Weblitzer\CFDev\Validation\Rules\Url;

// Site web dans un champ texte
['id' => 'website', 'type' => 'text', 'label' => 'Site web', 'rules' => [new Url()]]
```

---

### `Uuid`

Format UUID v4.

```php
use Weblitzer\CFDev\Validation\Rules\Uuid;

// Identifiant externe d'un produit
['id' => 'external_id', 'type' => 'text', 'label' => 'ID externe', 'rules' => [new Uuid()]]
```

---

### `Regex`

Expression régulière personnalisée.

```php
use Weblitzer\CFDev\Validation\Rules\Regex;

// Numéro de téléphone français
['id' => 'phone', 'type' => 'tel', 'label' => 'Téléphone', 'rules' => [
    new Regex('/^(\+33|0)[1-9](\d{8})$/'),
]]

// Couleur hexadécimale saisie manuellement
['id' => 'hex_color', 'type' => 'text', 'label' => 'Couleur hex', 'rules' => [
    new Regex('/^#[0-9a-fA-F]{6}$/'),
]]
```

---

### `Contains`

La valeur doit contenir la sous-chaîne donnée.

```php
use Weblitzer\CFDev\Validation\Rules\Contains;

// Le lien doit pointer vers le domaine interne
['id' => 'internal_link', 'type' => 'url', 'label' => 'Lien interne', 'rules' => [
    new Contains('monsite.fr'),
]]
```

---

### `StartsWith` / `EndsWith`

La valeur doit commencer / finir par une chaîne donnée.

```php
use Weblitzer\CFDev\Validation\Rules\StartsWith;
use Weblitzer\CFDev\Validation\Rules\EndsWith;

// URL sécurisée uniquement
['id' => 'api_endpoint', 'type' => 'url', 'label' => 'Endpoint API', 'rules' => [
    new StartsWith('https://'),
]]

// Fichier PDF uniquement (valeur = URL)
['id' => 'brochure', 'type' => 'file', 'label' => 'Brochure', 'rules' => [
    new EndsWith('.pdf'),
]]
```

---

### `DateAfter` / `DateBefore`

La date doit être après / avant une date donnée. Le format par défaut est `Y-m-d`.

```php
use Weblitzer\CFDev\Validation\Rules\DateAfter;
use Weblitzer\CFDev\Validation\Rules\DateBefore;

// Date de publication après le lancement du site
['id' => 'publish_date', 'type' => 'date', 'label' => 'Date de publication', 'rules' => [
    new DateAfter('2020-01-01'),
]]

// Date d'expiration avant fin 2030
['id' => 'expires_at', 'type' => 'date', 'label' => "Date d'expiration", 'rules' => [
    new DateBefore('2030-12-31'),
]]

// Combiné : fenêtre d'événement
['id' => 'event_date', 'type' => 'date', 'label' => "Date de l'événement", 'rules' => [
    new DateAfter('2025-01-01'),
    new DateBefore('2026-12-31'),
]]
```

---

### `DateAfterToday`

La date doit être dans le futur (strictement après aujourd'hui).

```php
use Weblitzer\CFDev\Validation\Rules\DateAfterToday;

// Événement à venir uniquement
['id' => 'event_date', 'type' => 'date', 'label' => "Date de l'événement", 'rules' => [
    new Required(),
    new DateAfterToday(),
]]

// Avec format personnalisé (si le datepicker retourne d/m/Y)
['id' => 'deadline', 'type' => 'date', 'label' => 'Échéance', 'rules' => [
    new DateAfterToday('d/m/Y'),
]]
```

---

### `FileExtension`

L'attachment doit avoir une extension autorisée. Reçoit l'ID de l'attachment.

```php
use Weblitzer\CFDev\Validation\Rules\FileExtension;

// PDF uniquement
['id' => 'brochure', 'type' => 'file', 'label' => 'Brochure PDF', 'rules' => [
    new Required(),
    new FileExtension(['pdf']),
]]

// Images web
['id' => 'banner', 'type' => 'image', 'label' => 'Bannière', 'rules' => [
    new FileExtension(['jpg', 'jpeg', 'png', 'webp']),
]]
```

---

### `FileMime`

L'attachment doit avoir un type MIME autorisé. Plus précis que `FileExtension`.

```php
use Weblitzer\CFDev\Validation\Rules\FileMime;

// Vidéo uniquement
['id' => 'video', 'type' => 'file', 'label' => 'Vidéo', 'rules' => [
    new FileMime(['video/mp4', 'video/webm']),
]]

// Images seulement (pas de SVG)
['id' => 'photo', 'type' => 'image', 'label' => 'Photo', 'rules' => [
    new FileMime(['image/jpeg', 'image/png', 'image/webp']),
]]
```

---

### `ImageExactDimensions`

L'image doit avoir exactement les dimensions spécifiées (en pixels). Les paramètres `width` et `height` sont optionnels — on peut valider l'un ou l'autre.

```php
use Weblitzer\CFDev\Validation\Rules\ImageExactDimensions;

// Open Graph : 1200×630 obligatoires
['id' => 'og_image', 'type' => 'image', 'label' => 'Image OG', 'rules' => [
    new Required(),
    new ImageExactDimensions(1200, 630),
]]

// Largeur exacte uniquement (hauteur libre)
['id' => 'banner', 'type' => 'image', 'label' => 'Bannière', 'rules' => [
    new ImageExactDimensions(width: 1920),
]]
```

---

### `ImageMinDimensions`

L'image doit avoir au minimum les dimensions spécifiées.

```php
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;

// Image suffisamment grande pour le hero
['id' => 'hero_image', 'type' => 'image', 'label' => 'Image hero', 'rules' => [
    new Required(),
    new ImageMinDimensions(1200, 600),
]]

// Largeur minimale uniquement
['id' => 'cover', 'type' => 'image', 'label' => 'Couverture', 'rules' => [
    new ImageMinDimensions(width: 800),
]]
```