<?php

namespace Weblitzer\CFDev\Cache;

use Weblitzer\CFDev\Registry;

/**
 * Registry-driven cache manager.
 *
 * Generates one .tmp file per object (post, term, user).
 * File content: all CFDev field groups for that object, with field values
 * resolved by type (images expanded, galleries expanded, links decoded, etc.).
 *
 * Auto-invalidates on save_post, edited_term, profile_update.
 *
 * Usage:
 *   $cache = new CacheManager();
 *   $data  = $cache->post(42);          // all groups for post 42
 *   $group = $cache->post(42)['livre_details'] ?? [];
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class CacheManager
{
    public const TTL = 86400; // 24 h

    private CacheStore $store;
    private CacheResolver $resolver;

    public function __construct()
    {
        $this->store    = new CacheStore();
        $this->resolver = new CacheResolver();
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function register(): void
    {
        add_action('save_post', fn(int $id)                              => $this->invalidatePost($id));
        add_action('edited_term', fn(int $id, int $tt, string $tax)        => $this->invalidateTerm($id, $tax), 10, 3);
        add_action('delete_term', fn(int $id, int $tt, string $tax)        => $this->invalidateTerm($id, $tax), 10, 3);
        add_action('profile_update', fn(int $id)                             => $this->invalidateUser($id));
    }

    // -------------------------------------------------------------------------
    // Public getters
    // -------------------------------------------------------------------------

    /**
     * Returns all cached field groups for a post.
     * Generates the cache file if missing or stale.
     *
     * @return array<string, array<string, mixed>>
     */
    public function post(int $post_id, bool $force = false): array
    {
        $key = 'post_' . $post_id;
        return $this->resolve($key, fn() => $this->generatePost($post_id), $force);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function term(int $term_id, string $taxonomy, bool $force = false): array
    {
        $key = 'term_' . $taxonomy . '_' . $term_id;
        return $this->resolve($key, fn() => $this->generateTerm($term_id, $taxonomy), $force);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function user(int $user_id, bool $force = false): array
    {
        $key = 'user_' . $user_id;
        return $this->resolve($key, fn() => $this->generateUser($user_id), $force);
    }

    // -------------------------------------------------------------------------
    // Invalidation
    // -------------------------------------------------------------------------

    public function invalidatePost(int $post_id): void
    {
        $this->store->delete('post_' . $post_id);
    }

    public function invalidateTerm(int $term_id, string $taxonomy): void
    {
        $this->store->delete('term_' . $taxonomy . '_' . $term_id);
    }

    public function invalidateUser(int $user_id): void
    {
        $this->store->delete('user_' . $user_id);
    }

    public function invalidateAll(): int
    {
        return $this->store->deleteAll();
    }

    // -------------------------------------------------------------------------
    // Store access (for admin page)
    // -------------------------------------------------------------------------

    public function store(): CacheStore
    {
        return $this->store;
    }

    // -------------------------------------------------------------------------
    // Generators
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    private function generatePost(int $post_id): array
    {
        $post_type = get_post_type($post_id);
        $groups    = [];

        foreach (Registry::all() as $entry) {
            if ($entry['meta_type'] !== 'post') {
                continue;
            }
            if (! in_array($post_type, $entry['targets'], true)) {
                continue;
            }
            $groups[$entry['id']] = $this->resolveEntry($entry, $post_id, 'post');
        }

        return ['post_id' => $post_id, 'generated_at' => time(), 'groups' => $groups];
    }

    /** @return array<string, mixed> */
    private function generateTerm(int $term_id, string $taxonomy): array
    {
        $groups = [];

        foreach (Registry::all() as $entry) {
            if ($entry['meta_type'] !== 'term') {
                continue;
            }
            if (! in_array($taxonomy, $entry['targets'], true)) {
                continue;
            }
            $groups[$entry['id']] = $this->resolveEntry($entry, $term_id, 'term');
        }

        return ['term_id' => $term_id, 'taxonomy' => $taxonomy, 'generated_at' => time(), 'groups' => $groups];
    }

    /** @return array<string, mixed> */
    private function generateUser(int $user_id): array
    {
        $groups = [];

        foreach (Registry::all() as $entry) {
            if ($entry['meta_type'] !== 'user') {
                continue;
            }
            $groups[$entry['id']] = $this->resolveEntry($entry, $user_id, 'user');
        }

        return ['user_id' => $user_id, 'generated_at' => time(), 'groups' => $groups];
    }

    // -------------------------------------------------------------------------
    // Entry resolution
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $entry  Registry entry
     * @return array<string, mixed>
     */
    private function resolveEntry(array $entry, int $object_id, string $meta_type): array
    {
        $data = [];

        // Flat fields
        foreach ($entry['fields'] as $field_id => $field) {
            $raw              = $this->raw($meta_type, $object_id, $field_id);
            $data[$field_id]  = $this->resolver->field($field['type'], $raw);
        }

        // Bundles
        foreach ($entry['bundles'] as $bundle_id => $bundle) {
            $raw           = $this->raw($meta_type, $object_id, $bundle_id);
            $data[$bundle_id] = $this->resolver->bundle($bundle['fields'], $raw);
        }

        return $data;
    }

    private function raw(string $meta_type, int $object_id, string $key): mixed
    {
        return match ($meta_type) {
            'user'  => get_user_meta($object_id, $key, true),
            'term'  => get_term_meta($object_id, $key, true),
            default => get_post_meta($object_id, $key, true),
        };
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function isEnabled(): bool
    {
        return (bool) get_option(\Weblitzer\CFDev\Admin\SettingsPage::OPTION_CACHE, false);
    }

    /**
     * @param  callable(): array<string, mixed> $generator
     * @return array<string, mixed>
     */
    private function resolve(string $key, callable $generator, bool $force): array
    {
        $enabled = $this->isEnabled();

        if ($enabled && ! $force && $this->store->exists($key) && $this->store->age($key) < self::TTL) {
            return $this->store->read($key) ?? [];
        }

        $data = $generator();

        if ($enabled) {
            $this->store->write($key, $data);
        }

        return $data;
    }
}
