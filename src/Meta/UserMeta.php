<?php

namespace CFDev\Meta;

use CFDev\Meta;
use CFDev\Support\WPValidator;
use CFDev\Validation\ErrorBag;

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

    protected function metaType(): string
    {
        return 'user'; 
    }

    /**
     * Constructor for User Meta
     *
     * @param   string          $id
     * @param   string|array    $title
     * @param   string|array    $locations
     * @param   array           $data
     *
     * @author  quidelantoine
     * @since   1.0.0
     * 
     */
    /**
     * @param string               $id
     * @param string|array<string> $title
     * @param string|array<string> $locations
     * @param array<mixed>         $data
     */
    public function __construct(string $id, $title, $locations, array $data = array())
    {
        parent::__construct($title);

        $this->id           = $id;
        $this->locations    = ! empty($locations) ? (array) $locations : ['show_user_profile', 'edit_user_profile'];

        // Chack if the class, function or method exist, otherwise use custom callback
        if (WPValidator::isWpCallback($data)) {
            $this->callback = $data;
        } else {
            $this->callback = array( &$this, 'callback' );

            // Build the meta box and fields
            $this->data = $this->build($data);

            add_action('personal_options_update', array( $this, 'saveUser' ));
            add_action('edit_user_profile_update', array( $this, 'saveUser' ));
            add_action('user_edit_form_tag', array( $this, 'editFormTag' ));
            add_action('admin_notices', array($this, 'showValidationNotice'));
        }

        foreach ($this->locations as $location) {
            /** @phpstan-ignore argument.type */
            add_action($location, $this->callback);
        }
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
        echo '<h3>' . esc_html($this->title) . '</h3>';

        parent::callback($user, $data);
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

            if ($this->data instanceof \CFDev\Fields\Bundle) {
                if (isset($values[$this->data->id])) {
                    $this->data->save($user_id, $values[$this->data->id]);
                }
            } elseif ($this->data instanceof \CFDev\Fields\Tabs || $this->data instanceof \CFDev\Fields\Accordion) {
                foreach ($this->data->tabs as $tab) {
                    if ($tab->fields instanceof \CFDev\Fields\Bundle) {
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
