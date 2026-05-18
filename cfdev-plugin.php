<?php

/**
 * Plugin Name:       Custom Field For Dev
 * Version:           1.0.0
 * Requires PHP:      8.3
 * Requires at least: 6.0
 * Text Domain:       cfdev
 */

defined('ABSPATH') || exit;

if (version_compare(PHP_VERSION, '8.1', '<')) {
    add_action('admin_notices', fn() =>
        print '<div class="notice notice-error"><p>Custom Field For Dev requires PHP 8.1+.</p></div>');
    return;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    return;
}
require __DIR__ . '/vendor/autoload.php';

CFDev\Initializer::instance(__FILE__);
