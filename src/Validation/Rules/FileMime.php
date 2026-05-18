<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

/**
 * Validates the MIME type of a WordPress attachment by its ID.
 *
 * Example:
 *   new File_Mime(['image/jpeg', 'image/png', 'image/webp'])
 */
final readonly class FileMime implements Validatable
{
    public function __construct(
        private array $allowed_mimes
    ) {
    }

    public function validate(mixed $value): bool
    {
        $attachment_id = $this->resolveAttachmentId($value);

        if ($attachment_id <= 0) {
            return false;
        }

        if (get_post_type($attachment_id) !== 'attachment') {
            return false;
        }

        $mime = get_post_mime_type($attachment_id);

        return $mime !== false && in_array($mime, $this->allowed_mimes, strict: true);
    }

    private function resolveAttachmentId(mixed $value): int
    {
        $id = (int) $value;
        if ($id > 0) {
            return $id;
        }
        if (is_string($value) && !empty($value)) {
            $cache_key = 'cfdev_att_' . md5($value);
            $cached    = wp_cache_get($cache_key, 'cfdev');
            if ($cached === false) {
                // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid
                $cached = attachment_url_to_postid($value);
                wp_cache_set($cache_key, $cached, 'cfdev', HOUR_IN_SECONDS);
            }
            return (int) $cached;
        }
        return 0;
    }

    public function getError(): string
    {
        return sprintf(
            __('This file type is not allowed. Accepted types: %s.', 'cfdev'),
            implode(', ', $this->allowed_mimes)
        );
    }
}
