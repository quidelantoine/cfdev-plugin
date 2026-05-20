<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Abstracts\FieldContainer;
use Weblitzer\CFDev\Validation\ErrorBag;

class Bundle extends FieldContainer
{
    /** @var array<string, \Weblitzer\CFDev\Field> */
    public array $fields = [];
    /** @var string|array<mixed> */
    public string|array $default_value = '';
    public bool $rest = false;

    /**
     * Construct for bundle
     *
     * @param   string      $id
     * @param   array<mixed> $data
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function __construct(string $id, array $data)
    {
        // Bundle data
        $this->default_value = isset($data['default_value']) ? $data['default_value'] : $this->default_value;
        // Bundle id
        $this->id = $this->buildId($id);
    }

    /**
     * Outputs a bundle
     * 
     * @param   object          $post
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function output(object $post): void
    {
        /** @var \WP_Post $post */
//        $meta = $this->meta_type === 'user'
//            ? get_user_meta($post->ID, $this->id, true)
//            : get_post_meta($post->ID, $this->id, true);
        $meta = match ($this->meta_type) {
            'user' => get_user_meta($post->ID, $this->id, true),
            'term' => get_term_meta($post->ID, $this->id, true),
            default => get_post_meta($post->ID, $this->id, true),
        };
        $meta = \Weblitzer\CFDev\Field::decodeMetaValue($meta);

        echo '<div id="' . esc_attr($this->id) . '" class="padding-wrap">';
        echo '<a class="button-secondary cfdev-button js-cfdev-add-sortable'
            . ' js-cfdev-add-bundle cfdev-add-sortable" href="#">'
            . '+ ' . esc_html(__('Add', 'cfdev')) . '</a>';
        echo '<ul class="js-cfdev-sortable cfdev-sortable js-cfdev-bundle" data-cfdev-sortable-type="bundle">';

        if (!empty($meta) && is_array($meta) && isset($meta[0])) {
            $this->renderMetaItems($meta, $post, count($meta) > 1);
        } elseif (!empty($this->default_value)) {
            $this->renderDefaultItems($post);
        } else {
            $this->renderEmptyItem($post);
        }

