<?php

/**
 * CFDev Demo — Page meta boxes
 */

use Weblitzer\CFDev\PostType;

$pageType = new PostType('page');

// ── Flat — tous les types de champs ──────────────────────────────────
$pageType->addMetaBox('cfdev_demo_page_flat', '[DEMO] Page — Tous les champs', generateArrayAllField('demo', 'page_flat'))
    ->onlyForTemplate('template-home.php');

// ── Bundle — tous les champs en lignes répétables ─────────────────────
$pageType->addMetaBox('cfdev_demo_page_bundle', '[DEMO] Page — Bundle', [
    'bundle',
    generateArrayAllField('demo', 'page_bundle'),
])->onlyForTemplate('template-home.php');

// ── Tabs — onglets avec champs plats ──────────────────────────────────
$pageType->addMetaBox('cfdev_demo_tabs', '[DEMO] Tabs', [
    'tabs',
    [
        'Onglet A' => generateArrayAllField('demo', 'tab_a'),
        'Onglet B' => [
            ['id' => '_demo_tab_b_file',  'type' => 'file',  'label' => 'Fichier', 'required' => true, 'rest' => true],
            ['id' => '_demo_tab_b_image', 'type' => 'image', 'label' => 'Image'],
        ],
    ],
])->onlyForTemplate('template-home.php');

// ── 3. Tabs + Bundle ──────────────────────────────────────────────────
$pageType->addMetaBox('cfdev_demo_tabs_bundle', '[DEMO] Tabs avec bundle', [
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
$pageType->addMetaBox('cfdev_demo_accordion', '[DEMO] Accordéon', [
    'accordion',
    [
        'Section A' => generateArrayAllField('demo', 'acc_a'),
        'Section B' => [
            ['id' => '_demo_acc_b_title', 'type' => 'text', 'label' => 'Titre B', 'required' => true],
            ['id' => '_demo_acc_b_text',  'type' => 'text', 'label' => 'Texte B', 'required' => true],
        ],
    ],
])->onlyForTemplate('template-home.php');

// ── [DEMO] onlyForId — champs réservés à la page "CFDev Test" ────────────────
// L'ID est résolu dynamiquement par slug pour rester valide après une réinstallation.
// Si la page n'existe pas encore (avant le seed), le bloc est ignoré silencieusement.
// ID résolu par slug. Fallback à 0 si absent — le groupe s'affiche quand même
// dans le Dashboard avec le badge de condition (MetaBox jamais visible en edit).
$_cfdev_test_page    = get_page_by_path('cfdev-test');
$_cfdev_test_page_id = ($_cfdev_test_page instanceof \WP_Post) ? $_cfdev_test_page->ID : 0;

$pageType->addMetaBox('cfdev_demo_page_id', '[DEMO] Page — onlyForId', [
    ['id' => '_demo_page_id_note',  'type' => 'text',    'label' => 'Note exclusive'],
    ['id' => '_demo_page_id_image', 'type' => 'image',   'label' => 'Image exclusive'],
    ['id' => '_demo_page_id_html',  'type' => 'wysiwyg', 'label' => 'Contenu exclusif'],
])->onlyForId($_cfdev_test_page_id);

unset($_cfdev_test_page, $_cfdev_test_page_id);

// ── 6. Accordion + Bundle ────────────────────────────────────────────
$pageType->addMetaBox('cfdev_demo_accordion_bundle', '[DEMO] Accordéon avec bundle', [
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
