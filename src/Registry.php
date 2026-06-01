<?php

namespace Weblitzer\CFDev;

use Weblitzer\CFDev\Fields\Accordion;
use Weblitzer\CFDev\Fields\Bundle;
use Weblitzer\CFDev\Fields\Heading;
use Weblitzer\CFDev\Fields\Tabs;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;

/**
 * Central registry of all declared meta boxes and their fields.
 *
 * Populated automatically when MetaBox, UserMeta, or TermMeta are instantiated.
 * Stores live references so conditions set after construction are always visible.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
class Registry
{
    /** @var array<int, Meta> */
    private static array $metas = [];

    /**
     * Registers a Meta instance. Called automatically from each subclass constructor.
     */
    public static function register(Meta $meta): void
    {
        self::$metas[] = $meta;
    }

    /**
     * Returns all registered entries as plain arrays.
     * Built lazily so conditions set after construction are included.
     *
     * Each entry:
     *   id, title, meta_type, targets, layout, conditions, source, fields
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return array_values(array_map([self::class, 'toEntry'], self::$metas));
    }

    /**
     * Returns MetaBox IDs registered more than once for the same post type.
     *
     * Example: addMetaBox('product_info', ...) called twice for post type 'product'
     * → ['product_info' => ['product']]
     *
     * WordPress silently keeps only the last registration, making the earlier one invisible.
     *
     * @return array<string, array<string>>  meta_box_id => post types where it is duplicated
     */
    public static function duplicateMetaBoxIds(): array
    {
        $map = [];

        foreach (self::$metas as $meta) {
            if (! ($meta instanceof MetaBox)) {
                continue;
            }
            foreach ($meta->post_types as $pt) {
                $key         = $meta->id . ':' . $pt;
                $map[$key]   = ($map[$key] ?? 0) + 1;
            }
        }

        $dups = [];
        foreach ($map as $key => $count) {
            if ($count > 1) {
                [$box_id, $pt] = explode(':', $key, 2);
                $dups[$box_id][] = $pt;
            }
        }

        return $dups;
    }

    /**
     * WordPress meta keys that CFDev should never use as field IDs because WordPress
     * writes to them internally.  Naming a custom field with one of these keys would
     * silently corrupt core features (featured image, page templates, trashed posts…).
     *
     * @var array<string>
     */
    private const WP_RESERVED_META_KEYS = [
        // Attachments / media
        '_thumbnail_id',
        '_wp_attached_file',
        '_wp_attachment_metadata',
        '_wp_attachment_image_alt',
        // Concurrent editing
        '_edit_lock',
        '_edit_last',
        // Page templates
        '_wp_page_template',
        // URL / slug management
        '_wp_old_slug',
        '_wp_old_date',
        '_wp_desired_post_slug',
        // Trash
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
        // Cron / pings
        '_pingme',
        '_encloseme',
        // Nav menus
        '_menu_item_type',
        '_menu_item_menu_item_parent',
        '_menu_item_object_id',
        '_menu_item_object',
        '_menu_item_target',
        '_menu_item_classes',
        '_menu_item_xfn',
        '_menu_item_url',
        // User meta — sessions & capabilities
        'session_tokens',
        'wp_capabilities',
        'wp_user_level',
        'dismissed_wp_pointers',
        'show_welcome_panel',
        'rich_editing',
        'syntax_highlighting',
        'comment_shortcuts',
        'admin_color',
        'show_admin_bar_front',
    ];

    /**
     * Returns field IDs that collide with WordPress reserved meta keys.
     *
     * Using a reserved key as a CFDev field ID would silently overwrite a WordPress
     * core value (featured image, page template, user sessions, etc.).
     *
     * Each entry: field_id => ['meta_box_id', …]
     *
     * @return array<string, array<string>>  field_id => meta box IDs where it appears
     */
    public static function reservedFieldIds(): array
    {
        $found = [];

        foreach (self::$metas as $meta) {
            foreach (array_keys($meta->fields) as $field_id) {
                if (in_array($field_id, self::WP_RESERVED_META_KEYS, true)) {
                    $found[$field_id][] = $meta->id;
                }
            }
        }

        return $found;
    }

    /**
     * Returns duplicate field IDs declared more than once inside the same meta object.
     *
     * A field declared twice in the same meta box (same ID in two tabs, or copy-paste error)
     * causes the first definition to be silently overwritten by the second during build().
     *
     * Each entry: ['meta_box' => string, 'field' => string, 'context' => string, 'message' => string]
     *
     * @return array<int, array{meta_box: string, field: string, context: string, message: string}>
     */
    public static function intraBoxDuplicates(): array
    {
        $all = [];
        foreach (self::$metas as $meta) {
            foreach ($meta->buildWarnings as $w) {
                $all[] = [
                    'meta_box' => $meta->id,
                    'field'    => $w['field'],
                    'context'  => $w['context'],
                    'message'  => $w['message'],
                ];
            }
        }
        return $all;
    }

    /**
     * Returns bundle IDs registered more than once for the same post type.
     *
     * Two meta boxes sharing the same bundle ID write to the same meta key in the database,
     * causing each save to overwrite the other's data.
     *
     * Each entry: bundle_id => post types where the collision occurs
     *
     * @return array<string, array<string>>
     */
    public static function duplicateBundleIds(): array
    {
        $map = [];

        foreach (self::$metas as $meta) {
            if (! ($meta instanceof MetaBox)) {
                continue;
            }
            foreach (
                array_keys($meta->data instanceof \Weblitzer\CFDev\Fields\Bundle
                ? [$meta->data->id => true]
                : self::resolveBundleIds($meta)) as $bundle_id
            ) {
                foreach ($meta->post_types as $pt) {
                    $key       = $bundle_id . ':' . $pt;
                    $map[$key] = ($map[$key] ?? 0) + 1;
                }
            }
        }

        $dups = [];
        foreach ($map as $key => $count) {
            if ($count > 1) {
                [$bundle_id, $pt] = explode(':', $key, 2);
                $dups[$bundle_id][] = $pt;
            }
        }

        return $dups;
    }

    /**
     * Returns all bundle IDs declared in a meta object (flat bundle, tabs, accordion).
     *
     * @return array<string, true>
     */
    private static function resolveBundleIds(Meta $meta): array
    {
        $ids = [];

        if ($meta->data instanceof \Weblitzer\CFDev\Fields\Bundle) {
            $ids[$meta->data->id] = true;
        } elseif ($meta->data instanceof Tabs || $meta->data instanceof Accordion) {
            foreach ($meta->data->tabs as $tab) {
                if ($tab->fields instanceof \Weblitzer\CFDev\Fields\Bundle) {
                    $ids[$tab->fields->id] = true;
                }
            }
        }

        return $ids;
    }

    /**
     * Returns field IDs that appear more than once on the same meta_type + target.
     *
     * Example: two meta boxes both declare `hero_image` on post type `page`
     * → ['hero_image' => ['box_a', 'box_b']]
     *
     * @return array<string, array<string>>
     */
    public static function duplicates(): array
    {
        $map = [];

        foreach (self::all() as $entry) {
            foreach (array_keys($entry['fields']) as $field_id) {
                foreach ($entry['targets'] as $target) {
                    $key         = $entry['meta_type'] . ':' . $target . ':' . $field_id;
                    $map[$key][] = $entry['id'];
                }
            }
        }

        $dups = [];
        foreach ($map as $key => $ids) {
            if (count($ids) > 1) {
                [, , $field_id] = explode(':', $key, 3);
                $dups[$field_id] = array_values(array_unique($ids));
            }
        }

        return $dups;
    }

    /**
     * Returns all fields flagged with `rest: true`, grouped by meta box.
     *
     * Each entry: { id, title, meta_type, targets, fields }
     * where fields is a map of field_id => { label, type, rest_type }
     *
     * @return array<int, array<string, mixed>>
     */
    public static function restFields(): array
    {
        $result = [];
        foreach (self::$metas as $meta) {
            $restFields = [];

            foreach ($meta->fields as $field_id => $field) {
                if ($field->rest && ! $field->in_bundle && ! ($field instanceof Heading)) {
                    $restFields[$field_id] = [
                        'label'     => $field->label,
                        'type'      => $field->type,
                        'rest_type' => $field->restType(),
                    ];
                }
            }

            foreach ($meta->doRestBundles() as $bundle) {
                $restFields[$bundle->id] = [
                    'label'     => $bundle->id,
                    'type'      => 'bundle',
                    'rest_type' => 'string',
                ];
            }

            $sections = self::restSections($meta);
            $bundles  = self::restBundles($meta);

            if (empty($restFields) && empty($sections) && empty($bundles)) {
                continue;
            }
            [$meta_type, $targets] = self::resolveTypeAndTargets($meta);
            $result[] = [
                'id'         => $meta->id,
                'title'      => $meta->title ?: $meta->id,
                'meta_type'  => $meta_type,
                'targets'    => $targets,
                'layout'     => self::resolveLayout($meta),
                'fields'     => $restFields,
                'bundles'    => $bundles,
                'sections'   => $sections,
                'conditions' => self::resolveConditions($meta),
            ];
        }
        return $result;
    }

    /**
     * Returns true if at least one Meta is registered for the given meta_type + target.
     *
     * Pass an empty $target to match any target (used for user meta which has no subtype).
     */
    public static function hasEntriesFor(string $meta_type, string $target = ''): bool
    {
        foreach (self::$metas as $meta) {
            [$type, $targets] = self::resolveTypeAndTargets($meta);
            if ($type !== $meta_type) {
                continue;
            }
            if ($target === '' || in_array($target, $targets, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if a field with the given ID is registered as an ajax field for the given meta_type.
     */
    public static function isAjaxField(string $field_id, string $meta_type): bool
    {
        foreach (self::$metas as $meta) {
            [$type] = self::resolveTypeAndTargets($meta);
            if ($type !== $meta_type) {
                continue;
            }
            if (isset($meta->fields[$field_id]) && $meta->fields[$field_id]->supports_ajax) {
                return true;
            }
        }
        return false;
    }

    /**
     * Clears all registered entries. Use in tests to prevent state bleed.
     */
    public static function reset(): void
    {
        self::$metas = [];
    }

    /** @return array<string, mixed> */
    private static function toEntry(Meta $meta): array
    {
        [$meta_type, $targets] = self::resolveTypeAndTargets($meta);

        $fields = [];
        foreach ($meta->fields as $field_id => $field) {
            if ($field instanceof Heading || $field->in_bundle) {
                continue;
            }
            $fields[$field_id] = self::fieldEntry($field);
        }

        return [
            'id'         => $meta->id,
            'title'      => $meta->title ?: $meta->id,
            'meta_type'  => $meta_type,
            'targets'    => $targets,
            'layout'     => self::resolveLayout($meta),
            'conditions' => self::resolveConditions($meta),
            'source'     => 'unknown',
            'fields'     => $fields,
            'bundles'    => self::resolveBundles($meta),
            'sections'   => self::resolveSections($meta),
        ];
    }

    /**
     * @return array{string, array<string>}
     */
    private static function resolveTypeAndTargets(Meta $meta): array
    {
        if ($meta instanceof MetaBox) {
            return ['post', $meta->post_types];
        }
        if ($meta instanceof UserMeta) {
            return ['user', $meta->locations];
        }
        if ($meta instanceof TermMeta) {
            return ['term', $meta->taxonomies];
        }
        return ['unknown', []];
    }

    private static function resolveLayout(Meta $meta): string
    {
        if ($meta->data instanceof Tabs) {
            return 'tabs';
        }
        if ($meta->data instanceof Accordion) {
            return 'accordion';
        }
        if ($meta->data instanceof Bundle) {
            return 'bundle';
        }
        return 'flat';
    }

    /** @return array<string, mixed> */
    private static function resolveConditions(Meta $meta): array
    {
        $conditions = [];

        if ($meta instanceof MetaBox) {
            if ($meta->only_for_id !== null) {
                $conditions['post_id'] = $meta->only_for_id;
            }
            if ($meta->only_for_template !== null) {
                $conditions['template'] = $meta->only_for_template;
            }
        }

        if ($meta instanceof UserMeta && ! empty($meta->only_for_roles)) {
            $conditions['roles'] = $meta->only_for_roles;
        }

        if ($meta instanceof TermMeta && $meta->only_if_parent !== null) {
            $conditions['parent_id'] = $meta->only_if_parent;
        }

        return $conditions;
    }

    /**
     * Sections for tabs/accordion layouts: one entry per tab/section with its fields.
     * Empty for flat and direct-bundle layouts.
     *
     * Each entry: { title, fields, bundle_id }
     * - fields    : flat fields in the section (empty when the section contains a bundle)
     * - bundle_id : ID of the bundle if the section wraps one, null otherwise
     *
     * @return array<int, array{title: string, fields: array<string, array<string, mixed>>, bundle_id: string|null}>
     */
    private static function resolveSections(Meta $meta): array
    {
        if (! ($meta->data instanceof Tabs) && ! ($meta->data instanceof Accordion)) {
            return [];
        }

        $sections = [];
        foreach ($meta->data->tabs as $tab) {
            if ($tab->fields instanceof Bundle) {
                $sections[] = [
                    'title'     => $tab->title,
                    'fields'    => [],
                    'bundle_id' => $tab->fields->id,
                ];
            } else {
                $fields = [];
                foreach ((array) $tab->fields as $field_id => $field) {
                    if ($field instanceof Heading) {
                        continue;
                    }
                    $fields[$field_id] = self::fieldEntry($field);
                }
                $sections[] = [
                    'title'     => $tab->title,
                    'fields'    => $fields,
                    'bundle_id' => null,
                ];
            }
        }

        return $sections;
    }

    /** @return array<string, array{fields: array<string, array<string, mixed>>}> */
    private static function resolveBundles(Meta $meta): array
    {
        if ($meta->data instanceof Bundle) {
            return [
                $meta->data->id => ['fields' => self::bundleFields($meta->data)],
            ];
        }

        if ($meta->data instanceof Tabs || $meta->data instanceof Accordion) {
            $bundles = [];
            foreach ($meta->data->tabs as $tab) {
                if ($tab->fields instanceof Bundle) {
                    $bundles[$tab->fields->id] = ['fields' => self::bundleFields($tab->fields)];
                }
            }
            return $bundles;
        }

        return [];
    }

    /** @return array<string, array{fields: array<string, array<string, mixed>>}> */
    private static function restBundles(Meta $meta): array
    {
        $result = [];
        foreach ($meta->doRestBundles() as $bundle) {
            $result[$bundle->id] = ['fields' => self::bundleFields($bundle)];
        }
        return $result;
    }

    /**
     * @return array<int, array{title: string, fields: array<string, array<string, mixed>>, bundle_id: string|null}>
     */
    private static function restSections(Meta $meta): array
    {
        if (! ($meta->data instanceof Tabs) && ! ($meta->data instanceof Accordion)) {
            return [];
        }

        $rest_bundle_ids = array_map(fn(Bundle $b) => $b->id, $meta->doRestBundles());
        $sections        = [];

        foreach ($meta->data->tabs as $tab) {
            if ($tab->fields instanceof Bundle) {
                if (in_array($tab->fields->id, $rest_bundle_ids, true)) {
                    $sections[] = [
                        'title'     => $tab->title,
                        'fields'    => [],
                        'bundle_id' => $tab->fields->id,
                    ];
                }
            } else {
                $fields = [];
                foreach ((array) $tab->fields as $field_id => $field) {
                    if ($field instanceof Heading || ! $field->rest) {
                        continue;
                    }
                    $fields[$field_id] = [
                        'label'     => $field->label,
                        'type'      => $field->type,
                        'rest_type' => $field->restType(),
                    ];
                }
                if (! empty($fields)) {
                    $sections[] = [
                        'title'     => $tab->title,
                        'fields'    => $fields,
                        'bundle_id' => null,
                    ];
                }
            }
        }

        return $sections;
    }

    /** @return array<string, array<string, mixed>> */
    private static function bundleFields(Bundle $bundle): array
    {
        $fields = [];
        foreach ($bundle->fields as $id => $field) {
            if ($field instanceof Heading) {
                continue;
            }
            $fields[$id] = self::fieldEntry($field);
        }
        return $fields;
    }

    /** @return array<string, mixed> */
    private static function fieldEntry(\Weblitzer\CFDev\Field $field): array
    {
        $rules = [];
        foreach ($field->getRules() as $rule) {
            if ($rule instanceof \Weblitzer\CFDev\Validation\Rules\Required) {
                continue;
            }
            $rules[] = self::describeRule($rule);
        }

        return [
            'type'     => $field->type,
            'label'    => $field->label,
            'required' => $field->required,
            'rules'    => $rules,
        ];
    }

    private static function describeRule(\Weblitzer\CFDev\Contracts\Validatable $rule): string
    {
        $rc   = new \ReflectionClass($rule);
        $slug = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $rc->getShortName()));
        $ctor = $rc->getConstructor();

        if ($ctor === null || empty($ctor->getParameters())) {
            return $slug;
        }

        $values = [];
        foreach ($ctor->getParameters() as $param) {
            if (! $rc->hasProperty($param->getName())) {
                continue;
            }
            $prop     = $rc->getProperty($param->getName());
            $raw      = $prop->getValue($rule);
            $values[] = is_array($raw) ? implode('|', $raw) : (string) $raw;
        }

        return $values ? $slug . ': ' . implode(', ', $values) : $slug;
    }
}
