<?php

namespace Weblitzer\CFDev\Admin\Export;

use Weblitzer\CFDev\Registry;

/**
 * Handles the admin_post_cfdev_export action.
 *
 * A form POST from DashboardPage reaches admin-post.php, which fires
 * admin_post_cfdev_export. This class verifies the nonce, serialises
 * Registry data and streams the file to the browser.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.7
 */
final class ExportHandler
{
    public const ACTION = 'cfdev_export';
    public const NONCE  = 'cfdev_export';

    public static function register(): void
    {
        add_action('admin_post_' . self::ACTION, [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions.', 'cfdev'), 403);
        }

        $nonce = isset($_POST['cfdev_export_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['cfdev_export_nonce']))
            : '';

        if (! wp_verify_nonce($nonce, self::NONCE)) {
            wp_die(esc_html__('Security check failed.', 'cfdev'), 403);
        }

        $format = isset($_POST['cfdev_export_format'])
            ? sanitize_text_field(wp_unslash($_POST['cfdev_export_format']))
            : 'json';

        $groups   = Registry::exportGroups();
        $filename = 'cfdev-export-' . gmdate('Ymd-His');

        if ($format === 'php') {
            $content      = FieldExporter::toPhp($groups);
            $content_type = 'text/plain';
            $filename    .= '.php';
        } else {
            $content      = FieldExporter::toJson($groups);
            $content_type = 'application/json';
            $filename    .= '.json';
        }

        header('Content-Type: ' . $content_type . '; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('X-Robots-Tag: noindex');

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $content;
        exit;
    }
}
