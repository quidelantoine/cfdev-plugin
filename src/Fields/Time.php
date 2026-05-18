<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\DateFormatHelper;

class Time extends Field
{
    public bool $supports_ajax = true;
    public bool $supports_bundle = true;

    public array $css_classes = array( 'js-cfdev-timepicker', 'cfdev-timepicker', 'timepicker', 'cfdev-input' );
    public array $data_attributes = array( 'time-format' => null );

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->data_attributes['time-format'] = DateFormatHelper::parse(isset($this->args['time_format']) ? $this->args['time_format'] : 'H:i');
    }

    public function outputHtml(string|array $value): string
    {
        $formatted_value = !empty($value) && is_numeric($value) && $value > 0 ?
            esc_attr(gmdate(
                isset($this->args['time_format']) ? $this->args['time_format'] : 'H:i',
                (int) $value
            )) :
            esc_attr($this->default_value);

        return '<input type="text" ' .
            $this->outputName() . ' ' .
            $this->outputId() . ' ' .
            $this->outputCssClass() . ' value="' .
            $formatted_value . '" ' .
            $this->outputDataAttributes() . ' />' .
            $this->outputExplanation();
    }

    public function saveValue(string|array $value): string|array
    {
        $timestamp = is_string($value) ? strtotime($value) : false;
        return $timestamp !== false ? (string) $timestamp : '';
    }
}
