<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Abstracts\WpDropdownSelectBase;

class UserSelect extends WpDropdownSelectBase
{
    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            ['orderby' => 'ID', 'class' => ''],
            $this->args
        );
        $this->args['class'] .= ' cfdev-input cfdev-select cfdev-user-select';
        $this->args['echo']   = 0;
    }

    protected function renderDropdown(): string
    {
        return (string) wp_dropdown_users($this->args);
    }
}
