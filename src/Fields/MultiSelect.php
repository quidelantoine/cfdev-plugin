<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\Str;

class MultiSelect extends Field
{
    public bool $supports_bundle       = true;
    
    public array $css_classes            = array( 'cfdev-input cfdev-select cfdev-multi-select' );
    
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);
        
        $this->default_value    = (array) $this->default_value;
        $this->after           .= '[]';
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
            '<select %s %s %s multiple="true">%s</select>%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $html,
            $this->outputExplanation()
        );
    }

    private function buildNoneOption(string|array $value): string
    {
        return sprintf(
            '<option value="0" %s>%s</option>',
            $this->resolveSelected(0, $value),
            htmlspecialchars($this->args['show_option_none'], ENT_QUOTES, 'UTF-8')
        );
    }

    private function buildOption(string $slug, string $name, string|array $value): string
    {
        return sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'),
            $this->resolveSelected($slug, $value),
            Str::beautify($name)
        );
    }

    private function resolveSelected(string|int $slug, string|array $value): string
    {
        $isSelected = match (true) {
            is_array($value)  => in_array($slug, $value),
            $value === '-1'   => false,
            default           => in_array($slug, $this->default_value),
        };

        return $isSelected ? 'selected="selected"' : '';
    }

    public function saveValue(string|array $value): string|array
    {
        return empty($value) ? '-1' : $value;
    }
}
