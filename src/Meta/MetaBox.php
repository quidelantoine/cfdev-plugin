<?php

namespace Weblitzer\CFDev\Meta;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Fields\Radios;
use Weblitzer\CFDev\Meta;
use Weblitzer\CFDev\Support\WPValidator;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * Registers the meta boxes
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */

class MetaBox extends Meta
{
    public string $context;
    public string $priority;
    /** @var array<string> */
    public array $post_types;

    public ?int $only_for_id = null;
    public ?string $only_for_template = null;
    /** @var list<callable(\WP_Post): bool> */
    public array $only_when = [];
    /** @var list<string> */
    public array $only_when_labels = [];

    protected function metaType(): string
    {
        return 'post'; 
    }

    /**
     * Constructs the meta box
     *
     * @param string               $id
     * @param string|array<string> $title
     * @param string|array<string> $post_type
     * @param array<mixed>|string  $data
     * @param string               $context
     * @param string               $priority
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function __construct($id, $title, string|array $post_type, $data = array(), $context = 'normal', $priority = 'default')
    {
        if (!empty($title)) {
            parent::__construct($title);

            $this->id = $id;
            $this->post_types = (array)$post_type;
            $this->context = $context;
            $this->priority = $priority;

            // Check if the class, function or method exist, otherwise use custom callback
            if (WPValidator::isWpCallback($data)) {
                $this->callback = $data;
            } else {
                $this->callback = array($this, 'callback');

                // Build the meta box and fields
                $this->data = $this->build($data);

                foreach ($this->post_types as $post_type) {
                    $this->registerRestMeta('post', $post_type);
                    add_filter('manage_' . $post_type . '_posts_columns', array($this, 'addColumn'));
                    add_action('manage_' . $post_type . '_posts_custom_column', array($this, 'addColumnContent'), 10, 2);
                    add_filter('manage_edit-' . $post_type . '_sortable_columns', array($this, 'addSortableColumn'), 10, 1);
                }

                add_action('save_post', array($this, 'savePost'));
                add_action('post_edit_form_tag', array($this, 'editFormTag'));
                add_action('admin_notices', array($this, 'showValidationNotice'));
            }

            // Add the meta box
            add_action('add_meta_boxes', array($this, 'addMetaBox'), 10, 2);

            \Weblitzer\CFDev\Registry::register($this);
        }
    }

    /**
     * Restrict this meta box to a specific post ID.
     */
    public function onlyForId(int $id): static
    {
        $this->only_for_id = $id;
        return $this;
    }

    /**
     * Restrict this meta box to pages using a specific template slug.
     * Example: 'template-home.php'
     */
    public function onlyForTemplate(string $template): static
    {
        $this->only_for_template = $template;
        return $this;
    }

    /**
     * Add a custom display/save condition. Receives the WP_Post and must return bool.
     * Multiple calls are ANDed. Applies to display, save, and REST output.
     * The optional $label is shown as a badge in the Dashboard and REST admin pages.
     *
     * @param callable(\WP_Post): bool $fn
     */
    public function onlyWhen(callable $fn, string $label = ''): static
    {
        $this->only_when[]        = $fn;
        $this->only_when_labels[] = $label;
        return $this;
    }

    /**
     * Strips conditioned REST fields from responses where the post does not match
     * the onlyForId / onlyForTemplate condition.
     *
     * @param string        $object_type  Always 'post' for MetaBox
     * @param string        $subtype      Post type slug (e.g. 'page')
     * @param array<string> $field_ids    Meta keys registered with show_in_rest
     */
    protected function addRestConditionFilter(string $object_type, string $subtype, array $field_ids): void
    {
        if ($this->only_for_id === null && $this->only_for_template === null && empty($this->only_when)) {
            return;
        }
        add_filter(
            'rest_prepare_' . $subtype,
            function (\WP_REST_Response $response, \WP_Post $post) use ($field_ids): \WP_REST_Response {
                if (! $this->matchesConditions($post)) {
                    $meta = $response->data['meta'] ?? [];
                    foreach ($field_ids as $id) {
                        unset($meta[$id]);
                    }
                    $response->data['meta'] = $meta;
                }
                return $response;
            },
            10,
            2
        );
    }

