<?php

namespace Weblitzer\CFDev\Admin;

/**
 * CFDev — Get Data page.
 * Debug viewer: displays data cached during development via CFDev::debug().
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class GetDataPage extends AdminPage
{
    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <?php self::header(__('Get Data', 'cfdev'), __('Debug', 'cfdev')); ?>
            <?php self::placeholder(); ?>
        </div>
        <?php
    }
}