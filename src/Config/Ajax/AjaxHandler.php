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
        add_action('wp_ajax_cfdev_field_ajax_save', ['Weblitzer\\CFDev\\Field', 'ajaxSave']);
        add_action('wp_ajax_cfdev_inspect', [self::class, 'handleInspect']);
        add_action('wp_ajax_cfdev_search_objects', [self::class, 'handleSearchObjects']);
    }

    public static function handleInspect(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        // phpcs:enable

        if (! wp_verify_nonce($nonce, 'cfdev_inspect')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $object_type = sanitize_key(wp_unslash($_POST['object_type'] ?? 'post'));
        $object_id   = absint(wp_unslash($_POST['object_id']   ?? 0));
        $taxonomy    = sanitize_key(wp_unslash($_POST['taxonomy']    ?? ''));
        $group_id    = sanitize_key(wp_unslash($_POST['group_id']    ?? ''));
        $force       = ! empty($_POST['force']);
        // phpcs:enable

        if ($object_id < 1) {
            wp_send_json_error(['message' => 'ID invalide']);
        }

        $result = (new \Weblitzer\CFDev\Cache\CacheManager())->inspect($object_type, $object_id, $taxonomy, $force);

        // Return only the requested group's fields, not all groups
        $groups = $result['data']['groups'] ?? [];
        if ($group_id !== '' && array_key_exists($group_id, $groups)) {
            $result['data'] = $groups[$group_id];
        } elseif ($group_id !== '') {
            $result['data'] = [];
        }

        wp_send_json_success($result);
    }

    public static function handleSearchObjects(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        // phpcs:enable

        if (! wp_verify_nonce($nonce, 'cfdev_search')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
        $object_type = sanitize_key(wp_unslash($_POST['object_type'] ?? 'post'));
        $targets_raw = sanitize_text_field(wp_unslash($_POST['targets'] ?? ''));
        $taxonomy    = sanitize_key(wp_unslash($_POST['taxonomy']  ?? ''));
        $search      = sanitize_text_field(wp_unslash($_POST['search']  ?? ''));
        // phpcs:enable

        $targets = array_values(array_filter(array_map('sanitize_key', explode(',', $targets_raw))));
        $results = [];

        if ($object_type === 'post' && ! empty($targets)) {
            $args = [
                'post_type'      => $targets,
                'post_status'    => ['publish', 'draft', 'private'],
                'posts_per_page' => 10,
                'no_found_rows'  => true,
                'orderby'        => $search !== '' ? 'relevance' : 'modified',
                'order'          => 'DESC',
            ];
            if ($search !== '') {
                $args['s'] = $search;
            }
            $q = new \WP_Query($args);
            foreach ($q->posts as $post) {
                $results[] = [
                    'id'    => $post->ID,
                    'label' => $post->post_title !== '' ? $post->post_title : '(sans titre) #' . $post->ID,
                    'meta'  => $post->post_type,
                ];
            }
        } elseif ($object_type === 'term') {
            $tax  = $taxonomy !== '' ? $taxonomy : ($targets[0] ?? '');
            $args = [
                'taxonomy'   => $tax !== '' ? $tax : $targets,
                'number'     => 10,
                'hide_empty' => false,
            ];
            if ($search !== '') {
                $args['search'] = $search;
            }
            $terms = get_terms($args);
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    $results[] = [
                        'id'    => $term->term_id,
                        'label' => $term->name,
                        'meta'  => $term->taxonomy,
                    ];
                }
            }
        } elseif ($object_type === 'user') {
            $args = ['number' => 10, 'fields' => 'all', 'orderby' => 'registered', 'order' => 'DESC'];
            if ($search !== '') {
                $args['search']         = '*' . $search . '*';
                $args['search_columns'] = ['display_name', 'user_login', 'user_email'];
            }
            $users = get_users($args);
            foreach ($users as $user) {
                $results[] = [
                    'id'    => $user->ID,
                    'label' => $user->display_name,
                    'meta'  => $user->user_login,
                ];
            }
        }

        wp_send_json_success($results);
    }
}
