<?php

namespace Weblitzer\CFDev;

use Weblitzer\CFDev\Support\Str;
use Weblitzer\CFDev\Validation\Validator;

/**
 * Custom Field Class
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */

class Field
{
    // Identification
    public string $id = '';
    public string $type = '';
    public string $name = '';
    public string $label = '';
    public string $description = '';
    public string $explanation = '';
    // Valeur/Options
    /** @var string|array<mixed> */
    public string|array $default_value = '';
    /** @var array<string, string> */
    public array $options = array(); // Only used for radio, checkboxes etc.
    /** @var array<string, mixed> */
    public array $args = array(); // Specific args for the field
    // Flag Behavior
    public bool $underscore = true;
    public bool $repeatable = false;
    public bool $ajax = false;
    // Context
    public string|null $parent = '';
    public string $meta_type = '';
    public bool $in_bundle = false;
    // AdminColumn
    public bool $show_admin_column      = false;
    public bool $admin_column_sortable  = false;
    public bool $admin_column_filter    = false;
    // Render
    /** @var array<string, mixed> */
    public array $data_attributes = array();
    /** @var array<string> */
    public array $css_classes = array();
    public string $pre = ''; // Before name
    public string $after = ''; // After name
    public string $pre_id = ''; // Before id
    public string $after_id = ''; // After id
    // Capabilities
    public bool $supports_repeatable = false;
    public bool $supports_bundle = false;
    public bool $supports_ajax = false;
    // REST API
    public bool $rest = false;
    // Validation
    public bool $required = false;
    /** @var array<\Weblitzer\CFDev\Contracts\Validatable> */
    protected array $rules = [];

