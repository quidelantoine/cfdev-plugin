<?php

namespace Weblitzer\CFDev\Config\Ajax;

use Weblitzer\CFDev\Contracts\Registerable;

/**
 * Gère l'enregistrement et le chargement des assets (styles et scripts)
 *
 * @package    CFDev
 * @subpackage CFDev\Config\Ajax
 * 
 * @author     quidelantoine
 * @since   1.0.0
 */
class AjaxHandler implements Registerable
{
    /**
     * Enregistre tous les hooks WordPress
     *
     * @since   1.0.0
     * @return void
     */
    public function register(): void
    {
        // Ajax
        add_action('wp_ajax_cfdev_field_ajax_save', ['Weblitzer\\CFDev\\Field', 'ajaxSave']);
    }
}
