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
                <p><strong><?php esc_html_e('IDs de champs dupliqués :', 'cfdev'); ?></strong></p>
                <ul>
                    <?php foreach ($dups as $field_id => $boxes) : ?>
                    <li>
                        <code><?php echo esc_html($field_id); ?></code>
                        <?php esc_html_e('déclaré dans', 'cfdev'); ?>
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
                    <?php esc_html_e('Utilisateurs', 'cfdev'); ?>
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
        self::scripts();
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
            echo '<p class="cfdev-empty">' . esc_html__('Aucun groupe déclaré.', 'cfdev') . '</p>';
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
                            <p class="cfdev-empty cfdev-empty--body"><?php esc_html_e('Aucun champ.', 'cfdev'); ?></p>
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
                        <p class="cfdev-empty cfdev-empty--body"><?php esc_html_e('Aucun champ.', 'cfdev'); ?></p>
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
                    <th><?php esc_html_e('Requis', 'cfdev'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field_id => $field) : ?>
                    <?php $is_dup = in_array($field_id, $dup_ids, true); ?>
                <tr<?php echo $is_dup ? ' class="cfdev-dup"' : ''; ?>>
                    <td>
                        <code><?php echo esc_html($field_id); ?></code>
                        <?php if ($is_dup) : ?>
                        <span class="cfdev-dup-badge" title="<?php esc_attr_e('ID dupliqué', 'cfdev'); ?>">⚠</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo self::typeBadge($field['type']);
                        ?>
                    </td>
                    <td><?php echo esc_html($field['label']); ?></td>
                    <td>
                        <?php if ($field['required']) : ?>
                        <span class="cfdev-required-check" aria-label="<?php esc_attr_e('requis', 'cfdev'); ?>">✓</span>
                        <?php endif; ?>
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
            'accordion' => __('Accordéon', 'cfdev'),
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

    private static function scripts(): void
    {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '
<script id="cfdev-registry-js">
(function () {
    // Tab switching
    var tabs   = document.querySelectorAll(".cfdev-tabs-nav .nav-tab");
    var panels = document.querySelectorAll(".cfdev-tab-panel");

    tabs.forEach(function (tab) {
        tab.addEventListener("click", function (e) {
            e.preventDefault();
            var target = this.getAttribute("href");
            panels.forEach(function (p) { p.hidden = true; });
            document.querySelector(target).hidden = false;
            tabs.forEach(function (t) { t.classList.remove("nav-tab-active"); });
            this.classList.add("nav-tab-active");
        });
    });

    // Group expand / collapse
    document.querySelectorAll(".cfdev-group-header").forEach(function (header) {
        function toggle() {
            var group  = header.closest(".cfdev-group");
            var body   = group.querySelector(".cfdev-group-body");
            var isOpen = !body.hidden;
            body.hidden = isOpen;
            group.classList.toggle("is-open", !isOpen);
            header.setAttribute("aria-expanded", String(!isOpen));
        }
        header.addEventListener("click", toggle);
        header.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggle(); }
        });
    });
}());
</script>';
        // phpcs:enable
    }
}
