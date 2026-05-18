<?php

namespace CFDev\Tests\Unit\Support;

use CFDev\Support\WPValidator;
use CFDev\Tests\Unit\CFDevTestCase;

class WPValidatorTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // isReservedTerm()
    // -------------------------------------------------------------------------

    public function testNonReservedTermReturnsFalse(): void
    {
        $this->assertFalse(WPValidator::isReservedTerm('book'));
    }

    public function testNonReservedTermCustomNameReturnsFalse(): void
    {
        $this->assertFalse(WPValidator::isReservedTerm('my_custom_post_type'));
    }

    public function testReservedTermReturnsWpError(): void
    {
        $result = WPValidator::isReservedTerm('post');
        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    public function testReservedTermErrorCode(): void
    {
        /** @var \WP_Error $result */
        $result = WPValidator::isReservedTerm('category');
        $this->assertSame('reserved_term_used', $result->get_error_code());
    }

    public function testReservedTermCheckIsCaseSensitive(): void
    {
        // Only lowercase 'post' is in the list — 'POST' is not reserved
        $this->assertFalse(WPValidator::isReservedTerm('POST'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reservedTermsProvider')]
    public function testKnownReservedTermsAreBlocked(string $term): void
    {
        $this->assertInstanceOf(\WP_Error::class, WPValidator::isReservedTerm($term));
    }

    public static function reservedTermsProvider(): array
    {
        return [
            ['post'],
            ['page'],
            ['attachment'],
            ['taxonomy'],
            ['author'],
            ['feed'],
            ['tag'],
            ['category'],
            ['type'],
            ['name'],
        ];
    }

    // -------------------------------------------------------------------------
    // isWpCallback()
    // -------------------------------------------------------------------------

    public function testStringCallbackReturnsTrue(): void
    {
        $this->assertTrue(WPValidator::isWpCallback('my_function'));
    }

    public function testStringCallbackAlwaysTrueRegardlessOfExistence(): void
    {
        // String path: no existence check, always true
        $this->assertTrue(WPValidator::isWpCallback('totally_nonexistent_function_xyz'));
    }

    public function testArrayWithExistingMethodReturnsTrue(): void
    {
        $this->assertTrue(WPValidator::isWpCallback([\Exception::class, 'getMessage']));
    }

    public function testArrayWithNonExistentMethodReturnsFalse(): void
    {
        $this->assertFalse(WPValidator::isWpCallback([\Exception::class, 'nonExistentMethod999']));
    }

    public function testArrayWithNonExistentClassReturnsFalse(): void
    {
        $this->assertFalse(WPValidator::isWpCallback(['NonExistentClass999', 'someMethod']));
    }

    public function testArrayWithExistingClassOnlyReturnsTrue(): void
    {
        // Array with class name only (no method) → class_exists check
        $this->assertTrue(WPValidator::isWpCallback([\Exception::class]));
    }

    public function testArrayWithNonExistentClassOnlyReturnsFalse(): void
    {
        $this->assertFalse(WPValidator::isWpCallback(['NonExistentClass999']));
    }

    public function testObjectInstanceWithMethodReturnsTrue(): void
    {
        $obj = new \stdClass();
        // stdClass doesn't have 'nonExistent' but method_exists still returns false
        $this->assertFalse(WPValidator::isWpCallback([$obj, 'nonExistentMethod']));
    }
}
