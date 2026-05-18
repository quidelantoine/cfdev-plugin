<?php

namespace CFDev;

use CFDev\Notice;
use CFDev\Support\Str;
use CFDev\Validation\ErrorBag;

/**
 * Custom Meta for handling meta data
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */

abstract class Meta
{
    abstract protected function metaType(): string;

    abstract protected function resolveObjectId(): int;

    public $id;
    public $title;
    public $callback;
    public $data;
    public $fields;
    public $description;

    protected static bool $noticeShown = false;

    /**
     * Construct for all meta types, creates title (and description)
     *
     * @param   string|array    $title
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function __construct($title)
    {
        if (is_array($title)) {
            $this->title        = Str::beautify($title[0]);
            $this->description  = $title[1];
        } else {
            $this->title        = Str::beautify($title);
        }
    }

    /**
     * Main callback for meta
     *
     * @param   object          $post
     * @param   object          $data
     * @return  mixed
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */

    public function callback(mixed $object, array $data = []): void
    {
        wp_nonce_field('cfdev_meta', 'cfdev_nonce');

        $data      = $this->data;
        $meta_type = $this->metaType();
        $object_id = $meta_type === 'post' ? get_the_ID() : ($object->ID ?? 0);

        ErrorBag::load($meta_type, (int) $object_id);

        if (empty($data)) {
            return;
        }

        echo '<input type="hidden" name="cfdev[__activate]" />';
        echo '<div class="cfdev"'
            . ' data-object-id="' . esc_attr($object_id) . '"'
            . ' data-meta-type="' . esc_attr($meta_type) . '">';

        if (! empty($this->description)) {
            echo '<p class="cfdev-box-description">' . wp_kses_post($this->description) . '</p>';
        }

        if (
            $data instanceof \CFDev\Fields\Tabs
            || $data instanceof \CFDev\Fields\Accordion
            || $data instanceof \CFDev\Fields\Bundle
        ) {
            $data->output($object);
        } else {
            $this->renderTable($data, $object);
        }

        echo '</div>';
    }

