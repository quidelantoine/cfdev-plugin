<?php

if (! function_exists('generateArrayAllField')) {
    /**
     * CFDev Demo — generateArrayAllField()
     *
     * Génère un tableau de champs couvrant tous les types disponibles.
     * Protégé par function_exists : si le thème actif définit déjà cette fonction,
     * la version du thème est utilisée (pas de conflit).
     *
     * @param string                     $name   Préfixe des IDs de champs
     * @param string                     $block  Suffixe de bloc (ex: 'intro', 'media')
     * @param array<string, array<mixed>> $rules  Règles de validation par type de champ
     * @param bool                       $ajax   Active le mode ajax sur les champs
     * @return array<int, array<string, mixed>>
     */
    function generateArrayAllField(string $name, string $block, array $rules = [], bool $ajax = true): array
    {
        $str = $name . '_' . $block;

        $field = function (array $config) use ($rules, $ajax): array {
            $type           = $config['type'];
            $config['ajax'] = $ajax;
            return isset($rules[$type])
                ? array_merge($config, ['rules' => $rules[$type]])
                : $config;
        };

        return [
            $field(['id' => '_text_' . $str . '_text', 'type' => 'text', 'label' => 'Text_' . $name,
                    'explanation' => 'Champ texte court', 'required' => true]),
            $field(['id' => '_text_' . $str . '_textarea', 'type' => 'textarea',
                    'label' => 'Textarea_' . $name, 'explanation' => 'Texte long']),

            $field(['type' => 'heading', 'label' => 'Dimensions']),

            $field(['id' => '_text_' . $str . '_qty',   'type' => 'number', 'label' => 'Quantité',
                    'args' => ['min' => 0, 'max' => 999, 'step' => 1]]),
            $field(['id' => '_text_' . $str . '_rate',  'type' => 'number', 'label' => 'Taux',
                    'args' => ['min' => 0.0, 'max' => 1.0, 'step' => 0.01]]),
            $field(['id' => '_text_' . $str . '_range', 'type' => 'range',  'label' => 'Opacité (%)',
                    'args' => ['min' => 0, 'max' => 100, 'step' => 10], 'default_value' => '100']),

            $field(['id' => '_text_' . $str . '_email',          'type' => 'email',           'label' => 'E-mail']),
            $field(['id' => '_text_' . $str . '_website',        'type' => 'url',             'label' => 'Site web']),
            $field(['id' => '_text_' . $str . '_phone',          'type' => 'tel',             'label' => 'Téléphone']),

            $field(['type' => 'heading', 'label' => 'Médias']),

            $field(['id' => '_img_'  . $str . '_main_image',     'type' => 'image',           'label' => 'Image principale']),
            $field(['id' => '_img_'  . $str . '_book_cover', 'type' => 'image_alt', 'label' => 'Couverture']),


            $field(['id' => '_text_' . $str . '_gallery',        'type' => 'gallery',         'label' => 'Galerie', 'explanation' => 'Images supplémentaires']),
            $field(['id' => '_text_' . $str . '_file',           'type' => 'file',            'label' => 'Fichier']),
            $field(['id' => '_text_' . $str . '_cta',            'type' => 'link',            'label' => 'Bouton principal']),
            $field(['id' => '_text_' . $str . '_cta_alt',        'type' => 'link',            'label' => 'Bouton secondaire', 'explanation' => 'Optionnel']),

            $field(['type' => 'heading', 'label' => 'Choix']),

            $field(['id' => '_text_' . $str . '_toggle',         'type' => 'toggle',          'label' => 'Toggle', 'default_value' => '']),
            $field(['id' => '_text_' . $str . '_checkbox',       'type' => 'checkbox',        'label' => 'Checkbox_' . $name]),
            $field(['id' => '_text_' . $str . '_checkboxes', 'type' => 'checkboxes', 'label' => 'Checkboxes_' . $name,
                    'options' => ['v1' => 'Valeur 1', 'v2' => 'Valeur 2', 'v3' => 'Valeur 3']]),
            $field(['id' => '_text_' . $str . '_radios', 'type' => 'radios', 'label' => 'Radios_' . $name,
                    'options' => ['v1' => 'Valeur 1', 'v2' => 'Valeur 2', 'v3' => 'Valeur 3']]),
            $field(['id' => '_text_' . $str . '_yesno',          'type' => 'yesno',           'label' => 'Yesno_' . $name]),
            $field(['id' => '_text_' . $str . '_select', 'type' => 'select', 'label' => 'Select_' . $name,
                    'options' => ['v1' => 'Valeur 1', 'v2' => 'Valeur 2', 'v3' => 'Valeur 3'],
                    'args'    => ['show_option_none' => '-- Choisir --']]),
            $field(['id' => '_text_' . $str . '_multiselect', 'type' => 'multi_select', 'label' => 'MultiSelect_' . $name,
                    'options' => ['v1' => 'Valeur 1', 'v2' => 'Valeur 2', 'v3' => 'Valeur 3']]),

            $field(['type' => 'heading', 'label' => 'Contenu riche']),

            $field(['id' => '_text_' . $str . '_wysiwyg',        'type' => 'wysiwyg',         'label' => 'Wysiwyg_' . $name]),
            $field(['id' => '_text_' . $str . '_color',          'type' => 'color',           'label' => 'Couleur']),

            $field(['type' => 'heading', 'label' => 'Dates']),

            $field(['id' => '_date_' . $str . '_date',           'type' => 'date',            'label' => 'Date',     'args' => ['date_format' => 'm/d/Y']]),
            $field(['id' => '_date_' . $str . '_datetime',       'type' => 'datetime',        'label' => 'Date + Heure', 'args' => ['date_format' => 'm/d/Y']]),
            $field(['id' => '_date_' . $str . '_time',           'type' => 'time',            'label' => 'Heure']),

            $field(['type' => 'heading', 'label' => 'Relations']),

            $field(['id' => '_post_' . $str . '_checkboxespost', 'type' => 'post_checkboxes',
                    'label' => 'Posts (cases)', 'args' => ['post_type' => 'post']]),
            $field(['id' => '_post_' . $str . '_selectpost', 'type' => 'post_select', 'label' => 'Post (select)',
                    'args' => ['post_type' => 'post', 'show_option_none' => '-- Choisir --']]),
            $field(['id' => '_term_' . $str . '_checkboxesterm', 'type' => 'term_checkboxes',
                    'label' => 'Termes (cases)', 'args' => ['taxonomy' => 'category']]),
            $field(['id' => '_term_' . $str . '_selectterm', 'type' => 'term_select', 'label' => 'Terme (select)',
                    'args' => ['taxonomy' => 'category', 'show_option_none' => '-- Choisir --']]),
            $field(['id' => '_user_' . $str . '_selectuser', 'type' => 'user_select', 'label' => 'Utilisateur',
                    'args' => ['show_option_none' => '-- Choisir --']]),
            $field(['id' => '_user_' . $str . '_reviewers', 'type' => 'user_checkboxes', 'label' => 'Relecteurs',
                    'args' => ['role' => 'administrator', 'orderby' => 'display_name']]),
        ];
    }
}
