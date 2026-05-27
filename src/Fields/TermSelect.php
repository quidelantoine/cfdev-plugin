<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Abstracts\WpDropdownSelectBase;

class TermSelect extends WpDropdownSelectBase
{
    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            ['taxonomy' => 'category', 'class' => '', 'hide_empty' => 0],
            $this->args
        );
        $this->args['class'] .= ' cfdev-input cfdev-select cfdev-term-select';
        $this->args['echo']   = 0;
    }

    protected function renderDropdown(): string
    {
        return (string) wp_dropdown_categories($this->args);
    }
}
