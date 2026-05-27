<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

class Gallery extends Field
{
    public bool $supports_repeatable = false;
    public bool $supports_bundle     = false;
    public bool $supports_ajax       = false;

    public function outputHtml(string|array $value): string
    {
        $ids   = is_array($value) ? array_filter(array_map('intval', $value)) : [];
        $items = '';

        foreach ($ids as $id) {
            $src = wp_get_attachment_image_src($id, 'thumbnail');
            if ($src) {
                $items .= $this->outputItem($id, $src[0]);
            }
        }

        return sprintf(
            '<div class="cfdev-gallery-wrap js-cfdev-gallery" data-field-name="%s" %s>'
                . '<div class="cfdev-gallery-items js-cfdev-gallery-items cfdev-sortable">%s</div>'
                . '<input type="button" class="button js-cfdev-gallery-add" value="%s" />'
            . '</div>%s',
            esc_attr(sprintf('cfdev[%s][]', $this->id)),
            $this->outputId(),
            $items,
            esc_attr(__('Add images', 'cfdev')),
            $this->outputExplanation()
        );
    }

    private function outputItem(int $id, string $url): string
    {
        return sprintf(
            '<div class="cfdev-gallery-item js-cfdev-gallery-item">'
                . '<input type="hidden" name="cfdev[%s][]" value="%d" />'
                . '<img src="%s" alt="" />'
                . '<button type="button" class="cfdev-gallery-remove js-cfdev-gallery-remove" aria-label="%s">&times;</button>'
            . '</div>',
            esc_attr($this->id),
            $id,
            esc_url($url),
            esc_attr(__('Remove image', 'cfdev'))
        );
    }

    /**
     * @param  string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('absint', $value)));
    }
}
