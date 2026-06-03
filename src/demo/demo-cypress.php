<?php

/**
 * CFDev Demo — Cypress fixtures for post + front-end test page template.
 *
 * Registers Tabs + Accordion directly on 'post' without any template
 * restriction so Cypress tests can target /wp-admin/post-new.php.
 *
 * Also registers the "CFDev Test" page template from the plugin so the
 * theme does not need to provide it (see src/templates/template-cfdev-test.php).
 */

use Weblitzer\CFDev\PostType;

// ── Front-end test page template ──────────────────────────────────────────────
// Expose the template to WP so it appears in the page editor dropdown.
add_filter('theme_page_templates', static function (array $templates): array {
    $templates['template-cfdev-test.php'] = 'CFDev Test';
    return $templates;
});

// Serve the plugin's template file when a page has this template selected.
add_filter('template_include', static function (string $template): string {
    $post_id = get_the_ID();
    if (is_page() && $post_id !== false) {
        $meta = get_post_meta($post_id, '_wp_page_template', true);
        if ($meta === 'template-cfdev-test.php') {
            $plugin_tpl = __DIR__ . '/../templates/template-cfdev-test.php';
            if (file_exists($plugin_tpl)) {
                return $plugin_tpl;
            }
        }
    }
    return $template;
});

$postType = new PostType('post');

// ── [CYPRESS] Tabs on post ────────────────────────────────────────────
// Tab 'Champs plats': generateArrayAllField('cypress', 'tab_a')
// Tab 'Bundle':       bundle ID = _cfdev_cypress_tabs (buildId prepends _)
$postType->addMetaBox('cfdev_cypress_tabs', '[CYPRESS] Tabs', [
    'tabs',
    [
        'Champs plats' => generateArrayAllField('cypress', 'tab_a'),
        'Bundle' => [
            ['bundle', generateArrayAllField('cypress', 'tab_bundle')],
        ],
    ],
]);

// ── [CYPRESS] Accordion on post ───────────────────────────────────────
// Section 'Champs plats': generateArrayAllField('cypress', 'acc_a')
// Section 'Bundle':       bundle ID = _cfdev_cypress_accordion (buildId prepends _)
$postType->addMetaBox('cfdev_cypress_accordion', '[CYPRESS] Accordéon', [
    'accordion',
    [
        'Champs plats' => generateArrayAllField('cypress', 'acc_a'),
        'Bundle' => [
            ['bundle', generateArrayAllField('cypress', 'acc_bundle')],
        ],
    ],
]);

// ── [CYPRESS] onlyWhen conditions ────────────────────────────────────────────
// cfdev_cypress_cond_admin  — condition passes (Cypress logs in as admin)
// cfdev_cypress_cond_pending — condition fails  (post is never 'pending' in normal test flow)
$postType->addMetaBox('cfdev_cypress_cond_admin', '[CYPRESS] onlyWhen (admin)', [
    ['id' => '_cond_admin_text', 'type' => 'text', 'label' => 'Admin only'],
])->onlyWhen(fn(\WP_Post $p) => current_user_can('manage_options'), 'Admins uniquement');

$postType->addMetaBox('cfdev_cypress_cond_pending', '[CYPRESS] onlyWhen (pending)', [
    ['id' => '_cond_pending_text', 'type' => 'text', 'label' => 'Pending only'],
])->onlyWhen(fn(\WP_Post $p) => $p->post_status === 'pending', 'Statut : pending');

// ── [CYPRESS] Repeatable fields on post ──────────────────────────────────────
// Field IDs: _rep_text, _rep_number, _rep_email, _rep_select
// POST names: cfdev[_rep_text][], cfdev[_rep_number][], …
$postType->addMetaBox('cfdev_cypress_repeatable', '[CYPRESS] Repeatable', [
    ['id' => '_rep_text',   'type' => 'text',   'label' => 'Tags',     'repeatable' => true],
    ['id' => '_rep_number', 'type' => 'number', 'label' => 'Scores',   'repeatable' => true],
    ['id' => '_rep_email',  'type' => 'email',  'label' => 'Contacts', 'repeatable' => true],
    ['id' => '_rep_select', 'type' => 'select', 'label' => 'Status',
        'options'    => ['draft' => 'Brouillon', 'review' => 'Révision', 'done' => 'Terminé'],
        'repeatable' => true,
    ],
]);
