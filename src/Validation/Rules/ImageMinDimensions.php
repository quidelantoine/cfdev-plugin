<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

/**
 * Validates the minimum width and height of a WordPress attachment by its ID.
 *
 * Example:
 *   new Image_Min_Dimensions(800, 600)
 *   new Image_Min_Dimensions(width: 800)  // height only optional
 *   new Image_Min_Dimensions(height: 600) // width only optional
 */
final class ImageMinDimensions implements Validatable
{
    public function __construct(
        private readonly int $width = 0,
        private readonly int $height = 0
    ) {
    }

    public function validate(mixed $value): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

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

        if ($this->width > 0 && (int) $meta['width'] < $this->width) {
            return false;
        }

        if ($this->height > 0 && (int) $meta['height'] < $this->height) {
            return false;
        }

        return true;
    }

    public function getError(): string
    {
        if ($this->width > 0 && $this->height > 0) {
            return sprintf(
                __('This image must be at least %dpx wide and %dpx tall.', 'cfdev'),
                $this->width,
                $this->height
            );
        }

        if ($this->width > 0) {
            return sprintf(
                __('This image must be at least %dpx wide.', 'cfdev'),
                $this->width
            );
        }

        return sprintf(
            __('This image must be at least %dpx tall.', 'cfdev'),
            $this->height
        );
    }
}
