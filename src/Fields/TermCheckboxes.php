<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Abstracts\CheckboxesBase;
use Weblitzer\CFDev\Support\Str;

class TermCheckboxes extends CheckboxesBase
{
    public mixed $terms = null;

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            ['taxonomy' => 'category'],
            $this->args
        );
        $this->initCheckboxes();
        $this->terms = get_terms($this->args['taxonomy'], $this->args);
    }

    /** @return array<object> */
    protected function getItems(): array
    {
        return is_array($this->terms) ? $this->terms : [];
    }

    /** @param string|array<mixed> $value */
    protected function buildCheckbox(object $item, string|array $value): string
    {
        /** @var \WP_Term $item */
        $termId   = $item->term_id;
        $termName = $item->name;
        $inputId  = $this->id . $this->after_id . '_' . Str::uglify($termName);

        $input = sprintf(
            '<input type="checkbox" %s %s %s value="%s" %s/>',
            $this->outputName(),
            $this->outputId($inputId),
            $this->outputCssClass(),
            htmlspecialchars((string) $termId, ENT_QUOTES, 'UTF-8'),
            $this->resolveChecked($termId, $value)
        );

        $label = sprintf('<label for="%s">%s</label>', $inputId, htmlspecialchars($termName, ENT_QUOTES, 'UTF-8'));

        return $input . ' ' . $label . '<br />';
    }
}
