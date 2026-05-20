<?php

/**
 * CFDev Demo — CPT & Taxonomies personnalisés
 */

// ── CPT : Leçons ───────────────────────────────────────────────────────
$lessons = register_cfdev_post_type('lessons', [
    'public'          => true,
    'capability_type' => 'post',
    'hierarchical'    => true,
    'menu_icon'       => 'dashicons-welcome-learn-more',
    'has_archive'     => true,
    'menu_position'   => 41,
    'rewrite'         => ['slug' => 'lessons'],
    'supports'        => ['title', 'thumbnail', 'excerpt', 'comments'],
    'show_in_rest'    => true,
], [
    'name'          => 'Leçons',
    'singular_name' => 'Leçon',
    'menu_name'     => 'Formations',
    'add_new_item'  => 'Ajouter une leçon',
    'edit_item'     => 'Éditer la leçon',
    'all_items'     => 'Toutes les leçons',
]);

if ($lessons !== null) {
    $lessons->addMetaBox('meta_home_intro', 'Introduction', require __DIR__ . '/cfdev/fields/lessons.php');
}

// ── Taxonomie : Modules (courses) ──────────────────────────────────────
register_cfdev_taxonomy('courses', 'lessons', [
    'show_admin_column'     => true,
    'admin_column_sortable' => true,
    'admin_column_filter'   => true,
], [
    'name'          => 'Modules',
    'singular_name' => 'Module',
    'menu_name'     => 'Modules',
])
->addTermMeta(require __DIR__ . '/cfdev/fields/courses.php');
