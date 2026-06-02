<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Validation\Validator;

class Email extends Field
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

        $attributes = implode(' ', array_filter([
            'type="email"',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            'value="' . esc_attr($content) . '"',
            $this->outputDataAttributes(),
        ]));

        return sprintf('<input %s />%s', $attributes, $this->outputExplanation());
    }

    /**
     * @param string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (is_array($value)) {
            return array_map(fn($v) => sanitize_email(is_string($v) ? $v : ''), $value);
        }

        return sanitize_email($value);
    }

    public function validate(mixed $value): Validator
    {
        $rules = $this->rules;

        // Format check only for scalar values; repeatable arrays are sanitized item-by-item in saveValue
        if (!empty($value) && is_string($value)) {
            $rules[] = new \Weblitzer\CFDev\Validation\Rules\Email();
        }

        return new Validator($value, $rules);
    }
}
