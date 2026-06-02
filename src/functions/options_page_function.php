<?php

/**
 * Registers an options page whose fields are stored in wp_options.
 *
 * @param string               $id     Unique slug — also used as option key prefix.
 * @param string|array<string> $title  Page title, or [title, description].
 * @param array<mixed>         $data   Field definitions (same format as addMetaBox).
 * @return \Weblitzer\CFDev\OptionsPage
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 * @example
 *   register_cfdev_options_page('site_settings', 'Site Settings', $fields);
 *   register_cfdev_options_page('social', 'Social', $fields)->asSubmenu('options-general.php');
 */
function register_cfdev_options_page(string $id, string|array $title, array $data = []): \Weblitzer\CFDev\OptionsPage
{
    if (! class_exists('\Weblitzer\CFDev\OptionsPage')) {
        throw new \RuntimeException('CFDev plugin must be active to use register_cfdev_options_page().');
    }

    return new \Weblitzer\CFDev\OptionsPage($id, $title, $data);
}
