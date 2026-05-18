<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Checkbox extends Field
{
    public bool $supports_bundle = true;
    /** @var array<string> */
    public array $css_classes = array( 'cfdev-input' );
    public bool $supports_ajax         = true;

    /**
     * @param string|array<mixed> $value
     */
    public function outputHtml(string|array $value): string
    {
        $checked = !empty($value)
            ? checked($value, 'on', false)
            : checked($this->default_value, 'on', false);

        $input = sprintf(
            '<input type="checkbox" %s %s %s %s />',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $checked
        );

        return sprintf(
            '<div class="cfdev-checkbox-wrap">%s</div>%s',
            $input,
            $this->outputExplanation()
        );
    }

    /**
     * @param string|array<mixed> $value
     * @return string
     */
    public function saveValue(string|array $value): string
    {
        return is_string($value) && !empty($value) ? $value : '-1';
    }
}
