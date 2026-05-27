<?php

namespace Weblitzer\CFDev\Abstracts;

use Weblitzer\CFDev\Field;

abstract class CheckboxesBase extends Field
{
    public bool $supports_bundle = true;

    /** @var array<string> */
    public array $css_classes = ['cfdev-input'];

    protected function initCheckboxes(): void
    {
        $this->default_value = (array) $this->default_value;
        $this->after        .= '[]';
    }

    /** @return array<object> */
    abstract protected function getItems(): array;

    /** @param string|array<mixed> $value */
    abstract protected function buildCheckbox(object $item, string|array $value): string;

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $items = $this->getItems();

        if (empty($items)) {
            return $this->outputExplanation();
        }

        $html = '';
        foreach ($items as $item) {
            $html .= $this->buildCheckbox($item, $value);
        }

        return sprintf(
            '<div %s class="cfdev-checkboxes-wrap">%s</div>%s',
            $this->outputId(),
            $html,
            $this->outputExplanation()
        );
    }

    /** @param string|array<mixed> $value */
    protected function resolveChecked(int $id, string|array $value): string
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
