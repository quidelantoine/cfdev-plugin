<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\Str;

class Checkboxes extends Field
{
    public bool $supports_bundle = true;

    /** @var array<string> */
    public array $css_classes = array( 'cfdev-input' );

    /**
     * @param array<mixed>  $field
     * @param string|null   $parent
     */
    public function __construct(array $field, string|null $parent)
    {
        parent::__construct($field, $parent);

        $this->default_value = (array) $this->default_value;
        $this->after        .= '[]';
    }

    /**
     * @param string|array<mixed> $value
     */
    public function outputHtml(string|array $value): string
    {
        if (empty($this->options)) {
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

    /** @param string|array<mixed> $value */
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

    /** @param string|array<mixed> $value */
    private function resolveChecked(string $slug, string|array $value): string
    {
        $isChecked = match (true) {
            is_array($value) => in_array($slug, $value),
            $value === '-1'  => false,
            default          => in_array($slug, (array) $this->default_value),
        };

        return $isChecked ? 'checked="checked"' : '';
    }

    /**
     * @param string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        return empty($value) ? '-1' : $value;
    }
}
