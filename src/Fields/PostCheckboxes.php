<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Abstracts\CheckboxesBase;
use Weblitzer\CFDev\Support\Str;

class PostCheckboxes extends CheckboxesBase
{
    /** @var array<\WP_Post> */
    protected array $posts = [];

    /** @param array<mixed> $field */
    public function __construct($field, $parent)
    {
        parent::__construct($field, $parent);

        $this->args = array_merge(
            ['post_type' => 'post', 'posts_per_page' => -1],
            $this->args
        );
        $this->initCheckboxes();
        $this->posts = array_values(array_filter(get_posts($this->args), fn ($p) => $p instanceof \WP_Post));
    }

    /** @return array<object> */
    protected function getItems(): array
    {
        return $this->posts;
    }

    /** @param string|array<mixed> $value */
    protected function buildCheckbox(object $item, string|array $value): string
    {
        /** @var \WP_Post $item */
        $inputId = $this->id . $this->after_id . '_' . Str::uglify($item->post_title);

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
            htmlspecialchars($item->post_title, ENT_QUOTES, 'UTF-8')
        );

        return $input . $label . '<br />';
    }
}
