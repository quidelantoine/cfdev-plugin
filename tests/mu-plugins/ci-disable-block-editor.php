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

// Direct render endpoint for spec 11 front-end tests.
// ?cfdev_render=POST_ID bypasses WordPress page routing entirely — the page slug
// 'cfdev-test' can be unresolvable in CI after a fresh wp-env reinstall (rewrite
// rules / permalink cache not flushed at the right moment). This hook intercepts
// any request that carries ?cfdev_render=N and serves the template directly,
// returning HTTP 200 regardless of what WordPress's query resolver thinks.
add_action('template_redirect', function (): void {
    $post_id = isset($_GET['cfdev_render']) ? (int) $_GET['cfdev_render'] : 0;
    if ($post_id <= 0) {
        return;
    }
    if (! current_user_can('edit_posts')) {
        status_header(403);
        exit('Access denied.');
    }
    $_GET['post_id'] = (string) $post_id;
    status_header(200);
    $plugin = WP_CONTENT_DIR . '/plugins/cfdev-plugin';
    require $plugin . '/src/templates/template-cfdev-test.php';
    exit;
}, 1);
