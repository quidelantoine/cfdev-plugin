<?php

// phpcs:disable PSR1.Files.SideEffects -- main plugin bootstrap, intentionally mixed.

/**
 * Plugin Name:       Custom Field For Dev
 * Description:       Code-first API for custom meta fields. 30+ types, bundles, tabs, validation, REST API and file cache.
 * Version:           1.0.8
 * Plugin URI:        https://github.com/quidelantoine/cfdev-plugin
 * Author:            quidelantoine
 * Author URI:        https://github.com/quidelantoine
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cfdev
 * Domain Path:       /languages
 * Requires PHP:      8.2
 * Requires at least: 6.5
 * Tested up to:      7.0
 */

defined('ABSPATH') || exit;

define('CFDEV_VERSION', '1.0.8');

if (version_compare(PHP_VERSION, '8.2', '<')) {
    add_action('admin_notices', fn() =>
        print '<div class="notice notice-error"><p>Custom Field For Dev requires PHP 8.2+.</p></div>');
    return;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'Weblitzer\\CFDev\\';
        if (! str_starts_with($class, $prefix)) {
            return;
        }
        $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

Weblitzer\CFDev\Initializer::instance(__FILE__);
