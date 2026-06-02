<?php

/**
 * CFDev Demo — Options pages (sections 16-20)
 *
 * Page principale autonome : tous les champs + sous-pages bundle/tabs/accordion
 * Sous "Réglages" WP       : champs plats (section 20)
 *
 * Lire une valeur : get_option('_opt_main_text')
 */

// ── 16. Page principale — tous les champs à plat (rest: true sur tous) ───────
register_cfdev_options_page(
    'cfdev_options_demo',
    ['[DEMO] Options — Tous les champs', 'Page de réglages globaux — couvre tous les types de champs.'],
    generateArrayAllField('opt', 'main', [], false, true)
)

// ── 17. Sous-page — Bundle (rest: true sur le bundle entier) ─────────────────
->addSubPage(
    'cfdev_options_bundle',
    '[DEMO] Options — Bundle',
    [
        'bundle',
        '_opt_bundle',
        generateArrayAllField('opt', 'bundle', [], false, false),
        ['rest' => true],
    ]
)

// ── 18. Sous-page — Tabs (onglet normal + onglet avec bundle) ─────────────────
->addSubPage(
    'cfdev_options_tabs',
    '[DEMO] Options — Tabs',
    [
        'tabs',
        [
            'Champs plats' => [
                ['id' => '_opt_tab_a_text',    'type' => 'text',     'label' => 'Texte',    'rest' => true],
                ['id' => '_opt_tab_a_textarea', 'type' => 'textarea', 'label' => 'Textarea'],
                ['id' => '_opt_tab_a_email',   'type' => 'email',    'label' => 'E-mail',   'rest' => true],
                ['id' => '_opt_tab_a_url',     'type' => 'url',      'label' => 'URL',      'rest' => true],
                ['id' => '_opt_tab_a_number',  'type' => 'number',   'label' => 'Nombre',   'rest' => true],
                ['id' => '_opt_tab_a_toggle',  'type' => 'toggle',   'label' => 'Toggle'],
                ['id' => '_opt_tab_a_select',  'type' => 'select',   'label' => 'Select',
                    'options' => ['v1' => 'Option 1', 'v2' => 'Option 2', 'v3' => 'Option 3'],
                    'args'    => ['show_option_none' => '-- Choisir --']],
                ['id' => '_opt_tab_a_image',   'type' => 'image',    'label' => 'Image'],
                ['id' => '_opt_tab_a_wysiwyg', 'type' => 'wysiwyg',  'label' => 'Wysiwyg'],
            ],
            'Bundle dans tab' => [
                [
                    'bundle',
                    '_opt_tab_bundle',
                    [
                        ['id' => '_opt_tb_title', 'type' => 'text',     'label' => 'Titre'],
                        ['id' => '_opt_tb_text',  'type' => 'textarea', 'label' => 'Texte'],
                        ['id' => '_opt_tb_image', 'type' => 'image',    'label' => 'Image'],
                        ['id' => '_opt_tb_url',   'type' => 'url',      'label' => 'Lien'],
                    ],
                    ['rest' => true],
                ],
            ],
        ],
    ]
)

// ── 19. Sous-page — Accordion (section normale + section avec bundle) ─────────
->addSubPage(
    'cfdev_options_accordion',
    '[DEMO] Options — Accordion',
    [
        'accordion',
        [
            'Informations générales' => [
                ['id' => '_opt_acc_site_name',  'type' => 'text',  'label' => 'Nom du site',   'required' => true],
                ['id' => '_opt_acc_site_desc',  'type' => 'textarea', 'label' => 'Description'],
                ['id' => '_opt_acc_site_email', 'type' => 'email', 'label' => 'E-mail de contact'],
                ['id' => '_opt_acc_site_phone', 'type' => 'tel',   'label' => 'Téléphone'],
                ['id' => '_opt_acc_site_logo',  'type' => 'image', 'label' => 'Logo'],
            ],
            'Réseaux sociaux' => [
                ['id' => '_opt_acc_facebook',  'type' => 'url', 'label' => 'Facebook',    'rest' => true],
                ['id' => '_opt_acc_instagram', 'type' => 'url', 'label' => 'Instagram',   'rest' => true],
                ['id' => '_opt_acc_twitter',   'type' => 'url', 'label' => 'Twitter / X', 'rest' => true],
                ['id' => '_opt_acc_linkedin',  'type' => 'url', 'label' => 'LinkedIn',    'rest' => true],
                ['id' => '_opt_acc_youtube',   'type' => 'url', 'label' => 'YouTube',     'rest' => true],
            ],
            'Bundle dans accordion' => [
                [
                    'bundle',
                    '_opt_acc_bundle',
                    [
                        ['id' => '_opt_ab_label', 'type' => 'text',  'label' => 'Libellé'],
                        ['id' => '_opt_ab_value', 'type' => 'text',  'label' => 'Valeur'],
                        ['id' => '_opt_ab_color', 'type' => 'color', 'label' => 'Couleur'],
                    ],
                    ['rest' => true],
                ],
            ],
        ],
    ]
);

// ── 20. Sous "Réglages" WP — champs plats ────────────────────────────────────
register_cfdev_options_page(
    'cfdev_options_reglages',
    ['[DEMO] Options — Sous Réglages', 'Page CFDev accessible via Réglages → CFDev Demo.'],
    [
        ['id' => '_opt_rgl_site_name',   'type' => 'text',     'label' => 'Nom du site',         'required' => true, 'rest' => true],
        ['id' => '_opt_rgl_tagline',     'type' => 'text',     'label' => 'Slogan',               'rest' => true],
        ['id' => '_opt_rgl_email',       'type' => 'email',    'label' => 'E-mail',               'rest' => true],
        ['id' => '_opt_rgl_phone',       'type' => 'tel',      'label' => 'Téléphone',            'rest' => true],
        ['id' => '_opt_rgl_address',     'type' => 'textarea', 'label' => 'Adresse'],
        ['id' => '_opt_rgl_logo',        'type' => 'image',    'label' => 'Logo'],
        ['id' => '_opt_rgl_color',       'type' => 'color',    'label' => 'Couleur principale'],
        ['id' => '_opt_rgl_maintenance', 'type' => 'toggle',   'label' => 'Mode maintenance'],
    ]
)->asSubmenu('options-general.php');
