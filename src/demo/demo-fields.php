<?php

/**
 * CFDev — Demo fields
 *
 * Activé via Config::$demo = true dans Initializer::boot().
 *
 * Post / Page
 *   1. Flat        — tous les types de champs
 *   2. Tabs        — onglets avec champs plats
 *   3. Tabs+Bundle — onglets dont un contient un bundle
 *   4. Bundle      — lignes répétables, tous les types
 *   5. Accordion   — sections repliables avec champs plats
 *   6. Accordion+Bundle — sections dont une contient un bundle
 *   7. onlyForTemplate — visible seulement sur template-home.php
 *
 * Term meta sur 'category'
 *   8. Flat
 *   9. Tabs
 *  10. Accordion+Bundle
 *
 * User meta
 *  11. Flat
 *  12. Tabs
 *  13. Bundle
 */

require_once __DIR__ . '/helpers.php';

add_action('init', static function (): void {

    $post  = new \Weblitzer\CFDev\PostType('post');
    $page  = new \Weblitzer\CFDev\PostType('page');

    // ── 1. Flat — tous les types de champs ────────────────────────────────
    $post->addMetaBox(
        'cfdev_demo_flat',
        '[DEMO] Tous les champs',
        generateArrayAllField('demo', 'flat', [
            'text' => [
                new \Weblitzer\CFDev\Validation\Rules\Required(),
                new \Weblitzer\CFDev\Validation\Rules\MinLength(3),
                new \Weblitzer\CFDev\Validation\Rules\MaxLength(50),
            ],
            'textarea' => [
                new \Weblitzer\CFDev\Validation\Rules\MinLength(10),
                new \Weblitzer\CFDev\Validation\Rules\MaxLength(500),
            ],
            'wysiwyg' => [
                new \Weblitzer\CFDev\Validation\Rules\MinLength(20),
            ],
            'color' => [
                new \Weblitzer\CFDev\Validation\Rules\Regex('/^#[0-9a-fA-F]{3,6}$/'),
            ],
            'image' => [
                new \Weblitzer\CFDev\Validation\Rules\FileExtension(['jpg', 'png', 'webp']),
                new \Weblitzer\CFDev\Validation\Rules\ImageMinDimensions(width: 640, height: 360),
            ],
            'file' => [
                new \Weblitzer\CFDev\Validation\Rules\FileMime(['application/pdf']),
            ],
            'gallery' => [
                new \Weblitzer\CFDev\Validation\Rules\MinItems(1),
                new \Weblitzer\CFDev\Validation\Rules\MaxItems(10),
            ],
            'date' => [
                new \Weblitzer\CFDev\Validation\Rules\DateAfter('2020-01-01'),
                new \Weblitzer\CFDev\Validation\Rules\DateBefore('2030-12-31'),
            ],
            'datetime' => [
                new \Weblitzer\CFDev\Validation\Rules\DateAfterToday(),
            ],
            'time' => [
                new \Weblitzer\CFDev\Validation\Rules\Regex('/^\d{2}:\d{2}$/'),
            ],
            'select'          => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'multi_select'    => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'checkboxes'      => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'radios'          => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'post_select'     => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'post_checkboxes' => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'term_select'     => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'term_checkboxes' => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'user_select'     => [new \Weblitzer\CFDev\Validation\Rules\Required()],
        ])
    );

    // ── 2. Tabs — onglets avec champs plats ───────────────────────────────
    $page->addMetaBox('cfdev_demo_tabs', '[DEMO] Tabs', [
        'tabs',
        [
            'Onglet A' => generateArrayAllField('demo', 'tab_a'),
            'Onglet B' => [
                ['id' => '_demo_tab_b_file',  'type' => 'file',  'label' => 'Fichier', 'required' => true],
                ['id' => '_demo_tab_b_image', 'type' => 'image', 'label' => 'Image'],
            ],
        ],
    ]);

    // ── 3. Tabs + Bundle ──────────────────────────────────────────────────
    $page->addMetaBox('cfdev_demo_tabs_bundle', '[DEMO] Tabs avec bundle', [
        'tabs',
        [
            'Tab avec bundle' => [
                ['bundle', generateArrayAllField('demo', 'tab_bundle')],
            ],
            'Tab normal' => [
                ['id' => '_demo_tbn_image', 'type' => 'image', 'label' => 'Image',   'required' => true, 'repeatable' => true],
                ['id' => '_demo_tbn_text',  'type' => 'text',  'label' => 'Libellé', 'required' => true, 'show_admin_column' => true],
            ],
        ],
    ]);

    // ── 4. Bundle — tous les champs en lignes répétables ──────────────────
    $post->addMetaBox('cfdev_demo_bundle', '[DEMO] Bundle', [
        'bundle',
        generateArrayAllField('demo', 'bundle', [
            'text'   => [new \Weblitzer\CFDev\Validation\Rules\Required()],
            'select' => [new \Weblitzer\CFDev\Validation\Rules\Required()],
        ]),
    ]);

    // ── 5. Accordion — sections repliables ───────────────────────────────
    $page->addMetaBox('cfdev_demo_accordion', '[DEMO] Accordéon', [
        'accordion',
        [
            'Section A' => generateArrayAllField('demo', 'acc_a'),
            'Section B' => [
                ['id' => '_demo_acc_b_title', 'type' => 'text', 'label' => 'Titre B', 'required' => true],
                ['id' => '_demo_acc_b_text',  'type' => 'text', 'label' => 'Texte B', 'required' => true],
            ],
        ],
    ]);

    // ── 6. Accordion + Bundle ────────────────────────────────────────────
    $page->addMetaBox('cfdev_demo_accordion_bundle', '[DEMO] Accordéon avec bundle', [
        'accordion',
        [
            'Section avec bundle' => [
                ['bundle', generateArrayAllField('demo', 'acc_bundle')],
            ],
            'Section normale' => [
                ['id' => '_demo_accn_text',  'type' => 'text',  'label' => 'Champ normal', 'required' => true],
                ['id' => '_demo_accn_image', 'type' => 'image', 'label' => 'Image normale'],
            ],
        ],
    ]);

    // ── 7. Page — visible seulement sur template-home.php ────────────────
    $page->addMetaBox('cfdev_demo_home', '[DEMO] Home Hero', [
        ['id' => '_demo_home_title',    'type' => 'text',  'label' => 'Titre',     'required' => true],
        ['id' => '_demo_home_subtitle', 'type' => 'text',  'label' => 'Sous-titre'],
        ['id' => '_demo_home_image',    'type' => 'image', 'label' => 'Image hero'],
    ])
        ->onlyForTemplate('template-home.php');

    // ── 8. Term meta flat — taxonomy 'category' ───────────────────────────
    new \Weblitzer\CFDev\Meta\TermMeta('category', generateArrayAllField('demo', 'term'));

    // ── 9. Term meta Tabs — taxonomy 'category' ───────────────────────────
    new \Weblitzer\CFDev\Meta\TermMeta('category', [
        'tabs',
        [
            'Onglet A' => generateArrayAllField('demo', 'term_tab_a'),
            'Onglet B' => [
                ['id' => '_demo_term_tab_b_color', 'type' => 'color', 'label' => 'Couleur'],
                ['id' => '_demo_term_tab_b_image', 'type' => 'image', 'label' => 'Image'],
            ],
        ],
    ]);

    // ── 10. Term meta Accordion+Bundle — taxonomy 'category' ──────────────
    new \Weblitzer\CFDev\Meta\TermMeta('category', [
        'accordion',
        [
            'Infos'    => generateArrayAllField('demo', 'term_acc_a'),
            'Galerie'  => [
                ['bundle', generateArrayAllField('demo', 'term_acc_bundle')],
            ],
        ],
    ]);

    // ── 11. User meta flat — tous les utilisateurs ────────────────────────
    new \Weblitzer\CFDev\Meta\UserMeta(
        'cfdev_demo_user',
        '[DEMO] Profil',
        generateArrayAllField('demo', 'user')
    );

    // ── 12. User meta Tabs ────────────────────────────────────────────────
    new \Weblitzer\CFDev\Meta\UserMeta('cfdev_demo_user_tabs', '[DEMO] Profil — Tabs', [
        'tabs',
        [
            'Infos'    => generateArrayAllField('demo', 'user_tab_a'),
            'Médias'   => [
                ['id' => '_demo_user_tab_b_avatar', 'type' => 'image', 'label' => 'Avatar'],
                ['id' => '_demo_user_tab_b_site',   'type' => 'url',   'label' => 'Site web'],
            ],
        ],
    ]);

    // ── 13. User meta Bundle ──────────────────────────────────────────────
    new \Weblitzer\CFDev\Meta\UserMeta('cfdev_demo_user_bundle', '[DEMO] Profil — Bundle', [
        'bundle',
        generateArrayAllField('demo', 'user_bundle', [
            'text' => [new \Weblitzer\CFDev\Validation\Rules\Required()],
        ]),
    ]);
});