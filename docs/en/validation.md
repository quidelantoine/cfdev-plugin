# Validation

[← README](../../readme.md) · [Français](../fr/validation.md)

Validation rules are attached directly to fields via the `rules` key. Rules run on save. Errors survive the POST → redirect cycle and appear inline in the edit form.

```php
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\MinLength;

['id' => 'title', 'type' => 'text', 'label' => 'Title', 'rules' => [
    new Required(),
    new MinLength(3),
]]
```

The `email` and `url` field types automatically inject their format rules when the value is non-empty — no need to add them manually.

---

## Required

Field cannot be empty. Two approaches:

```php
// Visual asterisk + server validation (recommended)
['id' => 'title', 'type' => 'text', 'required' => true]

// Server-side validation only, no asterisk
['id' => 'title', 'type' => 'text', 'rules' => [new Required()]]
```

`required => true` automatically injects the `Required` rule — no need to also declare it in `rules`.

Works on all types:

```php
['id' => 'cover',  'type' => 'image',  'required' => true]
['id' => 'status', 'type' => 'select', 'required' => true]
```

---

## Min / Max

Minimum / maximum numeric value.

```php
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\Max;

['id' => 'price', 'type' => 'number', 'rules' => [new Min(0), new Max(9999)]]
```

---

## Between

Numeric value between `$min` and `$max` (inclusive).

```php
use Weblitzer\CFDev\Validation\Rules\Between;

['id' => 'year',     'type' => 'number', 'rules' => [new Between(1900, 2100)]]
['id' => 'discount', 'type' => 'number', 'rules' => [new Between(0, 100)]]
```

---

## MinLength / MaxLength

Minimum / maximum character count (mbstring).

```php
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\MaxLength;

['id' => 'title',   'type' => 'text',     'rules' => [new MinLength(3), new MaxLength(100)]]
['id' => 'excerpt', 'type' => 'textarea', 'rules' => [new MaxLength(300)]]
```

---

## ExactLength

Exactly `$length` characters.

```php
use Weblitzer\CFDev\Validation\Rules\ExactLength;

['id' => 'zip',  'type' => 'text', 'rules' => [new ExactLength(5)]]
['id' => 'iban', 'type' => 'text', 'rules' => [new ExactLength(34)]]
```

---

## MinItems / MaxItems

Minimum / maximum number of items in an array (gallery, checkboxes). Empty values are ignored in the count.

```php
use Weblitzer\CFDev\Validation\Rules\MinItems;
use Weblitzer\CFDev\Validation\Rules\MaxItems;

['id' => 'gallery',   'type' => 'gallery',         'rules' => [new MinItems(1), new MaxItems(10)]]
['id' => 'reviewers', 'type' => 'user_checkboxes',  'rules' => [new MaxItems(3)]]
```

---

## IsNumeric

Value must be a number (integer or decimal).

```php
use Weblitzer\CFDev\Validation\Rules\IsNumeric;

['id' => 'latitude', 'type' => 'text', 'rules' => [new IsNumeric()]]
```

---

## Positive

Value must be strictly positive (> 0).

```php
use Weblitzer\CFDev\Validation\Rules\Positive;

['id' => 'stock',    'type' => 'number', 'rules' => [new Positive()]]
['id' => 'duration', 'type' => 'number', 'rules' => [new Positive()]]
```

---

## Alpha

Letters only (a–z, A–Z).

```php
use Weblitzer\CFDev\Validation\Rules\Alpha;

['id' => 'country_code', 'type' => 'text', 'rules' => [new Alpha(), new ExactLength(2)]]
```

---

## AlphaNumeric

Letters and digits only.

```php
use Weblitzer\CFDev\Validation\Rules\AlphaNumeric;

['id' => 'promo_code', 'type' => 'text', 'rules' => [new AlphaNumeric()]]
```

---

## Slug

WordPress slug format: lowercase letters, digits, hyphens.

```php
use Weblitzer\CFDev\Validation\Rules\Slug;

['id' => 'template_key', 'type' => 'text', 'rules' => [new Slug()]]
```

---

## Email

Valid email format (via `is_email()`). Auto-injected on `email` fields — useful on `text` fields that collect emails.

```php
use Weblitzer\CFDev\Validation\Rules\Email;

['id' => 'contact', 'type' => 'text', 'rules' => [new Required(), new Email()]]
```

---

## Url

Valid URL format. Auto-injected on `url` fields.

```php
use Weblitzer\CFDev\Validation\Rules\Url;

['id' => 'website', 'type' => 'text', 'rules' => [new Url()]]
```

---

## Uuid

UUID v4 format.

