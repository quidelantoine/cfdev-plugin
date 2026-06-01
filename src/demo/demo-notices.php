<?php

/**
 * CFDev Demo — Notices showcase
 *
 * Déclenche intentionnellement chaque type de notice du back-office pour
 * vérifier leur design visuel en une seule session de navigation.
 *
 * Activation (wp-config.php ou .env équivalent) :
 *   define('CFDEV_DEMO',         true);   // déjà requis
 *   define('CFDEV_DEMO_NOTICES', true);   // opt-in explicite
 *
 * ┌────────────────────────────────────────────────────────────────────────┐
 * │  Notice A — Terme de taxonomie réservé                                 │
 * │      Type    : notice-error (non dismissible)                          │
 * │      Visible : toutes les pages admin                                  │
 * │      URL     : n'importe quelle page /wp-admin/                        │
 * ├────────────────────────────────────────────────────────────────────────┤
 * │  Notice B — Classic Editor manquant                                    │
 * │      Type    : notice-warning is-dismissible                           │
 * │      Visible : édition de post + pages CFDev                           │
 * │      URL     : auto (sans Classic Editor actif)                        │
 * ├────────────────────────────────────────────────────────────────────────┤
 * │  Notices C–G — Erreurs de configuration                                │
 * │      Type    : notice-error ou notice-warning (dashboard uniquement)   │
 * │      Visible : Dashboard CFDev                                         │
 * │      URL     : /wp-admin/admin.php?page=cfdev                          │
 * │        C. IDs dupliqués dans la même meta box  → notice-error          │
 * │        D. Bundle ID dupliqué entre meta boxes  → notice-error          │
 * │        E. Clés meta réservées WordPress        → notice-error          │
 * │        F. Meta box ID dupliqué                 → notice-error          │
 * │        G. Field ID dupliqué entre meta boxes   → notice-warning        │
 * ├────────────────────────────────────────────────────────────────────────┤
 * │  Notice H — Erreurs de validation                                      │
 * │      Type    : notice-error is-dismissible, liste de liens             │
 * │      Visible : édition de post après Save                              │
 * │      URL     : créer un "[DEMO] Notice Test", publier sans remplir     │
 * │                les champs → la notice apparaît avec 4 + 2 liens        │
 * └────────────────────────────────────────────────────────────────────────┘
 */

if (! defined('CFDEV_DEMO_NOTICES') || ! CFDEV_DEMO_NOTICES) {
    return;
}

use Weblitzer\CFDev\Validation\Rules\Email;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\Required;

// ── CPT isolé ────────────────────────────────────────────────────────────────
// Toutes les meta boxes défectueuses ciblent ce CPT pour ne pas polluer
// post, page ou les autres CPTs de la démo réelle.
$nt = register_cfdev_post_type('cfdev_nt', [
    'label'       => '[DEMO] Notice Test',
    'public'      => true,
    'supports'    => ['title'],
    'menu_icon'   => 'dashicons-bell',
    'menu_position' => 40,
    'rewrite'     => ['slug' => 'cfdev-notice-test'],
], [
    'name'          => '[DEMO] Notice Tests',
    'singular_name' => '[DEMO] Notice Test',
    'add_new_item'  => 'Ajouter un Notice Test',
    'edit_item'     => 'Éditer le Notice Test',
    'all_items'     => 'Tous les Notice Tests',
]);

if (null === $nt) {
    return;
}

// ════════════════════════════════════════════════════════════════════════════
// A — Terme de taxonomie réservé
// → notice-error sur admin_notices (toutes les pages admin)
// Déclencheur : Taxonomy::__construct() détecte WPValidator::isReservedTerm('post')
// ════════════════════════════════════════════════════════════════════════════
register_cfdev_taxonomy('post', 'cfdev_nt', [], [
    'name'          => '[DEMO] Terme réservé',
    'singular_name' => '[DEMO] Terme réservé',
]);

// ════════════════════════════════════════════════════════════════════════════
// C — IDs dupliqués dans la même meta box (intra-box)
// → notice-error Dashboard, "Duplicate field IDs within the same meta box"
// Déclencheur : Meta::trackFieldWarning() détecte $this->fields[$id] déjà défini
// ════════════════════════════════════════════════════════════════════════════
$nt->addMetaBox('cfdev_nt_intra_dup', '[DEMO] C — Intra-box dup', [
    ['id' => '_nt_intra_dup', 'type' => 'text',  'label' => '1ʳᵉ déclaration (text)'],
    ['id' => '_nt_intra_dup', 'type' => 'email', 'label' => '2ᵉ déclaration même ID → écrase la 1ʳᵉ'],
]);

