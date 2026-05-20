<?php

/**
 * CFDev Demo — Post meta boxes (sections 1, 4)
 */

use Weblitzer\CFDev\PostType;
use Weblitzer\CFDev\Validation\Rules\Alpha;
use Weblitzer\CFDev\Validation\Rules\AlphaNumeric;
use Weblitzer\CFDev\Validation\Rules\Between;
use Weblitzer\CFDev\Validation\Rules\Contains;
use Weblitzer\CFDev\Validation\Rules\DateAfter;
use Weblitzer\CFDev\Validation\Rules\DateAfterToday;
use Weblitzer\CFDev\Validation\Rules\DateBefore;
use Weblitzer\CFDev\Validation\Rules\Email;
use Weblitzer\CFDev\Validation\Rules\EndsWith;
use Weblitzer\CFDev\Validation\Rules\ExactLength;
use Weblitzer\CFDev\Validation\Rules\FileExtension;
use Weblitzer\CFDev\Validation\Rules\FileMime;
use Weblitzer\CFDev\Validation\Rules\ImageExactDimensions;
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\MaxItems;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\MinItems;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\Numeric;
use Weblitzer\CFDev\Validation\Rules\Positive;
use Weblitzer\CFDev\Validation\Rules\Regex;
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Slug;
use Weblitzer\CFDev\Validation\Rules\StartsWith;
use Weblitzer\CFDev\Validation\Rules\Url;
use Weblitzer\CFDev\Validation\Rules\Uuid;

$postType = new PostType('post');

// ── 1. Flat — tous les types de champs + couverture maximale des Rules ───
$postType->addMetaBox(
    'cfdev_demo_flat',
    '[DEMO] Tous les champs — Tests Validation/Rules',
    generateArrayAllField('demo', 'flat', [

        // Rules: Required, MinLength, MaxLength, Contains, StartsWith, EndsWith
        'text' => [
            new Required(),
            new MinLength(3),
            new MaxLength(50),
            new Contains('a'),
            new StartsWith('A'),
            new EndsWith('z'),
        ],

        // Rules: MinLength, MaxLength, Contains
        'textarea' => [
            new MinLength(10),
            new MaxLength(500),
            new Contains('demo'),
        ],

        // Rules: Required, Numeric, Positive, Min, Max
        'number' => [
            new Required(),
            new Numeric(),
            new Positive(),
            new Min(1),
            new Max(500),
        ],

        // Rules: Between
        'range' => [
            new Between(10, 90),
        ],

        // Rules: Required, Email
        'email' => [
            new Required(),
            new Email(),
        ],

        // Rules: Required, Url, StartsWith
        'url' => [
            new Required(),
            new Url(),
            new StartsWith('https://'),
        ],

        // Rules: Required, Regex (format téléphone)
        'tel' => [
            new Required(),
            new Regex('/^\+?[\d\s\-\.]{7,15}$/'),
        ],

        // Rules: Required, FileExtension, ImageMinDimensions
        'image' => [
            new Required(),
            new FileExtension(['jpg', 'png', 'webp']),
            new ImageMinDimensions(width: 640, height: 360),
        ],

        // Rules: FileExtension, ImageMinDimensions
        'image_alt' => [
            new FileExtension(['jpg', 'png', 'webp']),
            new ImageMinDimensions(width: 200, height: 200),
        ],

        // Rules: Required, MinItems, MaxItems, FileExtension
        'gallery' => [
            new Required(),
            new MinItems(2),
            new MaxItems(8),
            new FileExtension(['jpg', 'png', 'webp']),
        ],

        // Rules: Required, FileMime, FileExtension
        'file' => [
            new Required(),
            new FileMime(['application/pdf']),
            new FileExtension(['pdf']),
        ],

        // Rules: Required, MinItems, MaxItems
        'checkboxes' => [
            new Required(),
            new MinItems(1),
            new MaxItems(2),
        ],

        // Rules: Required, MinItems, MaxItems
        'multi_select' => [
            new Required(),
            new MinItems(1),
            new MaxItems(2),
        ],

        // Rules: Required
        'radios'  => [new Required()],
        'select'  => [new Required()],
        'yesno'   => [new Required()],
        'toggle'  => [new Required()],

        // Rules: MinLength, MaxLength
        'wysiwyg' => [
            new MinLength(20),
            new MaxLength(2000),
        ],

        // Rules: Regex (format hex couleur)
        'color' => [
            new Regex('/^#[0-9a-fA-F]{3,6}$/'),
        ],

        // Rules: Required, DateAfter, DateBefore
        'date' => [
            new Required(),
            new DateAfter('2020-01-01'),
            new DateBefore('2030-12-31'),
        ],

        // Rules: DateAfterToday, DateBefore
        'datetime' => [
            new DateAfterToday(),
            new DateBefore('2035-12-31'),
        ],

        // Rules: Regex (format HH:MM)
        'time' => [
            new Regex('/^\d{2}:\d{2}$/'),
        ],

        // Rules: Required, MinItems, MaxItems
        'post_checkboxes' => [
            new Required(),
            new MinItems(1),
            new MaxItems(5),
        ],

        // Rules: Required, MinItems, MaxItems
        'term_checkboxes' => [
            new Required(),
            new MinItems(1),
            new MaxItems(5),
        ],

        // Rules: Required, MinItems
        'user_checkboxes' => [
            new Required(),
            new MinItems(1),
        ],

        'post_select' => [new Required()],
        'term_select' => [new Required()],
        'user_select' => [new Required()],
    ])
);

// ── Rules avancées : Alpha, AlphaNumeric, ExactLength, Slug, Uuid, ImageExactDimensions ──
$postType->addMetaBox('cfdev_demo_extra_rules', '[DEMO] Rules avancées', [
    ['id' => '_demo_extra_alpha',    'type' => 'text',  'label' => 'Alphabétique uniquement',
        'explanation' => 'Uniquement des lettres (a-z, A-Z), pas d\'espaces ni chiffres',
        'rules' => [new Alpha()]],
    ['id' => '_demo_extra_alphanum', 'type' => 'text',  'label' => 'Alphanumérique',
        'explanation' => 'Lettres et chiffres uniquement, pas d\'espaces',
        'rules' => [new AlphaNumeric()]],
    ['id' => '_demo_extra_slug',     'type' => 'text',  'label' => 'Slug (ex: mon-article-2024)',
        'explanation' => 'Minuscules, chiffres et tirets uniquement',
        'rules' => [new Required(), new Slug()]],
    ['id' => '_demo_extra_exact',    'type' => 'text',  'label' => 'Code postal (5 caractères exactement)',
        'explanation' => 'Doit contenir exactement 5 caractères',
        'rules' => [new Required(), new ExactLength(5)]],
    ['id' => '_demo_extra_uuid',     'type' => 'text',  'label' => 'UUID (ex: 550e8400-e29b-41d4-a716-446655440000)',
        'explanation' => 'Format UUID v4 attendu',
        'rules' => [new Required(), new Uuid()]],
    ['id' => '_demo_extra_image',    'type' => 'image', 'label' => 'Image exacte 800×600',
        'explanation' => 'L\'image doit faire exactement 800 pixels de large et 600 pixels de haut',
        'rules' => [new ImageExactDimensions(width: 800, height: 600)]],
]);

// ── Bundle — tous les champs en lignes répétables ──────────────────────
$postType->addMetaBox('cfdev_demo_bundle', '[DEMO] Bundle', [
    'bundle',
    generateArrayAllField('demo', 'bundle', [
        'text'   => [new Required()],
        'select' => [new Required()],
    ]),
]);
