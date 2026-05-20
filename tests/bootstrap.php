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

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps
if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string, mixed> */
        private array $params;
        /** @param array<string, mixed> $params */
        public function __construct(array $params = [])
        {
            $this->params = $params;
        }
        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }
    }
}
if (! class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public mixed $data;
        public int $status;
        public function __construct(mixed $data = null, int $status = 200)
        {
            $this->data   = $data;
            $this->status = $status;
        }
        public function get_data(): mixed
        {
            return $this->data;
        }
        public function get_status(): int
        {
            return $this->status;
        }
    }
}
if (! class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        public const READABLE = 'GET';
    }
}
if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;
        public string $post_type = 'post';
        public string $post_status = 'publish';
    }
}
if (! class_exists('WP_Term')) {
    class WP_Term
    {
        public int $term_id = 0;
        public string $taxonomy = '';
        public int $parent = 0;
    }
}
if (! class_exists('WP_User')) {
    class WP_User
    {
        public int $ID = 0;
        /** @var array<string> */
        public array $roles = [];
    }
}
if (! class_exists('WP_Post_Type')) {
    class WP_Post_Type
    {
        public bool $public = true;
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps

require_once dirname(__DIR__) . '/vendor/autoload.php';

\Brain\Monkey\setUp();
