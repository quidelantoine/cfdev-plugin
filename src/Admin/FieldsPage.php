<?php

namespace Weblitzer\CFDev\Admin;

use Weblitzer\CFDev\Registry;

/**
 * Read-only admin page listing all registered field groups from the Registry.
 * Accessible at Dashboard → CFDev.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class FieldsPage
{
    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $all  = Registry::all();
        $dups = Registry::duplicates();

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
                    <span class="cfdev-logo">CF</span>
                    <?php esc_html_e('Groupes de champs', 'cfdev'); ?>
                </h1>
                <span class="cfdev-header__count">
                    <?php echo esc_html(sprintf(
                        // translators: %d = number of groups
                        _n('%d groupe', '%d groupes', count($all), 'cfdev'),
                        count($all)
                    )); ?>
                </span>
                <?php if (! empty($dups)) : ?>
                <span class="cfdev-header__dups">
                    ⚠ <?php echo esc_html(sprintf(
                        // translators: %d = number of duplicate field IDs
                        _n('%d doublon', '%d doublons', count($dups), 'cfdev'),
                        count($dups)
                    )); ?>
                </span>
                <?php endif; ?>
            </div>

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
                    <?php esc_html_e('Termes', 'cfdev'); ?>
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
                <?php self::renderPanel($entries, $dups, $pt); ?>
            </div>
            <?php endforeach; ?>

            <div id="cfdev-tab-terms" class="cfdev-tab-panel"
                 <?php echo ($first_tab !== 'cfdev-tab-terms') ? 'hidden' : ''; ?>>
                <?php self::renderPanel($terms, $dups); ?>
            </div>
            <div id="cfdev-tab-users" class="cfdev-tab-panel"
                 <?php echo ($first_tab !== 'cfdev-tab-users') ? 'hidden' : ''; ?>>
                <?php self::renderPanel($users, $dups); ?>
            </div>

        </div>
        <?php
        self::inspectModal();
    }

    // -------------------------------------------------------------------------
    // Panels
    // -------------------------------------------------------------------------

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<string, array<string>>     $dups
     */
    private static function renderPanel(array $entries, array $dups, string $current_pt = ''): void
    {
        if (empty($entries)) {
            echo '<p class="cfdev-empty">' . esc_html__('No groups declared.', 'cfdev') . '</p>';
            return;
        }
        foreach ($entries as $entry) {
            self::renderGroup($entry, $dups, $current_pt);
        }
    }

    /**
     * @param array<string, mixed>         $entry
     * @param array<string, array<string>> $dups
     */
    private static function renderGroup(array $entry, array $dups, string $current_pt = ''): void
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
                    <?php esc_html_e('Aussi dans :', 'cfdev'); ?>
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
                        _n('%d champ', '%d champs', $total, 'cfdev'),
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
                    <?php esc_html_e('⚙ Inspecter', 'cfdev'); ?>
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
                        <span class="cfdev-rule-badge cfdev-rule-badge--required">requis</span>
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
            'roles'     => 'Rôle : ' . implode(', ', (array) $value),
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
                        'label' => $post->post_title !== '' ? $post->post_title : '(sans titre)',
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
                        <option value="0"><?php esc_html_e('— choisir —', 'cfdev'); ?></option>
                    </select>
                </div>

                <div id="cfdev-inspect-output" class="cfdev-inspect-output">
                    <p class="cfdev-inspect-hint"><?php esc_html_e('Chargement…', 'cfdev'); ?></p>
                </div>

            </div>
        </div>
        <?php
    }
}
