<?php

namespace Weblitzer\CFDev;

use Weblitzer\CFDev\Config\Config;
use Weblitzer\CFDev\Config\Assets\AssetLoader;
use Weblitzer\CFDev\Config\Ajax\AjaxHandler;

/**
 * Initializer handles init of Custom Field For Dev
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */
class Initializer
{
    private static ?self $instance = null;
    private Container $container;

    private function __construct(private readonly string $plugin_file)
    {
    }

    /**
     * Public function to set the instance
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */

    public static function instance(string $plugin_file): self
    {
        if (self::$instance === null) {
            self::$instance = new self($plugin_file);
            self::$instance->boot();
        }

        return self::$instance;
    }
    

    private function boot(): void
    {
        load_plugin_textdomain('cfdev', false, dirname(plugin_basename($this->plugin_file)) . '/languages');

        // Container
        $this->container = new Container();
        $this->container->bind(Config::class, new Config(
            version:      '1.0.6',
            dir:          untrailingslashit(plugin_dir_path($this->plugin_file)),
            url:          untrailingslashit(self::resolveUrl($this->plugin_file)),
            src_dir:      untrailingslashit(plugin_dir_path($this->plugin_file)) . '/src',
            demo:         defined('CFDEV_DEMO') && (bool) CFDEV_DEMO,
        ));
        $this->container->bind(AssetLoader::class, new AssetLoader(
            $this->container->get(Config::class)
        ));
        $this->container->bind(AjaxHandler::class, new AjaxHandler());

        // Includes
        $this->includes();

        // Start services
        $this->container->get(AssetLoader::class)->register();
        \Weblitzer\CFDev\Admin\AdminMenu::register();
        (new \Weblitzer\CFDev\Cache\CacheManager())->register();
        (new \Weblitzer\CFDev\Rest\CfdevRestApi())->register();
        // Je susi pas sure que cela soit necessaire ??? , et pas fonctionnelle , a virer ????
        $this->container->get(AjaxHandler::class)->register();
    }

    private function includes(): void
    {
        $src = $this->container->get(Config::class)->src_dir;

        require_once path_join($src, 'functions/post_type_function.php');
        require_once path_join($src, 'functions/taxonomy_function.php');
        require_once path_join($src, 'functions/user_meta_function.php');
        require_once path_join($src, 'functions/options_page_function.php');

        if ($this->container->get(Config::class)->demo) {
            require_once path_join($src, 'demo/demo-fields.php');
        }

        // Theme fields — load cfdev-fields.php from the active theme if present
        // Uses after_setup_theme so get_stylesheet_directory() is reliable
        add_action('after_setup_theme', static function (): void {
            $file = get_stylesheet_directory() . '/cfdev-fields.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }, 20);
    }

    /**
     * Determine the path to the CFDev folder
     * The url is different for childtheme, parenttheme or plugings
     *
     * @param   string  $file
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    private static function resolveUrl(string $file): string
    {
        $dir        = wp_normalize_path(dirname($file));
        $theme_dir  = wp_normalize_path(get_template_directory());
        $child_dir  = wp_normalize_path(get_stylesheet_directory());

        // Thème enfant
        if (str_starts_with($dir, $child_dir)) {
            $relative = str_replace($child_dir, '', $dir);
            return get_stylesheet_directory_uri() . $relative;
        }

        // Thème parent
        if (str_starts_with($dir, $theme_dir)) {
            $relative = str_replace($theme_dir, '', $dir);
            return get_template_directory_uri() . $relative;
        }

        // Plugin
        return plugin_dir_url($file);
    }
}
