<?php

namespace Weblitzer\CFDev\Support;

class DateFormatHelper
{
    private const PHP_TO_JQUERYUI = [
        // Day
        'd' => 'dd',   // Day with leading zero (01-31)
        'D' => 'D',    // Short day name (Mon-Sun)
        'j' => 'd',    // Day without leading zero (1-31)
        'l' => 'DD',   // Full day name (Monday-Sunday)
        'N' => '',      // ISO day of week (1=Mon, 7=Sun) — no equivalent
        'S' => '',      // Ordinal suffix (st, nd, rd, th) — no equivalent
        'w' => '',      // Day of week (0=Sun, 6=Sat) — no equivalent
        'z' => 'o',    // Day of year (0-365)
        // Week
        'W' => '',      // ISO week number — no equivalent
        // Month
        'F' => 'MM',   // Full month name (January-December)
        'm' => 'mm',   // Month with leading zero (01-12)
        'M' => 'M',    // Short month name (Jan-Dec)
        'n' => 'm',    // Month without leading zero (1-12)
        't' => '',      // Days in month (28-31) — no equivalent
        // Year
        'L' => '',      // Leap year (0 or 1) — no equivalent
        'o' => '',      // ISO year — no equivalent
        'Y' => 'yy',   // Full year (e.g. 2024)
        'y' => 'y',    // Two-digit year (e.g. 24)
        // Time
        'a' => 'tt',   // am/pm lowercase
        'A' => 'TT',   // AM/PM uppercase
        'B' => '',      // Swatch Internet Time — no equivalent
        'g' => 'h',    // 12-hour without leading zero (1-12)
        'G' => 'H',    // 24-hour without leading zero (0-23)
        'h' => 'hh',   // 12-hour with leading zero (01-12)
        'H' => 'HH',   // 24-hour with leading zero (00-23)
        'i' => 'mm',   // Minutes with leading zero (00-59)
        's' => 'ss',   // Seconds with leading zero (00-59)
        'u' => 'c',    // Microseconds
        // ISO 8601
        'c' => 'Z',    // Full ISO 8601 date
    ];

    public static function parse(string $php_format): string
    {
        $jqueryui_format = '';
        $escaping        = false;
        $length          = strlen($php_format);

        for ($i = 0; $i < $length; $i++) {
            $char = $php_format[$i];

            if ($char === '\\') {
                $next = $php_format[++$i] ?? '';
                $jqueryui_format .= $escaping ? $next : "'{$next}";
                $escaping = true;
                continue;
            }

            if ($escaping) {
                $jqueryui_format .= "'";
                $escaping = false;
            }

            $jqueryui_format .= self::PHP_TO_JQUERYUI[$char] ?? $char;
        }

        // Close any open escape sequence
        if ($escaping) {
            $jqueryui_format .= "'";
        }

        return $jqueryui_format;
    }
}
