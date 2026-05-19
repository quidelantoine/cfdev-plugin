<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Heading extends Field
{
    public function __construct(array $field, string|null $parent)
    {
        parent::__construct($field, $parent);

        if (empty($field['id'])) {
            $this->id = uniqid('heading_');
        }
    }

    public function outputHtml(string|array $value): string
    {
        $html = sprintf('<h3 class="cfdev-heading">%s</h3>', esc_html($this->label));

        if (!empty($this->description)) {
            $html .= sprintf('<p class="cfdev-heading-description cfdev-description">%s</p>', wp_kses_post($this->description));
        }

        return $html;
    }

    public function save(int $object_id, string|array $value): int|bool|\WP_Error
    {
        return false;
    }
}
