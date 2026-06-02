<?php

namespace Weblitzer\CFDev;

use Weblitzer\CFDev\Fields\Accordion;
use Weblitzer\CFDev\Fields\Bundle;
use Weblitzer\CFDev\Fields\Tabs;
use Weblitzer\CFDev\Support\WPValidator;
use Weblitzer\CFDev\Validation\ErrorBag;

/**
 * Registers a standalone admin page whose fields are stored in wp_options.
 *
 * Usage:
 *   register_cfdev_options_page('site_settings', 'Site Settings', $fields);
 *   register_cfdev_options_page('social', 'Social Media', $fields)->asSubmenu('options-general.php');
 *
 * Read a value: get_option('field_id')
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
class OptionsPage extends Meta
{
    public string $capability  = 'manage_options';
    public string $icon        = 'dashicons-admin-generic';
    public int $menu_position  = 83;

    private ?string $parent_slug = null;
    private string $action;

    protected function metaType(): string
    {
        return 'option';
    }

    protected function resolveObjectId(): int
    {
        return 0;
    }

    /**
     * @param string               $id
     * @param string|array<string> $title
     * @param array<mixed>         $data
     */
    public function __construct(string $id, string|array $title, array $data = [])
    {
        parent::__construct($title);

        $this->id     = $id;
        $this->action = 'cfdev_options_' . $id;

        if (WPValidator::isWpCallback($data)) {
            $this->callback = $data;
        } else {
            $this->callback = [$this, 'renderPage'];
            $this->data     = $this->build($data);

            add_action('admin_post_' . $this->action, [$this, 'saveOptions']);
            add_action('admin_notices', [$this, 'showValidationNotice']);
            add_action('rest_api_init', [$this, 'registerRestOptions']);
        }

        add_action('admin_menu', [$this, 'registerMenu']);

        Registry::register($this);
    }

    /**
     * Register as a submenu page under an existing admin menu.
     *
     * @param string $parent_slug  e.g. 'options-general.php', 'cfdev', or any menu slug
     */
    public function asSubmenu(string $parent_slug): static
    {
        $this->parent_slug = $parent_slug;
        return $this;
    }

    /**
     * Add a sub-page under this (top-level) options page and return $this for chaining.
     *
     * @param string               $id
     * @param string|array<string> $title
     * @param array<mixed>         $data
     */
    public function addSubPage(string $id, string|array $title, array $data = []): static
    {
        (new self($id, $title, $data))->asSubmenu('cfdev-' . $this->id);
        return $this;
    }

    public function registerMenu(): void
    {
        if ($this->parent_slug !== null) {
            add_submenu_page(
                $this->parent_slug,
                $this->title,
                $this->title,
                $this->capability,
                'cfdev-' . $this->id,
                [$this, 'renderPage']
            );
        } else {
            add_menu_page(
                $this->title,
                $this->title,
                $this->capability,
                'cfdev-' . $this->id,
                [$this, 'renderPage'],
                $this->icon,
                $this->menu_position
            );
        }
    }

    public function renderPage(): void
    {
        if (! current_user_can($this->capability)) {
            wp_die(esc_html__('You do not have permission to access this page.', 'cfdev'));
        }

        ErrorBag::load('option', 0);

        $dummy = (object) ['ID' => 0];

        echo '<div class="wrap">';
        echo '<h1>' . esc_html($this->title) . '</h1>';

        if (! empty($this->description)) {
            echo '<p class="cfdev-description">' . wp_kses_post($this->description) . '</p>';
        }

        $this->renderPageNotices();

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action" value="' . esc_attr($this->action) . '">';
        wp_nonce_field($this->action, 'cfdev_options_nonce');

        echo '<input type="hidden" name="cfdev[__activate]" />';
        echo '<div class="cfdev" data-object-id="0" data-meta-type="option">';

        if ($this->data instanceof Bundle || $this->data instanceof Tabs || $this->data instanceof Accordion) {
            $this->data->output($dummy);
        } elseif (! empty($this->data)) {
            $this->renderTable($this->data, $dummy);
        }

        echo '</div>';

        submit_button(__('Save settings', 'cfdev'));
        echo '</form>';
        echo '</div>';
    }

    private function renderPageNotices(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['cfdev-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'cfdev') . '</p></div>';
        }

        $errors = ErrorBag::all();
        if (empty($errors)) {
            return;
        }

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

        $count  = count($errors);
        $header = sprintf(
            // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders
            _n('%s field needs attention', '%s fields need attention', $count, 'cfdev'),
            '<strong>' . $count . '</strong>'
        );

        (new Notice(array_merge([$header], $items), 'error', true))->render();
    }

    public function saveOptions(): void
    {
        if (! current_user_can($this->capability)) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'cfdev'));
        }

        if (
            ! isset($_POST['cfdev_options_nonce'])
            || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_options_nonce'])), $this->action)
        ) {
            wp_die(esc_html__('Security check failed.', 'cfdev'));
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $values = isset($_POST['cfdev']) ? wp_unslash($_POST['cfdev']) : [];

        if (! empty($values)) {
            $errors = $this->validateFields($values);

            if (! empty($errors)) {
                ErrorBag::push('option', 0, $errors);
                wp_safe_redirect($this->pageUrl());
                exit;
            }

            $this->save(0, $values);
        }

        wp_safe_redirect(add_query_arg('cfdev-updated', '1', $this->pageUrl()));
        exit;
    }

    /**
     * @param array<mixed> $values
     */
    public function save(int $object_id, array $values): void
    {
        if ($this->data instanceof Bundle) {
            if (isset($values[$this->data->id])) {
                $this->data->save(0, $values[$this->data->id]);
            }
            return;
        }

        if ($this->data instanceof Tabs || $this->data instanceof Accordion) {
            foreach ($this->data->tabs as $tab) {
                if ($tab->fields instanceof Bundle) {
                    if (isset($values[$tab->fields->id])) {
                        $tab->fields->save(0, $values[$tab->fields->id]);
                    }
                }
            }
            $this->saveFlatFields($values);
            return;
        }

        $this->saveFlatFields($values);
    }

    /**
     * @param array<mixed> $values
     */
    private function saveFlatFields(array $values): void
    {
        foreach ($this->fields as $id => $field) {
            if ($field->in_bundle) {
                continue;
            }

            $value = $values[$id] ?? '';
            $value = apply_filters(
                'cfdev_option_meta_save_' . $field->type,
                apply_filters('cfdev_option_meta_save', $value, $field),
                $field
            );

            $field->save(0, $value);
        }
    }

    /**
     * Registers fields and bundles flagged with rest: true into /wp/v2/settings.
     * Uses register_setting() — the WP native mechanism for option REST exposure.
     * Only runs when the global cfdev_rest_enabled option is truthy (default: true).
     */
    public function registerRestOptions(): void
    {
        if ((int) get_option(\Weblitzer\CFDev\Admin\RestPage::OPTION_REST, 1) === 0) {
            return;
        }

        foreach ($this->fields as $field) {
            if (! $field->rest || $field->in_bundle) {
                continue;
            }
            register_setting('general', $field->id, [
                'type'         => $field->restType(),
                'show_in_rest' => true,
                'default'      => '',
            ]);
        }

        foreach ($this->doRestBundles() as $bundle) {
            register_setting('general', $bundle->id, [
                'type'         => 'string',
                'show_in_rest' => true,
                'default'      => '',
            ]);
        }
    }

    /**
     * Overrides Meta::showValidationNotice() — notices are rendered inline in renderPage()
     * after ErrorBag::load(), which runs later than admin_notices.
     */
    public function showValidationNotice(): void
    {
    }

    private function pageUrl(): string
    {
        $referer = wp_get_referer();
        if ($referer && str_contains($referer, 'page=cfdev-' . $this->id)) {
            return $referer;
        }

        if ($this->parent_slug !== null && str_contains($this->parent_slug, '.php')) {
            return admin_url($this->parent_slug . '?page=cfdev-' . $this->id);
        }

        return admin_url('admin.php?page=cfdev-' . $this->id);
    }
}
