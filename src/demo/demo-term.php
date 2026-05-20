<?php

/**
 * CFDev Demo — Term meta (sections 8-10)
 */

// ── 8. Term meta flat — taxonomy 'category' ───────────────────────────
new \Weblitzer\CFDev\Meta\TermMeta('category', 'Catégorie — Tous les champs', generateArrayAllField('demo', 'term'));

// ── 9. Term meta Tabs — taxonomy 'category' ───────────────────────────
new \Weblitzer\CFDev\Meta\TermMeta('category', 'Catégorie — Tabs', [
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
new \Weblitzer\CFDev\Meta\TermMeta('category', 'Catégorie — Accordéon with Bundle', [
    'accordion',
    [
        'Infos'   => generateArrayAllField('demo', 'term_acc_a'),
        'Galerie' => [
            ['bundle', generateArrayAllField('demo', 'term_acc_bundle')],
        ],
    ],
]);
