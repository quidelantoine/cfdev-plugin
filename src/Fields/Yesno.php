<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Yesno extends Field
{
    public bool $supports_bundle = true;
    /** @var array<string> */
    public array $css_classes    = array( 'cfdev-input' );

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $checked_value = ! empty($value) ? $value : $this->default_value;

        $yes = $this->outputRadio('yes', __('Yes', 'cfdev'), $checked_value);
        $no  = $this->outputRadio('no', __('No', 'cfdev'), $checked_value);

        return sprintf(
            '<div %s class="cfdev-checkbox-wrap">%s<br />%s</div>%s',
            $this->outputId(),
            $yes,
            $no,
            $this->outputExplanation()
        );
    }

    private function outputRadio(string $option, string $label, mixed $checked_value): string
    {
        $id = $this->id . $this->after_id . '_' . $option;

        return sprintf(
            '<input type="radio" %s %s %s value="%s" %s /> <label class="cfdev-label" for="%s">%s</label>',
            $this->outputName(),
            $this->outputId($id),
            $this->outputCssClass(),
            esc_attr($option),
            checked($checked_value, $option, false),
            esc_attr($id),
            esc_html($label)
        );
    }
}