        echo '</ul>';
        echo '</div>';
    }

    /** @param array<mixed> $meta */
    private function renderMetaItems(array $meta, object $post, bool $showRemove): void
    {
        foreach ($meta as $i => $bundle) {
            echo '<li class="cfdev-sortable-item js-cfdev-sortable-item">';
            echo '<div class="cfdev-handle-sortable js-cfdev-handle-sortable"></div>';
            echo '<fieldset>';
            echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cfdev-table">';

            foreach ($this->fields as $id => $field) {
                $field->pre      = '[' . $this->id . '][' . $i . ']';
                $field->after_id = '_' . $i;
                $value           = $meta[$i][$id] ?? '';
                $errorKey        = $this->id . '.' . $i . '.' . $id;

                $this->renderField($field, $id, $value, $post, $errorKey);
            }

            echo '</table>';
            echo '</fieldset>';
            echo $showRemove ? '<div class="cfdev-remove-sortable js-cfdev-remove-sortable"></div>' : '';
            echo '</li>';
        }
    }

    private function renderDefaultItems(object $post): void
    {
        if (!is_array($this->default_value)) {
            return;
        }

        foreach ($this->default_value as $i => $default) {
            echo '<li class="cfdev-sortable-item js-cfdev-sortable-item">';
            echo '<div class="cfdev-handle-sortable cfdev-handle-bundle js-cfdev-handle-sortable"></div>';
            echo '<fieldset>';
            echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cfdev-table">';

            $y = 0;
            foreach ($this->fields as $id => $field) {
                $field->pre           = '[' . $this->id . '][' . $i . ']';
                $field->after_id      = '_' . $i;
                $field->default_value = $this->default_value[$i][$y] ?? '';

                $this->renderField($field, $id, '', $post);
                $y++;
            }

            echo '</table>';
            echo '</fieldset>';
            echo '</li>';
        }
    }

    private function renderEmptyItem(object $post): void
    {
        echo '<li class="cfdev-sortable-item js-cfdev-sortable-item">';
        echo '<div class="cfdev-handle-sortable cfdev-handle-bundle js-cfdev-handle-sortable"></div>';
        echo '<fieldset>';
        echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cfdev-table">';

        foreach ($this->fields as $id => $field) {
            $field->pre      = '[' . $this->id . '][0]';
            $field->after_id = '_0';

            $this->renderField($field, $id, '', $post);
        }

        echo '</table>';
        echo '</fieldset>';
        echo '</li>';
    }

    private function renderField(\Weblitzer\CFDev\Field $field, string $id, mixed $value, object $post, ?string $errorKey = null): void
    {
        if ($field instanceof \Weblitzer\CFDev\Fields\Hidden) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
            return;
        }

        if ($field instanceof \Weblitzer\CFDev\Fields\Heading) {
            echo '<tr class="cfdev-heading-row"><td colspan="2">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->outputHtml('');
            echo '</td></tr>';
            return;
        }

        $fieldErrors = $errorKey ? ErrorBag::forField($errorKey) : [];
        $hasError    = !empty($fieldErrors);

        echo sprintf('<tr%s>', $hasError ? ' class="cfdev-has-error"' : '');

        echo sprintf(
            '<th class="cfdev-th">
            <label for="%s" class="cfdev-label">%s</label>%s
            <div class="cfdev-description">%s</div>
        </th>',
            esc_attr($id . $field->after_id),
            esc_html($field->label),
            $field->required ? ' <span class="cfdev-required">*</span>' : '',
            wp_kses_post($field->description)
        );

        echo '<td class="cfdev-td">';
        $this->renderFieldOutput($field, $value, $post);

        if ($hasError) {
            echo '<p class="cfdev-field-error">' . esc_html(implode(' ', $fieldErrors)) . '</p>';
        }

        echo '</td></tr>';
    }

    private function renderFieldOutput(\Weblitzer\CFDev\Field $field, mixed $value, object $post): void
    {
        if (!$field->supports_bundle) {
            echo '<em>' . esc_html(__("This input type doesn't support the bundle functionality (yet).", 'cfdev')) . '</em>';
            return;
        }

        if ($field->repeatable && $field->supports_repeatable) {
            echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">+ ' . esc_html(__('Add', 'cfdev')) . '</a>';
            echo '<ul class="js-cfdev-sortable cfdev-sortable cfdev_repeatable_wrap">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
            echo '</ul>';
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
        }
    }
