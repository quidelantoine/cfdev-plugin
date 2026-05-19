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
        ?>
        <div class="cfdev-header">
            <h1 class="cfdev-header__title">
                <span class="cfdev-logo">CF</span>
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
        $text = $label ?: __('Cette section est en cours de développement.', 'cfdev');
        ?>
        <div class="cfdev-placeholder">
            <span class="cfdev-placeholder__icon" aria-hidden="true">⚙</span>
            <p class="cfdev-placeholder__text"><?php echo esc_html($text); ?></p>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Base styles (shared across all pages)
    // -------------------------------------------------------------------------

    protected static function baseStyles(): void
    {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '
<style id="cfdev-base-css">
/* ── Shared CFDev admin styles ───────────────────────────────────── */
.cfdev-header {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0 20px;
}
.cfdev-header__title {
    margin: 0; font-size: 23px;
    display: flex; align-items: center; gap: 10px;
}
.cfdev-logo {
    background: #2271b1; color: #fff;
    font-size: 12px; font-weight: 800;
    padding: 4px 8px; border-radius: 4px; letter-spacing: -.5px;
}
.cfdev-header__sub {
    font-size: 13px; color: #787c82;
    background: #f6f7f7; border: 1px solid #c3c4c7;
    padding: 2px 10px; border-radius: 10px;
}

/* ── Placeholder ─────────────────────────────────────────────────── */
.cfdev-placeholder {
    display: flex; flex-direction: column; align-items: center;
    gap: 12px; padding: 60px 20px;
    background: #fff; border: 1px solid #c3c4c7;
    border-radius: 3px; color: #787c82; text-align: center;
    max-width: 480px; margin: 0 auto;
}
.cfdev-placeholder__icon { font-size: 36px; opacity: .35; }
.cfdev-placeholder__text { margin: 0; font-size: 14px; }
</style>';
        // phpcs:enable
    }
}
