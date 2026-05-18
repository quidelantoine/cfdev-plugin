<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

if (! defined('ABSPATH')) {
    define('ABSPATH', '/');
}

if (! defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}

if (! defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps
if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(
            public readonly string $code = '',
            public readonly string $message = '',
            public readonly mixed $data = '',
        ) {
        }
        public function get_error_code(): string
        {
            return $this->code;
        }
        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps

require_once dirname(__DIR__) . '/vendor/autoload.php';

\Brain\Monkey\setUp();
