<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Range extends Field
{
    public bool $supports_repeatable = true;
    public bool $supports_bundle     = true;
    public bool $supports_ajax       = true;

    /** @var array<string> */
    public array $css_classes = ['cfdev-input', 'cfdev-range', 'js-cfdev-range'];

    public function outputHtml(string|array $value): string
    {
        $scalar  = is_string($value) ? $value : '';
        $default = (is_string($this->default_value) && strlen($this->default_value) > 0) ? $this->default_value : '0';
        $content = strlen($scalar) > 0 ? $scalar : $default;

        $min  = isset($this->args['min'])  ? esc_attr((string) $this->args['min'])  : '0';
        $max  = isset($this->args['max'])  ? esc_attr((string) $this->args['max'])  : '100';
        $step = isset($this->args['step']) ? esc_attr((string) $this->args['step']) : '1';

        $attrs = implode(' ', array_filter([
            'type="range"',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            'value="' . esc_attr($content) . '"',
            'min="'   . $min  . '"',
            'max="'   . $max  . '"',
            'step="'  . $step . '"',
            $this->outputDataAttributes(),
        ]));

        return sprintf(
            '<div class="cfdev-range-wrap"><input %s /><output class="cfdev-range-output js-cfdev-range-output">%s</output></div>%s',
            $attrs,
            esc_html($content),
            $this->outputExplanation()
        );
    }

    /**
     * @param string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (is_array($value)) {
            return array_map(fn($v) => is_numeric($v) ? (string) $v : '', $value);
        }

        return is_numeric($value) ? (string) $value : '';
    }
}
