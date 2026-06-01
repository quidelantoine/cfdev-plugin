import js from '@eslint/js'
import globals from 'globals'

export default [
    js.configs.recommended,

    {
        // Fichiers tiers — ignorés complètement
        ignores: [
            'node_modules/**',
            'vendor/**',
            'assets/js/jquery.timepicker.js',
            'assets/js/jquery-ui.js',
        ],
    },

    {
        files: ['assets/js/**/*.js'],

        languageOptions: {
            ecmaVersion: 2017,
            sourceType: 'script', // scripts WP classiques, pas des ES modules
            globals: {
                // Navigateur
                ...globals.browser,
                // jQuery
                jQuery: 'readonly',
                $: 'readonly',
                // WordPress core
                wp:             'readonly',
                ajaxurl:        'readonly',
                pagenow:        'readonly',
                tinyMCE:        'readonly',
                tinyMCEPreInit: 'readonly',
                switchEditors:  'readonly',
                wpActiveEditor: 'writable',
                // WordPress quicktags editor
                QTags:         'readonly',
                // CFDev — objets localisés via wp_localize_script
                Cfdev:         'readonly',
                // Variables de module IIFE exportées dans admin-registry.js
                NONCE:      'readonly',
                NONCE_SRCH: 'readonly',
                AJAX_URL:   'readonly',
                CACHE:      'readonly',
                HIT:        'readonly',
                OFF:        'readonly',
            },
        },

        rules: {
            // ── Bugs critiques ────────────────────────────────────────────
            // no-shadow : le bug Bundle+Wysiwyg était exactement ça
            // (var settings dans .each() masquait le paramètre settings externe)
            'no-shadow': 'error',

            // Variables non définies → très probablement un global manquant
            'no-undef': 'error',

            // ── Qualité ───────────────────────────────────────────────────
            'eqeqeq':          ['warn', 'always', { null: 'ignore' }],
            'no-unused-vars':  ['warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
            'no-console':      'warn',

            // ── Style — warn seulement (codebase existante en var) ────────
            // Passer à const/let progressivement, sans bloquer CI tout de suite
            'no-var':          'warn',
            'prefer-const':    'warn',
        },
    },
]