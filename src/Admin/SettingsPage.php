<?php

namespace Weblitzer\CFDev\Admin;

/**
 * CFDev — Settings page.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class SettingsPage extends AdminPage
{
    public const OPTION_CACHE = 'cfdev_cache_enabled';

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <?php self::header(__('Réglages', 'cfdev')); ?>
            <?php self::placeholder(__('Aucun réglage disponible pour l\'instant.', 'cfdev')); ?>
        </div>
        <?php
    }
}