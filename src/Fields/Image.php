<?php

namespace CFDev\Fields;

use CFDev\Field;

class Image extends Field
{
    public bool $supports_repeatable   = true;
    public bool $supports_ajax         = true;
    public bool $supports_bundle       = true;

    /** @var array<string> */
    public array $css_classes            = array( 'cfdev-hidden', 'cfdev-input' );

    /** @param string|array<mixed> $value */
    public function outputHtml(string|array $value): string
    {
        $preview_size   = $this->args['preview_size'] ?? null;
        $fallback_size  = $preview_size ?? apply_filters('cfdev_preview_size', 'medium');
        $attachment_url = ! empty($value) ? wp_get_attachment_image_src((int) $value, $fallback_size) : null;
        $image          = $attachment_url ? '<img src="' . esc_url($attachment_url[0]) . '" />' : '';

        return implode('', [
            $this->outputHiddenInput($value),
            $this->outputUploadButton($preview_size), // null si non défini
            ! empty($value) ? sprintf(
                '<a href="#" class="js-cfdev-remove-media cfdev-remove-media">%s</a>',
                esc_html(__('Remove current image', 'cfdev'))
            ) : '',
            '<span class="cfdev-preview">' . $image . '</span>',
            $this->outputExplanation(),
        ]);
    }

    /** @param string|array<mixed> $value */
    private function outputHiddenInput(string|array $value): string
    {
        return sprintf(
            '<input type="hidden" %s %s %s value="%s" />',
            $this->outputName(),
            $this->outputId(),
            $this->outputCssClass(),
            esc_attr(is_string($value) && !empty($value) ? $value : '')
        );
    }

    /** @param string|array<mixed>|null $preview_size */
    private function outputUploadButton(string|array|null $preview_size): string
    {
        $attributes = [
            'id'                    => 'upload-image-button',
            'type'                  => 'button',
            'class'                 => 'button js-cfdev-upload',
            'value'                 => esc_attr(__('Select image', 'cfdev')),
            'data-cfdev-media-type' => 'image',
        ];

        // Uniquement si preview_size est explicitement défini dans $this->args
        if ($preview_size !== null) {
            $attributes['data-cfdev-media-preview-size'] = is_array($preview_size)
                ? esc_attr(wp_json_encode($preview_size) ?: '')
                : esc_attr($preview_size);
        }

        $attrs = implode(' ', array_map(
            fn(string $attr, string $val) => sprintf('%s="%s"', esc_attr($attr), $val),
            array_keys($attributes),
            $attributes
        ));

        return sprintf('<input %s />', $attrs);
    }
}
