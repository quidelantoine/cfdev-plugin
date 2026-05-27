<?php

namespace Weblitzer\CFDev\Abstracts;

use Weblitzer\CFDev\Field;

abstract class WpDropdownSelectBase extends Field
{
    public bool $supports_repeatable = true;
    public bool $supports_ajax       = true;
    public bool $supports_bundle     = true;

    public string $dropdown = '';

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $this->args['name']     = 'cfdev' . $this->pre . '[' . $this->id . ']' . $this->after . ($this->repeatable ? '[]' : '');
        $this->args['id']       = $this->id . $this->after_id;
        $this->args['selected'] = !empty($value) ? $value : $this->default_value;
        $this->dropdown         = $this->renderDropdown();

        return $this->dropdown . $this->outputExplanation();
    }

    abstract protected function renderDropdown(): string;
}
