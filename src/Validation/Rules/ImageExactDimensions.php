<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

/**
 * Validates the exact width and/or height of a WordPress attachment by its ID.
 *
 * Example:
 *   new Image_Exact_Dimensions(1200, 630) // width AND height (ex: og:image)
 *   new Image_Exact_Dimensions(width: 1200) // width only
 *   new Image_Exact_Dimensions(height: 630) // height only
 */
final class ImageExactDimensions implements Validatable
{
    public function __construct(
        private readonly int $width = 0,
        private readonly int $height = 0
    ) {
    }

    public function validate(mixed $value): bool
    {
        $attachment_id = (int) $value;

        if ($attachment_id <= 0) {
            return false;
        }

        if (get_post_type($attachment_id) !== 'attachment') {
            return false;
        }

        $meta = wp_get_attachment_metadata($attachment_id);

        if (empty($meta['width']) || empty($meta['height'])) {
            return false;
        }

        if ($this->width > 0 && (int) $meta['width'] !== $this->width) {
            return false;
        }

        if ($this->height > 0 && (int) $meta['height'] !== $this->height) {
            return false;
        }

        return true;
    }

    public function getError(): string
    {
        if ($this->width > 0 && $this->height > 0) {
            return sprintf(
                __('This image must be exactly %dpx × %dpx.', 'cfdev'),
                $this->width,
                $this->height
            );
        }

        if ($this->width > 0) {
            return sprintf(
                __('This image must be exactly %dpx wide.', 'cfdev'),
                $this->width
            );
        }

        return sprintf(
            __('This image must be exactly %dpx tall.', 'cfdev'),
            $this->height
        );
    }
}
