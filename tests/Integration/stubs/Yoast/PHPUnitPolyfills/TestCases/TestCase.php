<?php

namespace Yoast\PHPUnitPolyfills\TestCases;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace,PSR2.Methods.MethodDeclaration.Underscore

/**
 * Minimal stub bridging WP_UnitTestCase's snake_case lifecycle methods
 * (set_up, tear_down, …) to PHPUnit 13's camelCase hooks.
 */
abstract class TestCase extends PhpUnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::set_up_before_class();
    }

    public static function tearDownAfterClass(): void
    {
        static::tear_down_after_class();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->set_up();
    }

    protected function tearDown(): void
    {
        $this->tear_down();
        parent::tearDown();
    }

    public static function set_up_before_class() {} // phpcs:ignore
    public static function tear_down_after_class() {} // phpcs:ignore
    public function set_up() {} // phpcs:ignore
    public function tear_down() {} // phpcs:ignore
}
