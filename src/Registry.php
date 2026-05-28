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
