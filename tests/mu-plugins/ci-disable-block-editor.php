<?php

/**
 * Must-use plugin for wp-env / CI environments.
 *
 * 1. Disables Gutenberg so Cypress can reach classic meta boxes directly.
 * 2. Registers test page templates from the plugin directory so they appear in
 *    the Page Template dropdown regardless of which theme is active (no theme-
 *    directory dependency, no runtime `wp option get template` needed).
 */

add_filter('use_block_editor_for_post', '__return_false', 100);
add_filter('use_block_editor_for_post_type', '__return_false', 100);

add_filter('theme_page_templates', function (array $templates): array {
    $templates['template-home.php']       = 'Home';
    $templates['template-cfdev-test.php'] = 'CFDev Test';
    return $templates;
});

add_filter('template_include', function (string $template): string {
    $slug   = (string) get_page_template_slug();
    $plugin = WP_CONTENT_DIR . '/plugins/cfdev-plugin';
    $map    = [
        'template-home.php'       => $plugin . '/tests/templates/template-home.php',
        'template-cfdev-test.php' => $plugin . '/src/templates/template-cfdev-test.php',
    ];
    return ( isset($map[ $slug ]) && file_exists($map[ $slug ]) )
        ? $map[ $slug ]
        : $template;
});
