<?php

namespace Weblitzer\CFDev\Meta;

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

    protected function metaType(): string
    {
        return 'post'; 
    }

    /**
     * Constructs the meta box
     *
     * @param string               $id
     * @param string|array<string> $title
     * @param string               $post_type
     * @param array<mixed>|string  $data
     * @param string               $context
     * @param string               $priority
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function __construct($id, $title, string $post_type, $data = array(), $context = 'normal', $priority = 'default')
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
                    add_filter('manage_' . $post_type . '_posts_columns', array($this, 'addColumn'));
                    add_action('manage_' . $post_type . '_posts_custom_column', array($this, 'addColumnContent'), 10, 2);
                    add_filter('manage_edit-' . $post_type . '_sortable_columns', array($this, 'addSortableColumn'), 10, 1);
                }

                add_action('save_post', array($this, 'savePost'));
                add_action('post_edit_form_tag', array($this, 'editFormTag'));
                add_action('admin_notices', array($this, 'showValidationNotice'));
            }

            // Add the meta box
            add_action('add_meta_boxes', array($this, 'addMetaBox'));
        }
    }

    /**
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function addMetaBox(): void
    {
        foreach ($this->post_types as $post_type) {
            /** @var callable(): mixed $cb */
            $cb = $this->callback;
            /** @var 'core'|'default'|'high'|'low' $prio */
            $prio = $this->priority;
            add_meta_box(
                $this->id,
                $this->title,
                $cb,
                $post_type,
                $this->context,
                $prio
            );
        }
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
        $meta = \Weblitzer\CFDev\Field::decodeMetaValue(get_post_meta($post_id, $column, true));

        if ($this->fields) {
            foreach ($this->fields as $id_name => $field) {
                if ($column == $id_name) {
                    if ($field->repeatable && $field->supports_repeatable) {
                        echo esc_html(implode(', ', (array) $meta));
                    } else {
                        if ($field instanceof \Weblitzer\CFDev\Fields\Image) {
                            echo wp_get_attachment_image($meta, array(100, 100));
                        } elseif ($field instanceof \Weblitzer\CFDev\Fields\Radios) {
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
