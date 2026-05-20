<?php

/**
 * CFDev — Champs de la meta box "Introduction" pour le CPT Leçons.
 * Ce fichier est require'd directement en argument de addMetaBox() → doit retourner un array.
 */

return [
    ['id' => '_lesson_intro_text',  'type' => 'textarea', 'label' => 'Introduction',    'required' => true],
    ['id' => '_lesson_intro_image', 'type' => 'image',    'label' => 'Image de couverture'],
    ['id' => '_lesson_intro_cta',   'type' => 'link',     'label' => 'Bouton principal'],
    ['id' => '_lesson_duration',    'type' => 'text',     'label' => 'Durée estimée'],
    ['id' => '_lesson_level',       'type' => 'select',   'label' => 'Niveau',
        'options' => ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'],
        'args'    => ['show_option_none' => '-- Choisir --'],
    ],
];
