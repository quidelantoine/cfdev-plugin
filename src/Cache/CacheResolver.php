<?php

namespace Weblitzer\CFDev\Cache;

/**
 * Resolves raw meta values into enriched data structures based on field type.
 *
 * image    → { id, alt, full, thumbnail, medium, … }
 * gallery  → [ { id, alt, full, … }, … ]
 * file     → { id, url, filename }
 * link     → { url, text, target }
 * bundle   → [ { field_id: resolved_value, … }, … ]
 * others   → raw value
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class CacheResolver
{
    /**
     * Resolve a single flat field value.
     *
     * @param  string $type  CFDev field type (e.g. 'image', 'text', 'gallery')
     * @param  mixed  $raw   Value from get_post_meta / get_term_meta / get_user_meta
     * @return mixed
     */
    public function field(string $type, mixed $raw): mixed
    {
        if ($raw === '' || $raw === null || $raw === false) {
            return $raw;
        }

        return match ($type) {
            'image'                         => $this->image($raw),
            'gallery'                       => $this->gallery($raw),
            'file'                          => $this->file($raw),
            'link'                          => $this->link($raw),
            'checkboxes', 'multi_select',
            'post_checkboxes', 'term_checkboxes',
            'user_checkboxes'               => $this->multiValue($raw),
            default                         => $raw,
        };
    }

    /**
     * Resolve a bundle: array of rows, each row has fields resolved by type.
     *
     * @param  array<string, array{type: string, label: string, required: bool}> $field_defs
     * @param  mixed  $raw  Stored bundle value (array or serialized)
     * @return array<int, array<string, mixed>>
     */
    public function bundle(array $field_defs, mixed $raw): array
    {
        $rows = $this->toArray($raw);
        if (empty($rows)) {
            return [];
        }

        return array_values(array_map(function (mixed $row) use ($field_defs): array {
            $resolved = [];
            foreach ($field_defs as $field_id => $field) {
                $resolved[$field_id] = $this->field($field['type'], $row[$field_id] ?? '');
            }
            return $resolved;
        }, (array) $rows));
    }

    // -------------------------------------------------------------------------
    // Type resolvers
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function image(mixed $raw): array
    {
        $id = (int) $raw;
        if ($id <= 0) {
            return [];
        }

        $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        if ($alt === '') {
            // Fallback on the attachment title if no explicit alt is set
            $post = get_post($id);
            $alt  = $post ? $post->post_title : '';
        }

        $url    = (string) wp_get_attachment_url($id);
        $result = ['id' => $id, 'alt' => $alt, 'full' => $url];

        $meta = wp_get_attachment_metadata($id);
        foreach (array_keys($meta['sizes'] ?? []) as $size) {
            $src = wp_get_attachment_image_src($id, $size);
            if ($src) {
                $result[$size] = $src[0];
            }
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private function gallery(mixed $raw): array
    {
        $ids = $this->toArray($raw);
        return array_values(array_filter(array_map(
            fn($id) => $this->image($id),
            array_filter(array_map('intval', $ids))
        )));
    }

    /** @return array<string, mixed> */
    private function file(mixed $raw): array
    {
        $id = (int) $raw;
        if ($id <= 0) {
            return [];
        }
        return [
            'id'       => $id,
            'url'      => wp_get_attachment_url($id),
            'filename' => basename((string) get_attached_file($id)),
        ];
    }

    /** @return array<string, string> */
    private function link(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : ['url' => (string) $raw, 'text' => '', 'target' => ''];
    }

    /** @return array<int, mixed> */
    private function multiValue(mixed $raw): array
    {
        return is_array($raw) ? array_values($raw) : $this->toArray($raw);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return array<mixed> */
    private function toArray(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            // Legacy: PHP serialized (e.g. from older data)
            if (preg_match('/^[aO]:\d+:\{/', $raw)) {
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
                $unserialized = unserialize($raw);
                return is_array($unserialized) ? $unserialized : [];
            }
        }
        return [];
    }
}
