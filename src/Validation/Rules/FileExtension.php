<?php

namespace CFDev\Validation\Rules;

use CFDev\Contracts\Validatable;

/**
 * Validates the extension of a WordPress attachment by its ID.
 *
 * Example:
 *   new File_Extension(['jpg', 'png', 'webp'])
 */
final class FileExtension implements Validatable
{
    private array $allowed;

    public function __construct(array $extensions)
    {
        $this->allowed = array_map('strtolower', $extensions);
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

        $filepath = get_attached_file($attachment_id);

        if ($filepath === false) {
            return false;
        }

        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        return in_array($ext, $this->allowed, strict: true);
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
            __('This file must be one of the following types: %s.', 'cfdev'),
            implode(', ', $this->allowed)
        );
    }
}
