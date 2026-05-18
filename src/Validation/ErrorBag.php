<?php

namespace CFDev\Validation;

/**
 * Stores and retrieves per-field validation errors across the POST→redirect→GET cycle.
 *
 * Errors are persisted in a short-lived transient (60 s) keyed by meta type,
 * current editor user, and object id.  Call ::push() on save, ::load() in the
 * meta-box callback to move errors into the in-memory runtime, then ::forField()
 * to query individual fields during rendering.
 */
final class ErrorBag
{
    /** @var array<string, array{label: string, errors: array<string>}>|null */
    private static ?array $runtime = null;

    private const PREFIX = 'cfdev_errors_';
    private const TTL    = MINUTE_IN_SECONDS;

    // -------------------------------------------------------------------------
    // Transient key
    // -------------------------------------------------------------------------

    private static function key(string $meta_type, int $object_id): string
    {
        return self::PREFIX . $meta_type . '_' . get_current_user_id() . '_' . $object_id;
    }

    // -------------------------------------------------------------------------
    // Write (called on save)
    // -------------------------------------------------------------------------

    /**
     * Merges $errors into the transient so multiple meta boxes accumulate.
     *
     * @param  array<string, array{label: string, errors: string[]}> $errors
     */
    public static function push(string $meta_type, int $object_id, array $errors): void
    {
        $key      = self::key($meta_type, $object_id);
        $existing = get_transient($key) ?: [];
        set_transient($key, array_merge($existing, $errors), self::TTL);
    }

    // -------------------------------------------------------------------------
    // Read (called during rendering)
    // -------------------------------------------------------------------------

    /**
     * Reads the transient into the in-memory runtime and deletes it.
     * Safe to call multiple times — only the first call hits the DB.
     */
    public static function load(string $meta_type, int $object_id): void
    {
        if (self::$runtime !== null) {
            return;
        }
        $key           = self::key($meta_type, $object_id);
        self::$runtime = get_transient($key) ?: [];
        delete_transient($key);
    }

    /**
     * Reads the transient WITHOUT deleting or loading into runtime.
     * Used by admin_notices hooks that fire before the meta-box callback.
     *
     * @return array<string, array{label: string, errors: string[]}>
     */
    public static function peek(string $meta_type, int $object_id): array
    {
        return get_transient(self::key($meta_type, $object_id)) ?: [];
    }

    // -------------------------------------------------------------------------
    // Query runtime (called per-field during rendering)
    // -------------------------------------------------------------------------

    /**
     * Returns error messages for a field key from the in-memory runtime.
     *
     * Regular fields  : key = $field->id
     * Bundle fields   : key = $bundle->id . '.' . $row_index . '.' . $field->id
     *
     * @return string[]
     */
    public static function forField(string $field_key): array
    {
        return self::$runtime[$field_key]['errors'] ?? [];
    }

    /**
     * Returns all errors currently in the runtime.
     *
     * @return array<string, array{label: string, errors: string[]}>
     */
    public static function all(): array
    {
        return self::$runtime ?? [];
    }
}
