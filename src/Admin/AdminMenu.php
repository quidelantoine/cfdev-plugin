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
        // Main entry → Dashboard
        add_menu_page(
            __('CFDev', 'cfdev'),
            'CFDev',
            'manage_options',
            'cfdev',
            [DashboardPage::class, 'render'],
            'dashicons-screenoptions',
            30
        );

        // Sub-pages (first one renames the parent item in the sidebar)
        add_submenu_page('cfdev', __('Tableau de bord', 'cfdev'), __('Tableau de bord', 'cfdev'), 'manage_options', 'cfdev', [DashboardPage::class, 'render']);
        add_submenu_page('cfdev', __('Champs', 'cfdev'), __('Champs', 'cfdev'), 'manage_options', 'cfdev-fields', [FieldsPage::class,    'render']);
        add_submenu_page('cfdev', __('Réglages', 'cfdev'), __('Réglages', 'cfdev'), 'manage_options', 'cfdev-settings', [SettingsPage::class,  'render']);
        add_submenu_page('cfdev', __('Cache', 'cfdev'), __('Cache', 'cfdev'), 'manage_options', 'cfdev-cache', [CachePage::class,     'render']);
        add_submenu_page('cfdev', __('Get Data', 'cfdev'), __('Get Data', 'cfdev'), 'manage_options', 'cfdev-getdata', [GetDataPage::class,   'render']);
    }
}