```php
use Weblitzer\CFDev\Validation\Rules\Uuid;

['id' => 'external_id', 'type' => 'text', 'rules' => [new Uuid()]]
```

---

## Regex

Custom regular expression.

```php
use Weblitzer\CFDev\Validation\Rules\Regex;

['id' => 'phone',     'type' => 'tel',  'rules' => [new Regex('/^(\+33|0)[1-9](\d{8})$/')]]
['id' => 'hex_color', 'type' => 'text', 'rules' => [new Regex('/^#[0-9a-fA-F]{6}$/')]]
```

---

## Contains

Value must contain the given substring.

```php
use Weblitzer\CFDev\Validation\Rules\Contains;

['id' => 'internal_link', 'type' => 'url', 'rules' => [new Contains('example.com')]]
```

---

## StartsWith / EndsWith

Value must start / end with the given string.

```php
use Weblitzer\CFDev\Validation\Rules\StartsWith;
use Weblitzer\CFDev\Validation\Rules\EndsWith;

['id' => 'api_endpoint', 'type' => 'url', 'rules' => [new StartsWith('https://')]]
['id' => 'brochure',     'type' => 'file', 'rules' => [new EndsWith('.pdf')]]
```

---

## DateAfter / DateBefore

Date must be after / before the given date. Default format is `Y-m-d`.

```php
use Weblitzer\CFDev\Validation\Rules\DateAfter;
use Weblitzer\CFDev\Validation\Rules\DateBefore;

['id' => 'publish_date', 'type' => 'date', 'rules' => [new DateAfter('2020-01-01')]]
['id' => 'expires_at',   'type' => 'date', 'rules' => [new DateBefore('2030-12-31')]]

// Combined window
['id' => 'event_date', 'type' => 'date', 'rules' => [
    new DateAfter('2025-01-01'),
    new DateBefore('2026-12-31'),
]]
```

---

## DateAfterToday

Date must be strictly in the future.

```php
use Weblitzer\CFDev\Validation\Rules\DateAfterToday;

['id' => 'event_date', 'type' => 'date', 'rules' => [new Required(), new DateAfterToday()]]

// Custom format (if datepicker returns d/m/Y)
['id' => 'deadline', 'type' => 'date', 'rules' => [new DateAfterToday('d/m/Y')]]
```

---

## FileExtension

Attachment must have an allowed extension. Receives the attachment ID.

```php
use Weblitzer\CFDev\Validation\Rules\FileExtension;

['id' => 'brochure', 'type' => 'file',  'rules' => [new Required(), new FileExtension(['pdf'])]]
['id' => 'banner',   'type' => 'image', 'rules' => [new FileExtension(['jpg', 'jpeg', 'png', 'webp'])]]
```

---

## FileMime

Attachment must have an allowed MIME type. More precise than `FileExtension`.

```php
use Weblitzer\CFDev\Validation\Rules\FileMime;

['id' => 'video', 'type' => 'file',  'rules' => [new FileMime(['video/mp4', 'video/webm'])]]
['id' => 'photo', 'type' => 'image', 'rules' => [new FileMime(['image/jpeg', 'image/png', 'image/webp'])]]
```

---

## ImageExactDimensions

Image must be exactly the specified dimensions. `width` and `height` are optional — validate one or both.

```php
use Weblitzer\CFDev\Validation\Rules\ImageExactDimensions;

['id' => 'og_image', 'type' => 'image', 'rules' => [new Required(), new ImageExactDimensions(1200, 630)]]

// Width only
['id' => 'banner', 'type' => 'image', 'rules' => [new ImageExactDimensions(width: 1920)]]
```

---

## ImageMinDimensions

Image must be at least the specified dimensions.

```php
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;

['id' => 'hero', 'type' => 'image', 'rules' => [new Required(), new ImageMinDimensions(1200, 600)]]

// Minimum width only
['id' => 'cover', 'type' => 'image', 'rules' => [new ImageMinDimensions(width: 800)]]
```

---

## Summary

| Category | Rules |
|---|---|
| Required | `Required` |
| Length | `MinLength`, `MaxLength`, `ExactLength` |
| Number | `Min`, `Max`, `Between`, `IsNumeric`, `Positive` |
| Items | `MinItems`, `MaxItems` |
| Format | `Alpha`, `AlphaNumeric`, `Slug`, `Email`, `Url`, `Uuid` |
| String | `Regex`, `Contains`, `StartsWith`, `EndsWith` |
| Dates | `DateAfter`, `DateBefore`, `DateAfterToday` |
| Files | `FileExtension`, `FileMime` |
| Images | `ImageExactDimensions`, `ImageMinDimensions` |
