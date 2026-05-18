<?php

namespace CFDev\Fields;

use CFDev\Field;

class Toggle extends Field
{
    public bool $supports_bundle = true;
    public bool $supports_ajax   = true;
    /** @var array<string> */
    public array $css_classes    = ['cfdev-input'];

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $is_on = ! empty($value)
            ? ($value === 'on')
            : ($this->default_value === 'on');

        $checked = $is_on ? 'checked="checked"' : '';

        $input = sprintf(
            '<input type="checkbox" %s %s value="on" %s %s />',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $checked
        );

        return sprintf(
            '<div class="cfdev-toggle-wrap"><label class="cfdev-switch">%s<span class="cfdev-switch-slider"></span></label></div>%s',
            $input,
            $this->outputExplanation()
        );
    }

    /** @param string|array<mixed> $value */
    public function saveValue(string|array $value): string
    {
        return is_string($value) && !empty($value) ? $value : '-1';
    }
}
