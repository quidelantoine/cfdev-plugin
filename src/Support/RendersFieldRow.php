<?php

namespace Weblitzer\CFDev\Support;

trait RendersFieldRow
{
    protected function renderThHtml(string $labelFor, \Weblitzer\CFDev\Field $field): void
    {
        echo '<th class="cfdev-th"><label for="' . esc_attr($labelFor) . '" class="cfdev-label">';
        echo $field->fieldIconHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fieldIconHtml() uses esc_attr() internally
        echo esc_html($field->label) . '</label>';
        echo $field->required ? ' <span class="cfdev-required">*</span>' : '';
        echo '<div class="cfdev-description">' . wp_kses_post($field->description) . '</div></th>';
    }

    /** @param string[] $errors */
    protected function renderFieldErrors(array $errors): void
    {
        if (!empty($errors)) {
            echo '<p class="cfdev-field-error">' . esc_html(implode(' ', $errors)) . '</p>';
        }
    }
}
