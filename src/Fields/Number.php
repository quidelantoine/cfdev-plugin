<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Number extends Field
{
    public bool $supports_repeatable = true;
    public bool $supports_bundle     = true;
    public bool $supports_ajax       = true;

    /** @var array<string> */
    public array $css_classes = ['cfdev-input'];

    public function outputHtml(string|array $value): string
    {
        $scalar  = is_string($value) ? $value : '';
        $content = strlen($scalar) > 0 ? $scalar : (is_string($this->default_value) ? $this->default_value : '');

        $attrs = array_filter([
            'type="number"',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            'value="' . esc_attr($content) . '"',
            isset($this->args['min'])  ? 'min="'  . esc_attr((string) $this->args['min'])  . '"' : null,
            isset($this->args['max'])  ? 'max="'  . esc_attr((string) $this->args['max'])  . '"' : null,
            isset($this->args['step']) ? 'step="' . esc_attr((string) $this->args['step']) . '"' : null,
            $this->outputDataAttributes(),
        ]);

        return sprintf('<input %s />%s', implode(' ', $attrs), $this->outputExplanation());
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