    private function renderTable(iterable $data, object $object): void
    {
        echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cfdev-table">';

        foreach ($data as $id_name => $field) {
            $value = match ($this->metaType()) {
                'user'  => get_user_meta($object->ID, $id_name, true),
                'term'  => get_term_meta($object->ID, $id_name, true),
                default => get_post_meta($object->ID, $id_name, true),
            };
            $value = \CFDev\Field::decodeMetaValue($value);

            $field_errors = ErrorBag::forField($id_name);
            $has_error    = ! empty($field_errors);

            if ($field instanceof \CFDev\Fields\Hidden) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->output($value, $object);
                continue;
            }

            echo '<tr' . ($has_error ? ' class="cfdev-has-error"' : '') . '>';
            echo '<th class="cfdev-th">';
            echo '<label for="' . esc_attr($id_name) . '" class="cfdev_label">'
                . esc_html($field->label)
                . '</label>';
            echo $field->required ? ' <span class="cfdev-required">*</span>' : '';
            echo '<div class="cfdev-description description">'
                . wp_kses_post($field->description)
                . '</div>';
            echo '</th>';
            echo '<td class="cfdev-td">';

            $this->renderField($field, $value, $object);

            if ($has_error) {
                echo '<p class="cfdev-field-error">'
                    . esc_html(implode(' ', $field_errors))
                    . '</p>';
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    private function renderField(object $field, mixed $value, object $object): void
    {
        if ($field->repeatable && $field->supports_repeatable) {
            echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">';
            echo sprintf('+ %s', esc_html(__('Add', 'cfdev')));
            echo '</a>';
            echo '<ul class="js-cfdev-sortable cfdev-sortable cfdev_repeatable_wrap">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value, $object);
            echo '</ul>';
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value, $object);
        }
    }

    /**
     * Normal save method to save all the fields in a metabox
     * Metabox and User Meta rely on this method
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function save(int $object_id, array $values)
    {
        if (!isset($_POST['cfdev_nonce'], $_POST['cfdev'])) {
            return;
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['cfdev_nonce']));
        if (!wp_verify_nonce($nonce, 'cfdev_meta')) {
            return;
        }
        if (!current_user_can('edit_post', $object_id)) {
            return;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $cfdev_data = wp_kses_post_deep(wp_unslash($_POST['cfdev']));

        // Loop through each meta box
        //if (! empty($this->data) && isset($_POST['cfdev'])) { // je le garde pour le moment
        if (!empty($this->data) && !empty($cfdev_data)) {
            if ($this->data instanceof \CFDev\Fields\Bundle && $bundle = $this->data) {
                if (isset($values[$bundle->id])) {
                    $bundle->save($object_id, $values[$bundle->id]);
                }
            } elseif ($this->data instanceof \CFDev\Fields\Tabs || $this->data instanceof \CFDev\Fields\Accordion) {
                foreach ($this->data->tabs as $tab) {
                    if ($tab->fields instanceof \CFDev\Fields\Bundle && $bundle = $tab->fields) {
                        if (isset($values[$bundle->id])) {
                            $bundle->save($object_id, $values[$bundle->id]);
                        }
                    } else {
                        $this->save($object_id, $values);
                    }
                }
            } else {
                $this->save($object_id, $values);
            }
        }
    }

    /**
     * Checks if the given array are tabs
     *
     * @param   array           $data
     * @return  boolean
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public static function isTabs($data)
    {
        return isset($data[0]) && ( ! is_array($data[0]) ) && ( $data[0] == 'tabs' );
    }

    /**
     * Checks if the given array is an accordion
     *
     * @param   array           $data
     * @return  bool
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public static function isAccordion($data)
    {
        return isset($data[0]) && ( ! is_array($data[0]) ) && ( $data[0] == 'accordion' );
    }

    /**
     * Checks if the given array is a bundle
     *
     * @param   array           $data
     * @return  bool
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public static function isBundle($data)
    {
        return isset($data[0]) && ( ! is_array($data[0]) ) && ( $data[0] == 'bundle' );
    }

    // =========================================================
    // Admin notice
    // =========================================================

    /**
     * Shows a global admin notice if there are validation errors for the current object.
     * Hooked on admin_notices by each subclass constructor.
     */
    public function showValidationNotice(): void
    {
        if (self::$noticeShown) {
            return;
        }

        $object_id = $this->resolveObjectId();
        if (!$object_id) {
            return;
        }

        $errors = ErrorBag::peek($this->metaType(), $object_id);
        if (empty($errors)) {
            return;
        }

        self::$noticeShown = true;
        $items = array_map(
            function (string $key, array $e): string {
                $anchor = str_contains($key, '.') ? explode('.', $key)[0] : $key;
                return sprintf(
                    '<a href="#%s"><strong>%s</strong></a> <abbr title="%s" style="cursor:help">ⓘ</abbr> : %s',
                    esc_attr($anchor),
                    esc_html($e['label']),
                    esc_attr($key),
                    esc_html(implode(', ', $e['errors']))
                );
            },
            array_keys($errors),
            array_values($errors)
        );
        $header = sprintf(
            _n('%s field needs attention', '%s fields need attention', count($errors), 'cfdev'),
            '<strong>' . count($errors) . '</strong>'
        );
        (new Notice(
            array_merge([sprintf($header, count($errors))], $items),
            'error',
            true
        ))->render();
    }

    // =========================================================
    // Validation
    // =========================================================

    /**
     * Validates all fields and returns an error map.
     * Keys are field ids (or "bundle_id.row.field_id" for bundle fields).
     *
     * @param  array<string, mixed> $values  Raw $_POST['cfdev'] values
     * @return array<string, array{label: string, errors: string[]}>
     */
    protected function validateFields(array $values): array
    {
        $errors = [];

        if ($this->data instanceof \CFDev\Fields\Bundle) {
            $errors = $this->validateBundle($this->data, $values);
        } elseif ($this->data instanceof \CFDev\Fields\Tabs || $this->data instanceof \CFDev\Fields\Accordion) {
            foreach ($this->data->tabs as $tab) {
                if ($tab->fields instanceof \CFDev\Fields\Bundle) {
                    $errors = array_merge($errors, $this->validateBundle($tab->fields, $values));
                } else {
                    foreach ((array) $tab->fields as $id => $field) {
                        $entry = $this->validateSingleField($field, $values[$id] ?? '');
                        if ($entry) {
                            $errors[$id] = $entry;
                        }
                    }
                }
            }
        } else {
            foreach ($this->fields as $id => $field) {
                if ($field->in_bundle) {
                    continue;
                }
                $entry = $this->validateSingleField($field, $values[$id] ?? '');
                if ($entry) {
                    $errors[$id] = $entry;
                }
            }
        }

        return $errors;
    }

    /**
     * @return array{label: string, errors: string[]}|null
     */
    private function validateSingleField(\CFDev\Field $field, mixed $value): ?array
    {
        $validator = $field->validate($value);
        if ($validator->passes()) {
            return null;
        }
        return [
            'label'  => $field->label,
            'errors' => $validator->errors(),
        ];
    }

    /**
     * @return array<string, array{label: string, errors: string[]}>
     */
    private function validateBundle(\CFDev\Fields\Bundle $bundle, array $values): array
    {
        $errors = [];
        $rows   = $values[$bundle->id] ?? [];

        foreach ((array) $rows as $i => $row) {
            foreach ($bundle->fields as $id => $field) {
                $validator = $field->validate($row[$id] ?? '');
                if (! $validator->passes()) {
                    $key          = $bundle->id . '.' . $i . '.' . $id;
                    $errors[$key] = [
                        'label'  => sprintf('%s (ligne %d)', $field->label, $i + 1),
                        'errors' => $validator->errors(),
                    ];
                }
            }
        }

        return $errors;
    }

    // =========================================================
    // Build
    // =========================================================

    /**
     * This array builds the complete array with the right key => value pairs
     *
     * @param   array           $data
     * @return  array
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function build($data, $parent = null)
    {
        $return = array();

        if (is_array($data) && ! empty($data)) {
            if (self::isTabs($data) || self::isAccordion($data)) {
                $tabs               = self::isTabs($data) ? new  \CFDev\Fields\Tabs($this->id) : new  \CFDev\Fields\Accordion($this->id);
                $tabs->meta_type    = $this->metaType();

                foreach ($data[1] as $title => $fields) {
                    $tab            = new  \CFDev\Fields\Tab($title);
                    $tab->meta_type = $this->metaType();

                    if (self::isBundle($fields[0])) {
                        $tab->fields = $this->build($fields[0]);
                    } else {
                        foreach ($fields as $field) {
                            $class = $this->getClassFieldByType($field['type']);
                            if (class_exists($class)) {
                                $field = new $class($field, $this->id);
                                $field->meta_type           = $this->metaType();

                                $this->fields[$field->id]   = $field;
                                $tab->fields[$field->id]    = $field;
                            }
                        }
                    }

                    $tabs->tabs[$title] = $tab;
                }

                $return = $tabs;
            } elseif (self::isBundle($data)) {
                $bundle_id   = is_string($data[1]) ? $data[1] : $this->id;
                $fields_list = is_string($data[1]) ? ($data[2] ?? []) : $data[1];
                $bundle      = new \CFDev\Fields\Bundle($bundle_id, $data);

                foreach ($fields_list as $field) {
                    $class = $this->getClassFieldByType($field['type']);
                    if (class_exists($class)) {
                        $field = new $class($field, '');
                        //$field->repeatable = false; je veux le garder au cas ou
                        $field->ajax            = false;
                        $field->meta_type       = $this->metaType();
                        $field->in_bundle       = true;

                        $this->fields[$field->id]   = $field;
                        $bundle->fields[$field->id] = $field;
                        $bundle->meta_type          = $this->metaType();
                    }
                }

                $return = $bundle;
            } else {
                foreach ($data as $field) {
                    $class = $this->getClassFieldByType($field['type']);
                    if (class_exists($class)) {
                        $field = new $class($field, $this->id);
                        $field->meta_type           = $this->metaType();

                        $this->fields[$field->id]   = $field;
                        $return[$field->id]         = $field;
                    }
                }
            }
        }

        return $return;
    }

    private function getClassFieldByType($type)
    {
        return 'CFDev\\Fields\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));
    }

    /**
     * Adds multipart support to form
     *
     * @return  mixed
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public static function editFormTag()
    {
        echo ' enctype="multipart/form-data"';
    }
}
