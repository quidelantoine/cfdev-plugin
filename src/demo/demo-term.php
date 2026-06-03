<?php

/**
 * CFDev Demo — Term meta (sections 8-10)
 */

use Weblitzer\CFDev\Meta\TermMeta;

// ── 8. Term meta flat — taxonomy 'category' ───────────────────────────
new TermMeta('category', 'Catégorie — Tous les champs', generateArrayAllField('demo', 'term', [], true, false));

// ── 8b. Term meta Bundle — taxonomy 'category' ───────────────────────
new TermMeta('category', 'Catégorie — Bundle', [
    'bundle',
    generateArrayAllField('demo', 'term_bundle'),
]);

// ── 9. Term meta Tabs — taxonomy 'category' ───────────────────────────
new TermMeta('category', 'Catégorie — Tabs', [
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
new TermMeta('category', 'Catégorie — Accordéon with Bundle', [
    'accordion',
    [
        'Infos'   => generateArrayAllField('demo', 'term_acc_a'),
        'Galerie' => [
            ['bundle', generateArrayAllField('demo', 'term_acc_bundle'),['rest' => true]],
        ],
    ],
]);

// ── [DEMO] Conditions sur TermMeta ────────────────────────────────────────────
// ID résolu par slug. Fallback à 1 si absent — le groupe s'affiche quand même
// dans le Dashboard avec le badge de condition.
$_cfdev_uncat    = get_term_by('slug', 'uncategorized', 'category');
$_cfdev_uncat_id = ($_cfdev_uncat instanceof \WP_Term) ? $_cfdev_uncat->term_id : 1;

// onlyForId — champs visibles uniquement sur la catégorie "Uncategorized"
(new TermMeta('category', 'Catégorie — onlyForId (Uncategorized)', [
    ['id' => '_demo_term_id_note',  'type' => 'text',  'label' => 'Note exclusive'],
    ['id' => '_demo_term_id_image', 'type' => 'image', 'label' => 'Image exclusive'],
]))->onlyForId($_cfdev_uncat_id);

// onlyIfParent — champs visibles uniquement pour les sous-catégories directes d'Uncategorized
(new TermMeta('category', 'Catégorie — onlyIfParent (Sous-catégorie DEMO)', [
    ['id' => '_demo_term_parent_note', 'type' => 'text', 'label' => 'Info sous-catégorie'],
]))->onlyIfParent($_cfdev_uncat_id);

unset($_cfdev_uncat, $_cfdev_uncat_id);
