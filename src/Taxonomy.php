<?php

namespace CFDev;

use CFDev\Abstracts\ContentType;
use CFDev\Support\NameResolver;
use CFDev\Support\WPValidator;

/**
 * Registers and manages a Custom Taxonomy
 *
 * Supports method chaining for adding term meta
 * and admin column configuration.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 *
 */
class Taxonomy extends ContentType
{
    /**
     * Post type(s) this taxonomy is attached to
     *
     * @since   1.0.0
     * @var   array<string>
     */
    public array $post_type;

    /**
     * Registers or attaches a Custom Taxonomy
     *
     * @since   1.0.0
     * @param  string|array<string> $name       Singular name, or [singular, plural]
     * @param  string|array<string> $post_type  Post type(s) to attach to
     * @param  array<string,mixed>  $args       Arguments passed to register_taxonomy()
     * @param  array<string,string> $labels     Custom labels override
     */
    public function __construct(string|array $name, string|array|null $post_type = null, array $args = [], array $labels = [])
    {
        if (empty($name)) {
            return;
        }

        // Resolve names
        $resolved     = new NameResolver($name);
        $this->name   = $resolved->slug;
        $this->title  = $resolved->singular;
        $this->plural = $resolved->plural;

        $this->post_type = (array) $post_type;
        $this->args      = $args;
        $this->labels    = $labels;

        if (! taxonomy_exists($this->name)) {
            $reserved = WPValidator::isReservedTerm($this->name);

            if ($reserved) {
                (new Notice($reserved->get_error_message(), 'error'))->register();
                return;
            }

            $this->register();
        } else {
            $this->attach();
        }

        $this->setupAdminColumns();
    }

    /**
     * Registers the taxonomy with WordPress
     *
     * @since   1.0.0
     * @return void
     */
    public function register(): void
    {
        register_taxonomy($this->name, $this->post_type, $this->buildArgs());
    }

    /**
     * Attaches an existing taxonomy to the post type
     *
     * @since   1.0.0
     * @return void
     */
    public function attach(): void
    {
        register_taxonomy_for_object_type($this->name, $this->post_type);
    }

    /**
     * Adds term meta fields to the taxonomy
     *
     * @since   1.0.0
     * @param  array<mixed> $data       Fields to display
     * @param  array<string> $locations Form locations: 'add_form', 'edit_form'
     * @return static
     */
    public function addTermMeta(array $data = [], array $locations = ['add_form', 'edit_form']): static
    {
        new \CFDev\Meta\TermMeta($this->name, $data, $locations);

        return $this;
    }

    /**
     * Builds the labels array for WordPress registration
     *
     * @since   1.0.0
     * @return array<string, string>
     */
    protected function buildLabels(): array
    {
        return array_merge([
            'name'              => sprintf(_x('%s', 'taxonomy general name', 'cfdev'), $this->plural),
            'singular_name'     => sprintf(_x('%s', 'taxonomy singular name', 'cfdev'), $this->title),
            'search_items'      => sprintf(__('Search %s', 'cfdev'), $this->plural),
            'all_items'         => sprintf(__('All %s', 'cfdev'), $this->plural),
            'parent_item'       => sprintf(__('Parent %s', 'cfdev'), $this->title),
            'parent_item_colon' => sprintf(__('Parent %s:', 'cfdev'), $this->title),
            'edit_item'         => sprintf(__('Edit %s', 'cfdev'), $this->title),
            'update_item'       => sprintf(__('Update %s', 'cfdev'), $this->title),
            'add_new_item'      => sprintf(__('Add New %s', 'cfdev'), $this->title),
            'new_item_name'     => sprintf(__('New %s Name', 'cfdev'), $this->title),
            'menu_name'         => sprintf(__('%s', 'cfdev'), $this->plural),
        ], $this->labels);
    }

