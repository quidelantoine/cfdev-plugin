<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

// A tester +++ 
class UserSelect extends Field
{
    public bool $supports_repeatable   = true;
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;

    public string $dropdown = '';
    public mixed $value = null;

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            array(
                'orderby'       => 'ID',
                'class'         => '',
            ),
            $this->args
        );

        $this->args['class']    .= ' cfdev-input cfdev-select cfdev-user-select';
        $this->args['echo']     = 0;
    }

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $this->args['name']     = 'cfdev' . $this->pre . '[' . $this->id . ']' . $this->after . ( $this->repeatable ? '[]' : '' );
        $this->args['id']       = $this->id . $this->after_id;
        $this->args['selected'] = ( ! empty($value) ? $value : $this->default_value );
        // @phpstan-ignore-next-line argument.type — args built dynamically, shape verified at runtime
        $this->dropdown         = (string) wp_dropdown_users($this->args);

        $output = $this->dropdown;

        $output .= $this->outputExplanation();

        return $output;
    }
}
