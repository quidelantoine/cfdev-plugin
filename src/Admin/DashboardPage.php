<?php

namespace Weblitzer\CFDev\Admin;

use Weblitzer\CFDev\Registry;

/**
 * CFDev — Dashboard: lists all registered field groups.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class DashboardPage extends AdminPage
{
    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $all           = Registry::all();
        $dups          = Registry::duplicates();
        $dupBoxIds     = Registry::duplicateMetaBoxIds();
        $dupBundleIds  = Registry::duplicateBundleIds();
        $intraBoxDups  = Registry::intraBoxDuplicates();
        $reservedKeys  = Registry::reservedFieldIds();

        // One bucket per post type, sorted alphabetically
        $by_type = [];
        foreach ($all as $entry) {
            if ($entry['meta_type'] !== 'post') {
                continue;
            }
            foreach ($entry['targets'] as $pt) {
                $by_type[$pt][] = $entry;
            }
        }
        ksort($by_type);

        $terms = array_values(array_filter($all, fn($e) => $e['meta_type'] === 'term'));
        $users = array_values(array_filter($all, fn($e) => $e['meta_type'] === 'user'));

        // Determine which tab is active on first load
        $first_pt  = array_key_first($by_type);
        $first_tab = $first_pt ? 'cfdev-tab-pt-' . $first_pt : (! empty($terms) ? 'cfdev-tab-terms' : 'cfdev-tab-users');

        ?>
        <div class="wrap cfdev-registry">

            <div class="cfdev-header">
                <h1 class="cfdev-header__title">
                    <span class="cfdev-logo">
                        <span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>CF
                    </span>
                    <?php esc_html_e('Field groups', 'cfdev'); ?>
                </h1>
                <span class="cfdev-header__count">
                    <?php echo esc_html(sprintf(
                        // translators: %d = number of groups
                        _n('%d group', '%d groups', count($all), 'cfdev'),
                        count($all)
                    )); ?>
                </span>
                <?php if (! empty($dups)) : ?>
                <span class="cfdev-header__dups">
                    ⚠ <?php echo esc_html(sprintf(
                        // translators: %d = number of duplicate field IDs
                        _n('%d duplicate', '%d duplicates', count($dups), 'cfdev'),
                        count($dups)
                    )); ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if (! empty($intraBoxDups)) : ?>
            <div class="notice notice-error cfdev-notice-dups">
                <p><strong><?php esc_html_e('Duplicate field IDs within the same meta box:', 'cfdev'); ?></strong></p>
                <ul>
                    <?php foreach ($intraBoxDups as $w) : ?>
                    <li>
                        <code><?php echo esc_html($w['field']); ?></code>
                        <?php esc_html_e('declared more than once in', 'cfdev'); ?>
                        <strong><?php echo esc_html($w['meta_box']); ?></strong>
                        (<?php echo esc_html($w['context']); ?>)
                        — <?php esc_html_e('only the last declaration is active;', 'cfdev'); ?>
                        <?php esc_html_e('the earlier field definition and its saved data are silently lost.', 'cfdev'); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (! empty($dupBundleIds)) : ?>
            <div class="notice notice-error cfdev-notice-dups">
                <p><strong><?php esc_html_e('Duplicate bundle IDs across meta boxes:', 'cfdev'); ?></strong></p>
                <ul>
                    <?php foreach ($dupBundleIds as $bundle_id => $post_types) : ?>
                    <li>
                        <code><?php echo esc_html($bundle_id); ?></code>
                        <?php esc_html_e('is used as a bundle ID in multiple meta boxes for post type', 'cfdev'); ?>
                        <strong><?php echo esc_html(implode(', ', $post_types)); ?></strong>
                        — <?php esc_html_e('every save overwrites the other meta box\'s bundle data. Use a unique bundle ID for each meta box.', 'cfdev'); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (! empty($reservedKeys)) : ?>
            <div class="notice notice-error cfdev-notice-dups">
                <p><strong><?php esc_html_e('Reserved WordPress meta keys used as field IDs:', 'cfdev'); ?></strong></p>
                <ul>
                    <?php foreach ($reservedKeys as $field_id => $boxes) : ?>
                    <li>
                        <code><?php echo esc_html($field_id); ?></code>
                        <?php esc_html_e('in', 'cfdev'); ?>
                        <strong><?php echo esc_html(implode(', ', $boxes)); ?></strong>
                        — <?php esc_html_e('this key is used by WordPress core. Saving to it will corrupt native WordPress data', 'cfdev'); ?>
                        <?php esc_html_e('(featured image, page template, user sessions, etc.). Rename this field immediately.', 'cfdev'); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (! empty($dupBoxIds)) : ?>
            <div class="notice notice-error cfdev-notice-dups">
                <p><strong><?php esc_html_e('Duplicate meta box IDs:', 'cfdev'); ?></strong></p>
                <ul>
                    <?php foreach ($dupBoxIds as $box_id => $post_types) : ?>
                    <li>
                        <code><?php echo esc_html($box_id); ?></code>
                        <?php esc_html_e('is registered multiple times for post type', 'cfdev'); ?>
                        <strong><?php echo esc_html(implode(', ', $post_types)); ?></strong>
                        — <?php esc_html_e('WordPress only shows the last registration. Use a unique ID for each addMetaBox() call.', 'cfdev'); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (! empty($dups)) : ?>
            <div class="notice notice-warning cfdev-notice-dups">
                <p><strong><?php esc_html_e('Duplicate field IDs:', 'cfdev'); ?></strong></p>
                <ul>
                    <?php foreach ($dups as $field_id => $boxes) : ?>
                    <li>
                        <code><?php echo esc_html($field_id); ?></code>
                        <?php esc_html_e('declared in', 'cfdev'); ?>
                        <?php echo esc_html(implode(', ', $boxes)); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (empty($all)) : ?>
            <div class="cfdev-notice-empty">
                <p>
                    <?php esc_html_e('No field groups declared yet. Register your first group using', 'cfdev'); ?>
                    <code>register_cfdev_post_type()</code>,
                    <code>register_cfdev_taxonomy()</code>
                    <?php esc_html_e('or', 'cfdev'); ?>
                    <code>register_cfdev_user_meta()</code>.
                </p>
            </div>
            <?php endif; ?>

            <nav class="nav-tab-wrapper cfdev-tabs-nav">

                <?php foreach ($by_type as $pt => $entries) : ?>
                    <?php
                    $pt_obj    = get_post_type_object($pt);
                    $pt_label  = $pt_obj ? $pt_obj->labels->name : ucfirst($pt);
                    $tab_id    = 'cfdev-tab-pt-' . $pt;
                    $is_active = ($tab_id === $first_tab);
                    ?>
                <a href="#<?php echo esc_attr($tab_id); ?>"
                   class="nav-tab<?php echo $is_active ? ' nav-tab-active' : ''; ?>"
                   data-cfdev-tab>
                    <?php echo esc_html($pt_label); ?>
                    <span class="cfdev-tab-count"><?php echo count($entries); ?></span>
                </a>
                <?php endforeach; ?>

                <a href="#cfdev-tab-terms"
                   class="nav-tab<?php echo ($first_tab === 'cfdev-tab-terms') ? ' nav-tab-active' : ''; ?>"
                   data-cfdev-tab>
                    <?php esc_html_e('Terms', 'cfdev'); ?>
                    <span class="cfdev-tab-count"><?php echo count($terms); ?></span>
                </a>
                <a href="#cfdev-tab-users"
                   class="nav-tab<?php echo ($first_tab === 'cfdev-tab-users') ? ' nav-tab-active' : ''; ?>"
                   data-cfdev-tab>
                    <?php esc_html_e('Users', 'cfdev'); ?>
                    <span class="cfdev-tab-count"><?php echo count($users); ?></span>
                </a>

            </nav>

            <?php foreach ($by_type as $pt => $entries) : ?>
                <?php $tab_id = 'cfdev-tab-pt-' . $pt; ?>
            <div id="<?php echo esc_attr($tab_id); ?>"
                 class="cfdev-tab-panel"
                 <?php echo ($tab_id !== $first_tab) ? 'hidden' : ''; ?>>
                <?php self::renderPanel($entries, $dups, $dupBoxIds, $pt); ?>
            </div>
            <?php endforeach; ?>

            <div id="cfdev-tab-terms" class="cfdev-tab-panel"
                 <?php echo ($first_tab !== 'cfdev-tab-terms') ? 'hidden' : ''; ?>>
                <?php self::renderPanel($terms, $dups, $dupBoxIds); ?>
            </div>
            <div id="cfdev-tab-users" class="cfdev-tab-panel"
                 <?php echo ($first_tab !== 'cfdev-tab-users') ? 'hidden' : ''; ?>>
                <?php self::renderPanel($users, $dups, $dupBoxIds); ?>
            </div>

        </div>
        <?php
        self::inspectModal();
        self::codeModal();
    }

    // -------------------------------------------------------------------------
    // Panels
    // -------------------------------------------------------------------------

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<string, array<string>>     $dups
     * @param array<string, array<string>>     $dupBoxIds
     */
    private static function renderPanel(array $entries, array $dups, array $dupBoxIds = [], string $current_pt = ''): void
    {
        if (empty($entries)) {
            echo '<p class="cfdev-empty">' . esc_html__('No groups declared.', 'cfdev') . '</p>';
            return;
        }
        foreach ($entries as $entry) {
            self::renderGroup($entry, $dups, $dupBoxIds, $current_pt);
        }
    }

    /**
     * @param array<string, mixed>         $entry
     * @param array<string, array<string>> $dups
     * @param array<string, array<string>> $dupBoxIds
     */
    private static function renderGroup(array $entry, array $dups, array $dupBoxIds = [], string $current_pt = ''): void
    {
        $bundle_count = array_sum(array_map(fn($b) => count($b['fields']), $entry['bundles']));
        $total        = count($entry['fields']) + $bundle_count;
        $dup_ids      = array_keys($dups);

        // Other post types this group is also assigned to (shown only in CPT tabs)
        $other_pts = $current_pt !== ''
            ? array_values(array_filter($entry['targets'], fn($t) => $t !== $current_pt))
            : [];

        $conditions     = $entry['conditions'] ?? [];
        $default_tax    = $entry['meta_type'] === 'term' ? ($entry['targets'][0] ?? '') : '';
        $is_fixed       = isset($conditions['post_id']);
        $default_id     = $is_fixed
            ? (int) $conditions['post_id']
            : self::firstObjectId($entry['meta_type'], $entry['targets'], $conditions);
        $object_options = $is_fixed ? [] : self::objectOptions($entry['meta_type'], $entry['targets'], $default_tax, $conditions);
        ?>
        <div class="cfdev-group">

            <div class="cfdev-group-header" role="button" tabindex="0"
                 aria-expanded="false">

                <span class="cfdev-toggle-icon" aria-hidden="true">▶</span>

                <span class="cfdev-group-title"><?php echo esc_html($entry['title']); ?></span>
                <code class="cfdev-group-id"><?php echo esc_html($entry['id']); ?></code>
                <?php if (isset($dupBoxIds[$entry['id']])) : ?>
                <span class="cfdev-dup-badge cfdev-dup-badge--box"
                      title="<?php esc_attr_e('Duplicate meta box ID — WordPress only shows the last registration', 'cfdev'); ?>">⚠</span>
                <?php endif; ?>

                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo self::layoutBadge($entry['layout']);
                ?>

                <?php if (! empty($entry['bundles']) && in_array($entry['layout'], ['tabs', 'accordion'], true)) : ?>
                    <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo self::layoutBadge('bundle');
                    ?>
                <?php endif; ?>

                <?php if (! empty($other_pts)) : ?>
                <span class="cfdev-also-in">
                    <?php esc_html_e('Also in:', 'cfdev'); ?>
                    <?php foreach ($other_pts as $pt) : ?>
                        <?php
                        $pt_obj   = get_post_type_object($pt);
                        $pt_label = $pt_obj ? $pt_obj->labels->name : ucfirst($pt);
                        ?>
                    <span class="cfdev-also-in__tag"><?php echo esc_html($pt_label); ?></span>
                    <?php endforeach; ?>
                </span>
                <?php endif; ?>

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
                    <?php echo esc_html(sprintf(
                        // translators: %d = number of fields
                        _n('%d field', '%d fields', $total, 'cfdev'),
                        $total
                    )); ?>
                </span>

                <button type="button" class="cfdev-btn-inspect button button-small"
                        data-meta-type="<?php echo esc_attr($entry['meta_type']); ?>"
                        data-group-id="<?php echo esc_attr($entry['id']); ?>"
                        data-targets="<?php echo esc_attr(implode(',', $entry['targets'])); ?>"
                        data-default-id="<?php echo esc_attr((string) $default_id); ?>"
                        data-default-tax="<?php echo esc_attr($default_tax); ?>"
                        data-options="<?php echo esc_attr((string) wp_json_encode($object_options)); ?>"
                        data-fixed="<?php echo $is_fixed ? '1' : '0'; ?>">
                    <?php esc_html_e('⚙ Inspect', 'cfdev'); ?>
                </button>

                <button type="button" class="cfdev-btn-code button button-small"
                        data-group-id="<?php echo esc_attr($entry['id']); ?>"
                        data-code="<?php echo esc_attr(self::codeSnippet($entry)); ?>"
                        data-code-raw="<?php echo esc_attr(self::codeSnippet($entry, true)); ?>">
                    &lt;/&gt; <?php esc_html_e('Code', 'cfdev'); ?>
                </button>

            </div>

            <div class="cfdev-group-body" hidden>

                <?php if (! empty($entry['sections'])) : ?>
                    <?php foreach ($entry['sections'] as $section) : ?>
                    <div class="cfdev-section<?php echo $entry['layout'] === 'accordion' ? ' cfdev-section--accordion' : ''; ?>">
                        <div class="cfdev-section-title">
                            <span class="cfdev-section-icon" aria-hidden="true">
                                <?php echo $entry['layout'] === 'accordion' ? '▾' : '⊟'; ?>
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
                            <?php self::renderFieldsTable($entry['bundles'][$section['bundle_id']]['fields'], $dup_ids); ?>
                        <?php elseif (! empty($section['fields'])) : ?>
                            <?php self::renderFieldsTable($section['fields'], $dup_ids); ?>
                        <?php else : ?>
                            <p class="cfdev-empty cfdev-empty--body"><?php esc_html_e('No fields.', 'cfdev'); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                <?php else : ?>
                    <?php if (! empty($entry['fields'])) : ?>
                        <?php self::renderFieldsTable($entry['fields'], $dup_ids); ?>
                    <?php endif; ?>

                    <?php foreach ($entry['bundles'] as $bundle_id => $bundle) : ?>
                    <div class="cfdev-bundle">
                        <div class="cfdev-bundle-title">
                            <span aria-hidden="true">⊞</span>
                            <?php esc_html_e('Bundle', 'cfdev'); ?>
                            <code><?php echo esc_html($bundle_id); ?></code>
                        </div>
                        <?php self::renderFieldsTable($bundle['fields'], $dup_ids); ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($entry['fields']) && empty($entry['bundles'])) : ?>
                        <p class="cfdev-empty cfdev-empty--body"><?php esc_html_e('No fields.', 'cfdev'); ?></p>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>
        <?php
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     * @param array<string>                       $dup_ids
     */
    private static function renderFieldsTable(array $fields, array $dup_ids): void
    {
        ?>
        <table class="cfdev-fields-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'cfdev'); ?></th>
                    <th><?php esc_html_e('Type', 'cfdev'); ?></th>
                    <th><?php esc_html_e('Label', 'cfdev'); ?></th>
                    <th><?php esc_html_e('Validation', 'cfdev'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field_id => $field) : ?>
                    <?php $is_dup = in_array($field_id, $dup_ids, true); ?>
                <tr<?php echo $is_dup ? ' class="cfdev-dup"' : ''; ?>>
                    <td>
                        <code><?php echo esc_html($field_id); ?></code>
                        <?php if ($is_dup) : ?>
                        <span class="cfdev-dup-badge" title="<?php esc_attr_e('Duplicate ID', 'cfdev'); ?>">⚠</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo self::typeBadge($field['type']);
                        ?>
                    </td>
                    <td><?php echo esc_html($field['label']); ?></td>
                    <td class="cfdev-rules-cell">
                        <?php if ($field['required']) : ?>
                        <span class="cfdev-rule-badge cfdev-rule-badge--required"><?php esc_html_e('required', 'cfdev'); ?></span>
                        <?php endif; ?>
                        <?php foreach ($field['rules'] ?? [] as $rule) : ?>
                        <span class="cfdev-rule-badge"><?php echo esc_html($rule); ?></span>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    // -------------------------------------------------------------------------
    // Badges
    // -------------------------------------------------------------------------

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
        return sprintf(
            '<span class="cfdev-condition-badge">%s</span>',
            esc_html($label)
        );
    }

    private static function typeBadge(string $type): string
    {
        return sprintf(
            '<span class="cfdev-type cfdev-type--%s">%s</span>',
            esc_attr($type),
            esc_html($type)
        );
    }

    /**
     * Returns the ID of the first available object for a given meta type, targets, and conditions.
     *
     * @param  string[]             $targets     Post types, taxonomies, or empty for users.
     * @param  array<string, mixed> $conditions  Registry entry conditions.
     */
    private static function firstObjectId(string $meta_type, array $targets, array $conditions = []): int
    {
        if ($meta_type === 'post' && ! empty($targets)) {
            $args = [
                'post_type'      => $targets,
                'post_status'    => ['publish', 'draft', 'private'],
                'posts_per_page' => 1,
                'no_found_rows'  => true,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            ];
            if (isset($conditions['template'])) {
                $args['meta_key']   = '_wp_page_template';
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                $args['meta_value'] = $conditions['template'];
            }
            $q    = new \WP_Query($args);
            $post = $q->posts[0] ?? null;
            return $post instanceof \WP_Post ? $post->ID : 0;
        }

        if ($meta_type === 'term' && ! empty($targets)) {
            $args = [
                'taxonomy'   => $targets,
                'number'     => 1,
                'fields'     => 'ids',
                'hide_empty' => false,
                'orderby'    => 'id',
                'order'      => 'DESC',
            ];
            if (isset($conditions['parent_id'])) {
                $args['parent'] = (int) $conditions['parent_id'];
            }
            $terms = get_terms($args);
            return is_array($terms) && ! empty($terms) ? (int) $terms[0] : 0;
        }

        if ($meta_type === 'user') {
            $args = ['number' => 1, 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'DESC'];
            if (! empty($conditions['roles'])) {
                $args['role__in'] = (array) $conditions['roles'];
            }
            $users = get_users($args);
            return ! empty($users) ? (int) $users[0] : 0;
        }

        return 0;
    }

    /**
     * Returns all available objects for a meta type, filtered by conditions.
     *
     * @param  string[]             $targets     Post types or taxonomies.
     * @param  array<string, mixed> $conditions  Registry entry conditions.
     * @return array<int, array{id: int, label: string, meta: string}>
     */
    private static function objectOptions(string $meta_type, array $targets, string $taxonomy, array $conditions = []): array
    {
        if ($meta_type === 'post' && ! empty($targets)) {
            $args = [
                'post_type'      => $targets,
                'post_status'    => ['publish', 'draft', 'private'],
                'posts_per_page' => 100,
                'no_found_rows'  => true,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ];
            if (isset($conditions['template'])) {
                $args['meta_key']   = '_wp_page_template';
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                $args['meta_value'] = $conditions['template'];
            }
            $q    = new \WP_Query($args);
            $opts = [];
            foreach ($q->posts as $post) {
                if ($post instanceof \WP_Post) {
                    $opts[] = [
                        'id'    => $post->ID,
                        'label' => $post->post_title !== '' ? $post->post_title : __('(no title)', 'cfdev'),
                        'meta'  => $post->post_type,
                    ];
                }
            }
            return $opts;
        }

        if ($meta_type === 'term' && ! empty($targets)) {
            $tax  = $taxonomy !== '' ? $taxonomy : ($targets[0] ?? '');
            $args = [
                'taxonomy'   => $tax !== '' ? $tax : $targets,
                'number'     => 100,
                'hide_empty' => false,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ];
            if (isset($conditions['parent_id'])) {
                $args['parent'] = (int) $conditions['parent_id'];
            }
            $terms = get_terms($args);
            $opts  = [];
            if (is_array($terms)) {
                foreach ($terms as $term) {
                    $opts[] = [
                        'id'    => $term->term_id,
                        'label' => $term->name,
                        'meta'  => $term->taxonomy,
                    ];
                }
            }
            return $opts;
        }

        if ($meta_type === 'user') {
            $args = ['number' => 100, 'fields' => 'all', 'orderby' => 'display_name', 'order' => 'ASC'];
            if (! empty($conditions['roles'])) {
                $args['role__in'] = (array) $conditions['roles'];
            }
            $users = get_users($args);
            $opts  = [];
            foreach ($users as $user) {
                $opts[] = [
                    'id'    => $user->ID,
                    'label' => $user->display_name,
                    'meta'  => $user->user_login,
                ];
            }
            return $opts;
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // Code snippet
    // -------------------------------------------------------------------------

    /**
     * Generates a PHP code snippet for reading the group's field values.
     *
     * @param array<string, mixed> $entry Registry entry.
     * @param bool                 $raw   True = raw value extraction only (no HTML output).
     */
    private static function codeSnippet(array $entry, bool $raw = false): string
    {
        $id   = (string) $entry['id'];
        $type = (string) ($entry['meta_type'] ?? 'post');

        if ($type === 'post') {
            $call = 'post($post->ID)';
        } elseif ($type === 'term') {
            $tax  = str_replace("'", "\\'", (string) ($entry['targets'][0] ?? 'taxonomy'));
            $call = "term(\$term->term_id, '{$tax}')";
        } else {
            $call = 'user($user->ID)';
        }

        $gid   = str_replace("'", "\\'", $id);
        $lines = [
            '<?php',
            '$data  = (new \Weblitzer\CFDev\Cache\CacheManager())->' . $call . ';',
            "\$group = \$data['groups']['{$gid}'] ?? [];",
        ];

        $fl = $raw
            ? static fn(string $f, array $d, string $s): array => self::fieldLinesRaw($f, $d, $s)
            : static fn(string $f, array $d, string $s): array => self::fieldLines($f, $d, $s);

        if (! empty($entry['sections'])) {
            foreach ($entry['sections'] as $section) {
                $lines[] = '';
                $lines[] = '// ' . ($section['title'] ?? '');
                $bid     = $section['bundle_id'] ?? null;
                if ($bid !== null && isset($entry['bundles'][$bid])) {
                    $sbid    = str_replace("'", "\\'", (string) $bid);
                    $lines[] = "\$rows = \$group['{$sbid}'] ?? [];";
                    $lines[] = 'foreach ($rows as $row) {';
                    foreach ($entry['bundles'][$bid]['fields'] as $fid => $field) {
                        foreach ($fl((string) $fid, $field, '$row') as $l) {
                            $lines[] = '    ' . $l;
                        }
                    }
                    $lines[] = '}';
                } elseif (! empty($section['fields'])) {
                    foreach ($section['fields'] as $fid => $field) {
                        array_push($lines, ...$fl((string) $fid, $field, '$group'));
                    }
                }
            }
        } else {
            foreach ($entry['fields'] as $fid => $field) {
                $lines[] = '';
                array_push($lines, ...$fl((string) $fid, $field, '$group'));
            }
            foreach ($entry['bundles'] as $bundle_id => $bundle) {
                $sbid    = str_replace("'", "\\'", (string) $bundle_id);
                $lines[] = '';
                $lines[] = '// Bundle: ' . $bundle_id;
                $lines[] = "\$rows = \$group['{$sbid}'] ?? [];";
                $lines[] = 'foreach ($rows as $row) {';
                foreach ($bundle['fields'] as $fid => $field) {
                    foreach ($fl((string) $fid, $field, '$row') as $l) {
                        $lines[] = '    ' . $l;
                    }
                }
                $lines[] = '}';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Returns PHP code lines for reading a single field value.
     *
     * @param  array<string, mixed> $field Field data from the Registry.
     * @return list<string>
     */
    private static function fieldLines(string $fid, array $field, string $src = '$group'): array
    {
        $type  = (string) ($field['type'] ?? 'text');
        $label = (string) ($field['label'] ?? $fid);
        $var   = '$' . $fid;
        $sfid  = str_replace("'", "\\'", $fid);

        switch ($type) {
            case 'image':
                return [
                    '// ' . $label,
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    "if (! empty({$var}['url'])) {",
                    "    echo '<img'",
                    "        . ' src=\"'    . esc_url({$var}['url'])         . '\"'",
                    "        . ' alt=\"'    . esc_attr({$var}['alt'] ?? '')  . '\"'",
                    "        . ' width=\"'  . (int) ({$var}['width']  ?? 0) . '\"'",
                    "        . ' height=\"' . (int) ({$var}['height'] ?? 0) . '\"'",
                    "        . '>';",
                    "    // All registered sizes: thumbnail, medium, large, full",
                    "    \$sizes  = {$var}['sizes'] ?? [];",
                    "    \$thumb  = \$sizes['thumbnail']['url'] ?? {$var}['url'];",
                    "    \$medium = \$sizes['medium']['url']    ?? {$var}['url'];",
                    "    \$large  = \$sizes['large']['url']     ?? {$var}['url'];",
                    "    // WP responsive markup (recommended)",
                    "    echo wp_get_attachment_image({$var}['id'], 'medium');",
                    '}',
                ];
            case 'gallery':
                return [
                    '// ' . $label . ' (array of images)',
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    'foreach (' . $var . ' as $img) {',
                    "    \$sizes  = \$img['sizes'] ?? [];",
                    "    \$thumb  = \$sizes['thumbnail']['url'] ?? \$img['url'];",
                    "    \$medium = \$sizes['medium']['url']    ?? \$img['url'];",
                    "    \$large  = \$sizes['large']['url']     ?? \$img['url'];",
                    "    // WP responsive markup",
                    "    echo wp_get_attachment_image(\$img['id'], 'medium');",
                    '}',
                ];
            case 'file':
                return [
                    '// ' . $label,
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    "if (! empty({$var}['url'])) {",
                    "    // {$var}['id'], {$var}['title'], {$var}['mime_type']",
                    "    echo '<a href=\"' . esc_url({$var}['url']) . '\">'",
                    "        . esc_html({$var}['title'] ?? '') . '</a>';",
                    '}',
                ];
            case 'checkboxes':
            case 'multi_select':
                return [
                    '// ' . $label . ' (array of values)',
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    'foreach (' . $var . ' as $item) {',
                    '    echo esc_html($item);',
                    '}',
                ];
            case 'post_checkboxes':
                return [
                    '// ' . $label . ' (array of post IDs — one query)',
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    "if (! empty({$var})) {",
                    "    \$posts = get_posts([",
                    "        'post__in'       => array_map('absint', {$var}),",
                    "        'posts_per_page' => -1,",
                    "        'orderby'        => 'post__in',",
                    "        'no_found_rows'  => true,",
                    "    ]);",
                    '    foreach ($posts as $p) {',
                    "        echo '<a href=\"' . esc_url(get_permalink(\$p->ID)) . '\">'",
                    "            . esc_html(\$p->post_title) . '</a>';",
                    '    }',
                    '}',
                ];
            case 'term_checkboxes':
                return [
                    '// ' . $label . ' (array of term IDs — one query)',
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    "if (! empty({$var})) {",
                    "    \$terms = get_terms([",
                    "        'include'    => array_map('absint', {$var}),",
                    "        'hide_empty' => false,",
                    "        'orderby'    => 'include',",
                    "    ]);",
                    '    foreach ($terms as $t) {',
                    "        \$link = get_term_link(\$t);",
                    "        if (is_wp_error(\$link)) continue;",
                    "        echo '<a href=\"' . esc_url(\$link) . '\">' . esc_html(\$t->name) . '</a>';",
                    '    }',
                    '}',
                ];
            case 'user_checkboxes':
                return [
                    '// ' . $label . ' (array of user IDs — one query)',
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    "if (! empty({$var})) {",
                    "    \$users = get_users([",
                    "        'include' => array_map('absint', {$var}),",
                    "        'orderby' => 'include',",
                    "    ]);",
                    '    foreach ($users as $u) {',
                    "        echo esc_html(\$u->display_name);",
                    "        // \$u->user_email, \$u->user_login, \$u->roles",
                    '    }',
                    '}',
                ];
            case 'wysiwyg':
                return [
                    '// ' . $label,
                    "echo wp_kses_post({$src}['{$sfid}'] ?? '');",
                ];
            case 'textarea':
                return [
                    '// ' . $label,
                    "echo nl2br(esc_html({$src}['{$sfid}'] ?? ''));",
                ];
            case 'link':
                return [
                    '// ' . $label,
                    "{$var} = {$src}['{$sfid}'] ?? [];",
                    "if (! empty({$var}['url'])) {",
                    "    \$url    = esc_url({$var}['url']);",
                    "    \$text   = esc_html({$var}['text'] ?? {$var}['url']);",
                    "    \$extern = ! empty({$var}['target']);",
                    "    \$rel    = \$extern ? ' rel=\"noopener noreferrer\"' : '';",
                    "    \$tgt    = \$extern ? ' target=\"_blank\"' : '';",
                    "    // Standard link",
                    "    echo '<a href=\"' . \$url . '\"' . \$tgt . \$rel . '>' . \$text . '</a>';",
                    "    // As a CTA button",
                    "    echo '<a href=\"' . \$url . '\"' . \$tgt . \$rel . ' class=\"button\">' . \$text . '</a>';",
                    "    // URL and text separately",
                    "    // \$url  → {$var}['url']   (raw URL string)",
                    "    // \$text → {$var}['text']  (display label)",
                    '}',
                ];
            case 'color':
                return [
                    '// ' . $label . ' (hex color)',
                    "{$var} = esc_attr({$src}['{$sfid}'] ?? '');",
                    "if ({$var}) {",
                    "    echo '<span style=\"color:' . {$var} . '\">' . {$var} . '</span>';",
                    '}',
                ];
            case 'number':
            case 'range':
                return [
                    '// ' . $label,
                    "echo intval({$src}['{$sfid}'] ?? 0);",
                ];
            case 'yesno':
            case 'toggle':
            case 'checkbox':
                return [
                    '// ' . $label,
                    "if (! empty({$src}['{$sfid}'])) {",
                    '    // checked / enabled',
                    '} else {',
                    '    // unchecked / disabled',
                    '}',
                ];
            case 'post_select':
                return [
                    '// ' . $label . ' (post ID)',
                    "{$var} = absint({$src}['{$sfid}'] ?? 0);",
                    "if ({$var}) {",
                    "    echo esc_html(get_the_title({$var}));",
                    "    echo esc_url(get_permalink({$var}));",
                    '}',
                ];
            case 'term_select':
                return [
                    '// ' . $label . ' (term ID)',
                    "{$var} = absint({$src}['{$sfid}'] ?? 0);",
                    "if ({$var}) {",
                    "    \$term = get_term({$var});",
                    "    if (\$term && ! is_wp_error(\$term)) {",
                    "        echo esc_html(\$term->name);",
                    "        \$link = get_term_link(\$term);",
                    "        if (! is_wp_error(\$link)) {",
                    "            echo esc_url(\$link);",
                    "        }",
                    '    }',
                    '}',
                ];
            case 'user_select':
                return [
                    '// ' . $label . ' (user ID)',
                    "{$var} = absint({$src}['{$sfid}'] ?? 0);",
                    "if ({$var}) {",
                    "    \$user = get_userdata({$var});",
                    "    if (\$user) {",
                    "        echo esc_html(\$user->display_name);",
                    "        echo esc_html(\$user->user_email);",
                    '    }',
                    '}',
                ];
            case 'url':
                return [
                    '// ' . $label,
                    "{$var} = esc_url({$src}['{$sfid}'] ?? '');",
                    "if ({$var}) {",
                    "    echo '<a href=\"' . {$var} . '\">' . {$var} . '</a>';",
                    '}',
                ];
            case 'email':
                return [
                    '// ' . $label,
                    "{$var} = sanitize_email({$src}['{$sfid}'] ?? '');",
                    "if (is_email({$var})) {",
                    "    echo '<a href=\"mailto:' . esc_attr({$var}) . '\">' . esc_html({$var}) . '</a>';",
                    '}',
                ];
            case 'tel':
                return [
                    '// ' . $label,
                    "{$var} = esc_html({$src}['{$sfid}'] ?? '');",
                    "if ({$var}) {",
                    "    \$tel = preg_replace('/[^0-9+]/', '', {$var});",
                    "    echo '<a href=\"tel:' . esc_attr(\$tel) . '\">' . {$var} . '</a>';",
                    '}',
                ];
            case 'date':
                return [
                    '// ' . $label . ' (stored as Y-m-d)',
                    "{$var} = {$src}['{$sfid}'] ?? '';",
                    "if ({$var}) {",
                    "    echo esc_html(wp_date(get_option('date_format'), strtotime({$var})));",
                    '}',
                ];
            case 'datetime':
                return [
                    '// ' . $label . ' (stored as Y-m-d H:i)',
                    "{$var} = {$src}['{$sfid}'] ?? '';",
                    "if ({$var}) {",
                    "    \$fmt = get_option('date_format') . ' ' . get_option('time_format');",
                    "    echo esc_html(wp_date(\$fmt, strtotime({$var})));",
                    '}',
                ];
            case 'time':
                return [
                    '// ' . $label . ' (stored as H:i)',
                    "{$var} = {$src}['{$sfid}'] ?? '';",
                    "if ({$var}) {",
                    "    echo esc_html(wp_date(get_option('time_format'), strtotime('today ' . {$var})));",
                    '}',
                ];
            default:
                // text, select, radio, hidden, etc.
                return [
                    '// ' . $label,
                    "echo esc_html({$src}['{$sfid}'] ?? '');",
                ];
        }
    }

    /**
     * Raw version: direct access path only, no variable declaration or HTML output.
     *
     * @param  array<string, mixed> $field Field data from the Registry.
     * @return list<string>
     */
    private static function fieldLinesRaw(string $fid, array $field, string $src = '$group'): array
    {
        $type  = (string) ($field['type'] ?? 'text');
        $label = (string) ($field['label'] ?? $fid);
        $sfid  = str_replace("'", "\\'", $fid);

        switch ($type) {
            case 'image':
                return [
                    '// ' . $label,
                    "{$src}['{$sfid}'] ?? [];",
                    "// ['url'], ['alt'], ['id'], ['width'], ['height']",
                    "// ['sizes']['thumbnail']['url'], ['medium']['url'], ['large']['url']",
                ];
            case 'gallery':
                return [
                    '// ' . $label . ' (array of images)',
                    "{$src}['{$sfid}'] ?? [];",
                    "// each: ['url'], ['alt'], ['id'], ['sizes']['thumbnail|medium|large']['url']",
                ];
            case 'file':
                return [
                    '// ' . $label,
                    "{$src}['{$sfid}'] ?? [];",
                    "// ['url'], ['title'], ['id'], ['mime_type']",
                ];
            case 'link':
                return [
                    '// ' . $label,
                    "{$src}['{$sfid}'] ?? [];",
                    "// ['url'], ['text'], ['target'] (bool — true = _blank)",
                ];
            case 'checkboxes':
            case 'multi_select':
                return [
                    '// ' . $label . ' (array of values)',
                    "{$src}['{$sfid}'] ?? [];",
                ];
            case 'post_checkboxes':
                return [
                    '// ' . $label . ' (array of post IDs)',
                    "{$src}['{$sfid}'] ?? [];",
                ];
            case 'term_checkboxes':
                return [
                    '// ' . $label . ' (array of term IDs)',
                    "{$src}['{$sfid}'] ?? [];",
                ];
            case 'user_checkboxes':
                return [
                    '// ' . $label . ' (array of user IDs)',
                    "{$src}['{$sfid}'] ?? [];",
                ];
            case 'yesno':
            case 'toggle':
            case 'checkbox':
                return [
                    '// ' . $label . ' (bool)',
                    "! empty({$src}['{$sfid}']);",
                ];
            case 'post_select':
                return [
                    '// ' . $label . ' (post ID)',
                    "absint({$src}['{$sfid}'] ?? 0);",
                ];
            case 'term_select':
                return [
                    '// ' . $label . ' (term ID)',
                    "absint({$src}['{$sfid}'] ?? 0);",
                ];
            case 'user_select':
                return [
                    '// ' . $label . ' (user ID)',
                    "absint({$src}['{$sfid}'] ?? 0);",
                ];
            case 'number':
            case 'range':
                return [
                    '// ' . $label . ' (int)',
                    "intval({$src}['{$sfid}'] ?? 0);",
                ];
            default:
                // text, textarea, wysiwyg, color, url, email, tel, date, datetime, time, select, radio, hidden
                return [
                    '// ' . $label,
                    "{$src}['{$sfid}'] ?? '';",
                ];
        }
    }

    private static function codeModal(): void
    {
        ?>
        <div id="cfdev-code-modal" class="cfdev-modal" hidden aria-modal="true" role="dialog"
             aria-labelledby="cfdev-code-modal-title">
            <div class="cfdev-modal-overlay"></div>
            <div class="cfdev-modal-box cfdev-modal-box--code">

                <div class="cfdev-modal-header">
                    <h2 class="cfdev-modal-title" id="cfdev-code-modal-title">
                        &lt;/&gt; <?php esc_html_e('Code', 'cfdev'); ?>
                        <code id="cfdev-code-group-id" class="cfdev-modal-group-id"></code>
                    </h2>
                    <div class="cfdev-code-tabs" role="tablist">
                        <button type="button" id="cfdev-code-tab-display"
                                class="cfdev-code-tab is-active" role="tab">
                            <?php esc_html_e('Display', 'cfdev'); ?>
                        </button>
                        <button type="button" id="cfdev-code-tab-raw"
                                class="cfdev-code-tab" role="tab">
                            <?php esc_html_e('Raw', 'cfdev'); ?>
                        </button>
                    </div>
                    <button type="button" id="cfdev-code-copy" class="button button-small cfdev-btn-regen">
                        &#x2398; <?php esc_html_e('Copy', 'cfdev'); ?>
                    </button>
                    <button type="button" class="cfdev-modal-close"
                            aria-label="<?php esc_attr_e('Close', 'cfdev'); ?>">&#x2715;</button>
                </div>

                <div class="cfdev-code-body">
                    <pre id="cfdev-code-pre" class="cfdev-code-pre"><code id="cfdev-code-output"></code></pre>
                </div>

            </div>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------

    private static function inspectModal(): void
    {
        ?>
        <div id="cfdev-inspect-modal" class="cfdev-modal" hidden aria-modal="true" role="dialog"
             aria-labelledby="cfdev-modal-title">
            <div class="cfdev-modal-overlay"></div>
            <div class="cfdev-modal-box">

                <div class="cfdev-modal-header">
                    <h2 class="cfdev-modal-title" id="cfdev-modal-title">
                        <span class="cfdev-modal-meta-label"></span>
                        <code class="cfdev-modal-group-id"></code>
                    </h2>
                    <span id="cfdev-inspect-cache-badge" class="cfdev-cache-badge" hidden></span>
                    <button type="button" id="cfdev-inspect-force" class="button button-small cfdev-btn-regen">
                        &#x21BA; <?php esc_html_e('Regenerate', 'cfdev'); ?>
                    </button>
                    <button type="button" class="cfdev-modal-close"
                            aria-label="<?php esc_attr_e('Close', 'cfdev'); ?>">&#x2715;</button>
                </div>

                <div id="cfdev-inspect-toolbar" class="cfdev-inspect-toolbar" hidden>
                    <select id="cfdev-object-select" class="cfdev-object-select">
                        <option value="0"><?php esc_html_e('— select —', 'cfdev'); ?></option>
                    </select>
                </div>

                <div id="cfdev-inspect-output" class="cfdev-inspect-output">
                    <p class="cfdev-inspect-hint"><?php esc_html_e('Loading…', 'cfdev'); ?></p>
                </div>

            </div>
        </div>
        <?php
    }
}
