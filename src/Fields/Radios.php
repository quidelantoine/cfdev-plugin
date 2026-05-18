<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\Str;

class Radios extends Field
{
    public bool $supports_bundle       = true;
    
    public array $css_classes = array( 'cfdev-input' );
    public array $data_attributes = array( 'default-value' => null );

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);
        
        $this->data_attributes['default-value']  = $this->default_value;
        $this->after                            .= '[]';
    }

    public function outputHtml(string|array $value): string
    {
        if (empty($this->options)) {
            return $this->outputExplanation();
        }

        $radios = '';
        foreach ($this->options as $slug => $name) {
            $radios .= $this->buildRadio($slug, $name, $value);
        }

        return sprintf(
            '<div %s class="cfdev-checkboxes-wrap" %s>%s</div>%s',
            $this->outputId(),
            $this->outputDataAttributes(),
            $radios,
            $this->outputExplanation()
        );
    }

    private function buildRadio(string $slug, string $name, string|array $value): string
    {
        $inputId = $this->id . $this->after_id . '_' . Str::uglify($slug);
        $checked = $this->resolveChecked($slug, $value);

        $input = sprintf(
            '<input type="radio" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'),
            $checked
        );

        $label = sprintf(
            '<label %s>%s</label>',
            $this->outputForAttribute($inputId),
            Str::beautify($name)
        );

        return $input . ' ' . $label . '<br />';
    }

    private function resolveChecked(string $slug, string|array $value): string
    {
        if (empty($value)) {
            return checked($this->default_value, $slug, false);
        }

        $decoded = \CFDev\Field::decodeMetaValue($value);
        $values  = is_array($decoded) ? $decoded : [];

        return in_array($slug, $values) ? 'checked="checked"' : '';
    }
}
