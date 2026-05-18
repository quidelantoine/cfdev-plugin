<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Hidden extends Field
{
    /** @var array<string> */
    public array $css_classes = array( 'cfdev-input' );

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $scalar  = is_string($value) ? $value : '';
        $content = strlen($scalar) > 0 ? $scalar : $this->default_value;

        return sprintf(
            '<input type="hidden" %s %s %s value="%s" %s />%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            esc_attr(is_string($content) ? $content : ''),
            $this->outputDataAttributes(),
            $this->outputExplanation()
        );
    }
}
