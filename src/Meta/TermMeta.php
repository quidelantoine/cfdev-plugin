<?php

namespace Weblitzer\CFDev\Meta;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Fields\Accordion;
use Weblitzer\CFDev\Fields\Bundle;
use Weblitzer\CFDev\Fields\Hidden;
use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Fields\Tabs;
use Weblitzer\CFDev\Fields\Wysiwyg;
use Weblitzer\CFDev\Meta;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * Registers the meta boxes
 *
 * @author  quidelantoine
 * @since   1.0.0
 *
 */

class TermMeta extends Meta
{
    /** @var array<string> */
    public array $taxonomies;
    /** @var array<string> */
    public array $locations;
    public ?int $only_if_parent = null;

    protected function metaType(): string
    {
        return 'term'; 
    }

    /**
     * Construct the term meta
     *
     * @param   string|array    $taxonomy
     * @param   array           $data
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     * @param string|array<string> $taxonomy
     * @param array<mixed>         $data
     * @param array<string>        $locations
     */
    public function __construct($taxonomy, string $title = '', $data = array(), $locations = array( 'add_form', 'edit_form' ))
    {
        parent::__construct($title);

        $this->taxonomies   = (array) $taxonomy;
        $this->locations    = (array) $locations;
        $this->id           = (string) current($this->taxonomies);

        // Build the meta box and fields
        $this->data = $this->build($data);

        foreach ($this->taxonomies as $taxonomy) {
            $this->registerRestMeta('term', $taxonomy);
            if (in_array('add_form', $this->locations)) {
                add_action($taxonomy . '_add_form_fields', array( $this, 'addFormFields' ));
                add_action('created_' . $taxonomy, array( $this, 'saveTerm' ));
            }

            if (in_array('edit_form', $this->locations)) {
                add_action($taxonomy . '_edit_form_fields', array( $this, 'editFormFields' ));
                add_action('edited_' . $taxonomy, array( $this, 'saveTerm' ));
            }

            add_action('admin_notices', array($this, 'showValidationNotice'));

            add_filter('manage_edit-' . $taxonomy . '_columns', array( $this, 'addColumn' ));
            add_filter('manage_' . $taxonomy . '_custom_column', array( $this, 'addColumnContent' ), 10, 3);
        }

        \Weblitzer\CFDev\Registry::register($this);
    }

    /**
     * Restrict this section to terms whose direct parent matches the given term ID.
     * On the add-term form, reads the parent from $_GET['parent'] if present.
     */
    public function onlyIfParent(int $parent_id): static
    {
        $this->only_if_parent = $parent_id;
        return $this;
    }

    /**
     * Strips conditioned REST fields from term responses where the term's parent
     * does not match the onlyIfParent condition.
     *
     * @param string        $object_type  Always 'term' for TermMeta
     * @param string        $subtype      Taxonomy slug (e.g. 'category')
     * @param array<string> $field_ids    Meta keys registered with show_in_rest
     */
    protected function addRestConditionFilter(string $object_type, string $subtype, array $field_ids): void
    {
        if ($this->only_if_parent === null) {
            return;
        }
        add_filter(
            'rest_prepare_' . $subtype,
            function (\WP_REST_Response $response, \WP_Term $term) use ($field_ids): \WP_REST_Response {
                if ($term->parent !== $this->only_if_parent) {
                    $meta = $response->data['meta'] ?? [];
                    foreach ($field_ids as $id) {
                        unset($meta[$id]);
                    }
                    $response->data['meta'] = $meta;
                }
                return $response;
            },
            10,
            2
        );
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    private function resolveTitle(string $taxonomy): string
    {
        if ($this->title !== '') {
            return $this->title;
        }
        $obj = get_taxonomy($taxonomy);
        return $obj ? $obj->labels->singular_name : ucfirst($taxonomy);
    }

    /**
     * Add fields to the add term form
     *
     * @param   string      $taxonomy
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function addFormFields(string $taxonomy): void
    {
        if ($this->only_if_parent !== null) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $parent = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;
            if ($parent !== $this->only_if_parent) {
                return;
            }
        }

        wp_nonce_field('cfdev_meta', 'cfdev_nonce');
        echo '<input type="hidden" name="cfdev[__activate]" />';

        echo '<div class="cfdev-postbox">';
        echo '<div class="cfdev-postbox-header"><h2 class="cfdev-postbox-title">'
            . esc_html($this->resolveTitle($taxonomy)) . '</h2></div>';
        echo '<div class="cfdev-postbox-inside">';
        echo '<div class="cfdev" data-object-id="0" data-meta-type="term">';

        if ($this->data instanceof Tabs || $this->data instanceof Accordion) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => 0]);
        } elseif ($this->data instanceof Bundle) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => 0]);
        } else {
            $this->renderTable($this->data, (object) ['ID' => 0]);
        }

        echo '</div></div></div>';
    }

    /**
     * Add fields to the edit term form
     *
     * @param   \WP_Term    $term
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function editFormFields(\WP_Term $term): void
    {
        if ($this->only_if_parent !== null && $term->parent !== $this->only_if_parent) {
            return;
        }

        wp_nonce_field('cfdev_meta', 'cfdev_nonce');
        ErrorBag::load('term', (int) $term->term_id);
        echo '<input type="hidden" name="cfdev[__activate]" />';

        echo '<tr class="cfdev form-field"><td colspan="2">';
        echo '<div class="cfdev-postbox">';
        echo '<div class="cfdev-postbox-header"><h2 class="cfdev-postbox-title">'
            . esc_html($this->resolveTitle($term->taxonomy)) . '</h2></div>';
        echo '<div class="cfdev-postbox-inside">';
        echo '<div class="cfdev" data-object-id="' . esc_attr((string) $term->term_id)
            . '" data-meta-type="term">';

        if ($this->data instanceof Tabs || $this->data instanceof Accordion) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => $term->term_id]);
        } elseif ($this->data instanceof Bundle) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => $term->term_id]);
        } else {
            $this->renderTable($this->data, (object) ['ID' => $term->term_id]);
        }

        echo '</div></div></div>';
        echo '</td></tr>';
    }

    /**
     * Save the term
     *
     * @param   int         $term_id
     *
     * @author  quidelantoine
     * @since   1.0.0
     */
    public function saveTerm(int $term_id): void
    {
        // Verify nonce
        if (! ( isset($_POST['cfdev_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_nonce'])), 'cfdev_meta') )) {
            return;
        }

