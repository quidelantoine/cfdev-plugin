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
    public function __construct($taxonomy, $data = array(), $locations = array( 'add_form', 'edit_form' ))
    {
        $this->taxonomies   = (array) $taxonomy;
        $this->locations    = (array) $locations;
        $this->id           = (string) current($this->taxonomies);

        // Build the meta box and fields
        $this->data = $this->build($data);

        foreach ($this->taxonomies as $taxonomy) {
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

        if ($this->data instanceof Tabs || $this->data instanceof Accordion) {
            echo '<div class="cfdev">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => 0]);
            echo '</div>';
            return;
        }

        if ($this->data instanceof Bundle) {
            echo '<div class="form-field cfdev">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => 0]);
            echo '</div>';
            return;
        }

        /* Loop through $data */
        foreach ($this->data as $id_name => $field) {
            $value = '';

            if ($field instanceof \Weblitzer\CFDev\Fields\Heading) {
                echo '<div class="cfdev cfdev-heading-row">';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->outputHtml('');
                echo '</div>';
                continue;
            }

            if (! $field instanceof Hidden) {
                echo '<div class="form-field cfdev">';
                    echo '<label for="' . esc_attr($id_name) . '" class="cfdev_label">' . esc_html($field->label) . '</label>';
                if (! empty($field->description)) {
                    echo '<div class="cfdev-description description">' . wp_kses_post($field->description) . '</div>';
                }
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $field->output($value);
                echo '</div>';
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->output($value);
            }
        }
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
        $value = get_cfdev_term_meta($term->term_id, $term->taxonomy);

        ErrorBag::load('term', (int) $term->term_id);

        echo '<input type="hidden" name="cfdev[__activate]" />';

        if ($this->data instanceof Tabs || $this->data instanceof Accordion) {
            echo '<tr class="cfdev form-field"><td colspan="2">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => $term->term_id]);
            echo '</td></tr>';
            return;
        }

        if ($this->data instanceof Bundle) {
            echo '<tr class="cfdev form-field"><td colspan="2">';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            $this->data->output((object) ['ID' => $term->term_id]);
            echo '</td></tr>';
            return;
        }

        /* Loop through $data */
        foreach ($this->data as $id_name => $field) {
            if ($field instanceof \Weblitzer\CFDev\Fields\Heading) {
                echo '<tr class="cfdev cfdev-heading-row"><td colspan="2">';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->outputHtml('');
                echo '</td></tr>';
                continue;
            }

            $value[$id_name] = isset($value[$id_name]) ? $value[$id_name] : '';

            if (! $field instanceof Hidden) {
                $field_errors = ErrorBag::forField($id_name);
                $has_error    = ! empty($field_errors);

                echo '<tr class="cfdev form-field' . ($has_error ? ' cfdev-has-error' : '') . '">';
                    echo '<th scope="row" valign="top" class="cfdev-th">';
                        echo '<label for="' . esc_attr($id_name) . '" class="cfdev_label">' . esc_html($field->label) . '</label>';
                if (! empty($field->description)) {
                    echo '<div class="cfdev-description description">' . wp_kses_post($field->description) . '</div>';
                }
                    echo '</th>';
                    echo '<td class="cfdev-td">';
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo $field->output($value[$id_name]);
                if ($has_error) {
                    echo '<p class="cfdev-field-error">' . esc_html(implode(' ', $field_errors)) . '</p>';
                }
                    echo '</td>';
                echo '</tr>';
            } else {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $field->output($value);
            }
        }
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
