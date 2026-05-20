<?php

/**
 * CFDev — Champs TermMeta pour la taxonomie Modules (courses).
 * Ce fichier est require'd directement en argument de addTermMeta() → doit retourner un array.
 */

return [
    ['id' => '_course_description', 'type' => 'textarea',  'label' => 'Description du module'],
    ['id' => '_course_image',       'type' => 'image',     'label' => 'Image du module'],
    ['id' => '_course_order',       'type' => 'number',    'label' => 'Ordre d\'affichage',
        'args' => ['min' => 0, 'max' => 999, 'step' => 1],
    ],
    ['id' => '_course_color',       'type' => 'color',     'label' => 'Couleur du module'],
];