<?php

namespace Weblitzer\CFDev\Rest;

use Weblitzer\CFDev\Admin\RestPage;
use Weblitzer\CFDev\Cache\CacheManager;
use Weblitzer\CFDev\Registry;

/**
 * Custom REST endpoints returning resolved CFDev field data via CacheManager.
 *
 * Routes:
 *   GET /wp-json/cfdev/v1/post/{id}
 *   GET /wp-json/cfdev/v1/term/{taxonomy}/{id}
 *   GET /wp-json/cfdev/v1/user/{id}
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class CfdevRestApi
{
    private const NAMESPACE = 'cfdev/v1';

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        if ((int) get_option(RestPage::OPTION_API, 1) === 0) {
            return;
        }

        register_rest_route(
            self::NAMESPACE,
            '/post/(?P<id>\d+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'handlePost'],
                'permission_callback' => [$this, 'canReadPost'],
                'args'                => [
                    'id' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/term/(?P<taxonomy>[a-z0-9_-]+)/(?P<id>\d+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'handleTerm'],
                'permission_callback' => [$this, 'canReadTerm'],
                'args'                => [
                    'taxonomy' => ['required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key'],
                    'id'       => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                ],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/user/(?P<id>\d+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'handleUser'],
                'permission_callback' => [$this, 'canReadUser'],
                'args'                => [
                    'id' => ['required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint'],
                ],
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Handlers
    // -------------------------------------------------------------------------

    public function handlePost(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $id   = (int) $request->get_param('id');
        $post = get_post($id);

        if (! $post instanceof \WP_Post) {
            return new \WP_Error('cfdev_not_found', __('Post not found.', 'cfdev'), ['status' => 404]);
        }

        $entries = self::restEntriesFor('post', $post->post_type);
        if (empty($entries)) {
            return new \WP_Error('cfdev_not_found', __('No CFDev fields exposed for this post type.', 'cfdev'), ['status' => 404]);
        }

        $all    = (new CacheManager())->post($id);
        $groups = self::filterGroups($all['groups'] ?? [], $entries);

        return new \WP_REST_Response([
            'id'     => $id,
            'groups' => $groups,
        ]);
    }

    public function handleTerm(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $id       = (int) $request->get_param('id');
        $taxonomy = (string) $request->get_param('taxonomy'); // sanitized via route args
        $term     = get_term($id, $taxonomy);

        if (! $term instanceof \WP_Term) {
            return new \WP_Error('cfdev_not_found', __('Term not found.', 'cfdev'), ['status' => 404]);
        }

        $entries = self::restEntriesFor('term', $taxonomy);
        if (empty($entries)) {
            return new \WP_Error('cfdev_not_found', __('No CFDev fields exposed for this taxonomy.', 'cfdev'), ['status' => 404]);
        }

        $all    = (new CacheManager())->term($id, $taxonomy);
        $groups = self::filterGroups($all['groups'] ?? [], $entries);

        return new \WP_REST_Response([
            'id'       => $id,
            'taxonomy' => $taxonomy,
            'groups'   => $groups,
        ]);
    }

    public function handleUser(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $id   = (int) $request->get_param('id');
        $user = get_userdata($id);

        if (! $user instanceof \WP_User) {
            return new \WP_Error('cfdev_not_found', __('User not found.', 'cfdev'), ['status' => 404]);
        }

        $entries = self::restEntriesFor('user');
        if (empty($entries)) {
            return new \WP_Error('cfdev_not_found', __('No CFDev fields exposed for users.', 'cfdev'), ['status' => 404]);
        }

        $all    = (new CacheManager())->user($id);
        $groups = self::filterGroups($all['groups'] ?? [], $entries);

        return new \WP_REST_Response([
            'id'     => $id,
            'groups' => $groups,
        ]);
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    public function canReadPost(\WP_REST_Request $request): bool|\WP_Error
    {
        $id   = (int) $request->get_param('id');
        $post = get_post($id);

        if (! $post instanceof \WP_Post) {
            return new \WP_Error('cfdev_not_found', __('Post not found.', 'cfdev'), ['status' => 404]);
        }

        $post_type = get_post_type_object($post->post_type);
        if ($post->post_status === 'publish' && $post_type instanceof \WP_Post_Type && $post_type->public) {
            return true;
        }

        if (current_user_can('read_post', $id)) {
            return true;
        }

        return is_user_logged_in()
            ? new \WP_Error('rest_forbidden', __('Insufficient permissions.', 'cfdev'), ['status' => 403])
            : new \WP_Error('rest_forbidden', __('Authentication required.', 'cfdev'), ['status' => 401]);
    }

    public function canReadTerm(\WP_REST_Request $request): bool|\WP_Error
    {
        $taxonomy = sanitize_key((string) $request->get_param('taxonomy'));
        $tax_obj  = get_taxonomy($taxonomy);

        if ($tax_obj === false) {
            return new \WP_Error('cfdev_invalid_taxonomy', __('Invalid taxonomy.', 'cfdev'), ['status' => 400]);
        }

        if ($tax_obj->public) {
            return true;
        }

        if (current_user_can('manage_terms')) {
            return true;
        }

        return is_user_logged_in()
            ? new \WP_Error('rest_forbidden', __('Insufficient permissions.', 'cfdev'), ['status' => 403])
            : new \WP_Error('rest_forbidden', __('Authentication required.', 'cfdev'), ['status' => 401]);
    }

    public function canReadUser(\WP_REST_Request $request): bool|\WP_Error
    {
        if (! is_user_logged_in()) {
            return new \WP_Error('rest_forbidden', __('Authentication required.', 'cfdev'), ['status' => 401]);
        }

        $id = (int) $request->get_param('id');

        if (get_current_user_id() === $id || current_user_can('edit_user', $id)) {
            return true;
        }

        return new \WP_Error('rest_forbidden', __('Insufficient permissions.', 'cfdev'), ['status' => 403]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns Registry rest entries filtered by meta_type and optional target.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function restEntriesFor(string $meta_type, string $target = ''): array
    {
        return array_values(array_filter(
            Registry::restFields(),
            fn($e) => $e['meta_type'] === $meta_type
                && ($target === '' || in_array($target, $e['targets'], true))
        ));
    }

    /**
     * Filters CacheManager group data to only include rest-flagged fields.
     *
     * @param array<string, mixed>              $all_groups  Raw CacheManager groups
     * @param array<int, array<string, mixed>>  $entries     Registry rest entries
     * @return array<string, mixed>
     */
    private static function filterGroups(array $all_groups, array $entries): array
    {
        $groups = [];
        foreach ($entries as $entry) {
            $group_id = $entry['id'];
            if (! isset($all_groups[$group_id]) || ! is_array($all_groups[$group_id])) {
                continue;
            }
            $rest_keys         = array_keys($entry['fields']);
            $groups[$group_id] = array_intersect_key($all_groups[$group_id], array_flip($rest_keys));
        }
        return $groups;
    }
}
