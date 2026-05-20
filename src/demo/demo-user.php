<?php

/**
 * CFDev Demo — User meta (sections 11-13)
 */

use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Validation\Rules\Required;

// ── 11. User meta flat — tous les utilisateurs ────────────────────────
new UserMeta(
    'cfdev_demo_user',
    '[DEMO] Profil',
    generateArrayAllField('demo', 'user')
);

// ── 12. User meta Tabs ────────────────────────────────────────────────
new UserMeta('cfdev_demo_user_tabs', '[DEMO] Profil — Tabs', [
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
new UserMeta('cfdev_demo_user_bundle', '[DEMO] Profil — Bundle', [
    'bundle',
    generateArrayAllField('demo', 'user_bundle', [
        'text' => [new Required()],
    ]),
]);
