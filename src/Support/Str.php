<?php

namespace CFDev\Support;

/**
 * String manipulation helpers
 *
 * @package CFDev\Support
 * @author  quidelantoine
 * @since   1.0.0
 */
final class Str
{
    /**
     * @since   1.0.0
     */
    public static function beautify(string $string): string
    {
        return apply_filters('cfdev_beautify', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * @since   1.0.0
     */
    public static function uglify(string $string): string
    {
        return apply_filters('cfdev_uglify', str_replace('-', '_', sanitize_title($string)));
    }

    /**
     * @since   1.0.0
     */
    public static function pluralize(string $string): string
    {
        $plural = [
            ['/(quiz)$/i',               '$1zes'  ],
            ['/^(ox)$/i',                '$1en'   ],
            ['/([m|l])ouse$/i',          '$1ice'  ],
            ['/(matr|vert|ind)ix|ex$/i', '$1ices' ],
            ['/(x|ch|ss|sh)$/i',         '$1es'   ],
            ['/([^aeiouy]|qu)y$/i',      '$1ies'  ],
            ['/([^aeiouy]|qu)ies$/i',    '$1y'    ],
            ['/(hive)$/i',               '$1s'    ],
            ['/(?:([^f])fe|([lr])f)$/i', '$1$2ves'],
            ['/sis$/i',                  'ses'    ],
            ['/([ti])um$/i',             '$1a'    ],
            ['/(buffal|tomat)o$/i',      '$1oes'  ],
            ['/(bu)s$/i',                '$1ses'  ],
            ['/(alias|status)$/i',       '$1es'   ],
            ['/(octop|vir)us$/i',        '$1i'    ],
            ['/(ax|test)is$/i',          '$1es'   ],
            ['/s$/i',                    's'      ],
            ['/$/',                      's'      ],
        ];

        $irregular = [
            ['move',   'moves'   ],
            ['sex',    'sexes'   ],
            ['child',  'children'],
            ['man',    'men'     ],
            ['person', 'people'  ],
        ];

        $uncountable = ['sheep', 'fish', 'series', 'species', 'money', 'rice', 'information', 'equipment'];

        if (in_array(strtolower($string), $uncountable, true)) {
            return apply_filters('cfdev_pluralize', $string);
        }

        foreach ($irregular as [$singular, $plural_form]) {
            if (strtolower($string) === $singular) {
                return apply_filters('cfdev_pluralize', $plural_form);
            }
        }

        foreach ($plural as [$pattern, $replacement]) {
            if (preg_match($pattern, $string)) {
                return apply_filters('cfdev_pluralize', preg_replace($pattern, $replacement, $string));
            }
        }

        return apply_filters('cfdev_pluralize', $string);
    }
}
