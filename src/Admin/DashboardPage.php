<?php

namespace Weblitzer\CFDev\Admin;

/**
 * CFDev — Dashboard page.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class DashboardPage extends AdminPage
{
    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        self::baseStyles();
        ?>
        <div class="wrap">
            <?php self::header(__('Tableau de bord', 'cfdev')); ?>
            <?php self::placeholder(); ?>
        </div>
        <?php
    }
}