    /**
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function addMetaBox(string $screen = '', ?\WP_Post $post = null): void
    {
        if (! $this->matchesConditions($post)) {
            return;
        }

        foreach ($this->post_types as $post_type) {
            /** @var callable(): mixed $cb */
            $cb = $this->callback;
            /** @var 'core'|'default'|'high'|'low' $prio */
            $prio = $this->priority;
            $mb_title = '<span class="cfdev-mb-title"><span class="dashicons dashicons-lightbulb cfdev-mb-icon" aria-hidden="true"></span>'
                . esc_html($this->title) . '</span>';
            add_meta_box(
                $this->id,
                $mb_title,
                $cb,
                $post_type,
                $this->context,
                $prio
            );
        }
    }

    private function matchesConditions(?\WP_Post $post): bool
    {
        if ($post === null) {
            return true;
        }
        if ($this->only_for_id !== null && $post->ID !== $this->only_for_id) {
            return false;
        }
        if ($this->only_for_template !== null && get_page_template_slug($post->ID) !== $this->only_for_template) {
            return false;
        }
        foreach ($this->only_when as $fn) {
            if (! $fn($post)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Hooks into the save hook for the newly registered Post Type
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function savePost(int $post_id): void
    {
        // Deny the wordpress autosave function
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_doing_ajax()) {
            return;
        }

        // Verify nonce
        if (!(isset($_POST['cfdev_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_nonce'])), 'cfdev_meta'))) {
            return;
        }

        // Is the post from the given post type?
        if (!in_array(get_post_type($post_id), array_merge($this->post_types, array('revision')))) {
            return;
        }

        // Respect location conditions (id, template, onlyWhen callables)
        if ($this->only_for_id !== null || $this->only_for_template !== null || !empty($this->only_when)) {
            $post = get_post($post_id);
            if (! ($post instanceof \WP_Post) || ! $this->matchesConditions($post)) {
                return;
            }
        }

        // Is the current user capable to edit this post
        $post_type_slug = get_post_type($post_id);
        $post_type_obj  = $post_type_slug ? get_post_type_object($post_type_slug) : null;
        if (!$post_type_obj || !current_user_can($post_type_obj->cap->edit_post, $post_id)) {
            return;
        }

        //$values = isset($_POST['cfdev']) ? $_POST['cfdev'] : array();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $values = isset($_POST['cfdev']) ? wp_unslash($_POST['cfdev']) : array();

        if (!empty($values)) {
            $errors = $this->validateFields($values);

            if (!empty($errors)) {
                ErrorBag::push('post', $post_id, $errors);
            }

            parent::save($post_id, $values);
        }
    }

    protected function resolveObjectId(): int
    {
        // admin_notices fires (via admin-header.php) before post.php sets up
        // the global $post, so get_the_ID() returns 0 at that point.
        // $_GET['post'] is always present on the post-edit URL and is reliable.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['post'])) {
            return absint($_GET['post']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }
        return absint(get_the_ID());
    }

    /**
     * Normal save method to save all the fields in a metabox
     *
     * @param  array<mixed> $values
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function save(int $post_id, array $values): void
    {
        foreach ($this->fields as $id => $field) {
            if ($field->in_bundle) {
                continue;
            }

            $value = isset($values[$id]) ? $values[$id] : '';
            $value = apply_filters("cfdev_post_meta_save_$field->type", apply_filters('cfdev_post_meta_save', $value, $field, $post_id), $field, $post_id);

            $field->save($post_id, $value);
        }
    }

    /**
     * Used to add a column head to the Post Type's List Table
     *
     * @param  array<string, string> $columns
     * @return array<string, string>
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function addColumn(array $columns): array
    {
        unset($columns['date']);

        foreach ($this->fields as $id_name => $field) {
            if ($field->show_admin_column) {
                $columns[$id_name] = $field->label;
            }
        }

        $columns['date'] = __('Date', 'cfdev');
        return $columns;
    }

    /**
     * Used to add the column content to the column head
     *
     * @param string $column
     * @param integer $post_id
     * @return  void
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function addColumnContent($column, $post_id): void
    {
        $meta = Field::decodeMetaValue(get_post_meta($post_id, $column, true));

        if ($this->fields) {
            foreach ($this->fields as $id_name => $field) {
                if ($column == $id_name) {
                    if ($field->repeatable && $field->supports_repeatable) {
                        echo esc_html(implode(', ', (array) $meta));
                    } else {
                        if ($field instanceof Image) {
                            echo wp_get_attachment_image($meta, array(100, 100));
                        } elseif ($field instanceof Radios) {
                            echo isset($field->options[$meta[0]]) ? esc_html($field->options[$meta[0]]) : '';
                        } else {
                            echo esc_html($meta);
                        }
                    }

                    break;
                }
            }
        }
    }

    /**
     * Used to make all columns sortable
     *
     * @param  array<string, string> $columns
     * @return array<string, string>
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function addSortableColumn(array $columns): array
    {
        if ($this->fields) {
            foreach ($this->fields as $id_name => $field) {
                if ($field->admin_column_sortable) {
                    $columns[$id_name] = $field->label;
                }
            }
        }

        return $columns;
    }
}
