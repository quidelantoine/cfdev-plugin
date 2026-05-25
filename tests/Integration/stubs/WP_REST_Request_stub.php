<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps

/**
 * PHPStan stub for WP_REST_Request.
 * Overrides the templated version in wordpress-stubs that confuses PHPStan's
 * constructor type inference.
 *
 * @implements ArrayAccess<string, mixed>
 */
class WP_REST_Request implements ArrayAccess
{
    /** @param array<string, mixed> $attributes */
    public function __construct(string $method = '', string $route = '', array $attributes = [])
    {
    }

    public function get_method(): string
    {
        return ''; 
    }

    public function set_method(string $method): void
    {
    }

    /** @return mixed */
    public function get_param(string $key)
    {
        return null; 
    }

    /** @param mixed $value */
    public function set_param(string $key, $value): void
    {
    }

    /** @return array<string, mixed> */
    public function get_params(): array
    {
        return []; 
    }

    /** @return array<string, mixed> */
    public function get_query_params(): array
    {
        return []; 
    }

    /** @return array<string, mixed> */
    public function get_body_params(): array
    {
        return []; 
    }

    /** @return array<string, mixed> */
    public function get_json_params(): array
    {
        return []; 
    }

    /** @return array<string, mixed> */
    public function get_url_params(): array
    {
        return []; 
    }

    /** @param mixed $offset */
    public function offsetExists($offset): bool
    {
        return false; 
    }

    /** @param mixed $offset @return mixed */
    public function offsetGet($offset)
    {
        return null; 
    }

    /** @param mixed $offset @param mixed $value */
    public function offsetSet($offset, $value): void
    {
    }

    /** @param mixed $offset */
    public function offsetUnset($offset): void
    {
    }
}

/**
 * PHPStan stub for WP_Error: adds get_error_data() missing from the szepeviktor type narrowing.
 */
class WP_Error
{
    public mixed $data = '';

    /** @param mixed $data */
    public function __construct(string $code = '', string $message = '', $data = '')
    {
        $this->data = $data;
    }

    /** @return list<string|int> */
    public function get_error_codes(): array
    {
        return []; 
    }

    /** @return string|int */
    public function get_error_code()
    {
        return ''; 
    }

    /**
     * @param string|int $code
     * @return list<string>
     */
    public function get_error_messages($code = ''): array
    {
        return []; 
    }

    /** @param string|int $code */
    public function get_error_message($code = ''): string
    {
        return ''; 
    }

    /** @param string|int $code */
    public function get_error_data(string|int $code = ''): mixed
    {
        return null; 
    }

    public function has_errors(): bool
    {
        return false; 
    }

    /** @param string|int $code @param mixed $data */
    public function add(string|int $code, string $message, mixed $data = ''): void
    {
    }

    /** @param mixed $data @param string|int $code */
    public function add_data(mixed $data, string|int $code = ''): void
    {
    }

    /** @param string|int $code */
    public function remove(string|int $code): void
    {
    }
}

/**
 * PHPStan stub for WP_Post_Type with $label property.
 */
class WP_Post_Type
{
    public string $name = '';
    public string $label = '';
    public string $description = '';
    public bool $public = false;
    public bool $hierarchical = false;
    public bool $show_ui = false;
    public bool $show_in_menu = false;
    public bool $show_in_nav_menus = false;
    public bool $show_in_admin_bar = false;
    public bool $show_in_rest = false;
    public int $menu_position = 0;
    /** @var \stdClass */
    public object $cap;
    /** @var \stdClass */
    public object $labels;
    /** @var array<string, mixed> */
    public array $supports = [];
}

/**
 * PHPStan stub for WP_Term with $name property.
 */
class WP_Term
{
    public int $term_id = 0;
    public string $name = '';
    public string $slug = '';
    public string $taxonomy = '';
    public string $description = '';
    public int $parent = 0;
    public int $count = 0;
    public string $filter = '';
    public int $term_taxonomy_id = 0;
}