    /**
     * Builds the args array for WordPress registration
     *
     * @since   1.0.0
     * @return array<string, mixed>
     */
    protected function buildArgs(): array
    {
        return array_merge([
            'label'              => sprintf(__('%s', 'cfdev'), $this->plural),
            'labels'             => $this->buildLabels(),
            'hierarchical'       => true,
            'public'             => true,
            'show_ui'            => true,
            'show_in_nav_menus'  => true,
            '_builtin'           => false,
            'show_admin_column'  => false,
        ], $this->args);
    }

    // =========================================================
    // Admin Columns
    // =========================================================

    /**
     * Registers admin column hooks if show_admin_column is enabled
     *
     * @since   1.0.0
     * @return void
     */
    private function setupAdminColumns(): void
    {
        if (empty($this->args['show_admin_column'])) {
            return;
        }

        foreach ($this->post_type as $post_type) {
            // Legacy WordPress < 3.5 support
            if (get_bloginfo('version') < '3.5') {
                add_filter('manage_' . $post_type . '_posts_columns', [$this, 'addColumn']);
                add_action('manage_' . $post_type . '_posts_custom_column', [$this, 'addColumnContent'], 10, 2);
            }

            if (! empty($this->args['admin_column_sortable'])) {
                add_action('manage_edit-' . $post_type . '_sortable_columns', [$this, 'addSortableColumn'], 10, 2);
            }
        }

        if (! empty($this->args['admin_column_filter'])) {
            add_action('restrict_manage_posts', [$this, 'renderPostFilter']);
            add_filter('parse_query', [$this, 'applyPostFilter']);
        }
    }

    /**
     * Adds a column to the post list table
     *
     * @since   1.0.0
     * @param  array<string, string> $columns  Existing columns
     * @return array<string, string>
     */
    public function addColumn(array $columns): array
    {
        unset($columns['date']);

        $columns[$this->name] = $this->title;
        $columns['date']      = __('Date', 'cfdev');

        return $columns;
    }

    /**
     * Renders the column content for each row
     *
     * @since   1.0.0
     * @param  string  $column   Current column name
     * @param  int     $post_id  Current post ID
     * @return void
     */
    public function addColumnContent(string $column, int $post_id): void
    {
        if ($column !== $this->name) {
            return;
        }

        $terms = wp_get_post_terms($post_id, $this->name, ['fields' => 'names']);

        echo esc_html(implode(', ', $terms));
    }

    /**
     * Makes the taxonomy column sortable
     *
     * @since   1.0.0
     * @param  array<string, string> $columns  Sortable columns
     * @return array<string, string>
     */
    public function addSortableColumn(array $columns): array
    {
        $key             = get_bloginfo('version') < '3.5' ? $this->name : 'taxonomy-' . $this->name;
        $columns[$key]   = $this->title;

        return $columns;
    }

    /**
     * Renders the taxonomy filter dropdown above the post list
     *
     * @since   1.0.0
     * @return void
     */
    public function renderPostFilter(): void
    {
        global $typenow, $wp_query;

        if (! in_array($typenow, $this->post_type, true)) {
            return;
        }

        wp_dropdown_categories([
            'show_option_all' => sprintf(__('Show all %s', 'cfdev'), $this->plural),
            'taxonomy'        => $this->name,
            'name'            => $this->name,
            'orderby'         => 'name',
            'selected'        => $wp_query->query[$this->name] ?? '',
            'hierarchical'    => true,
            'show_count'      => true,
            'hide_empty'      => true,
        ]);
    }

    /**
     * Converts term ID to slug in the query when filter is applied
     *
     * @since   1.0.0
     * @param  \WP_Query $query  Current query object
     * @return void
     */
    public function applyPostFilter(\WP_Query $query): \WP_Query
    {
        global $pagenow;

        $vars = &$query->query_vars;

        if ($pagenow !== 'edit.php') {
            return $query;
        }

        if (isset($vars[$this->name]) && is_numeric($vars[$this->name]) && $vars[$this->name]) {
            $term = get_term_by('id', $vars[$this->name], $this->name);
            if ($term && ! is_wp_error($term)) {
                $vars[$this->name] = $term->slug;
            }
        }

        return $query;
    }
}
