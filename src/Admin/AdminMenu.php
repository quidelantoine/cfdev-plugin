<?php

namespace Weblitzer\CFDev\Admin;

/**
 * Registers all CFDev admin pages and sub-menu items.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class AdminMenu
{
    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'build']);
    }

    public static function build(): void
    {
        // Main entry → Dashboard (field groups list)
        add_menu_page(
            __('CFDev', 'cfdev'),
            'CFDev',
            'manage_options',
            'cfdev',
            [DashboardPage::class, 'render'],
            'dashicons-lightbulb',
            82
        );

        // Sub-pages (first one renames the parent item in the sidebar)
        add_submenu_page('cfdev', __('Dashboard', 'cfdev'), __('Dashboard', 'cfdev'), 'manage_options', 'cfdev', [DashboardPage::class, 'render']);
        add_submenu_page('cfdev', __('Cache', 'cfdev'), __('Cache', 'cfdev'), 'manage_options', 'cfdev-cache', [CachePage::class, 'render']);
        add_submenu_page('cfdev', __('REST API', 'cfdev'), __('REST API', 'cfdev'), 'manage_options', 'cfdev-rest', [RestPage::class, 'render']);
    }
}
