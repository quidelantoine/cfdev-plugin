<?php

namespace CFDev\Fields;

use CFDev\Field;

class Hidden extends Field
{
    public array $css_classes = array( 'cfdev-input' );

    public function outputHtml(string|array $value): string
    {
        $value = strlen((string) $value) > 0 ? $value : $this->default_value;

        return sprintf(
            '<input type="hidden" %s %s %s value="%s" %s />%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            esc_attr($value),
            $this->outputDataAttributes(),
            $this->outputExplanation()
        );
    }
}
