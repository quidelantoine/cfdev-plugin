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
        add_action('admin_notices', [self::class, 'noticeClassicEditor']);
    }

    /**
     * Shows a warning when Classic Editor is not active.
     * CFDev meta boxes run inside a Gutenberg iframe without it,
     * which breaks AJAX fields and Wysiwyg editors.
     * Only shown on post edit screens and CFDev admin pages.
     */
    public static function noticeClassicEditor(): void
    {
        $screen = get_current_screen();
        if (! $screen) {
            return;
        }

        $on_post_edit = in_array($screen->base, ['post', 'post-new'], true);
        $on_cfdev     = str_contains($screen->id, 'cfdev');

        if (! $on_post_edit && ! $on_cfdev) {
            return;
        }

        if (function_exists('is_plugin_active') && is_plugin_active('classic-editor/classic-editor.php')) {
            return;
        }

        $install_url = admin_url('plugin-install.php?s=classic+editor&tab=search&type=term');

        $message = esc_html__(
            'The Classic Editor plugin is recommended. Without it, CFDev meta boxes run inside'
            . ' a block editor iframe and some features (AJAX fields, Wysiwyg) may not work correctly.',
            'cfdev'
        );
        $link = sprintf(
            '<a href="%s">%s</a>',
            esc_url($install_url),
            esc_html__('Install Classic Editor →', 'cfdev')
        );
        printf(
            '<div class="notice notice-warning is-dismissible"><p><strong>CFDev</strong> — %s %s</p></div>',
            $message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $link     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        );
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
