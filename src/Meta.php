<?php

namespace Weblitzer\CFDev;

use Weblitzer\CFDev\Notice;
use Weblitzer\CFDev\Support\RendersFieldRow;
use Weblitzer\CFDev\Support\Str;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * Custom Meta for handling meta data
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */

abstract class Meta
{
    use RendersFieldRow;

    abstract protected function metaType(): string;

    abstract protected function resolveObjectId(): int;

    public string $id = '';
    public string $title = '';
    /** @var callable|array<mixed>|string|null */
    public mixed $callback = null;
    /** @var mixed */
    public mixed $data = null;
    /** @var array<string, \Weblitzer\CFDev\Field> */
    public array $fields = [];
    public string $description = '';

    /**
     * Warnings collected during build() — duplicate field IDs within this meta object.
     * Each entry: ['field' => string, 'context' => string, 'message' => string]
     *
     * @var array<int, array{field: string, context: string, message: string}>
     */
    public array $buildWarnings = [];

    protected static bool $noticeShown = false;

    /**
     * Construct for all meta types, creates title (and description)
     *
     * @param   string|array<string>    $title
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function __construct($title)
    {
        if (is_array($title)) {
            $this->title        = Str::beautify($title[0]);
            $this->description  = $title[1];
        } else {
            $this->title        = Str::beautify($title);
        }
    }

    /**
     * Main callback for meta
     *
     * @param   mixed           $object
     * @param   array           $data
     * @return  void
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */

    /** @param array<mixed> $data */
    public function callback(mixed $object, array $data = []): void
    {
        wp_nonce_field('cfdev_meta', 'cfdev_nonce');

        $data      = $this->data;
        $meta_type = $this->metaType();
        $object_id = $meta_type === 'post' ? get_the_ID() : ($object->ID ?? 0);

        ErrorBag::load($meta_type, (int) $object_id);

        if (empty($data)) {
            return;
        }

        echo '<input type="hidden" name="cfdev[__activate]" />';
        echo '<div class="cfdev"'
            . ' data-object-id="' . esc_attr($object_id) . '"'
            . ' data-meta-type="' . esc_attr($meta_type) . '">';

        if (! empty($this->description)) {
            echo '<p class="cfdev-box-description">' . wp_kses_post($this->description) . '</p>';
        }

        if (
            $data instanceof \Weblitzer\CFDev\Fields\Tabs
            || $data instanceof \Weblitzer\CFDev\Fields\Accordion
            || $data instanceof \Weblitzer\CFDev\Fields\Bundle
        ) {
            $data->output($object);
        } else {
            $this->renderTable($data, $object);
        }

        echo '</div>';
    }

    /** @param iterable<string, \Weblitzer\CFDev\Field> $data */
    protected function renderTable(iterable $data, object $object): void
    {
        /** @var object{ID: int} $object */
        echo '<table border="0" cellpadding="0" cellspacing="0" class="form-table cfdev-table">';

        foreach ($data as $id_name => $field) {
            if ($field instanceof \Weblitzer\CFDev\Fields\Heading) {
                echo '<tr class="cfdev-heading-row"><td colspan="2">';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->outputHtml('');
                echo '</td></tr>';
                continue;
            }

            $value = match ($this->metaType()) {
                'user'  => get_user_meta($object->ID, $id_name, true),
                'term'  => get_term_meta($object->ID, $id_name, true),
                default => get_post_meta($object->ID, $id_name, true),
            };
            $value = \Weblitzer\CFDev\Field::decodeMetaValue($value);

            $field_errors = ErrorBag::forField($id_name);
            $has_error    = ! empty($field_errors);

            if ($field instanceof \Weblitzer\CFDev\Fields\Hidden) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->output($value);
                continue;
            }

            echo '<tr' . ($has_error ? ' class="cfdev-has-error"' : '') . '>';
            $this->renderThHtml($id_name, $field);
            echo '<td class="cfdev-td">';
            $this->renderField($field, $value, $object);
            $this->renderFieldErrors($field_errors);
            echo '</td></tr>';
        }

