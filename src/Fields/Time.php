<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Support\DateFormatHelper;

class Time extends Field
{
    public bool $supports_ajax        = true;
    public bool $supports_bundle      = true;
    public bool $supports_repeatable  = true;

    /** @var array<string> */
    public array $css_classes = array( 'js-cfdev-timepicker', 'cfdev-timepicker', 'timepicker', 'cfdev-input' );
    /** @var array<string, mixed> */
    public array $data_attributes = array( 'time-format' => null );

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->data_attributes['time-format'] = DateFormatHelper::parse(isset($this->args['time_format']) ? $this->args['time_format'] : 'H:i');
    }

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $formatted_value = !empty($value) && is_numeric($value) && $value > 0 ?
            esc_attr(gmdate(
                isset($this->args['time_format']) ? $this->args['time_format'] : 'H:i',
                (int) $value
            )) :
            esc_attr(is_string($this->default_value) ? $this->default_value : '');

        return '<input type="text" ' .
            $this->outputName() . ' ' .
            $this->outputId() . ' ' .
            $this->outputCssClass() . ' value="' .
            $formatted_value . '" ' .
            $this->outputDataAttributes() . ' />' .
            $this->outputExplanation();
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
