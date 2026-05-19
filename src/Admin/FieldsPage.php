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

        self::styles();
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

    // -------------------------------------------------------------------------
    // Assets (inlined — admin page only)
    // -------------------------------------------------------------------------

    private static function styles(): void
    {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '
<style id="cfdev-registry-css">
.cfdev-registry { max-width: 1200px; }

/* ── Header ─────────────────────────────────────────────────────── */
.cfdev-header {
    display: flex; align-items: center; gap: 12px;
    margin: 20px 0 16px;
}
.cfdev-header__title {
    margin: 0; font-size: 23px;
    display: flex; align-items: center; gap: 10px;
}
.cfdev-logo {
    background: #2271b1; color: #fff;
    font-size: 12px; font-weight: 800;
    padding: 4px 8px; border-radius: 4px; letter-spacing: -.5px;
}
.cfdev-header__count {
    font-size: 13px; color: #787c82;
    background: #f6f7f7; border: 1px solid #c3c4c7;
    padding: 2px 10px; border-radius: 10px;
}
.cfdev-header__dups {
    font-size: 13px; color: #8a2424;
    background: #fcf0f1; border: 1px solid #f5c5c6;
    padding: 2px 10px; border-radius: 10px;
}
.cfdev-notice-dups { margin-top: 0; }
.cfdev-notice-dups code { background: #f6f7f7; padding: 1px 5px; border-radius: 3px; }
.cfdev-notice-dups ul  { margin: 4px 0 0 20px; }

/* ── Tabs ────────────────────────────────────────────────────────── */
.cfdev-tabs-nav { margin-bottom: 0; border-bottom: 1px solid #c3c4c7; }
.cfdev-tab-count {
    display: inline-block; background: #dcdcde;
    border-radius: 10px; font-size: 11px; font-weight: 600;
    padding: 1px 7px; margin-left: 5px; vertical-align: middle; color: #1d2327;
}
.nav-tab-active .cfdev-tab-count { background: #2271b1; color: #fff; }
.cfdev-tab-panel { padding-top: 10px; }

.cfdev-empty {
    color: #787c82; font-style: italic;
    padding: 24px 0;
}
.cfdev-empty--body { padding: 12px 16px; margin: 0; }

/* ── Group card ──────────────────────────────────────────────────── */
.cfdev-group {
    background: #fff; border: 1px solid #c3c4c7;
    border-radius: 3px; margin-bottom: 2px;
    transition: box-shadow .1s ease;
}
.cfdev-group:hover { box-shadow: 0 1px 4px rgba(0,0,0,.07); }
.cfdev-group.is-open { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }

.cfdev-group-header {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: 8px; padding: 11px 16px;
    cursor: pointer; user-select: none;
    outline: none;
}
.cfdev-group-header:hover  { background: #f6f7f7; }
.cfdev-group-header:focus  { box-shadow: inset 0 0 0 2px #2271b1; }
.cfdev-group.is-open .cfdev-group-header { background: #f0f6fc; }

.cfdev-toggle-icon {
    font-size: 9px; color: #787c82; width: 14px; flex-shrink: 0;
    transition: transform .15s ease;
}
.cfdev-group.is-open .cfdev-toggle-icon { transform: rotate(90deg); }

.cfdev-group-title {
    font-weight: 600; font-size: 14px; color: #1d2327; min-width: 160px;
}
.cfdev-group-id {
    font-family: Consolas, monospace; font-size: 11px;
    color: #787c82; background: #f6f7f7;
    padding: 2px 7px; border-radius: 3px;
}

/* ── Layout badges ───────────────────────────────────────────────── */
.cfdev-badge {
    font-size: 10px; font-weight: 700; padding: 2px 8px;
    border-radius: 10px; text-transform: uppercase; letter-spacing: .4px;
}
.cfdev-badge--flat      { background: #f0f0f1; color: #50575e; }
.cfdev-badge--tabs      { background: #d0e8f5; color: #135e97; }
.cfdev-badge--accordion { background: #e8d5f5; color: #6b21a8; }
.cfdev-badge--bundle    { background: #d1fae5; color: #065f46; }

/* ── Targets ─────────────────────────────────────────────────────── */
.cfdev-targets { display: flex; gap: 4px; flex-wrap: wrap; }
.cfdev-target {
    font-size: 11px; background: #e7f3ff; color: #0a4b78;
    border: 1px solid #b3d7f5; padding: 1px 7px; border-radius: 3px;
}

/* ── Also-in (multi post-type groups) ───────────────────────────── */
.cfdev-also-in {
    display: flex; align-items: center; gap: 4px;
    font-size: 11px; color: #787c82;
}
.cfdev-also-in__tag {
    background: #f0f0f1; color: #50575e;
    border: 1px solid #dcdcde;
    padding: 1px 7px; border-radius: 3px; font-size: 11px;
}

/* ── Conditions ──────────────────────────────────────────────────── */
.cfdev-conditions { display: flex; gap: 4px; flex-wrap: wrap; }
.cfdev-condition-badge {
    font-size: 11px; background: #fff8e5; color: #7a5c00;
    border: 1px solid #f5e07a; padding: 1px 8px; border-radius: 3px;
}

/* ── Field count ─────────────────────────────────────────────────── */
.cfdev-field-count { margin-left: auto; font-size: 12px; color: #787c82; }

/* ── Group body ──────────────────────────────────────────────────── */
.cfdev-group-body { border-top: 1px solid #f0f0f1; }

/* ── Fields table ────────────────────────────────────────────────── */
.cfdev-fields-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cfdev-fields-table thead th {
    background: #f6f7f7; border-bottom: 1px solid #e0e0e0;
    padding: 7px 14px; text-align: left;
    font-weight: 600; font-size: 10px;
    text-transform: uppercase; letter-spacing: .6px; color: #50575e;
}
.cfdev-fields-table thead th:first-child { width: 30%; }
.cfdev-fields-table thead th:nth-child(2) { width: 14%; }
.cfdev-fields-table thead th:last-child   { width: 70px; text-align: center; }
.cfdev-fields-table tbody td {
    padding: 8px 14px; border-bottom: 1px solid #f0f0f1; vertical-align: middle;
}
.cfdev-fields-table tbody tr:last-child td { border-bottom: none; }
.cfdev-fields-table tbody tr:hover td { background: #fafafa; }
.cfdev-fields-table tbody tr.cfdev-dup td { background: #fff8f8; }
.cfdev-fields-table tbody td:last-child   { text-align: center; }
.cfdev-fields-table code {
    font-family: Consolas, monospace; font-size: 12px;
    background: #f6f7f7; padding: 1px 5px; border-radius: 3px; color: #50575e;
}
.cfdev-dup-badge    { color: #d63638; margin-left: 5px; }
.cfdev-required-check { color: #00a32a; font-weight: 700; font-size: 15px; }

/* ── Type badges ─────────────────────────────────────────────────── */
.cfdev-type {
    display: inline-block; font-size: 11px;
    padding: 2px 7px; border-radius: 3px;
    background: #f0f0f1; color: #50575e;
}
.cfdev-type--text,.cfdev-type--textarea,.cfdev-type--wysiwyg,
.cfdev-type--email,.cfdev-type--url,.cfdev-type--tel,
.cfdev-type--number,.cfdev-type--range,.cfdev-type--hidden
    { background: #e7f3ff; color: #0a4b78; }
.cfdev-type--image,.cfdev-type--gallery,.cfdev-type--file
    { background: #e8f5e9; color: #1b5e20; }
.cfdev-type--select,.cfdev-type--multi_select,
.cfdev-type--checkboxes,.cfdev-type--radios,
.cfdev-type--yesno,.cfdev-type--toggle,.cfdev-type--checkbox
    { background: #fff3e0; color: #7a3e00; }
.cfdev-type--date,.cfdev-type--datetime,.cfdev-type--time
    { background: #f3e5f5; color: #4a148c; }
.cfdev-type--post_select,.cfdev-type--post_checkboxes,
.cfdev-type--term_select,.cfdev-type--term_checkboxes,
.cfdev-type--user_select,.cfdev-type--user_checkboxes
    { background: #fce4ec; color: #880e4f; }
.cfdev-type--color { background: #e8eaf6; color: #283593; }
.cfdev-type--link  { background: #e0f2f1; color: #004d40; }

/* ── Sections (tabs / accordion) ────────────────────────────────── */
.cfdev-section { border-top: 1px solid #f0f0f1; }
.cfdev-section:first-child { border-top: none; }
.cfdev-section-title {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 14px 6px;
    background: #f6f7f7;
    border-left: 3px solid #2271b1;
    font-size: 12px; font-weight: 600; color: #1d2327;
}
.cfdev-section-icon { color: #2271b1; font-size: 13px; }
.cfdev-section--accordion .cfdev-section-title { border-left-color: #6b21a8; }
.cfdev-section--accordion .cfdev-section-icon  { color: #6b21a8; }
.cfdev-section-bundle-ref {
    display: inline-flex; align-items: center; gap: 5px;
    margin-left: 6px; font-weight: 400; color: #065f46; font-size: 11px;
}
.cfdev-section-bundle-ref code {
    font-family: Consolas, monospace; font-size: 11px;
    background: #dcfce7; padding: 1px 6px; border-radius: 3px; color: #065f46;
}

/* ── Bundle sub-section ──────────────────────────────────────────── */
.cfdev-bundle { border-top: 2px solid #d1fae5; }
.cfdev-bundle-title {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 14px; background: #f0fdf4;
    font-size: 12px; font-weight: 600; color: #065f46;
}
.cfdev-bundle-title code {
    font-family: Consolas, monospace; font-size: 11px;
    background: #dcfce7; padding: 1px 6px; border-radius: 3px; color: #065f46;
}
</style>';
        // phpcs:enable
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
