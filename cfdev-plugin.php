<?php

/**
 * Plugin Name:       Custom Field For Dev
 * Version:           1.0.0
 * Requires PHP:      8.2
 * Requires at least: 6.5
 * Tested up to:      7.0
 * Text Domain:       cfdev
 */

defined('ABSPATH') || exit;

if (version_compare(PHP_VERSION, '8.2', '<')) {
    add_action('admin_notices', fn() =>
        print '<div class="notice notice-error"><p>Custom Field For Dev requires PHP 8.2+.</p></div>');
    return;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return;
}
require __DIR__ . '/vendor/autoload.php';

Weblitzer\CFDev\Initializer::instance(__FILE__);