    /**
     * Constructs a Custom_Field
     *
     * @param   array<mixed>    $field
     * @param   string|null     $parent
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function __construct(array $field, string|null $parent)
    {
        $this->type             = $field['type'] ?? $this->type;
        $this->name             = $field['name'] ?? $this->name;
        $this->label            = $field['label'] ?? $this->label;
        $this->description      = $field['description'] ?? $this->description;
        $this->explanation      = $field['explanation'] ?? $this->explanation;
        $this->default_value    = $field['default_value'] ?? $this->default_value;
        $this->options          = $field['options'] ?? $this->options;
        $this->args             = $field['args'] ?? $this->args;
        $this->underscore       = $field['underscore'] ?? $this->underscore;
        $this->rest             = $field['rest'] ?? $this->rest;
        $this->required         = $field['required'] ?? $this->required;
        $this->repeatable       = $field['repeatable'] ?? $this->repeatable;
        $this->ajax             = $field['ajax'] ?? $this->ajax;
        $this->css_classes      = isset($field['css_classes']) ? array_merge($this->css_classes, $field['css_classes']) : $this->css_classes;
        // Column
        $this->show_admin_column     = $field['show_admin_column'] ?? $this->show_admin_column;
        $this->admin_column_sortable = $field['admin_column_sortable'] ?? $this->admin_column_sortable;
        $this->admin_column_filter   = $field['admin_column_filter'] ?? $this->admin_column_filter;
        // Mostly name of the meta-box
        $this->parent = $parent;
        // ID is used as id to select the field, if it's not in the $field parameter, the id will be generated
        $this->id = $field['id'] ?? $this->buildId($this->name, $parent ?? '');
        // Auto-add Required rule if field is marked required
        if ($this->required) {
            $this->rules[] = new \Weblitzer\CFDev\Validation\Rules\Required();
        }

        // Additional rules declared inline in the field config array
        if (! empty($field['rules']) && is_array($field['rules'])) {
            $this->rules = array_merge($this->rules, $field['rules']);
        }
    }

    // =========================================================
    // Output
    // =========================================================

    /**
     * Outputs a field based on its type
     *
     * @param   string|array<mixed>  $value
     * @return  mixed
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function output(string|array $value): mixed
    {
        if ($this->repeatable && $this->supports_repeatable) {
            return $this->repeatableOutput($value);
        } elseif ($this->ajax && $this->supports_ajax) {
            return $this->ajaxOutput($value);
        } else {
            return $this->outputHtml($value);
        }
    }

    /**
     * Output method
     * Defaults to a normal text field
     *
     * @param   string|array<mixed>  $value
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputHtml(string|array $value): string
    {
        $scalar  = is_string($value) ? $value : '';
        $content = strlen($scalar) > 0 ? $scalar : (is_string($this->default_value) ? $this->default_value : '');

        $attributes = implode(' ', array_filter([
            'type="text"',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            'value="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '"',
            $this->outputDataAttributes(),
        ]));

        return sprintf('<input %s />%s', $attributes, $this->outputExplanation());
    }

    /**
     * Outputs the field, ready for repeatable functionality
     *
     * @param   string|array<mixed>  $value
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function repeatableOutput(string|array $value): string
    {
        $this->after = '[]';

        $items     = is_array($value) ? $value : [$value];
        $isMultiple = count($items) > 1;
        $output    = '';

        foreach ($items as $item) {
            $output .= $this->buildSortableItem($item, $isMultiple);
        }

        return $output;
    }

    private function buildSortableItem(string $item, bool $showRemoveButton): string
    {
        $drag    = esc_attr(__('Drag to reorder', 'cfdev'));
        $handle  = '<button type="button" class="cfdev-handle-sortable js-cfdev-handle-sortable" aria-label="' . $drag . '"></button>';
        $content = '<fieldset>' . $this->outputHtml($item) . '</fieldset>';
        $remove  = $showRemoveButton
            ? '<button type="button" class="js-cfdev-remove-sortable cfdev-remove-sortable" aria-label="' . esc_attr(__('Remove', 'cfdev')) . '"></button>'
            : '';
        $closing = '</li>';

        return sprintf(
            '<li class="cfdev-field cfdev-sortable-item js-cfdev-sortable-item">%s%s%s%s',
            $handle,
            $content,
            $remove,
            $closing
        );
    }

    /**
     * Outputs the field, ready for ajax save
     *
     * @param   string|array<mixed>  $value
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function ajaxOutput(string|array $value): string
    {
        $output = $this->outputHtml($value);
        $output .= '<button type="button" class="cfdev-ajax-save js-cfdev-ajax-save button-secondary">' . esc_html(__('Save', 'cfdev')) . '</button>';

        return $output;
    }

    // =========================================================
    // Save
    // =========================================================
    /**
     * Saves the field value to the appropriate meta type
     *
     * @param int              $object_id
     * @param string|array<mixed> $value
     *
     * @return int|bool|\WP_Error
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function save(int $object_id, string|array $value): int|bool|\WP_Error
    {
        $value = $this->saveValue($value);

        if (is_array($value)) {
            $value = wp_json_encode($value);
        }

        return match ($this->meta_type) {
            'user' => update_user_meta($object_id, $this->id, $value),
            'post' => update_post_meta($object_id, $this->id, $value),
            'term' => update_term_meta($object_id, $this->id, $value),
            default => false,
        };
    }

    /**
     * Decodes a meta value stored as JSON. Returns the original value unchanged
     * for plain strings (e.g. the '-1' empty sentinel) and non-string types.
     */
    public static function decodeMetaValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || ($value[0] !== '[' && $value[0] !== '{')) {
            return $value;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Second attempt: WordPress may have added slashes when storing the string.
        $decoded = json_decode(wp_unslash($value), true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Output save value
     *
     * @param string|array<mixed> $value
     *
     * @return string|array<mixed>
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function saveValue(string|array $value): string|array
    {
        return $value;
    }

    /**
     * Saves an ajax field
     *
     * @return  void
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public static function ajaxSave(): void
    {
        if (! isset($_POST['cfdev'])) {
            wp_send_json_error(['message' => 'Invalid request.'], 400);
        }

        $nonce = sanitize_text_field($_POST['cfdev']['nonce'] ?? '');
        if (! wp_verify_nonce($nonce, 'cfdev_ajax_save')) {
            wp_send_json_error(['message' => 'Invalid nonce.'], 403);
        }

        $object_id = intval($_POST['cfdev']['object_id'] ?? 0);
        $field_id  = sanitize_text_field($_POST['cfdev']['field_id']  ?? '');
        $value     = sanitize_text_field($_POST['cfdev']['value']     ?? '');
        $meta_type = sanitize_key($_POST['cfdev']['meta_type']        ?? '');

        if (empty($object_id) || empty($field_id)) {
            wp_send_json_error(['message' => 'Missing required fields.'], 400);
        }

        $can_save = match ($meta_type) {
            'post' => current_user_can('edit_post', $object_id),
            'user' => current_user_can('edit_user', $object_id),
            'term' => current_user_can('edit_term', $object_id),
            default => false,
        };

        if (! $can_save) {
            wp_send_json_error(['message' => 'Insufficient permissions.'], 403);
        }

        match ($meta_type) {
            'post' => update_post_meta($object_id, $field_id, $value),
            'user' => update_user_meta($object_id, $field_id, $value),
            'term' => update_term_meta($object_id, $field_id, $value),
            default => null,
        };

        wp_send_json_success();
    }

    // =========================================================
    // HTML Attribute Helpers
    // =========================================================

    /**
     * Outputs the field name attribute
     *
     * @param   string|null     $overwrite
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputName(?string $overwrite = null): string
    {
        $name = $overwrite ?: "cfdev$this->pre[$this->id]$this->after";
        return "name=\"$name\"";
    }

    /**
     * Outputs the field id attribute
     *
     * @param string|null $overwrite
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputId(?string $overwrite = null): string
    {
        $id = $overwrite ?? "$this->pre_id$this->id$this->after_id";
        return "id=\"$id\"";
    }

    /**
     * Outputs the field css classes
     *
     * @param   array<string>   $extra
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputCssClass(array $extra = array()): string
    {
        $classes = array_merge($this->css_classes, $extra);
        return 'class="' . implode(' ', $classes) . '"';
    }

    /**
     * Outputs the field data attributes
     *
     * @param   array<string, mixed>    $extra
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputDataAttributes(array $extra = array()): string
    {
        $output = '';

        foreach (array_merge($this->data_attributes, $extra) as $attribute => $value) {
            if (! is_null($value)) {
                $output .= 'data-' . $attribute . '="' . $value . '"';
            } elseif (isset($this->args[Str::uglify($attribute)])) {
                $output .= 'data-' . $attribute . '="' . $this->args[Str::uglify($attribute)] . '"';
            }
        }

        return $output;
    }

    /**
     * Outputs the for attribute
     *
     * @param   string|null     $for
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputForAttribute(?string $for = null): string
    {
        return $for ? 'for="' . $for . '"' : '';
    }

    /**
     * Outputs the field explanation
     *
     * @return  string
     * 1. If the field is repeatable, it will not output anything
     * 2. If the field has no explanation, it will not output anything
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function outputExplanation(): string
    {
        if ($this->repeatable || !$this->explanation) {
            return '';
        }
        //return "<em class=\"cfdev-explanation\">$this->explanation</em>";
        return sprintf('<em class="cfdev-explanation">%s</em>', esc_html($this->explanation));
    }

    // =========================================================
    // Validation
    // =========================================================

    /**
     * Sets validation rules on the field
     *
     * @since  1.0.0
     * @param  array<\Weblitzer\CFDev\Contracts\Validatable> $rules  Validation rules to apply
     * @return static
     */
    public function setRules(array $rules): static
    {
        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /** @return array<\Weblitzer\CFDev\Contracts\Validatable> */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Validates the field value against all rules
     *
     * @since  1.0.0
     * @param  mixed $value  Value to validate
     * @return Validator
     */
    public function validate(mixed $value): Validator
    {
        return new Validator($value, $this->rules);
    }


    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Builds a string used as field id and name
     *
     * @param   string          $name
     * @param   string          $parent
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function buildId(string $name, string $parent): string
    {
        return apply_filters('cfdev_build_id', ( $this->underscore ? '_' : '' ) . ( ! empty($parent) ? Str::uglify($parent) . '_' : '' ) . Str::uglify($name));
    }

    public function restType(): string
    {
        return match ($this->type) {
            'number' => 'number',
            default  => 'string',
        };
    }
}
