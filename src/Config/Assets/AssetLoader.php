<?php

namespace CFDev\Config\Assets;

use CFDev\Config\Config;
use CFDev\Contracts\Registerable;

/**
 * Gère l'enregistrement et le chargement des assets (styles et scripts)
 *
 * @package    CFDev
 * @subpackage CFDev\Config\Assets
 * 
 * @author     quidelantoine
 * @since   1.0.0
 */
class AssetLoader implements Registerable
{
    /**
     * @since   1.0.0
     * @param Config $config Configuration du plugin (url, version, dir)
     */
    public function __construct(
        private readonly Config $config
    ) {
    }

    /**
     * Enregistre tous les hooks WordPress
     *
     * @since   1.0.0
     * @return void
     */
    public function register(): void
    {
        add_action('admin_init', [$this, 'registerStyles']);
        add_action('admin_print_styles', [$this, 'enqueueStyles']);
        add_action('admin_init', [$this, 'registerScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Enregistre les styles
     *
     * @since   1.0.0
     * @return void
     */
    public function registerStyles(): void
    {
        wp_register_style(
            'cfdev-jquery-ui',
            $this->config->url . '/assets/css/jquery-ui.css',
            [],
            $this->config->version,
            'screen'
        );

        wp_register_style(
            'cfdev',
            $this->config->url . '/assets/css/style.css',
            [],
            $this->config->version,
            'screen'
        );
    }

    /**
     * Charge les styles en file d'attente
     *
     * @since   1.0.0
     * @return void
     */
    public function enqueueStyles(): void
    {
        wp_enqueue_style('thickbox');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('cfdev-jquery-ui');
        wp_enqueue_style('cfdev');
    }

    /**
     * Enregistre les scripts
     *
     * @since   1.0.0
     * @return void
     */
    public function registerScripts(): void
    {
        wp_register_script(
            'jquery-timepicker',
            $this->config->url . '/assets/js/jquery.timepicker.js',
            ['jquery'],
            $this->config->version,
            true
        );

        wp_register_script(
            'cfdev',
            $this->config->url . '/assets/js/functions.js',
            ['jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-tabs',
             'jquery-ui-accordion', 'jquery-ui-sortable', 'wp-color-picker',
             'jquery-timepicker', 'jquery-ui-slider'],
            $this->config->version,
            true
        );
    }

    /**
     * Charge les scripts en file d'attente
     *
     * @since   1.0.0
     * @return void
     */
    public function enqueueScripts(): void
    {
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        wp_enqueue_script('thickbox');
        wp_enqueue_script('cfdev');
        wp_enqueue_script('media-upload');

        $this->localizeScripts();
    }

    /**
     * Localise les scripts avec les variables PHP → JS
     *
     * @since   1.0.0
     * @return void
     */
    private function localizeScripts(): void
    {
        wp_localize_script('cfdev', 'Cfdev', [
            'home_url'     => get_home_url(),
            'ajax_url'     => admin_url('admin-ajax.php'),
            'date_format'  => get_option('date_format'),
            'wp_version'   => get_bloginfo('version'),
            'remove_image' => __('Remove current image', 'cfdev'),
            'remove_file'  => __('Remove current file', 'cfdev'),
            'saving'       => __('Saving...', 'cfdev'),
            'saved'        => __('Saved!', 'cfdev'),
            'nonce' => wp_create_nonce('cfdev_ajax_save'),
        ]);
    }
}
