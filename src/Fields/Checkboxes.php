<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\Str;

class Checkboxes extends Field
{
    public bool $supports_bundle = true;
    
    public array $css_classes = array( 'cfdev-input' );

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->default_value = (array) $this->default_value;
        $this->after        .= '[]';
    }

    public function outputHtml(string|array $value): string
    {
        if (!is_array($this->options)) {
            return $this->outputExplanation();
        }

        $html = '';
        foreach ($this->options as $slug => $name) {
            $html .= $this->buildCheckbox($slug, $name, $value);
        }

        return sprintf(
            '<div %s class="cfdev-padding-wrap cfdev-checkboxes-wrap">%s</div>%s',
            $this->outputId(),
            $html,
            $this->outputExplanation()
        );
    }

    private function buildCheckbox(string $slug, string $name, string|array $value): string
    {
        $inputId = $this->id . $this->after_id . '_' . Str::uglify($slug);

        $input = sprintf(
            '<input type="checkbox" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'),
            $this->resolveChecked($slug, $value)
        );

        $label = sprintf(
            '<label %s>%s</label>',
            $this->outputForAttribute($inputId),
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
        );

        return $input . $label . '<br />';
    }

    private function resolveChecked(string $slug, string|array $value): string
    {
        $isChecked = match (true) {
            is_array($value) => in_array($slug, $value),
            $value === '-1'  => false,
            default          => in_array($slug, $this->default_value),
        };

        return $isChecked ? 'checked="checked"' : '';
    }

    /**
     * @param string|array $value
     * @return string|array
     */
    public function saveValue(string|array $value): string|array
    {
        return empty($value) ? '-1' : $value;
    }
}