        // Respect parent condition
        if ($this->only_if_parent !== null) {
            $term = get_term($term_id);
            if (! $term instanceof \WP_Term || $term->parent !== $this->only_if_parent) {
                return;
            }
        }

        if (! empty($this->data) && isset($_POST['cfdev'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $values = is_array($_POST['cfdev']) ? wp_unslash($_POST['cfdev']) : [];

            $errors = $this->validateFields($values);
            if (! empty($errors)) {
                ErrorBag::push('term', $term_id, $errors);
            }

            if ($this->data instanceof Tabs || $this->data instanceof Accordion) {
                foreach ($this->data->tabs as $tab) {
                    if ($tab->fields instanceof Bundle) {
                        if (isset($values[$tab->fields->id])) {
                            $tab->fields->save($term_id, $values[$tab->fields->id]);
                        }
                    } else {
                        foreach ((array) $tab->fields as $field) {
                            $raw       = $values[$field->id] ?? '';
                            $sanitized = $this->sanitizeFieldValue($raw, $field);
                            update_term_meta($term_id, $field->id, $field->saveValue($sanitized));
                        }
                    }
                }
                return;
            }

            if ($this->data instanceof Bundle) {
                if (isset($values[$this->data->id])) {
                    $this->data->save($term_id, $values[$this->data->id]);
                }
                return;
            }

            foreach ($this->fields as $id_name => $field) {
                if ($field instanceof \Weblitzer\CFDev\Fields\Heading) {
                    continue;
                }
                $raw       = $values[$field->id] ?? '';
                $sanitized = $this->sanitizeFieldValue($raw, $field);
                update_term_meta($term_id, $field->id, $field->saveValue($sanitized));
            }
        }
    }

    /** @return string|array<string> */
    private function sanitizeFieldValue(mixed $raw, Field $field): string|array
    {
        if (is_array($raw)) {
            return array_map('sanitize_text_field', $raw);
        }
        if ($field instanceof Wysiwyg) {
            return wp_kses_post($raw);
        }
        return sanitize_text_field($raw);
    }

    protected function resolveObjectId(): int
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET['tag_ID']) ? (int) $_GET['tag_ID'] : 0;
    }

    /**
     * Used to add a column head to the Taxonomy's List Table
     *
     * @param   array           $columns
     * @return  array
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    /**
     * @param  array<string, string> $columns
     * @return array<string, string>
     */
    public function addColumn(array $columns): array
    {
        foreach ($this->fields as $id_name => $field) {
            if ($field->show_admin_column) {
                $columns[$id_name] = $field->label;
            }
        }

        return $columns;
    }

    /**
     * Used to add the column content to the column head
     *
     * @param   string          $row
     * @param   string          $column
     * @param   int             $term_id
     * @return  string
     *
     * @author  quidelantoine
     * @since   1.0.0
     *
     */
    public function addColumnContent(string $row, string $column, int $term_id): string
    {
        $screen = get_current_screen();

        if ($screen) {
            $taxonomy = $screen->taxonomy;
            $meta     = get_cfdev_term_meta($term_id, $taxonomy, (string) $column);

            foreach ($this->fields as $id_name => $field) {
                if ($column === $id_name) {
                    if ($field->repeatable && $field->supports_repeatable) {
                        return esc_html(implode(', ', (array) $meta));
                    }
                    if ($field instanceof Image) {
                        return wp_get_attachment_image((int) $meta, [100, 100]);
                    }
                    return esc_html((string) $meta);
                }
            }
        }

        return $row;
    }
}
