<?php

namespace Weblitzer\CFDev\Admin;

use Weblitzer\CFDev\Cache\CacheManager;
use Weblitzer\CFDev\Cache\CacheStore;
use Weblitzer\CFDev\Registry;

/**
 * CFDev — Cache admin page.
 * Lists cached .tmp files and provides flush actions.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.0
 */
final class CachePage extends AdminPage
{
    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        // Save cache option
        if (
            isset($_POST['cfdev_cache_option_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_cache_option_nonce'])), 'cfdev_cache_option')
        ) {
            update_option(SettingsPage::OPTION_CACHE, isset($_POST['cfdev_cache_enabled']) ? '1' : '0');
            add_settings_error('cfdev_cache', 'saved', __('Réglage enregistré.', 'cfdev'), 'success');
        }

        // Handle flush action
        $flushed = null;
        if (
            isset($_POST['cfdev_cache_action'], $_POST['cfdev_cache_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cfdev_cache_nonce'])), 'cfdev_cache_flush')
        ) {
            $action  = sanitize_text_field(wp_unslash($_POST['cfdev_cache_action']));
            $manager = new CacheManager();

            if ($action === 'flush_all') {
                $flushed = $manager->invalidateAll();
            } elseif ($action === 'flush_one' && isset($_POST['cfdev_cache_key'])) {
                $key = sanitize_text_field(wp_unslash($_POST['cfdev_cache_key']));
                $manager->store()->delete($key);
                $flushed = 1;
            }
        }

        $cache_on = (bool) get_option(SettingsPage::OPTION_CACHE, false);
        $store    = (new CacheManager())->store();
        $files    = $store->listAll();
        $registry = array_column(Registry::all(), null, 'id');

        self::baseStyles();
        self::pageStyles();
        settings_errors('cfdev_cache');
        ?>
        <div class="wrap">
            <?php self::header(__('Cache', 'cfdev'), sprintf(
                // translators: %d = number of cached files
                _n('%d fichier', '%d fichiers', count($files), 'cfdev'),
                count($files)
            )); ?>

            <?php if ($flushed !== null) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html(sprintf(
                    // translators: %d = number of deleted files
                    _n('%d fichier supprimé.', '%d fichiers supprimés.', $flushed, 'cfdev'),
                    $flushed
                )); ?></p>
            </div>
            <?php endif; ?>

            <!-- Cache toggle -->
            <div class="cfdev-cache-option">
                <form method="post" class="cfdev-cache-option__form">
                    <?php wp_nonce_field('cfdev_cache_option', 'cfdev_cache_option_nonce'); ?>
                    <label class="cfdev-toggle-wrap" for="cfdev_cache_enabled">
                        <input type="checkbox"
                               id="cfdev_cache_enabled"
                               name="cfdev_cache_enabled"
                               value="1"
                               onchange="this.form.submit()"
                               <?php checked($cache_on); ?>>
                        <span class="cfdev-toggle-slider"></span>
                    </label>
                    <span class="cfdev-cache-option__label">
                        <?php if ($cache_on) : ?>
                            <strong><?php esc_html_e('Cache actif', 'cfdev'); ?></strong>
                            <span class="cfdev-cache-option__hint"><?php esc_html_e('— les données sont lues et écrites depuis les fichiers .tmp', 'cfdev'); ?></span>
                        <?php else : ?>
                            <strong><?php esc_html_e('Cache inactif', 'cfdev'); ?></strong>
                            <span class="cfdev-cache-option__hint"><?php esc_html_e('— données toujours lues en direct depuis la base (recommandé en développement)', 'cfdev'); ?></span>
                        <?php endif; ?>
                    </span>
                </form>
            </div>

            <!-- Toolbar -->
            <div class="cfdev-cache-toolbar">
                <form method="post">
                    <?php wp_nonce_field('cfdev_cache_flush', 'cfdev_cache_nonce'); ?>
                    <input type="hidden" name="cfdev_cache_action" value="flush_all">
                    <button type="submit" class="button button-secondary cfdev-btn-flush"
                            <?php echo empty($files) ? 'disabled' : ''; ?>>
                        <?php esc_html_e('Vider tout le cache', 'cfdev'); ?>
                    </button>
                </form>
                <span class="cfdev-cache-dir">
                    <code><?php echo esc_html($store->dir()); ?></code>
                </span>
            </div>

            <?php if (empty($files)) : ?>
                <?php self::placeholder(__('Aucun fichier en cache.', 'cfdev')); ?>
            <?php else : ?>
            <table class="cfdev-cache-table widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Objet', 'cfdev'); ?></th>
                        <th><?php esc_html_e('Type', 'cfdev'); ?></th>
                        <th><?php esc_html_e('Groupes', 'cfdev'); ?></th>
                        <th><?php esc_html_e('Taille', 'cfdev'); ?></th>
                        <th><?php esc_html_e('Âge', 'cfdev'); ?></th>
                        <th><?php esc_html_e('Modifié', 'cfdev'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file) :
                        $info  = self::objectInfo($file, $store, $registry);
                        $stale = $file['age'] > CacheManager::TTL;
                        ?>
                    <tr class="<?php echo $stale ? 'cfdev-stale' : ''; ?>">
                        <td>
                            <span class="cfdev-object-label"><?php echo esc_html($info['label']); ?></span>
                            <span class="cfdev-object-key"><code><?php echo esc_html($file['key']); ?>.tmp</code></span>
                            <?php if ($stale) : ?>
                            <span class="cfdev-badge-stale"><?php esc_html_e('Expiré', 'cfdev'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cfdev-type-pill cfdev-type-pill--<?php echo esc_attr($info['object_type']); ?>">
                                <?php echo esc_html($info['subtype']); ?>
                            </span>
                        </td>
                        <td class="cfdev-groups-cell">
                            <?php if (empty($info['groups'])) : ?>
                                <span class="cfdev-no-groups">—</span>
                            <?php else : ?>
                                <?php foreach ($info['groups'] as $group_title) : ?>
                                <span class="cfdev-group-tag"><?php echo esc_html($group_title); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(self::formatSize($file['size'])); ?></td>
                        <td><?php echo esc_html(self::formatAge($file['age'])); ?></td>
                        <td><?php echo esc_html((string) wp_date('d/m/Y H:i', $file['modified'])); ?></td>
                        <td>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('cfdev_cache_flush', 'cfdev_cache_nonce'); ?>
                                <input type="hidden" name="cfdev_cache_action" value="flush_one">
                                <input type="hidden" name="cfdev_cache_key" value="<?php echo esc_attr($file['key']); ?>">
                                <button type="submit" class="button button-link cfdev-btn-del">
                                    <?php esc_html_e('Supprimer', 'cfdev'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Object info resolution
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>         $file     Entry from CacheStore::listAll()
     * @param  array<string, array<string,mixed>> $registry Registry::all() indexed by id
     * @return array{object_type:string, subtype:string, id:int, label:string, groups:list<string>}
     */
    private static function objectInfo(array $file, CacheStore $store, array $registry): array
    {
        $key  = $file['key'];
        $data = $store->read($key) ?? [];

        $groups = [];
        foreach (array_keys($data['groups'] ?? []) as $group_id) {
            $groups[] = $registry[$group_id]['title'] ?? $group_id;
        }

        if (str_starts_with($key, 'post_')) {
            $id      = (int) substr($key, 5);
            $pt      = get_post_type($id) ?: 'post';
            $pto     = get_post_type_object($pt);
            $subtype = $pto ? $pto->labels->singular_name : ucfirst($pt);
            $label   = get_the_title($id) ?: '—';
            return ['object_type' => 'post', 'subtype' => $subtype, 'id' => $id, 'label' => $label, 'groups' => $groups];
        }

        if (str_starts_with($key, 'term_')) {
            $taxonomy = $data['taxonomy'] ?? '';
            $id       = (int) ($data['term_id'] ?? 0);
            $term     = $id && $taxonomy ? get_term($id, $taxonomy) : null;
            $tax_obj  = $taxonomy ? get_taxonomy($taxonomy) : null;
            $subtype  = $tax_obj ? $tax_obj->labels->singular_name : ($taxonomy ?: 'term');
            $label    = ($term && ! is_wp_error($term)) ? $term->name : '—';
            return ['object_type' => 'term', 'subtype' => $subtype, 'id' => $id, 'label' => $label, 'groups' => $groups];
        }

        if (str_starts_with($key, 'user_')) {
            $id   = (int) substr($key, 5);
            $user = get_userdata($id);
            return ['object_type' => 'user', 'subtype' => __('Utilisateur', 'cfdev'), 'id' => $id, 'label' => $user ? $user->display_name : '—', 'groups' => $groups];
        }

        return ['object_type' => 'autre', 'subtype' => 'autre', 'id' => 0, 'label' => $key, 'groups' => $groups];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' o';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' Ko';
        }
        return round($bytes / 1048576, 2) . ' Mo';
    }

    private static function formatAge(int $seconds): string
    {
        if ($seconds < 60) {
            return sprintf(
                // translators: %d = seconds
                _n('%d s', '%d s', $seconds, 'cfdev'),
                $seconds
            );
        }
        if ($seconds < 3600) {
            $m = (int) ($seconds / 60);
            return sprintf(
                // translators: %d = minutes
                _n('%d min', '%d min', $m, 'cfdev'),
                $m
            );
        }
        if ($seconds < 86400) {
            $h = (int) ($seconds / 3600);
            return sprintf(
                // translators: %d = hours
                _n('%d h', '%d h', $h, 'cfdev'),
                $h
            );
        }
        $d = (int) ($seconds / 86400);
        return sprintf(
            // translators: %d = days
            _n('%d j', '%d j', $d, 'cfdev'),
            $d
        );
    }

    private static function pageStyles(): void
    {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '
<style id="cfdev-cache-css">
/* Cache option toggle */
.cfdev-cache-option {
    display: flex; align-items: center;
    background: #fff; border: 1px solid #dcdcde;
    border-radius: 4px; padding: 14px 18px;
    margin-bottom: 18px; gap: 14px;
}
.cfdev-cache-option__form { display: flex; align-items: center; gap: 12px; margin: 0; }
.cfdev-cache-option__label { font-size: 13px; line-height: 1.5; }
.cfdev-cache-option__label strong { display: inline; }
.cfdev-cache-option__hint { color: #787c82; }

/* Toggle switch */
.cfdev-toggle-wrap {
    position: relative; display: inline-flex;
    align-items: center; cursor: pointer; flex-shrink: 0;
}
.cfdev-toggle-wrap input[type="checkbox"] {
    position: absolute; opacity: 0; width: 0; height: 0;
}
.cfdev-toggle-slider {
    width: 40px; height: 22px;
    background: #c3c4c7; border-radius: 22px;
    transition: background .2s;
}
.cfdev-toggle-slider::after {
    content: ""; position: absolute;
    width: 16px; height: 16px; border-radius: 50%;
    background: #fff; top: 3px; left: 3px;
    transition: transform .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,.3);
}
.cfdev-toggle-wrap input:checked + .cfdev-toggle-slider { background: #00a32a; }
.cfdev-toggle-wrap input:checked + .cfdev-toggle-slider::after { transform: translateX(18px); }

/* Toolbar */
.cfdev-cache-toolbar {
    display: flex; align-items: center; gap: 16px;
    margin-bottom: 16px;
}
.cfdev-cache-dir code {
    font-size: 11px; color: #787c82;
    background: #f6f7f7; padding: 2px 7px; border-radius: 3px;
}

/* Table */
.cfdev-cache-table { border-radius: 3px; overflow: hidden; }
.cfdev-cache-table thead th {
    font-size: 11px; text-transform: uppercase;
    letter-spacing: .5px; color: #50575e;
    background: #f6f7f7; font-weight: 600;
}
.cfdev-cache-table tbody tr.cfdev-stale td { background: #fff8f0; }
.cfdev-cache-table code { font-size: 11px; color: #787c82; }

/* Object cell */
.cfdev-object-label { display: block; font-weight: 600; font-size: 13px; color: #1d2327; }
.cfdev-object-key   { display: block; margin-top: 2px; }

/* Badges */
.cfdev-badge-stale {
    display: inline-block; font-size: 10px; font-weight: 700;
    background: #fef3cd; color: #7a5c00;
    border: 1px solid #f5d78e; padding: 1px 6px;
    border-radius: 10px; margin-left: 6px; vertical-align: middle;
}

/* Type pills */
.cfdev-type-pill {
    display: inline-block; font-size: 11px; font-weight: 600;
    padding: 2px 8px; border-radius: 10px; white-space: nowrap;
}
.cfdev-type-pill--post  { background: #e7f3ff; color: #0a4b78; }
.cfdev-type-pill--term  { background: #e8f5e9; color: #1b5e20; }
.cfdev-type-pill--user  { background: #f3e5f5; color: #4a148c; }
.cfdev-type-pill--autre { background: #f0f0f1; color: #50575e; }

/* Groups */
.cfdev-groups-cell { max-width: 260px; }
.cfdev-group-tag {
    display: inline-block; font-size: 11px;
    background: #f0f0f1; color: #3c434a;
    border: 1px solid #dcdcde; padding: 1px 7px;
    border-radius: 3px; margin: 1px 2px 1px 0;
}
.cfdev-no-groups { color: #c3c4c7; }

/* Buttons */
.cfdev-btn-del { color: #d63638 !important; padding: 0 !important; }
.cfdev-btn-del:hover { color: #8a2424 !important; }
.cfdev-btn-flush:disabled { opacity: .5; cursor: default; }
</style>';
        // phpcs:enable
    }
}
