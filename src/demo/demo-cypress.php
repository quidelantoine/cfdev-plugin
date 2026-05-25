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
