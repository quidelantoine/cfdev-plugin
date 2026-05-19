<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Support\Str;

class UserCheckboxes extends Field
{
    public bool $supports_bundle = true;

    /** @var array<string> */
    public array $css_classes = ['cfdev-input'];
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

        $this->default_value = (array) $this->default_value;
        $this->users         = get_users($this->args);
        $this->after        .= '[]';
    }

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        if (empty($this->users)) {
            return $this->outputExplanation();
        }

        $html = '';
        foreach ($this->users as $user) {
            $html .= $this->buildCheckbox($user, $value);
        }

        return sprintf(
            '<div %s class="cfdev-checkboxes-wrap">%s</div>%s',
            $this->outputId(),
            $html,
            $this->outputExplanation()
        );
    }

    /** @param string|array<mixed> $value */
    private function buildCheckbox(object $user, string|array $value): string
    {
        /** @var \WP_User $user */
        $inputId = $this->id . $this->after_id . '_' . Str::uglify($user->display_name);

        $input = sprintf(
            '<input type="checkbox" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            $user->ID,
            $this->resolveChecked($user->ID, $value)
        );

        $label = sprintf(
            '<label for="%s">%s</label>',
            $inputId,
            htmlspecialchars($user->display_name, ENT_QUOTES, 'UTF-8')
        );

        return $input . ' ' . $label . '<br />';
    }

    /** @param string|array<mixed> $value */
    private function resolveChecked(int $id, string|array $value): string
    {
        $isChecked = match (true) {
            is_array($value) => in_array($id, $value),
            $value === '-1'  => false,
            default          => in_array($id, (array) $this->default_value),
        };

        return $isChecked ? 'checked="checked"' : '';
    }

    /**
     * @param  string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        return empty($value) ? '-1' : $value;
    }
}