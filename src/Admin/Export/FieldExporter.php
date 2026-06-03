<?php

namespace Weblitzer\CFDev\Admin\Export;

/**
 * Converts field group definitions to JSON or PHP export strings.
 *
 * Accepts the array produced by Registry::exportGroups() and serialises
 * it to a downloadable format. No WordPress dependencies — fully testable.
 *
 * @package CFDev
 * @author  quidelantoine
 * @since   1.0.7
 */
final class FieldExporter
{
    /**
     * Serialises groups to pretty-printed JSON.
     *
     * @param  array<int, array<string, mixed>> $groups  From Registry::exportGroups()
     */
    public static function toJson(array $groups): string
    {
        $payload = [
            'version'     => CFDEV_VERSION,
            'exported_at' => gmdate('c'),
            'groups'      => $groups,
        ];

        return (string) wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Serialises groups to a PHP file containing a return statement.
     *
     * The output can be included directly or pasted into a cfdev-fields.php
     * inside an add_action('init', ...) callback.
     *
     * @param  array<int, array<string, mixed>> $groups  From Registry::exportGroups()
     */
    public static function toPhp(array $groups): string
    {
        $date = gmdate('Y-m-d H:i:s');
        return implode("\n", [
            '<?php',
            '// CFDev — Field definitions export',
            '// Generated : ' . $date,
            '// Version   : ' . CFDEV_VERSION,
            '// Usage     : each group maps to one addMetaBox() / addTermMeta() / addUserMeta() call.',
            '',
            'return ' . self::phpArray($groups, 0) . ';',
            '',
        ]);
    }

    // -------------------------------------------------------------------------
    // PHP pretty-printer
    // -------------------------------------------------------------------------

    /**
     * Recursively renders a PHP value as `[...]` array syntax.
     *
     * @param  mixed $value
     */
    private static function phpArray(mixed $value, int $depth): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return "'" . addcslashes($value, "'\\") . "'";
        }
        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }
            $indent = str_repeat('    ', $depth + 1);
            $close  = str_repeat('    ', $depth);
            $is_list = array_is_list($value);
            $items   = [];
            foreach ($value as $k => $v) {
                $key     = $is_list ? '' : self::phpArray($k, $depth + 1) . ' => ';
                $items[] = $indent . $key . self::phpArray($v, $depth + 1);
            }
            return "[\n" . implode(",\n", $items) . ",\n" . $close . ']';
        }

        return 'null';
    }
}
