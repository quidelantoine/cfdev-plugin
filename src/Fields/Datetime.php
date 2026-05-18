<?php

namespace CFDev\Fields;

use CFDev\Field;
use CFDev\Support\DateFormatHelper;

class Datetime extends Field
{
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;

    public array $css_classes            = array( 'js-cfdev-datetimepicker', 'cfdev-datetimepicker', 'datetimepicker', 'cfdev-input' );
    public array $data_attributes        = array( 'time-format' => null, 'date-format' => null );

    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->data_attributes['date-format'] = DateFormatHelper::parse(isset($this->args['date_format']) ? $this->args['date_format'] : 'm/d/Y');
        $this->data_attributes['time-format'] = DateFormatHelper::parse(isset($this->args['time_format']) ? $this->args['time_format'] : 'H:i');
    }

    public function outputHtml(string|array $value): string
    {
        // 1. Formate la valeur si elle n'est pas vide.
        $formatted_value = !empty($value) ?
            $this->formatDatetime($value) :
            esc_attr($this->default_value);

        // 2. Construit le HTML.
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

    /**
     * Formate un timestamp en date/heure selon les arguments.
     */
    protected function formatDatetime(string|array $value): string
    {
        // 1. Vérifie que $value est un timestamp valide.
        if (!is_numeric($value) || $value <= 0) {
            return esc_attr($this->default_value);
        }

        // 2. Détermine le format.
        $format = 'm/d/Y H:i';
        if (isset($this->args['date_format'], $this->args['time_format'])) {
            $format = trim($this->args['date_format'] . ' ' . $this->args['time_format']);
        }

        // 3. Utilise gmdate() pour éviter les problèmes de fuseau horaire.
        return esc_attr(gmdate($format, (int) $value));
    }

    public function validate(mixed $value): \CFDev\Validation\Validator
    {
        $date_format = $this->args['date_format'] ?? 'm/d/Y';
        $time_format = $this->args['time_format'] ?? 'H:i';
        $date        = \DateTime::createFromFormat(trim($date_format . ' ' . $time_format), (string) $value);
        return parent::validate($date !== false ? $date->format('Y-m-d H:i') : $value);
    }

    public function saveValue(string|array $value): string|array
    {
        $timestamp = is_string($value) ? strtotime($value) : false;
        return $timestamp !== false ? (string) $timestamp : '';
    }
}
