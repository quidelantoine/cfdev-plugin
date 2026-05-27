<?php

namespace Weblitzer\CFDev\Admin;

/**
 * Shared rendering helpers for all CFDev admin pages.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
abstract class AdminPage
{
    // -------------------------------------------------------------------------
    // Shared header
    // -------------------------------------------------------------------------

    protected static function header(string $title, string $subtitle = ''): void
    {
        $dashboard_url = admin_url('admin.php?page=cfdev');
        ?>
        <div class="cfdev-header">
            <h1 class="cfdev-header__title">
                <a href="<?php echo esc_url($dashboard_url); ?>" class="cfdev-logo" aria-label="<?php esc_attr_e('CFDev Dashboard', 'cfdev'); ?>">
                    <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>CF
                </a>
                <?php echo esc_html($title); ?>
            </h1>
            <?php if ($subtitle !== '') : ?>
            <span class="cfdev-header__sub"><?php echo esc_html($subtitle); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Empty-state placeholder
    // -------------------------------------------------------------------------

    protected static function placeholder(string $label = ''): void
    {
        $text = $label ?: __('This section is under development.', 'cfdev');
        ?>
        <div class="cfdev-placeholder">
            <span class="cfdev-placeholder__icon" aria-hidden="true">⚙</span>
            <p class="cfdev-placeholder__text"><?php echo esc_html($text); ?></p>
        </div>
        <?php
    }
}