//    public function output($post)
//    {
//        echo '<div class="padding-wrap">';
//            echo '<a class="button-secondary cfdev-button js-cfdev-add-sortable js-cfdev-add-bundle cfdev-add-sortable" href="#">';
//                echo sprintf('+ %s', esc_html(__('Add', 'cfdev')));
//            echo '</a>';
//
//            echo '<ul class="js-cfdev-sortable cfdev-sortable js-cfdev-bundle" data-cfdev-sortable-type="bundle">';
//
//                $meta = $this->meta_type == 'user' ? get_user_meta($post->ID, $this->id, true) : get_post_meta($post->ID, $this->id, true);
//
//        if (! empty($meta) && isset($meta[0])) {
//            $i = 0;
//            foreach ($meta as $bundle) {
//                echo '<li class="cfdev-sortable-item js-cfdev-sortable-item">';
//                    echo '<div class="cfdev-handle-sortable js-cfdev-handle-sortable"></div>';
//                    echo '<fieldset>';
//                    echo '<table border="0" cellading="0" cellspacing="0" class="form-table cfdev-table">';
//
//                foreach ($this->fields as $id => $field) {
//                    $field->pre      = '[' . $this->id . '][' . $i . ']';
//                    $field->after_id = '_' . $i;
//                    $value           = isset($meta[$i][$id]) ? $meta[$i][$id] : '';
//                    $error_key       = $this->id . '.' . $i . '.' . $id;
//                    $field_errors    = ErrorBag::forField($error_key);
//                    $has_error       = ! empty($field_errors);
//
//                    if (! $field instanceof \Weblitzer\CFDev\Fields\Hidden) {
//                        echo '<tr' . ( $has_error ? ' class="cfdev-has-error"' : '' ) . '>';
//                            echo '<th class="cfdev-th">';
//                                echo '<label for="' . esc_attr($id . $field->after_id) . '" class="cfdev-label">' . esc_html($field->label) . '</label>';
//                                echo $field->required ? ' <span class="cfdev-required">*</span>' : '';
//                                echo '<div class="cfdev-description">' . wp_kses_post($field->description) . '</div>';
//                            echo '</th>';
//                            echo '<td class="cfdev-td">';
//
//                        if ($field->supports_bundle) {
//                            if ($field->repeatable && $field->supports_repeatable) {
//                                        echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">';
//                                            echo sprintf('+ %s', esc_html(__('Add', 'cfdev')));
//                                        echo '</a>';
//                                        echo '<ul class="js-cfdev-sortable cfdev-sortable cfdev_repeatable_wrap">';
//                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                                            echo $field->output($value);
//                                        echo '</ul>';
//                            } else {
//                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                                echo $field->output($value);
//                            }
//                        } else {
//                            echo '<em>' . esc_html(__('This input type doesn\'t support the bundle functionality (yet).', 'cfdev')) . '</em>';
//                        }
//
//                        if ($has_error) {
//                            echo '<p class="cfdev-field-error">' . esc_html(implode(' ', $field_errors)) . '</p>';
//                        }
//
//                            echo '</td>';
//                        echo '</tr>';
//                    } else {
//                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                        echo $field->output($value);
//                    }
//                }
//
//                    echo '</table>';
//                    echo '</fieldset>';
//                    echo count($meta) > 1 ? '<div class="cfdev-remove-sortable js-cfdev-remove-sortable"></div>' : '';
//                        echo '</li>';
//
//                        $i++;
//            }
//        } elseif (! empty($this->default_value)) {
//            $i = 0;
//
//            foreach ($this->default_value as $default) {
//                echo '<li class="cfdev-sortable-item js-cfdev-sortable-item">';
//                    echo '<div class="cfdev-handle-sortable cfdev-handle-bundle js-cfdev-handle-sortable"></div>';
//                    echo '<fieldset>';
//                    echo '<table border="0" cellading="0" cellspacing="0" class="form-table cfdev-table">';
//
//                        $fields = $this->fields;
//                        $y      = 0;
//
//                foreach ($fields as $id => $field) {
//                    $field->pre             = '[' . $this->id . '][' . $i . ']';
//                    $field->after_id        = '_' . $i;
//                    $field->default_value   = $this->default_value[$i][$y];
//                    $value                  = '';
//
//                    if (! $field instanceof \Weblitzer\CFDev\Fields\Hidden) {
//                        echo '<tr>';
//                            echo '<th class="cfdev-th">';
//                                echo '<label for="' . esc_attr($id . $field->after_id) . '" class="cfdev-label">' . esc_html($field->label) . '</label>';
//                                echo '<div class="cfdev-description">' . wp_kses_post($field->description) . '</div>';
//                            echo '</th>';
//                            echo '<td class="cfdev-td">';
//
//                        if ($field->supports_bundle) {
//                            if ($field->repeatable && $field->supports_repeatable) {
//                                        echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">';
//                                            echo sprintf('+ %s', esc_html(__('Add', 'cfdev')));
//                                        echo '</a>';
//                                        echo '<ul class="js-cfdev-sortable cfdev-sortable cfdev_repeatable_wrap">';
//                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                                            echo $field->output($value);
//                                        echo '</ul>';
//                            } else {
//                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                                echo $field->output($value);
//                            }
//                        } else {
//                            echo '<em>' . esc_html(__('This input type doesn\'t support the bundle functionality (yet).', 'cfdev')) . '</em>';
//                        }
//
//                        echo '</td>';
//                        echo '</tr>';
//                    } else {
//                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                        echo $field->output($value);
//                    }
//
//                    $y++;
//                }
//
//                echo '</table>';
//                echo '</fieldset>';
//                echo '</li>';
//                $i++;
//            }
//        } else {
//            echo '<li class="cfdev-sortable-item js-cfdev-sortable-item">';
//                echo '<div class="cfdev-handle-sortable cfdev-handle-bundle js-cfdev-handle-sortable"></div>';
//                echo '<fieldset>';
//                echo '<table border="0" cellading="0" cellspacing="0" class="form-table cfdev-table">';
//
//                    $fields = $this->fields;
//
//            foreach ($fields as $id => $field) {
//                $field->pre         = '[' . $this->id . '][0]';
//                $field->after_id    = '_0';
//                $value              = '';
//
//                if (! $field instanceof \Weblitzer\CFDev\Fields\Hidden) {
//                    echo '<tr>';
//                        echo '<th class="cfdev-th">';
//                            echo '<label for="' . esc_attr($id . $field->after_id) . '" class="cfdev-label">' . esc_html($field->label) . '</label>';
//                            echo '<div class="cfdev-description">' . wp_kses_post($field->description) . '</div>';
//                        echo '</th>';
//                        echo '<td class="cfdev-td">';
//
//                    if ($field->supports_bundle) {
//                        if ($field->repeatable && $field->supports_repeatable) {
//                                    echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">';
//                                        echo sprintf('+ %s', esc_html(__('Add', 'cfdev')));
//                                    echo '</a>';
//                                    echo '<ul class="js-cfdev-sortable cfdev-sortable cfdev_repeatable_wrap">';
//                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                                        echo $field->output($value);
//                                    echo '</ul>';
//                        } else {
//                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                            echo $field->output($value);
//                        }
//                    } else {
//                        echo '<em>' . esc_html(__('This input type doesn\'t support the bundle functionality (yet).', 'cfdev')) . '</em>';
//                    }
//
//                    echo '</td>';
//                    echo '</tr>';
//                } else {
//                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                    echo $field->output($value);
//                }
//            }
//            echo '</table>';
//            echo '</fieldset>';
//            echo '</li>';
//        }
//        echo '</ul>';
//        echo '</div>';
//    }

    /**
     * Save bundle meta
     * 
     * @param   int             $object_id
     * @param   array<mixed>    $values
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function save(int $object_id, array $values): void
    {
        $values = apply_filters("cfdev_" . $this->meta_type . "_meta_save_bundle_$this->id", $values, $this, $object_id);   
        $values = apply_filters('cfdev_' . $this->meta_type . '_meta_save_bundle', $values, $this, $object_id);
        $values = array_values(array_filter($values, 'is_array'));

        foreach ($values as $row_id => $row) {
            foreach ($row as $id => $value) {
                if (isset($this->fields[$id])) {
                    $values[$row_id][$id] = $this->fields[$id]->saveValue($value);
                }
            }
        }

        $json = wp_json_encode($values);

        if (false === $json) {
            return;
        }

        if ($this->meta_type == 'user') {
            delete_user_meta($object_id, $this->id);
            update_user_meta($object_id, $this->id, $json);
        } elseif ($this->meta_type == 'term') {
            delete_term_meta($object_id, $this->id);
            update_term_meta($object_id, $this->id, $json);
        } else {
            delete_post_meta($object_id, $this->id);
            update_post_meta($object_id, $this->id, $json);
        }
    }

    /**
     * Build the id for the bundle
     *
     * @param   string  $id
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function buildId(string $id): string
    {
        if (strpos($id, '_', 0) !== 0) {
            $id = '_' . $id;
        }

        return $id;
    }
}
