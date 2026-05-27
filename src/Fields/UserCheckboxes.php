<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Abstracts\CheckboxesBase;
use Weblitzer\CFDev\Support\Str;

class UserCheckboxes extends CheckboxesBase
{
    /** @var array<\WP_User> */
    protected array $users = [];

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            ['orderby' => 'display_name'],
            $this->args
        );
        $this->initCheckboxes();
        $this->users = get_users($this->args);
    }

    /** @return array<object> */
    protected function getItems(): array
    {
        return $this->users;
    }

    /** @param string|array<mixed> $value */
    protected function buildCheckbox(object $item, string|array $value): string
    {
        /** @var \WP_User $item */
        $inputId = $this->id . $this->after_id . '_' . Str::uglify($item->display_name);

        $input = sprintf(
            '<input type="checkbox" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            $item->ID,
            $this->resolveChecked($item->ID, $value)
        );

        $label = sprintf(
            '<label for="%s">%s</label>',
            $inputId,
            htmlspecialchars($item->display_name, ENT_QUOTES, 'UTF-8')
        );

        return $input . ' ' . $label . '<br />';
    }
}
