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
        public readonly mixed $data;
        public function __construct(
            public readonly string $code = '',
            public readonly string $message = '',
            mixed $data = '',
        ) {
            $this->data = $data;
        }
        public function get_error_code(): string
        {
            return $this->code;
        }
        public function get_error_message(): string
        {
            return $this->message;
        }
        public function get_error_data(string|int $code = ''): mixed
        {
            return $this->data;
        }
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps
if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string, mixed> */
        private array $params = [];
        /** @param array<string, mixed> $attributes */
        public function __construct(string $method = '', string $route = '', array $attributes = [])
        {
            $this->params = $attributes;
        }
        /** @param mixed $value */
        public function set_param(string $key, $value): void
        {
            $this->params[$key] = $value;
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
        public string $post_title = '';
        public string $post_type = 'post';
        public string $post_status = 'publish';
    }
}
if (! class_exists('WP_Term')) {
    class WP_Term
    {
        public int $term_id = 0;
        public string $name = '';
        public string $slug = '';
        public string $taxonomy = '';
        public int $parent = 0;
    }
}
if (! class_exists('WP_User')) {
    class WP_User
    {
        public int $ID = 0;
        public string $display_name = '';
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
if (! class_exists('WP_Query')) {
    class WP_Query
    {
        /** @var array<\WP_Post> */
        public array $posts = [];
        /** @param array<string, mixed> $args */
        public function __construct(array $args = [])
        {
        }
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps

require_once dirname(__DIR__) . '/vendor/autoload.php';

\Brain\Monkey\setUp();
