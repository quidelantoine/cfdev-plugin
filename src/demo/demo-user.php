<?php

/**
 * CFDev Demo — User meta (sections 11-13)
 */

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
        'Infos'  => generateArrayAllField('demo', 'user_tab_a'),
        'Médias' => [
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
