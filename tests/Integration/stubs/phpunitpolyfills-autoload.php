<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Stub bridging WP_UnitTestCase's snake_case lifecycle hooks (set_up, tear_down, …)
 * to PHPUnit 13's camelCase API.  yoast/phpunit-polyfills does not yet support
 * PHPUnit 13, so we ship this minimal shim instead of the real package.
 */

require_once __DIR__ . '/Yoast/PHPUnitPolyfills/TestCases/TestCase.php';
