<?php

namespace CFDev\Fields;

use CFDev\Abstracts\FieldContainer;
use CFDev\Support\Str;
use CFDev\Validation\ErrorBag;

class Tab extends FieldContainer
{
    public string $title = '';
    /** @var array<string, \CFDev\Field>|\CFDev\Fields\Bundle */
    public array|\CFDev\Fields\Bundle $fields = [];

    public function __construct(string $title)
    {
        $this->id       = Str::uglify($title);
        $this->title    = Str::beautify($title);
    }

    //public function output($post, $type)
//    {
//        $fields = $this->fields;
//
//        // Show header
//        if ($type == 'accordion') {
//            echo '<h3>' . esc_html($this->title) . '</h3>';
//        }
//
//        echo '<div id="cfdev-' . esc_attr($this->id) . '">';
//
//        if ($fields instanceof \CFDev\Fields\Bundle) {
//            $fields->output($post);
//        } else {
//            echo '<table border="0" cellading="0" cellspacing="0" class="from-table cfdev-table">';
//            foreach ($fields as $id => $field) {
//                $value = $this->meta_type == 'user' ? get_user_meta($post->ID, $id, true) : get_post_meta($post->ID, $id, true);
//
//                if (! $field instanceof \CFDev\Fields\Hidden) {
//                    $field_errors = ErrorBag::forField($id);
//                    $has_error    = ! empty($field_errors);
//
//                    echo '<tr' . ( $has_error ? ' class="cfdev-has-error"' : '' ) . '>';
//                        echo '<th class="cfdev-th">';
//                            echo '<label for="' . esc_attr($id) . '" class="cfdev-label">' . esc_html($field->label) . '</label>';
//                            echo $field->required ? ' <span class="cfdev-required">*</span>' : '';
//                            echo '<div class="cfdev-description">' . wp_kses_post($field->description) . '</div>';
//                        echo '</th>';
//                        echo '<td class="cfdev-td">';
//
//                    if ($field->repeatable && $field->supports_repeatable) {
//                        echo '<div class="cfdev-padding-wrap">';
//                        echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">';
//                        echo sprintf('+ %s', esc_html(__('Add', 'cfdev')));
//                        echo '</a>';
//                        echo '<ul class="js-cfdev-sortable cfdev-sortable">';
//                    }
//                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                        echo $field->output($value);
//
//                    if ($field->repeatable && $field->supports_repeatable) {
//                        echo '</ul></div>';
//                    }
//
//                    if ($has_error) {
//                        echo '<p class="cfdev-field-error">' . esc_html(implode(' ', $field_errors)) . '</p>';
//                    }
//
//                            echo '</td>';
//                            echo '</tr>';
//                } else {
//                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
//                    echo $field->output($value);
//                }
//            }
//                echo '</table>';
//        }
//        echo '</div>';
//    }


    public function output(object $post, string $type): void
    {
        if ($type === 'accordion') {
            echo '<h3>' . esc_html($this->title) . '</h3>';
        }

        echo '<div id="cfdev-' . esc_attr($this->id) . '">';

        if ($this->fields instanceof \CFDev\Fields\Bundle) {
            $this->fields->output($post);
        } else {
            $this->renderTable($post);
        }

        echo '</div>';
    }

    private function renderTable(object $post): void
    {
        echo '<table border="0" cellpadding="0" cellspacing="0" class="from-table cfdev-table">';

        foreach ($this->fields as $id => $field) {
            $value = $this->resolveValue($post->ID, $id);

            if ($field instanceof \CFDev\Fields\Hidden) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->output($value);
            } else {
                $this->renderRow($id, $field, $value);
            }
        }

