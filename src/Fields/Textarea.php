<?php

namespace CFDev\Fields;

use CFDev\Field;

class Textarea extends Field
{
    public bool $supports_repeatable   = true;
    public bool $supports_bundle       = true;
    public bool $supports_ajax         = true;

    /** @var array<string> */
    public array $css_classes            = array( 'cfdev-input' );

    /** @param string|array<mixed> $value */
    public function outputHtml(array|string $value): string
    {
        $content = strlen($value) > 0 ? $value : $this->default_value;

        return sprintf(
            '<textarea %s %s %s>%s</textarea>%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $content,
            $this->outputExplanation()
        );
    }
}
