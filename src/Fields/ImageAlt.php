<?php

namespace Weblitzer\CFDev\Fields;

use Weblitzer\CFDev\Field;

/**
 * Image + custom alt text in a single compound field.
 *
 * Stores {'id': int, 'alt': string} serialised as JSON.
 * The alt overrides the attachment's native alt on the front-end.
 */
class ImageAlt extends Field
{
    public bool $supports_repeatable = false;
    public bool $supports_bundle     = true;
    public bool $supports_ajax       = false;

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $data  = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);
        $id    = (int) ($data['id'] ?? 0);
        $alt   = (string) ($data['alt'] ?? '');
        $base  = "cfdev{$this->pre}[{$this->id}]";

        $preview_size  = $this->args['preview_size'] ?? null;
        $fallback_size = $preview_size ?? apply_filters('cfdev_preview_size', 'medium');
        $src           = $id > 0 ? wp_get_attachment_image_src($id, $fallback_size) : null;
        $img           = $src ? '<img src="' . esc_url($src[0]) . '" alt="" />' : '';
        $remove        = $id > 0
            ? '<button type="button" class="js-cfdev-remove-media cfdev-remove-media">'
                . esc_html(__('Remove current image', 'cfdev')) . '</button>'
            : '';

        return sprintf(
            '<div class="cfdev-field cfdev-image-alt-wrap" %s>'
                . '<input type="hidden" name="%s[id]" class="cfdev-hidden cfdev-input" value="%s" />'
                . '<input id="upload-image-button" type="button" class="button js-cfdev-upload"'
                    . ' data-cfdev-media-type="image" value="%s" />'
                . '%s'
                . '<span class="cfdev-preview">%s</span>'
                . '<input type="text" name="%s[alt]" class="cfdev-input cfdev-image-alt-text"'
                    . ' value="%s" placeholder="%s" />'
            . '</div>%s',
            $this->outputId(),
            esc_attr($base),
            $id > 0 ? $id : '',
            esc_attr(__('Select image', 'cfdev')),
            $remove,
            $img,
            esc_attr($base),
            esc_attr($alt),
            esc_attr(__('Alternative text for the image', 'cfdev')),
            $this->outputExplanation()
        );
    }

    /**
     * @param  string|array<mixed> $value
     * @return string|array<mixed>
     */
    public function saveValue(string|array $value): string|array
    {
        if (! is_array($value)) {
            return ['id' => 0, 'alt' => ''];
        }

        return [
            'id'  => max(0, (int) ($value['id'] ?? 0)),
            'alt' => sanitize_text_field((string) ($value['alt'] ?? '')),
        ];
    }
}
