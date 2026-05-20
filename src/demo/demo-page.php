<?php

/**
 * CFDev Demo — Page meta boxes
 */

$page = new \Weblitzer\CFDev\PostType('page');

// ── Flat — tous les types de champs ──────────────────────────────────
$page->addMetaBox('cfdev_demo_page_flat', '[DEMO] Page — Tous les champs', generateArrayAllField('demo', 'page_flat'))->onlyForTemplate('template-home.php');

// ── Bundle — tous les champs en lignes répétables ─────────────────────
$page->addMetaBox('cfdev_demo_page_bundle', '[DEMO] Page — Bundle', [
    'bundle',
    generateArrayAllField('demo', 'page_bundle'),
])->onlyForTemplate('template-home.php');

// ── Tabs — onglets avec champs plats ──────────────────────────────────
$page->addMetaBox('cfdev_demo_tabs', '[DEMO] Tabs', [
    'tabs',
    [
        'Onglet A' => generateArrayAllField('demo', 'tab_a'),
        'Onglet B' => [
            ['id' => '_demo_tab_b_file',  'type' => 'file',  'label' => 'Fichier', 'required' => true],
            ['id' => '_demo_tab_b_image', 'type' => 'image', 'label' => 'Image'],
        ],
    ],
])->onlyForTemplate('template-home.php');

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
])->onlyForTemplate('template-home.php');

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
])->onlyForTemplate('template-home.php');

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
])->onlyForTemplate('template-home.php');
