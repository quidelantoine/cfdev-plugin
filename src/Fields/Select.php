<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\Str;

class Select extends Field
{
    public bool $supports_repeatable   = true;
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;

    public array $css_classes            = array( 'cfdev-input cfdev-select' );
    public array $data_attributes        = array( 'default-value' => null );

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);
        
        $this->data_attributes['default-value'] = $this->default_value;
    }

    public function outputHtml(string|array $value): string
    {

        $html = '';

        if (isset($this->args['show_option_none'])) {
            $html .= $this->buildNoneOption($value);
        }

        if (!empty($this->options)) {
            foreach ($this->options as $slug => $name) {
                $html .= $this->buildOption($slug, $name, $value);
            }
        }

        return sprintf(
            '<select %s %s %s %s>%s</select>%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $this->outputDataAttributes(),
            $html,
            $this->outputExplanation()
        );
    }

    private function buildNoneOption(string|array $value): string
    {
        $selected = empty($value) ? 'selected="selected"' : '';

        return sprintf(
            '<option value="0" %s>%s</option>',
            $selected,
            htmlspecialchars($this->args['show_option_none'], ENT_QUOTES, 'UTF-8')
        );
    }

    private function buildOption(string $slug, string $name, string|array $value): string
    {
        $selected = (is_string($value) && $value !== '')
            ? selected($slug, $value, false)
            : selected($this->default_value, $slug, false);

        return sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'),
            $selected,
            Str::beautify($name)
        );
    }
}