        echo '</table>';
    }

    private function renderField(\Weblitzer\CFDev\Field $field, mixed $value, object $object): void
    {
        if ($field->repeatable && $field->supports_repeatable) {
            echo '<button type="button" class="button-secondary cfdev-button js-cfdev-add-field js-cfdev-add-sortable">'
                . '+ ' . esc_html(__('Add', 'cfdev')) . '</button>';
            echo '<ul class="js-cfdev-sortable cfdev-sortable cfdev_repeatable_wrap">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
            echo '</ul>';
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field->output($value);
        }
    }

    /**
     * Normal save method to save all the fields in a metabox
     * Metabox and User Meta rely on this method
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    /** @param array<mixed> $values */
    public function save(int $object_id, array $values): void
    {
        if (!isset($_POST['cfdev_nonce'], $_POST['cfdev'])) {
            return;
        }
        $nonce = sanitize_text_field(wp_unslash($_POST['cfdev_nonce']));
        if (!wp_verify_nonce($nonce, 'cfdev_meta')) {
            return;
        }
        if (!current_user_can('edit_post', $object_id)) {
            return;
        }

        if (!empty($this->data) && !empty($values)) {
            if ($this->data instanceof \Weblitzer\CFDev\Fields\Bundle) {
                $bundle = $this->data;
                if (isset($values[$bundle->id])) {
                    $bundle->save($object_id, $values[$bundle->id]);
                }
            } elseif ($this->data instanceof \Weblitzer\CFDev\Fields\Tabs || $this->data instanceof \Weblitzer\CFDev\Fields\Accordion) {
                foreach ($this->data->tabs as $tab) {
                    if ($tab->fields instanceof \Weblitzer\CFDev\Fields\Bundle) {
                        $bundle = $tab->fields;
                        if (isset($values[$bundle->id])) {
                            $bundle->save($object_id, $values[$bundle->id]);
                        }
                    } else {
                        $this->save($object_id, $values);
                    }
                }
            } else {
                $this->save($object_id, $values);
            }
        }
    }

    /**
     * Checks if the given array are tabs
     *
     * @param   array           $data
     * @return  boolean
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    /** @param array<mixed> $data */
    public static function isTabs(array $data): bool
    {
        return isset($data[0]) && ( ! is_array($data[0]) ) && ( $data[0] == 'tabs' );
    }

    /**
     * Checks if the given array is an accordion
     *
     * @param   array           $data
     * @return  bool
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    /** @param array<mixed> $data */
    public static function isAccordion(array $data): bool
    {
        return isset($data[0]) && ( ! is_array($data[0]) ) && ( $data[0] == 'accordion' );
    }

    /**
     * Checks if the given array is a bundle
     *
     * @param   array           $data
     * @return  bool
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    /** @param array<mixed> $data */
    public static function isBundle(array $data): bool
    {
        return isset($data[0]) && ( ! is_array($data[0]) ) && ( $data[0] == 'bundle' );
    }

    // =========================================================
    // Admin notice
    // =========================================================

    /**
     * Shows a global admin notice if there are validation errors for the current object.
     * Hooked on admin_notices by each subclass constructor.
     */
    public function showValidationNotice(): void
    {
        if (self::$noticeShown) {
            return;
        }

        $object_id = $this->resolveObjectId();
        if (!$object_id) {
            return;
        }

        $errors = ErrorBag::peek($this->metaType(), $object_id);
        if (empty($errors)) {
            return;
        }

        self::$noticeShown = true;
        $items = array_map(
            function (string $key, array $e): string {
                $anchor = str_contains($key, '.') ? explode('.', $key)[0] : $key;
                return sprintf(
                    '<a href="#%s"><strong>%s</strong></a> <abbr title="%s" style="cursor:help">ⓘ</abbr> : %s',
                    esc_attr($anchor),
                    esc_html($e['label']),
                    esc_attr($key),
                    esc_html(implode(', ', $e['errors']))
                );
            },
            array_keys($errors),
            array_values($errors)
        );
        $header = sprintf(
            _n('%s field needs attention', '%s fields need attention', count($errors), 'cfdev'),
            '<strong>' . count($errors) . '</strong>'
        );
        (new Notice(
            array_merge([sprintf($header, count($errors))], $items),
            'error',
            true
        ))->render();
    }

    // =========================================================
    // Validation
    // =========================================================

    /**
     * Validates all fields and returns an error map.
     * Keys are field ids (or "bundle_id.row.field_id" for bundle fields).
     *
     * @param  array<string, mixed> $values  Raw $_POST['cfdev'] values
     * @return array<string, array{label: string, errors: string[]}>
     */
    protected function validateFields(array $values): array
    {
        $errors = [];

        if ($this->data instanceof \Weblitzer\CFDev\Fields\Bundle) {
            $errors = $this->validateBundle($this->data, $values);
        } elseif ($this->data instanceof \Weblitzer\CFDev\Fields\Tabs || $this->data instanceof \Weblitzer\CFDev\Fields\Accordion) {
            foreach ($this->data->tabs as $tab) {
                if ($tab->fields instanceof \Weblitzer\CFDev\Fields\Bundle) {
                    $errors = array_merge($errors, $this->validateBundle($tab->fields, $values));
                } else {
                    foreach ((array) $tab->fields as $id => $field) {
                        $entry = $this->validateSingleField($field, $values[$id] ?? '');
                        if ($entry) {
                            $errors[$id] = $entry;
                        }
                    }
                }
            }
        } else {
            foreach ($this->fields as $id => $field) {
                if ($field->in_bundle) {
                    continue;
                }
                $entry = $this->validateSingleField($field, $values[$id] ?? '');
                if ($entry) {
                    $errors[$id] = $entry;
                }
            }
        }

        return $errors;
    }

    /**
     * @return array{label: string, errors: string[]}|null
     */
    private function validateSingleField(\Weblitzer\CFDev\Field $field, mixed $value): ?array
    {
        $validator = $field->validate($value);
        if ($validator->passes()) {
            return null;
        }
        return [
            'label'  => $field->label,
            'errors' => $validator->errors(),
        ];
    }

    /**
     * @return array<string, array{label: string, errors: string[]}>
     */
    /**
     * @param  array<mixed>                                        $values
     * @return array<string, array{label: string, errors: string[]}>
     */
    private function validateBundle(\Weblitzer\CFDev\Fields\Bundle $bundle, array $values): array
    {
        $errors = [];
        $rows   = $values[$bundle->id] ?? [];

        foreach ((array) $rows as $i => $row) {
            foreach ($bundle->fields as $id => $field) {
                $validator = $field->validate($row[$id] ?? '');
                if (! $validator->passes()) {
                    $key          = $bundle->id . '.' . $i . '.' . $id;
                    $errors[$key] = [
                        'label'  => sprintf('%s (ligne %d)', $field->label, $i + 1),
                        'errors' => $validator->errors(),
                    ];
                }
            }
        }

        return $errors;
    }

    // =========================================================
    // REST API
    // =========================================================

    /**
     * Registers fields flagged with `rest: true` into the WP REST API via register_meta().
     * Only runs when the global cfdev_rest_enabled option is truthy (default: true).
     *
     * @param string $object_type  'post', 'term', or 'user'
     * @param string $subtype      Post type, taxonomy, or '' for users
     */
    protected function registerRestMeta(string $object_type, string $subtype = ''): void
    {
        add_action('rest_api_init', function () use ($object_type, $subtype): void {
            $this->doRegisterRestMeta($object_type, $subtype);
        });
    }

    /**
     * Calls register_meta() for every field flagged with `rest: true`.
     * Extracted for testability — called by the rest_api_init closure.
     * When object-level conditions are set (onlyForId, onlyForTemplate, onlyIfParent),
     * the fields are still registered globally but a rest_prepare_* filter strips them
     * from responses where the object does not match the condition.
     */
    public function doRegisterRestMeta(string $object_type, string $subtype = ''): void
    {
        if ((int) get_option(\Weblitzer\CFDev\Admin\RestPage::OPTION_REST, 1) === 0) {
            return;
        }
        $registered = [];

        foreach ($this->fields as $field) {
            if (! $field->rest || $field->in_bundle) {
                continue;
            }
            $args = [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => $field->restType(),
            ];
            if ($subtype !== '') {
                $args['object_subtype'] = $subtype;
            }
            register_meta($object_type, $field->id, $args);
            $registered[] = $field->id;
        }

        // Register bundles flagged with rest: true (stored as a single JSON string)
        foreach ($this->doRestBundles() as $bundle) {
            $args = ['show_in_rest' => true, 'single' => true, 'type' => 'string'];
            if ($subtype !== '') {
                $args['object_subtype'] = $subtype;
            }
            register_meta($object_type, $bundle->id, $args);
            $registered[] = $bundle->id;
        }

        if (! empty($registered)) {
            $this->addRestConditionFilter($object_type, $subtype, $registered);
        }
    }

    /**
     * Returns all Bundle instances in this meta group that are flagged with rest: true.
     * Covers direct bundles and bundles nested inside Tabs or Accordion.
     *
     * @return array<\Weblitzer\CFDev\Fields\Bundle>
     */
    public function doRestBundles(): array
    {
        $bundles = [];

        if ($this->data instanceof \Weblitzer\CFDev\Fields\Bundle && $this->data->rest) {
            $bundles[] = $this->data;
        } elseif ($this->data instanceof \Weblitzer\CFDev\Fields\Tabs || $this->data instanceof \Weblitzer\CFDev\Fields\Accordion) {
            foreach ($this->data->tabs as $tab) {
                if ($tab->fields instanceof \Weblitzer\CFDev\Fields\Bundle && $tab->fields->rest) {
                    $bundles[] = $tab->fields;
                }
            }
        }

        return $bundles;
    }

    /**
     * Adds a rest_prepare_* filter to strip conditioned fields from REST responses
     * that do not match the declared object-level condition.
     * No-op by default; overridden by MetaBox and TermMeta when conditions are set.
     *
     * @param string        $object_type  'post', 'term', or 'user'
     * @param string        $subtype      Post type, taxonomy slug, or ''
     * @param array<string> $field_ids    Meta keys to strip when condition fails
     */
    protected function addRestConditionFilter(string $object_type, string $subtype, array $field_ids): void
    {
    }

    // =========================================================
    // Build
    // =========================================================

    /**
     * This array builds the complete array with the right key => value pairs
     *
     * @param   mixed           $data
     * @return  array<string, \Weblitzer\CFDev\Field>|\Weblitzer\CFDev\Fields\Tabs|\Weblitzer\CFDev\Fields\Accordion|\Weblitzer\CFDev\Fields\Bundle
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    /**
     * Records a duplicate-field-ID warning when the same ID is registered twice in this meta object.
     * Called before each $this->fields[$id] assignment inside build().
     */
    private function trackFieldWarning(string $field_id, string $context): void
    {
        if (! isset($this->fields[$field_id])) {
            return;
        }
        $this->buildWarnings[] = [
            'field'   => $field_id,
            'context' => $context,
            'message' => sprintf(
                'Field ID "%s" is declared more than once in meta box "%s" (context: %s). Only the last declaration is active — the earlier field definition is silently lost.',
                $field_id,
                $this->id,
                $context
            ),
        ];
    }

    public function build(mixed $data, mixed $parent = null): mixed
    {
        $return = array();

        if (is_array($data) && ! empty($data)) {
            if (self::isTabs($data) || self::isAccordion($data)) {
                $tabs               = self::isTabs($data) ? new  \Weblitzer\CFDev\Fields\Tabs($this->id) : new  \Weblitzer\CFDev\Fields\Accordion($this->id);
                $tabs->meta_type    = $this->metaType();

                foreach ($data[1] as $title => $fields) {
                    $tab            = new  \Weblitzer\CFDev\Fields\Tab($title);
                    $tab->meta_type = $this->metaType();

                    if (self::isBundle($fields[0])) {
                        $tab->fields = $this->build($fields[0]);
                    } else {
                        $tabFields = [];
                        foreach ($fields as $field) {
                            $class = $this->getClassFieldByType($field['type']);
                            if (class_exists($class)) {
                                $field = new $class($field, $this->id);
                                /** @var \Weblitzer\CFDev\Field $field */
                                        $field->meta_type           = $this->metaType();

                                $this->trackFieldWarning($field->id, $title);
                                $this->fields[$field->id]   = $field;
                                $tabFields[$field->id]      = $field;
                            }
                        }
                        $tab->fields = $tabFields;
                    }

                    $tabs->tabs[$title] = $tab;
                }

                $return = $tabs;
            } elseif (self::isBundle($data)) {
                $bundle_id   = is_string($data[1]) ? $data[1] : $this->id;
                $fields_list = is_string($data[1]) ? ($data[2] ?? []) : $data[1];
                $bundle      = new \Weblitzer\CFDev\Fields\Bundle($bundle_id, $data);
                // Detect rest: true in options (4th element with ID, 3rd without)
                $bundle_opts = is_string($data[1]) ? ($data[3] ?? []) : ($data[2] ?? []);
                if (is_array($bundle_opts) && ! empty($bundle_opts['rest'])) {
                    $bundle->rest = true;
                }

                foreach ($fields_list as $field) {
                    $class = $this->getClassFieldByType($field['type']);
                    if (class_exists($class)) {
                        $field = new $class($field, '');
                        /** @var \Weblitzer\CFDev\Field $field */
                        //$field->repeatable = false; je veux le garder au cas ou
                        $field->ajax            = false;
                        $field->meta_type       = $this->metaType();
                        $field->in_bundle       = true;

                        $this->trackFieldWarning($field->id, 'bundle:' . $bundle_id);
                        $this->fields[$field->id]   = $field;
                        $bundle->fields[$field->id] = $field;
                        $bundle->meta_type          = $this->metaType();
                    }
                }

                $return = $bundle;
            } else {
                foreach ($data as $field) {
                    $class = $this->getClassFieldByType($field['type']);
                    if (class_exists($class)) {
                        $field = new $class($field, $this->id);
                        /** @var \Weblitzer\CFDev\Field $field */
                        $field->meta_type           = $this->metaType();

                        $this->trackFieldWarning($field->id, 'flat');
                        $this->fields[$field->id]   = $field;
                        $return[$field->id]         = $field;
                    }
                }
            }
        }

        return $return;
    }

    private function getClassFieldByType(string $type): string
    {
        return 'Weblitzer\\CFDev\\Fields\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $type)));
    }


    /**
     * Adds multipart support to form
     *
     * @return  void
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public static function editFormTag(): void
    {
        echo ' enctype="multipart/form-data"';
    }
}
