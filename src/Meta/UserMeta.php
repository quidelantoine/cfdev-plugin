<?php

namespace Weblitzer\CFDev\Meta;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Fields\Accordion;
use Weblitzer\CFDev\Fields\Bundle;
use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Fields\Tabs;
use Weblitzer\CFDev\Meta;
use Weblitzer\CFDev\Support\WPValidator;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * User Meta
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */

class UserMeta extends Meta
{
    /** @var array<string> */
    public array $locations;
    public int $priority;
    /** @var array<string> */
    public array $only_for_roles = [];

    protected function metaType(): string
    {
        return 'user';
    }

    /**
     * @param string               $id
     * @param string|array<string> $title
     * @param array<mixed>         $data
     * @param string|array<string> $locations
     * @param int                  $priority   Hook priority — controls display order when multiple UserMeta sections exist
     */
    public function __construct(string $id, $title, array $data = [], array|string $locations = [], int $priority = 10)
    {
        parent::__construct($title);

        $this->id       = $id;
        $this->priority = $priority;
        $this->locations = ! empty($locations) ? (array) $locations : ['show_user_profile', 'edit_user_profile'];

        // Chack if the class, function or method exist, otherwise use custom callback
        if (WPValidator::isWpCallback($data)) {
            $this->callback = $data;
        } else {
            $this->callback = array( &$this, 'callback' );

            // Build the meta box and fields
            $this->data = $this->build($data);

            $this->registerRestMeta('user');
            $this->setupAdminColumns();

            add_action('personal_options_update', array( $this, 'saveUser' ));
            add_action('edit_user_profile_update', array( $this, 'saveUser' ));
            add_action('user_edit_form_tag', array( $this, 'editFormTag' ));
            add_action('admin_notices', array($this, 'showValidationNotice'));
        }

        foreach ($this->locations as $location) {
            /** @phpstan-ignore argument.type */
            add_action($location, $this->callback, $this->priority);
        }

        \Weblitzer\CFDev\Registry::register($this);
    }

    /**
     * Restrict this section to users with at least one of the given roles.
     * Example: ->onlyForRole('administrator') or ->onlyForRole(['editor', 'author'])
     *
     * @param string|array<string> $roles
     */
    public function onlyForRole(string|array $roles): static
    {
        $this->only_for_roles = array_merge($this->only_for_roles, (array) $roles);
        return $this;
    }

    /**
     * Callback for user meta, adds a title
     * 
     * @param   int                 $user [description]
     * @param   array       $data [description]
     *
     * @author  quidelantoine
     * @since   1.0.0
     * 
     */
    /** @param array<mixed> $data */
    public function callback(mixed $user, $data = array()): void
    {
        if (! empty($this->only_for_roles)) {
            $user_roles = (array) ($user->roles ?? []);
            if (empty(array_intersect($this->only_for_roles, $user_roles))) {
                return;
            }
        }

        echo '<div class="cfdev-postbox">';
        echo '<div class="cfdev-postbox-header"><h2 class="cfdev-postbox-title">'
            . esc_html($this->title) . '</h2></div>';
        echo '<div class="cfdev-postbox-inside">';
        parent::callback($user, $data);
        echo '</div></div>';
    }

    /**
     * Hooks into the save hook for the user meta
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function saveUser(int $user_id): void
    {
        // Verify nonce
        if (! ( isset($_POST['cfdev_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_nonce'])), 'cfdev_meta') )) {
            return;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $values = isset($_POST['cfdev']) ? wp_unslash($_POST['cfdev']) : array();

        if (! empty($values)) {
            $errors = $this->validateFields($values);

            if (! empty($errors)) {
                ErrorBag::push('user', $user_id, $errors);
            }

            if ($this->data instanceof Bundle) {
                if (isset($values[$this->data->id])) {
                    $this->data->save($user_id, $values[$this->data->id]);
                }
            } elseif ($this->data instanceof Tabs || $this->data instanceof Accordion) {
                foreach ($this->data->tabs as $tab) {
                    if ($tab->fields instanceof Bundle) {
                        if (isset($values[$tab->fields->id])) {
                            $tab->fields->save($user_id, $values[$tab->fields->id]);
                        }
                    } else {
                        $this->save($user_id, $values);
                    }
                }
            } else {
                $this->save($user_id, $values);
            }
        }
    }

    // =========================================================
    // Admin Columns
    // =========================================================

    private function setupAdminColumns(): void
    {
        $hasColumn = false;

        foreach ($this->fields as $field) {
            if ($field->show_admin_column) {
                $hasColumn = true;
                break;
            }
        }

        if (! $hasColumn) {
            return;
        }

        add_filter('manage_users_columns', array($this, 'addColumn'));
        add_filter('manage_users_custom_column', array($this, 'addColumnContent'), 10, 3);
        add_filter('manage_users_sortable_columns', array($this, 'addSortableColumn'));
    }

    /**
     * @param  array<string, string> $columns
     * @return array<string, string>
     */
    public function addColumn(array $columns): array
    {
        foreach ($this->fields as $id_name => $field) {
            if ($field->show_admin_column) {
                $columns[$id_name] = $field->label;
            }
        }

        return $columns;
    }

    public function addColumnContent(string $value, string $column, int $user_id): string
    {
        foreach ($this->fields as $id_name => $field) {
            if ($column !== $id_name) {
                continue;
            }

            $meta = Field::decodeMetaValue(get_user_meta($user_id, $column, true));

            if ($field->repeatable && $field->supports_repeatable) {
                return esc_html(implode(', ', (array) $meta));
            }

            if ($field instanceof Image) {
                return wp_get_attachment_image((int) $meta, [100, 100]);
            }

            return esc_html((string) $meta);
        }

        return $value;
    }

    /**
     * @param  array<string, string> $columns
     * @return array<string, string>
     */
    public function addSortableColumn(array $columns): array
    {
        foreach ($this->fields as $id_name => $field) {
            if ($field->admin_column_sortable) {
                $columns[$id_name] = $field->label;
            }
        }

        return $columns;
    }

    protected function resolveObjectId(): int
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
    }

    /**
     * Normal save method to save all the fields in a metabox
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    /** @param array<mixed> $values */
    public function save(int $user_id, array $values): void
    {
        foreach ($this->fields as $id => $field) {
            if ($field->in_bundle) {
                continue;
            }
            
            $value = isset($values[$id]) ? $values[$id] : '';
            $value = apply_filters("cfdev_user_meta_save_$field->type", apply_filters('cfdev_user_meta_save', $value, $field, $user_id), $field, $user_id);

            $field->save($user_id, $value);
        }
    }
}
