<?php

/**
 * CFDev Demo — WooCommerce fields
 *
 * Loaded unconditionally by demo-fields.php; returns early if WooCommerce is
 * not active. Registers a MetaBox on 'product' and a TermMeta on 'product_cat'.
 */

if (! defined('WC_PLUGIN_FILE')) {
    return;
}

// Force the classic product editor so CFDev metaboxes are visible.
// WooCommerce 8+ enables a block-based product editor by default which
// does not support classic metaboxes. Remove this filter if you are
// intentionally using the WC block editor and do not need CFDev fields
// on products (product_cat / product_tag TermMeta are unaffected).
add_filter('woocommerce_admin_features', static function (array $features): array {
    $features['product-block-editor'] = false;
    return $features;
});

// ── Product MetaBox ───────────────────────────────────────────────────────────

new \Weblitzer\CFDev\Meta\MetaBox(
    'cfdev_wc_product',
    '[CFDev] Infos produit',
    'product',
    [
        ['id' => 'cfdev_wc_badge',     'type' => 'text',     'label' => 'Badge personnalisé',
         'description' => 'Ex : Nouveau, Promo, Exclusif'],
        ['id' => 'cfdev_wc_note',      'type' => 'textarea', 'label' => 'Note promotionnelle'],
        ['id' => 'cfdev_wc_highlight', 'type' => 'toggle',   'label' => 'Mettre en avant'],
        ['id' => 'cfdev_wc_priority',  'type' => 'select',   'label' => 'Priorité',
         'options' => ['normal' => 'Normale', 'high' => 'Haute', 'urgent' => 'Urgente'],
         'args'    => ['show_option_none' => '-- Choisir --']],
    ]
);

// ── Product category TermMeta ────────────────────────────────────────────────

new \Weblitzer\CFDev\Meta\TermMeta(
    'product_cat',
    '[CFDev] Catégorie produit',
    [
        ['id' => 'cfdev_wc_cat_banner', 'type' => 'text', 'label' => 'Texte bannière catégorie'],
    ]
);
