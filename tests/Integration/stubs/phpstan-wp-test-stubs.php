<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols,PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use PHPUnit\Framework\TestCase;

/**
 * PHPStan stubs for the WordPress test framework classes.
 * These are not autoloaded — they exist solely so PHPStan can resolve
 * WP_UnitTestCase and related symbols without running the full WP test bootstrap.
 */

class WP_UnitTestCase extends TestCase
{
    /** @var array<string, mixed> */
    public array $caught_deprecated = [];

    /** @var array<string, mixed> */
    public array $caught_doing_it_wrong = [];

    public static function factory(): WP_UnitTest_Factory
    {
        return new WP_UnitTest_Factory();
    }

    public function deprecated_function_run(string $function, string $replacement, string $version): void
    {
    }

    public function doing_it_wrong_run(string $function, string $message, string $version): void
    {
    }

    public function expectDeprecated(): void
    {
    }

    public function expectedDeprecated(): void
    {
    }
}

class WP_UnitTest_Factory
{
    public WP_UnitTest_Factory_For_Post $post;
    public WP_UnitTest_Factory_For_User $user;
    public WP_UnitTest_Factory_For_Term $term;
    public WP_UnitTest_Factory_For_Comment $comment;

    public function __construct()
    {
        $this->post    = new WP_UnitTest_Factory_For_Post();
        $this->user    = new WP_UnitTest_Factory_For_User();
        $this->term    = new WP_UnitTest_Factory_For_Term();
        $this->comment = new WP_UnitTest_Factory_For_Comment();
    }
}

class WP_UnitTest_Factory_For_Post
{
    /** @param array<string, mixed> $args */
    public function create(array $args = []): int
    {
        return 0;
    }

    /** @param array<string, mixed> $args */
    public function create_and_get(array $args = []): WP_Post
    {
        return new WP_Post(new stdClass());
    }
}

class WP_UnitTest_Factory_For_User
{
    /** @param array<string, mixed> $args */
    public function create(array $args = []): int
    {
        return 0;
    }
}

class WP_UnitTest_Factory_For_Term
{
    /** @param array<string, mixed> $args */
    public function create(array $args = []): int
    {
        return 0;
    }
}

class WP_UnitTest_Factory_For_Comment
{
    /** @param array<string, mixed> $args */
    public function create(array $args = []): int
    {
        return 0;
    }
}

class WPDieException extends \Exception
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}

/**
 * @param string|string[] $hook_name
 * @param callable|string $callback
 */
function tests_add_filter(string $hook_name, $callback, int $priority = 10, int $accepted_args = 1): bool
{
    return true;
}
