<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\DateFormatHelper;

class Date extends Field
{
    public bool $supports_ajax = true;
    public bool $supports_bundle = true;

    public array $css_classes = array( 'js-cfdev-datepicker', 'cfdev-datepicker', 'datepicker', 'cfdev-input' );
    public array $data_attributes = array( 'date-format' => null );

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->data_attributes['date-format'] = DateFormatHelper::parse(isset($this->args['date_format']) ? $this->args['date_format'] : 'm/d/Y');
    }

    public function outputHtml(string|array $value): string
    {
        $formatted_value = $this->formatDateValue($value);

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
            return esc_attr($this->default_value);
        }

        $format = isset($this->args['date_format']) ? $this->args['date_format'] : 'm/d/Y';
        return esc_attr(gmdate($format, $value));
    }

    public function validate(mixed $value): \CFDev\Validation\Validator
    {
        $format = $this->args['date_format'] ?? 'm/d/Y';
        $date   = \DateTime::createFromFormat($format, (string) $value);
        return parent::validate($date !== false ? $date->format('Y-m-d') : $value);
    }

    public function saveValue(string|array $value): string|array
    {
        $timestamp = is_string($value) ? strtotime($value) : false;
        return $timestamp !== false ? (string) $timestamp : '';
    }
}
