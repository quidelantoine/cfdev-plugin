<?php

/**
 * CFDev — Demo fields
 *
 * Activé via Config::$demo = true dans Initializer::boot().
 *
 * Post      → demo-post.php    (sections 1, 4)
 * Page      → demo-page.php    (sections 2, 3, 5, 6, 7)
 * Term meta → demo-term.php    (sections 8-10)
 * User meta → demo-user.php    (sections 11-13)
 * CPT custom → demo-custom.php (Leçons, Modules)
 */

require_once __DIR__ . '/helpers.php';

add_action('init', static function (): void {
    require __DIR__ . '/demo-post.php';
    require __DIR__ . '/demo-page.php';
    require __DIR__ . '/demo-term.php';
    require __DIR__ . '/demo-user.php';
    require __DIR__ . '/demo-custom.php';
});