        echo '</table>';
    }

    private function resolveValue(int $postId, string $id): mixed
    {
        $value = match ($this->meta_type) {
            'user'  => get_user_meta($postId, $id, true),
            'term'  => get_term_meta($postId, $id, true),
            default => get_post_meta($postId, $id, true),
        };

        return \CFDev\Field::decodeMetaValue($value);
    }

    private function renderRow(string $id, \CFDev\Field $field, mixed $value): void
    {
        $fieldErrors = ErrorBag::forField($id);
        $hasError    = !empty($fieldErrors);

        echo sprintf('<tr%s>', $hasError ? ' class="cfdev-has-error"' : '');

        echo sprintf(
            '<th class="cfdev-th">
            <label for="%s" class="cfdev-label">%s</label>%s
            <div class="cfdev-description">%s</div>
        </th>',
            esc_attr($id),
            esc_html($field->label),
            $field->required ? ' <span class="cfdev-required">*</span>' : '',
            wp_kses_post($field->description)
        );

        echo '<td class="cfdev-td">';
        $this->renderFieldOutput($field, $value);

        if ($hasError) {
            echo '<p class="cfdev-field-error">' . esc_html(implode(' ', $fieldErrors)) . '</p>';
        }

        echo '</td></tr>';
    }

    private function renderFieldOutput(\CFDev\Field $field, mixed $value): void
    {
        if ($field->repeatable && $field->supports_repeatable) {
            echo '<div class="cfdev-padding-wrap">';
            echo '<a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">+ ' . esc_html(__('Add', 'cfdev')) . '</a>';
            echo '<ul class="js-cfdev-sortable cfdev-sortable">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
            echo '</ul></div>';
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
        }
    }

//    public function output(object $post, string $type): void
//    {
//        if ($type === 'accordion') {
//            echo '<h3>' . esc_html($this->title) . '</h3>';
//        }
//
//        echo '<div id="cfdev-' . esc_attr($this->id) . '">';
//
//        if ($this->fields instanceof \CFDev\Fields\Bundle) {
//            $this->fields->output($post);
//        } else {
//            echo $this->buildTable($post);
//        }
//
//        echo '</div>';
//    }
//
//    private function buildTable(object $post): string
//    {
//        $html = '<table border="0" cellpadding="0" cellspacing="0" class="from-table cfdev-table">';
//
//        foreach ($this->fields as $id => $field) {
//            $value = $this->resolveValue($post->ID, $id);
//            $html .= $field instanceof \CFDev\Fields\Hidden
//                ? $field->output($value)
//                : $this->buildRow($id, $field, $value);
//        }
//
//        return $html . '</table>';
//    }
//
//    private function resolveValue(int $postId, string $id): mixed
//    {
//        return $this->meta_type === 'user'
//            ? get_user_meta($postId, $id, true)
//            : get_post_meta($postId, $id, true);
//    }
//
//    private function buildRow(string $id, \CFDev\Field $field, mixed $value): string
//    {
//        $fieldErrors = ErrorBag::forField($id);
//        $hasError    = !empty($fieldErrors);
//
//        $th = sprintf(
//            '<th class="cfdev-th">
//            <label for="%s" class="cfdev-label">%s</label>%s
//            <div class="cfdev-description">%s</div>
//        </th>',
//            esc_attr($id),
//            esc_html($field->label),
//            $field->required ? ' <span class="cfdev-required">*</span>' : '',
//            wp_kses_post($field->description)
//        );
//
//        $td = sprintf(
//            '<td class="cfdev-td">%s%s</td>',
//            $this->buildFieldOutput($field, $value),
//            $hasError ? '<p class="cfdev-field-error">' . esc_html(implode(' ', $fieldErrors)) . '</p>' : ''
//        );
//
//        return sprintf(
//            '<tr%s>%s%s</tr>',
//            $hasError ? ' class="cfdev-has-error"' : '',
//            $th,
//            $td
//        );
//    }
//
//    private function buildFieldOutput(\CFDev\Field $field, mixed $value): string
//    {
//        if (!$field->repeatable || !$field->supports_repeatable) {
//            return $field->output($value);
//        }
//
//        return sprintf(
//            '<div class="cfdev-padding-wrap">
//            <a class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable" href="#">+ %s</a>
//            <ul class="js-cfdev-sortable cfdev-sortable">%s</ul>
//        </div>',
//            esc_html(__('Add', 'cfdev')),
//            $field->output($value)
//        );
//    }
}