// ════════════════════════════════════════════════════════════════════════════
// D — Bundle ID partagé entre deux meta boxes
// → notice-error Dashboard, "Duplicate bundle IDs across meta boxes"
// Déclencheur : Registry::duplicateBundleIds() trouve > 1 occurrence de '_nt_shared_bundle'
//               sur le même post type → les saves s'écrasent mutuellement
// ════════════════════════════════════════════════════════════════════════════
$nt->addMetaBox('cfdev_nt_bundle_a', '[DEMO] D1 — Bundle (ID partagé)', [
    'bundle', '_nt_shared_bundle',
    [
        ['id' => '_nt_ba_text', 'type' => 'text', 'label' => 'Texte (bundle A)'],
    ],
]);
$nt->addMetaBox('cfdev_nt_bundle_b', '[DEMO] D2 — Bundle (même ID que D1)', [
    'bundle', '_nt_shared_bundle',
    [
        ['id' => '_nt_bb_text', 'type' => 'text', 'label' => 'Texte (bundle B)'],
    ],
]);

// ════════════════════════════════════════════════════════════════════════════
// E — Clés meta réservées WordPress
// → notice-error Dashboard, "Reserved WordPress meta keys used as field IDs"
// Déclencheur : Registry::reservedFieldIds() trouve ces IDs dans WP_RESERVED_META_KEYS
// ════════════════════════════════════════════════════════════════════════════
$nt->addMetaBox('cfdev_nt_reserved', '[DEMO] E — Clés réservées', [
    ['id' => '_thumbnail_id',    'type' => 'text', 'label' => '_thumbnail_id (image mise en avant)'],
    ['id' => '_wp_page_template','type' => 'text', 'label' => '_wp_page_template (template de page)'],
]);

// ════════════════════════════════════════════════════════════════════════════
// F — Meta box ID dupliqué
// → notice-error Dashboard, "Duplicate meta box IDs"
// Déclencheur : Registry::duplicateMetaBoxIds() trouve 'cfdev_nt_dup_box' deux fois
//               WP ne conserve que la dernière registration ; F1 disparaît
// ════════════════════════════════════════════════════════════════════════════
$nt->addMetaBox('cfdev_nt_dup_box', '[DEMO] F1 — Première meta box (ID dupliqué)', [
    ['id' => '_nt_f1', 'type' => 'text', 'label' => 'Champ F1 (invisible — écrasé par F2)'],
]);
$nt->addMetaBox('cfdev_nt_dup_box', '[DEMO] F2 — Deuxième meta box (même ID)', [
    ['id' => '_nt_f2', 'type' => 'text', 'label' => 'Champ F2 (seul visible dans WP)'],
]);

// ════════════════════════════════════════════════════════════════════════════
// G — Field ID dupliqué entre meta boxes différentes (cross-box)
// → notice-warning Dashboard, "Duplicate field IDs"
// Déclencheur : Registry::duplicates() trouve '_nt_cross' dans G1 et G2
//               Les deux meta boxes lisent/écrivent la même meta key
// ════════════════════════════════════════════════════════════════════════════
$nt->addMetaBox('cfdev_nt_cross_a', '[DEMO] G1 — Cross-box dup (A)', [
    ['id' => '_nt_cross', 'type' => 'text', 'label' => 'Champ partagé (aussi dans G2)'],
]);
$nt->addMetaBox('cfdev_nt_cross_b', '[DEMO] G2 — Cross-box dup (B)', [
    ['id' => '_nt_cross', 'type' => 'text', 'label' => 'Champ partagé (aussi dans G1)'],
]);

// ════════════════════════════════════════════════════════════════════════════
// H — Erreurs de validation (champs plats + bundle)
// → notice-error is-dismissible sur la page d'édition, après Save
//
// Scénario de test :
//   1. Aller dans [DEMO] Notice Tests > Ajouter
//   2. Remplir uniquement le Titre du post, laisser tous les champs cfdev vides
//   3. Cliquer "Publier / Mettre à jour"
//   4. La notice apparaît avec 6 liens :
//      - 4 erreurs champs plats (text + email + number + textarea)
//      - 2 erreurs bundle row 0 (text + email, clé "_cfdev_nt_vbundle.0._nt_vb_*")
// ════════════════════════════════════════════════════════════════════════════
$nt->addMetaBox('cfdev_nt_validation', '[DEMO] H — Validation (champs plats)', [
    ['id' => '_nt_v_text',  'type' => 'text',     'label' => 'Titre (requis, ≥ 3 car.)',
     'rules' => [new Required(), new MinLength(3)]],
    ['id' => '_nt_v_email', 'type' => 'email',    'label' => 'E-mail (requis, format valide)',
     'rules' => [new Required(), new Email()]],
    ['id' => '_nt_v_qty',   'type' => 'number',   'label' => 'Quantité (requis, 1–99)',
     'rules' => [new Required(), new Min(1), new Max(99)]],
    ['id' => '_nt_v_desc',  'type' => 'textarea', 'label' => 'Description (requis, ≥ 10 car.)',
     'rules' => [new Required(), new MinLength(10)]],
]);

$nt->addMetaBox('cfdev_nt_validation_bundle', '[DEMO] H — Validation (bundle rows)', [
    'bundle', '_cfdev_nt_vbundle',
    [
        ['id' => '_nt_vb_text',  'type' => 'text',  'label' => 'Texte bundle (requis)',
         'rules' => [new Required()]],
        ['id' => '_nt_vb_email', 'type' => 'email', 'label' => 'E-mail bundle (requis)',
         'rules' => [new Required(), new Email()]],
    ],
]);