<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Support\DateFormatHelper;

class Date extends Field
{
    public bool $supports_ajax        = true;
    public bool $supports_bundle      = true;
    public bool $supports_repeatable  = true;

    /** @var array<string> */
    public array $css_classes = array( 'js-cfdev-datepicker', 'cfdev-datepicker', 'datepicker', 'cfdev-input' );
    /** @var array<string, mixed> */
    public array $data_attributes = array( 'date-format' => null );

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->data_attributes['date-format'] = DateFormatHelper::parse(isset($this->args['date_format']) ? $this->args['date_format'] : 'm/d/Y');
    }

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $formatted_value = $this->formatDateValue(is_string($value) ? $value : '');

        return sprintf(
            '<input type="text" %s %s %s value="%s" %s />%s',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            $formatted_value,
            $this->outputDataAttributes(),
            $this->outputExplanation()
        );
    }

    private function formatDateValue(int|string $value): string
    {
        if (empty($value) || !is_numeric($value)) {
            return esc_attr(is_string($this->default_value) ? $this->default_value : '');
        }

        $format = isset($this->args['date_format']) ? $this->args['date_format'] : 'm/d/Y';
        return esc_attr(gmdate($format, (int) $value));
    }

    public function validate(mixed $value): \Weblitzer\CFDev\Validation\Validator
    {
        if (is_array($value)) {
            return parent::validate($value);
        }
        $format = $this->args['date_format'] ?? 'm/d/Y';
        $date   = \DateTime::createFromFormat($format, (string) $value);
        return parent::validate($date !== false ? $date->format('Y-m-d') : $value);
    }

    /**
     * @param  string|array<mixed>  $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (is_array($value)) {
            return array_map(fn($v) => $this->saveValue(is_string($v) ? $v : ''), $value);
        }
        $timestamp = strtotime($value);
        return $timestamp !== false ? (string) $timestamp : '';
    }
}
