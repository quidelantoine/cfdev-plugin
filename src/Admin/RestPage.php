<?php

namespace Weblitzer\CFDev\Admin;

use Weblitzer\CFDev\Registry;

/**
 * CFDev — REST API admin page.
 *
 * Toggles for REST and CFDev API, plus a tabbed table of all fields flagged with rest: true.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class RestPage extends AdminPage
{
    public const OPTION_REST = 'cfdev_rest_enabled';
    public const OPTION_API  = 'cfdev_api_enabled';

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Handle toggle saves
        if (
            isset($_POST['cfdev_rest_option_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_rest_option_nonce'])), 'cfdev_rest_option')
        ) {
            $which = sanitize_text_field(wp_unslash($_POST['cfdev_rest_which'] ?? ''));
            if ($which === 'rest') {
                update_option(self::OPTION_REST, isset($_POST['cfdev_rest_enabled']) ? '1' : '0');
            } elseif ($which === 'api') {
                update_option(self::OPTION_API, isset($_POST['cfdev_api_enabled']) ? '1' : '0');
            }
        }

        $rest_on = (bool) get_option(self::OPTION_REST, true);
        $api_on  = (bool) get_option(self::OPTION_API, true);
        $entries = Registry::restFields();
        $home    = rtrim(get_home_url(), '/');

        // ── Group rest entries by meta_type / target for tabs ─────────────────
        $by_type = [];
        $terms   = [];
        $users   = [];
        $options = [];

        foreach ($entries as $entry) {
            if ($entry['meta_type'] === 'term') {
                $terms[] = $entry;
            } elseif ($entry['meta_type'] === 'user') {
                $users[] = $entry;
            } elseif ($entry['meta_type'] === 'option') {
                $options[] = $entry;
            } else {
                foreach ($entry['targets'] as $pt) {
                    $by_type[$pt][] = $entry;
                }
            }
        }
        ksort($by_type);

        $first_pt  = array_key_first($by_type);
        $first_tab = $first_pt ? 'cfdev-rest-tab-pt-' . $first_pt
            : (! empty($terms)   ? 'cfdev-rest-tab-terms'
            : (! empty($users)   ? 'cfdev-rest-tab-users'
            : 'cfdev-rest-tab-options'));

        $total_rest = count($entries);

        ?>
        <div class="wrap cfdev-rest-page">
            <?php self::header(__('REST API', 'cfdev')); ?>

            <div class="cfdev-rest-toggles">
                <div class="cfdev-cache-option">
                    <form method="post" class="cfdev-cache-option__form">
                        <?php wp_nonce_field('cfdev_rest_option', 'cfdev_rest_option_nonce'); ?>
                        <input type="hidden" name="cfdev_rest_which" value="rest">
                        <label class="cfdev-toggle-wrap" for="cfdev_rest_enabled">
                            <input type="checkbox" id="cfdev_rest_enabled" name="cfdev_rest_enabled"
                                   value="1" onchange="this.form.submit()"
                                   <?php checked($rest_on); ?>>
                            <span class="cfdev-toggle-slider"></span>
                        </label>
                        <span class="cfdev-cache-option__label">
                            <?php if ($rest_on) : ?>
                                <strong><?php esc_html_e('Native WP REST active', 'cfdev'); ?></strong>
                            <?php else : ?>
                                <strong><?php esc_html_e('Native WP REST inactive', 'cfdev'); ?></strong>
                            <?php endif; ?>
                            <span class="cfdev-cache-option__hint">
                                — <code>/wp-json/wp/v2/</code>
                                <?php esc_html_e('— raw values (image ID, JSON string for bundles)', 'cfdev'); ?>
                            </span>
                        </span>
                    </form>
                </div>
                <div class="cfdev-cache-option">
                    <form method="post" class="cfdev-cache-option__form">
                        <?php wp_nonce_field('cfdev_rest_option', 'cfdev_rest_option_nonce'); ?>
                        <input type="hidden" name="cfdev_rest_which" value="api">
                        <label class="cfdev-toggle-wrap" for="cfdev_api_enabled">
                            <input type="checkbox" id="cfdev_api_enabled" name="cfdev_api_enabled"
                                   value="1" onchange="this.form.submit()"
                                   <?php checked($api_on); ?>>
                            <span class="cfdev-toggle-slider"></span>
                        </label>
                        <span class="cfdev-cache-option__label">
                            <?php if ($api_on) : ?>
                                <strong><?php esc_html_e('CFDev API active', 'cfdev'); ?></strong>
                            <?php else : ?>
                                <strong><?php esc_html_e('CFDev API inactive', 'cfdev'); ?></strong>
                            <?php endif; ?>
                            <span class="cfdev-cache-option__hint">
                                — <code>/wp-json/cfdev/v1/</code>
                                <?php esc_html_e('— resolved values (enriched images, decoded bundles)', 'cfdev'); ?>
                            </span>
                        </span>
                    </form>
                </div>
            </div>

            <?php // ── Section 1: Exposed fields (with tabs) ──────────────────── ?>
            <div class="cfdev-rest-section">
                <h2>
                    <?php esc_html_e('Currently exposed fields', 'cfdev'); ?>
                    <?php if ($total_rest > 0) : ?>
                        <span class="cfdev-tab-count cfdev-rest-total"><?php echo esc_html((string) $total_rest); ?></span>
                    <?php endif; ?>
                </h2>

                <?php if ($total_rest === 0) : ?>
                    <?php self::placeholder(__('No fields marked rest: true yet.', 'cfdev')); ?>
                <?php else : ?>
                    <nav class="nav-tab-wrapper cfdev-tabs-nav">
                        <?php foreach ($by_type as $pt => $pt_entries) : ?>
                            <?php
                            $pt_obj   = get_post_type_object($pt);
                            $pt_label = ($pt_obj && isset($pt_obj->labels->name))
                                ? $pt_obj->labels->name : ucfirst($pt);
                            $tab_id   = 'cfdev-rest-tab-pt-' . $pt;
                            ?>
                            <a href="#<?php echo esc_attr($tab_id); ?>"
                               class="nav-tab<?php echo ($tab_id === $first_tab) ? ' nav-tab-active' : ''; ?>"
                               data-cfdev-tab>
                                <?php echo esc_html($pt_label); ?>
                                <span class="cfdev-tab-count"><?php echo esc_html((string) self::countFields($pt_entries)); ?></span>
                            </a>
                        <?php endforeach; ?>

                        <?php if (! empty($terms)) : ?>
                            <a href="#cfdev-rest-tab-terms"
                               class="nav-tab<?php echo ($first_tab === 'cfdev-rest-tab-terms') ? ' nav-tab-active' : ''; ?>"
                               data-cfdev-tab>
                                <?php esc_html_e('Terms', 'cfdev'); ?>
                                <span class="cfdev-tab-count"><?php echo esc_html((string) self::countFields($terms)); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if (! empty($users)) : ?>
                            <a href="#cfdev-rest-tab-users"
                               class="nav-tab<?php echo ($first_tab === 'cfdev-rest-tab-users') ? ' nav-tab-active' : ''; ?>"
                               data-cfdev-tab>
                                <?php esc_html_e('Users', 'cfdev'); ?>
                                <span class="cfdev-tab-count"><?php echo esc_html((string) self::countFields($users)); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if (! empty($options)) : ?>
                            <a href="#cfdev-rest-tab-options"
                               class="nav-tab<?php echo ($first_tab === 'cfdev-rest-tab-options') ? ' nav-tab-active' : ''; ?>"
                               data-cfdev-tab>
                                <?php esc_html_e('Options', 'cfdev'); ?>
                                <span class="cfdev-tab-count"><?php echo esc_html((string) self::countFields($options)); ?></span>
                            </a>
                        <?php endif; ?>
                    </nav>

                    <?php foreach ($by_type as $pt => $pt_entries) : ?>
                        <?php $tab_id = 'cfdev-rest-tab-pt-' . $pt; ?>
                        <div id="<?php echo esc_attr($tab_id); ?>" class="cfdev-tab-panel"
                                <?php echo ($tab_id !== $first_tab) ? 'hidden' : ''; ?>>
                            <?php self::renderFieldsTable($pt_entries, 'post', $home); ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if (! empty($terms)) : ?>
                        <div id="cfdev-rest-tab-terms"
                             class="cfdev-tab-panel"
                                <?php echo ($first_tab !== 'cfdev-rest-tab-terms') ? 'hidden' : ''; ?>>
                            <?php self::renderFieldsTable($terms, 'term', $home); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($users)) : ?>
                        <div id="cfdev-rest-tab-users"
                             class="cfdev-tab-panel"
                                <?php echo ($first_tab !== 'cfdev-rest-tab-users') ? 'hidden' : ''; ?>>
                            <?php self::renderFieldsTable($users, 'user', $home); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (! empty($options)) : ?>
                        <div id="cfdev-rest-tab-options"
                             class="cfdev-tab-panel"
                                <?php echo ($first_tab !== 'cfdev-rest-tab-options') ? 'hidden' : ''; ?>>
                            <?php self::renderFieldsTable($options, 'option', $home); ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>

        </div>
        <?php
        self::bundleModal();
    }

    private static function bundleModal(): void
    {
        ?>
        <div id="cfdev-rest-bundle-modal" class="cfdev-modal" hidden aria-modal="true" role="dialog"
             aria-labelledby="cfdev-rest-bundle-modal-title">
            <div class="cfdev-modal-overlay"></div>
            <div class="cfdev-modal-box">
                <div class="cfdev-modal-header">
                    <h2 class="cfdev-modal-title" id="cfdev-rest-bundle-modal-title">
                        <span aria-hidden="true">⊞</span>
                        <?php esc_html_e('Bundle', 'cfdev'); ?>
                        <code id="cfdev-rest-bundle-key"></code>
                    </h2>
                    <button type="button" class="cfdev-modal-close"
                            aria-label="<?php esc_attr_e('Close', 'cfdev'); ?>">&#x2715;</button>
                </div>
                <p class="description">
                    <?php esc_html_e('Exposure is all-or-nothing: all fields in the bundle are included.', 'cfdev'); ?>
                    <?php esc_html_e('Individual fields inside cannot be selected separately.', 'cfdev'); ?>
                </p>
                <div id="cfdev-rest-bundle-body"></div>
            </div>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Rendering helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private static function renderFieldsTable(array $entries, string $meta_type, string $home): void
    {
        if (empty($entries)) {
            echo '<p class="cfdev-empty">' . esc_html__('No groups declared.', 'cfdev') . '</p>';
            return;
        }

        foreach ($entries as $entry) :
            $layout        = $entry['layout'] ?? 'flat';
            $target        = $entry['targets'][0] ?? '';
            $example_id    = self::exampleId($entry['meta_type'], $target);
            $is_option     = $entry['meta_type'] === 'option';
            $cfdev_path    = self::cfdevEndpoint($entry['meta_type'], $entry['targets'], $example_id);
            $ep_cfdev_url  = (($example_id > 0 || $is_option) && $cfdev_path !== '') ? $home . $cfdev_path : '';
            $native_suffix = $entry['meta_type'] === 'post' ? '?_fields=id,title,meta' : '';
            $ep_native_url = ($example_id > 0 || $is_option)
                ? $home . self::nativeEndpoint($entry['meta_type'], $entry['targets'], $example_id) . $native_suffix
                : '';
            $ep_cfdev_txt  = self::cfdevEndpoint($entry['meta_type'], $entry['targets'], $example_id);
            $ep_native_txt = self::nativeEndpoint($entry['meta_type'], $entry['targets'], $example_id)
                . ($example_id > 0 ? $native_suffix : '');

            $flat_fields  = array_filter($entry['fields'] ?? [], fn($f) => ($f['type'] ?? '') !== 'bundle');
            $bundle_count = array_sum(array_map(fn($b) => count($b['fields']), $entry['bundles'] ?? []));
            $field_count  = count($flat_fields) + $bundle_count;
            ?>
            <div class="cfdev-group">

                <div class="cfdev-group-header" role="button" tabindex="0" aria-expanded="false">
                    <span class="cfdev-toggle-icon" aria-hidden="true">▶</span>
                    <span class="cfdev-group-title"><?php echo esc_html($entry['title']); ?></span>
                    <code class="cfdev-group-id"><?php echo esc_html($entry['id']); ?></code>

                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::layoutBadge($layout);
                    if (! empty($entry['bundles']) && in_array($layout, ['tabs', 'accordion'], true)) {
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo self::layoutBadge('bundle');
                    }
                    ?>

                    <?php if (! empty($entry['conditions'])) : ?>
                        <span class="cfdev-conditions">
                        <?php foreach ($entry['conditions'] as $key => $value) : ?>
                            <?php
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo self::conditionBadge($key, $value);
                            ?>
                        <?php endforeach; ?>
                    </span>
                    <?php endif; ?>

                    <span class="cfdev-field-count">
                        <?php
                        // translators: %d = number of fields
                        echo esc_html(sprintf(_n('%d field', '%d fields', $field_count, 'cfdev'), $field_count));
                        ?>
                    </span>
                </div>

                <div class="cfdev-group-body" hidden>
                    <?php if (! empty($entry['sections'])) : ?>
                        <?php foreach ($entry['sections'] as $section) : ?>
                        <div class="cfdev-section<?php echo $layout === 'accordion' ? ' cfdev-section--accordion' : ''; ?>">
                            <div class="cfdev-section-title">
                                <span class="cfdev-section-icon" aria-hidden="true">
                                    <?php echo $layout === 'accordion' ? '▾' : '⊟'; ?>
                                </span>
                                <?php echo esc_html($section['title']); ?>
                                <?php if ($section['bundle_id'] !== null) : ?>
                                    <span class="cfdev-section-bundle-ref">
                                        <span aria-hidden="true">⊞</span>
                                        <?php esc_html_e('Bundle', 'cfdev'); ?>
                                        <code><?php echo esc_html($section['bundle_id']); ?></code>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($section['bundle_id'] !== null && isset($entry['bundles'][$section['bundle_id']])) : ?>
                                <?php self::renderRestTable(
                                    $entry['bundles'][$section['bundle_id']]['fields'],
                                    $ep_cfdev_url,
                                    $ep_cfdev_txt,
                                    $ep_native_url,
                                    $ep_native_txt,
                                    $meta_type,
                                    $section['bundle_id']
                                ); ?>
                            <?php elseif (! empty($section['fields'])) : ?>
                                <?php self::renderRestTable(
                                    $section['fields'],
                                    $ep_cfdev_url,
                                    $ep_cfdev_txt,
                                    $ep_native_url,
                                    $ep_native_txt,
                                    $meta_type
                                ); ?>
                            <?php else : ?>
                                <p class="cfdev-empty cfdev-empty--body"><?php esc_html_e('No fields.', 'cfdev'); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                    <?php else : ?>
                        <?php if (! empty($flat_fields)) : ?>
                            <?php self::renderRestTable(
                                $flat_fields,
                                $ep_cfdev_url,
                                $ep_cfdev_txt,
                                $ep_native_url,
                                $ep_native_txt,
                                $meta_type
                            ); ?>
                        <?php endif; ?>

                        <?php foreach ($entry['bundles'] ?? [] as $bundle_id => $bundle) : ?>
                        <div class="cfdev-bundle">
                            <div class="cfdev-bundle-title">
                                <span aria-hidden="true">⊞</span>
                                <?php esc_html_e('Bundle', 'cfdev'); ?>
                                <code><?php echo esc_html($bundle_id); ?></code>
                            </div>
                            <?php self::renderRestTable(
                                $bundle['fields'],
                                $ep_cfdev_url,
                                $ep_cfdev_txt,
                                $ep_native_url,
                                $ep_native_txt,
                                $meta_type,
                                $bundle_id
                            ); ?>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($flat_fields) && empty($entry['bundles'])) : ?>
                            <p class="cfdev-empty cfdev-empty--body"><?php esc_html_e('No fields.', 'cfdev'); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
            <?php
        endforeach;
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     */
    private static function renderRestTable(
        array $fields,
        string $ep_cfdev_url,
        string $ep_cfdev_txt,
        string $ep_native_url,
        string $ep_native_txt,
        string $meta_type,
        string $bundle_key = ''
    ): void {
        ?>
        <table class="widefat striped cfdev-rest-table">
            <thead>
            <tr>
                <th><?php esc_html_e('Meta key', 'cfdev'); ?></th>
                <th><?php esc_html_e('Label', 'cfdev'); ?></th>
                <th><?php esc_html_e('REST type', 'cfdev'); ?></th>
                <th><?php esc_html_e('CFDev endpoint', 'cfdev'); ?></th>
                <th><?php esc_html_e('Native endpoint', 'cfdev'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($bundle_key !== '') :
                $bundle_fields_arr = [];
                foreach ($fields as $field_id => $field) {
                    $bundle_fields_arr[] = [
                        'id'        => $field_id,
                        'label'     => $field['label'] ?? '',
                        'rest_type' => $field['rest_type'] ?? 'string',
                    ];
                }
                $bundle_fields_json = wp_json_encode($bundle_fields_arr) ?: '[]';
                ?>
                <tr class="cfdev-bundle-key-row">
                    <td>
                        <code><?php echo esc_html($bundle_key); ?></code>
                        <span class="cfdev-badge cfdev-badge--bundle"><?php esc_html_e('bundle', 'cfdev'); ?></span>
                    </td>
                    <td>
                        <button type="button" class="cfdev-bundle-fields-btn button button-small"
                                data-cfdev-bundle-key="<?php echo esc_attr($bundle_key); ?>"
                                data-cfdev-bundle-fields="<?php echo esc_attr($bundle_fields_json); ?>"
                                data-cfdev-ep-cfdev-url="<?php echo esc_url($ep_cfdev_url); ?>"
                                data-cfdev-ep-cfdev-txt="<?php echo esc_attr($ep_cfdev_txt); ?>"
                                data-cfdev-ep-native-url="<?php echo esc_url($ep_native_url); ?>"
                                data-cfdev-ep-native-txt="<?php echo esc_attr($ep_native_txt); ?>"
                                data-cfdev-meta-type="<?php echo esc_attr($meta_type); ?>">
                            <?php esc_html_e('⊞ View fields', 'cfdev'); ?>
                        </button>
                    </td>
                    <td>
                        <span class="cfdev-rule-badge">array</span>
                        <span class="cfdev-rule-badge"><?php esc_html_e('string (native)', 'cfdev'); ?></span>
                    </td>
                    <td>
                        <?php if ($ep_cfdev_url !== '') : ?>
                            <a href="<?php echo esc_url($ep_cfdev_url); ?>"
                               target="_blank" rel="noopener noreferrer">
                                <code><?php echo esc_html($ep_cfdev_txt); ?></code>
                            </a>
                        <?php else : ?>
                            <code><?php echo esc_html($ep_cfdev_txt); ?></code>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($ep_native_url !== '') : ?>
                            <a href="<?php echo esc_url($ep_native_url); ?>"
                               target="_blank" rel="noopener noreferrer">
                                <code><?php echo esc_html($ep_native_txt); ?></code>
                            </a>
                        <?php else : ?>
                            <code><?php echo esc_html($ep_native_txt); ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($fields as $field_id => $field) : ?>
                <tr>
                    <td><code><?php echo esc_html($field_id); ?></code></td>
                    <td><?php echo esc_html($field['label'] ?? ''); ?></td>
                    <td><span class="cfdev-rule-badge"><?php echo esc_html($field['rest_type'] ?? 'string'); ?></span></td>
                    <td>
                        <?php if ($ep_cfdev_url !== '') : ?>
                            <a href="<?php echo esc_url($ep_cfdev_url); ?>"
                               target="_blank" rel="noopener noreferrer">
                                <code><?php echo esc_html($ep_cfdev_txt); ?></code>
                            </a>
                        <?php else : ?>
                            <code><?php echo esc_html($ep_cfdev_txt); ?></code>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($ep_native_url !== '') : ?>
                            <a href="<?php echo esc_url($ep_native_url); ?>"
                               target="_blank" rel="noopener noreferrer">
                                <code><?php echo esc_html($ep_native_txt); ?></code>
                            </a>
                        <?php else : ?>
                            <code><?php echo esc_html($ep_native_txt); ?></code>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private static function countFields(array $entries): int
    {
        $total = 0;
        foreach ($entries as $e) {
            $flat   = array_filter($e['fields'] ?? [], fn($f) => ($f['type'] ?? '') !== 'bundle');
            $total += count($flat);
            foreach ($e['bundles'] ?? [] as $bundle) {
                $total += count($bundle['fields']);
            }
        }
        return $total;
    }

    /**
     * @param array<string> $targets
     */
    private static function cfdevEndpoint(string $meta_type, array $targets, int $id = 0): string
    {
        $target  = $targets[0] ?? '';
        $id_part = $id > 0 ? (string) $id : '{id}';
        return match ($meta_type) {
            'post'   => '/wp-json/cfdev/v1/post/' . $id_part,
            'term'   => '/wp-json/cfdev/v1/term/' . $target . '/' . $id_part,
            'user'   => '/wp-json/cfdev/v1/user/' . $id_part,
            'option' => '/wp-json/cfdev/v1/options/' . $target,
            default  => '/wp-json/cfdev/v1/...',
        };
    }

    /**
     * @param array<string> $targets
     */
    private static function nativeEndpoint(string $meta_type, array $targets, int $id = 0): string
    {
        $target  = $targets[0] ?? '';
        $id_part = $id > 0 ? (string) $id : '{id}';

        if ($meta_type === 'post') {
            $pt_obj    = get_post_type_object($target);
            $pt_arr    = $pt_obj ? (array) $pt_obj : [];
            $rest_base = ! empty($pt_arr['rest_base']) ? (string) $pt_arr['rest_base'] : $target . 's';
            return '/wp-json/wp/v2/' . $rest_base . '/' . $id_part;
        }

        if ($meta_type === 'term') {
            $taxonomy  = get_taxonomy($target);
            $rest_base = ($taxonomy && $taxonomy->rest_base) ? $taxonomy->rest_base : $target . 's';
            return '/wp-json/wp/v2/' . $rest_base . '/' . $id_part;
        }

        return match ($meta_type) {
            'user'   => '/wp-json/wp/v2/users/' . $id_part,
            'option' => '/wp-json/wp/v2/settings',
            default  => '/wp-json/wp/v2/...',
        };
    }

    private static function exampleId(string $meta_type, string $target): int
    {
        if ($meta_type === 'post') {
            $posts = get_posts(['post_type' => $target, 'numberposts' => 1, 'post_status' => 'publish']);
            return ! empty($posts) ? $posts[0]->ID : 0;
        }
        if ($meta_type === 'term') {
            $terms = get_terms(['taxonomy' => $target, 'number' => 1, 'hide_empty' => false]);
            return is_array($terms) && ! empty($terms) ? $terms[0]->term_id : 0;
        }
        if ($meta_type === 'user') {
            $users = get_users(['number' => 1]);
            return ! empty($users) ? (int) $users[0]->ID : 0;
        }
        return 0;
    }

    private static function layoutBadge(string $layout): string
    {
        $labels = [
            'flat'      => __('Flat', 'cfdev'),
            'tabs'      => __('Tabs', 'cfdev'),
            'accordion' => __('Accordion', 'cfdev'),
            'bundle'    => __('Bundle', 'cfdev'),
        ];
        return sprintf(
            '<span class="cfdev-badge cfdev-badge--%s">%s</span>',
            esc_attr($layout),
            esc_html($labels[$layout] ?? $layout)
        );
    }

    private static function conditionBadge(string $key, mixed $value): string
    {
        $label = match ($key) {
            'post_id'   => 'ID : ' . $value,
            'template'  => 'Template : ' . basename((string) $value),
            'roles'     => 'Role: ' . implode(', ', (array) $value),
            'parent_id' => 'Parent : ' . $value,
            default     => $key . ' : ' . $value,
        };
        return sprintf('<span class="cfdev-condition-badge">%s</span>', esc_html($label));
    }
}