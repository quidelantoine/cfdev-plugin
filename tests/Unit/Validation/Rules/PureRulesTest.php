<?php

namespace Weblitzer\CFDev\Tests\Unit\Validation\Rules;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Weblitzer\CFDev\Validation\Rules\Alpha;
use Weblitzer\CFDev\Validation\Rules\AlphaNumeric;
use Weblitzer\CFDev\Validation\Rules\Between;
use Weblitzer\CFDev\Validation\Rules\Contains;
use Weblitzer\CFDev\Validation\Rules\Email;
use Weblitzer\CFDev\Validation\Rules\EndsWith;
use Weblitzer\CFDev\Validation\Rules\ExactLength;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\MaxItems;
use Weblitzer\CFDev\Validation\Rules\MaxLength;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\MinItems;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\IsNumeric;
use Weblitzer\CFDev\Validation\Rules\Positive;
use Weblitzer\CFDev\Validation\Rules\Regex;
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Rules\Slug;
use Weblitzer\CFDev\Validation\Rules\StartsWith;
use Weblitzer\CFDev\Validation\Rules\Url;
use Weblitzer\CFDev\Validation\Rules\Uuid;

class PureRulesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Required
    // -------------------------------------------------------------------------

    public function testRequiredValid(): void
    {
        $this->assertTrue((new Required())->validate('hello'));
    }

    public function testRequiredEmptyString(): void
    {
        $this->assertFalse((new Required())->validate(''));
    }

    public function testRequiredNull(): void
    {
        $this->assertFalse((new Required())->validate(null));
    }

    public function testRequiredZeroString(): void
    {
        $this->assertFalse((new Required())->validate('0'));
    }

    public function testRequiredGetError(): void
    {
        $this->assertNotEmpty((new Required())->getError());
    }

    // -------------------------------------------------------------------------
    // Alpha
    // -------------------------------------------------------------------------

    public function testAlphaValid(): void
    {
        $this->assertTrue((new Alpha())->validate('Hello'));
    }

    public function testAlphaWithNumber(): void
    {
        $this->assertFalse((new Alpha())->validate('Hello1'));
    }

    public function testAlphaWithSpace(): void
    {
        $this->assertFalse((new Alpha())->validate('Hello World'));
    }

    public function testAlphaEmpty(): void
    {
        $this->assertFalse((new Alpha())->validate(''));
    }

    public function testAlphaGetError(): void
    {
        $this->assertNotEmpty((new Alpha())->getError());
    }

    // -------------------------------------------------------------------------
    // Alpha_Numeric
    // -------------------------------------------------------------------------

    public function testAlphaNumericValid(): void
    {
        $this->assertTrue((new AlphaNumeric())->validate('Hello123'));
    }

    public function testAlphaNumericWithSpecialChar(): void
    {
        $this->assertFalse((new AlphaNumeric())->validate('Hello!'));
    }

    public function testAlphaNumericGetError(): void
    {
        $this->assertNotEmpty((new AlphaNumeric())->getError());
    }

    // -------------------------------------------------------------------------
    // Slug
    // -------------------------------------------------------------------------

    public function testSlugValid(): void
    {
        $this->assertTrue((new Slug())->validate('my-slug-123'));
    }

    public function testSlugWithUppercase(): void
    {
        $this->assertFalse((new Slug())->validate('My-Slug'));
    }

    public function testSlugWithDoubleDash(): void
    {
        $this->assertFalse((new Slug())->validate('my--slug'));
    }

    public function testSlugTrailingDash(): void
    {
        $this->assertFalse((new Slug())->validate('my-slug-'));
    }

    public function testSlugGetError(): void
    {
        $this->assertNotEmpty((new Slug())->getError());
    }

    // -------------------------------------------------------------------------
    // Regex
    // -------------------------------------------------------------------------

    public function testRegexMatches(): void
    {
        $this->assertTrue((new Regex('/^\d{5}$/'))->validate('75001'));
    }

    public function testRegexNoMatch(): void
    {
        $this->assertFalse((new Regex('/^\d{5}$/'))->validate('ABC'));
    }

    public function testRegexGetError(): void
    {
        $this->assertNotEmpty((new Regex('/^\d+$/'))->getError());
    }

    // -------------------------------------------------------------------------
    // Exact_Length
    // -------------------------------------------------------------------------

    public function testExactLengthValid(): void
    {
        $this->assertTrue((new ExactLength(5))->validate('75001'));
    }

    public function testExactLengthTooShort(): void
    {
        $this->assertFalse((new ExactLength(5))->validate('7500'));
    }

    public function testExactLengthTooLong(): void
    {
        $this->assertFalse((new ExactLength(5))->validate('750011'));
    }

    public function testExactLengthGetError(): void
    {
        $error = (new ExactLength(5))->getError();
        $this->assertStringContainsString('5', $error);
    }

    // -------------------------------------------------------------------------
    // Min / Max / Between
    // -------------------------------------------------------------------------

    public function testMinValid(): void
    {
        $this->assertTrue((new Min(18))->validate(18));
        $this->assertTrue((new Min(18))->validate(25));
    }

    public function testMinInvalid(): void
    {
        $this->assertFalse((new Min(18))->validate(17));
    }

    public function testMinGetError(): void
    {
        $error = (new Min(18))->getError();
        $this->assertStringContainsString('18', $error);
    }

    public function testMaxValid(): void
    {
        $this->assertTrue((new Max(100))->validate(100));
        $this->assertTrue((new Max(100))->validate(50));
    }

    public function testMaxInvalid(): void
    {
        $this->assertFalse((new Max(100))->validate(101));
    }

    public function testMaxGetError(): void
    {
        $error = (new Max(100))->getError();
        $this->assertStringContainsString('100', $error);
    }

    public function testBetweenValid(): void
    {
        $this->assertTrue((new Between(1, 10))->validate(5));
        $this->assertTrue((new Between(1, 10))->validate(1));
        $this->assertTrue((new Between(1, 10))->validate(10));
    }

    public function testBetweenBelow(): void
    {
        $this->assertFalse((new Between(1, 10))->validate(0));
    }

    public function testBetweenAbove(): void
    {
        $this->assertFalse((new Between(1, 10))->validate(11));
    }

    public function testBetweenNonNumeric(): void
    {
        $this->assertFalse((new Between(1, 10))->validate('abc'));
    }

    public function testBetweenGetError(): void
    {
        $error = (new Between(1, 10))->getError();
        $this->assertStringContainsString('1', $error);
        $this->assertStringContainsString('10', $error);
    }

    // -------------------------------------------------------------------------
    // Positive
    // -------------------------------------------------------------------------

    public function testPositiveValid(): void
    {
        $this->assertTrue((new Positive())->validate(1));
        $this->assertTrue((new Positive())->validate(0.1));
    }

    public function testPositiveZero(): void
    {
        $this->assertFalse((new Positive())->validate(0));
    }

    public function testPositiveNegative(): void
    {
        $this->assertFalse((new Positive())->validate(-1));
    }

    public function testPositiveGetError(): void
    {
        $this->assertNotEmpty((new Positive())->getError());
    }

    // -------------------------------------------------------------------------
    // Url
    // -------------------------------------------------------------------------

    public function testUrlValid(): void
    {
        $this->assertTrue((new Url())->validate('https://example.com'));
    }

    public function testUrlInvalid(): void
    {
        $this->assertFalse((new Url())->validate('not-a-url'));
    }

    public function testUrlGetError(): void
    {
        $this->assertNotEmpty((new Url())->getError());
    }

    // -------------------------------------------------------------------------
    // Uuid
    // -------------------------------------------------------------------------

    public function testUuidValid(): void
    {
        $this->assertTrue((new Uuid())->validate('550e8400-e29b-41d4-a716-446655440000'));
    }

    public function testUuidInvalid(): void
    {
        $this->assertFalse((new Uuid())->validate('not-a-uuid'));
    }

    public function testUuidGetError(): void
    {
        $this->assertNotEmpty((new Uuid())->getError());
    }

    // -------------------------------------------------------------------------
    // MinLength / MaxLength
    // -------------------------------------------------------------------------

    public function testMinLengthValid(): void
    {
        $this->assertTrue((new MinLength(3))->validate('abc'));
        $this->assertTrue((new MinLength(3))->validate('abcd'));
    }

    public function testMinLengthTooShort(): void
    {
        $this->assertFalse((new MinLength(3))->validate('ab'));
    }

    public function testMinLengthEmptyString(): void
    {
        $this->assertFalse((new MinLength(1))->validate(''));
    }

    public function testMinLengthGetError(): void
    {
        $error = (new MinLength(3))->getError();
        $this->assertStringContainsString('3', $error);
    }

    public function testMaxLengthValid(): void
    {
        $this->assertTrue((new MaxLength(5))->validate('hello'));
        $this->assertTrue((new MaxLength(5))->validate('hi'));
    }

    public function testMaxLengthTooLong(): void
    {
        $this->assertFalse((new MaxLength(5))->validate('toolong'));
    }

    public function testMaxLengthEmptyStringAlwaysPasses(): void
    {
        $this->assertTrue((new MaxLength(5))->validate(''));
    }

    public function testMaxLengthGetError(): void
    {
        $error = (new MaxLength(5))->getError();
        $this->assertStringContainsString('5', $error);
    }

    // -------------------------------------------------------------------------
    // Numeric
    // -------------------------------------------------------------------------

    public function testNumericValidInteger(): void
    {
        $this->assertTrue((new IsNumeric())->validate(42));
    }

    public function testNumericValidFloat(): void
    {
        $this->assertTrue((new IsNumeric())->validate(3.14));
    }

    public function testNumericValidStringNumber(): void
    {
        $this->assertTrue((new IsNumeric())->validate('42'));
    }

    public function testNumericInvalidString(): void
    {
        $this->assertFalse((new IsNumeric())->validate('abc'));
    }

    public function testNumericEmptyString(): void
    {
        $this->assertFalse((new IsNumeric())->validate(''));
    }

    public function testNumericGetError(): void
    {
        $this->assertNotEmpty((new IsNumeric())->getError());
    }

    // -------------------------------------------------------------------------
    // Contains / StartsWith / EndsWith
    // -------------------------------------------------------------------------

    public function testContainsValid(): void
    {
        $this->assertTrue((new Contains('@'))->validate('user@example.com'));
    }

    public function testContainsInvalid(): void
    {
        $this->assertFalse((new Contains('@'))->validate('no-at-sign'));
    }

    public function testContainsGetError(): void
    {
        $error = (new Contains('@'))->getError();
        $this->assertStringContainsString('@', $error);
    }

    public function testStartsWithValid(): void
    {
        $this->assertTrue((new StartsWith('https://'))->validate('https://example.com'));
    }

    public function testStartsWithInvalid(): void
    {
        $this->assertFalse((new StartsWith('https://'))->validate('http://example.com'));
    }

    public function testStartsWithGetError(): void
    {
        $error = (new StartsWith('https://'))->getError();
        $this->assertStringContainsString('https://', $error);
    }

    public function testEndsWithValid(): void
    {
        $this->assertTrue((new EndsWith('.pdf'))->validate('document.pdf'));
    }

    public function testEndsWithInvalid(): void
    {
        $this->assertFalse((new EndsWith('.pdf'))->validate('document.docx'));
    }

    public function testEndsWithGetError(): void
    {
        $error = (new EndsWith('.pdf'))->getError();
        $this->assertStringContainsString('.pdf', $error);
    }

    // -------------------------------------------------------------------------
    // Email (uses WP is_email())
    // -------------------------------------------------------------------------

    public function testEmailValid(): void
    {
        Functions\when('is_email')->justReturn(true);
        $this->assertTrue((new Email())->validate('user@example.com'));
    }

    public function testEmailInvalid(): void
    {
        Functions\when('is_email')->justReturn(false);
        $this->assertFalse((new Email())->validate('not-an-email'));
    }

    public function testEmailGetError(): void
    {
        $this->assertNotEmpty((new Email())->getError());
    }

    // -------------------------------------------------------------------------
    // MinItems
    // -------------------------------------------------------------------------

    public function testMinItemsValidWithEnoughItems(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertTrue((new MinItems(2))->validate([1, 2]));
        $this->assertTrue((new MinItems(2))->validate([1, 2, 3]));
    }

    public function testMinItemsFailsWithTooFewItems(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertFalse((new MinItems(2))->validate([1]));
        $this->assertFalse((new MinItems(2))->validate([]));
    }

    public function testMinItemsFiltersEmptyValues(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertFalse((new MinItems(2))->validate(['', null, '-1']));
        $this->assertTrue((new MinItems(2))->validate([1, '', 2]));
    }

    public function testMinItemsZeroPassesForNonArray(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertTrue((new MinItems(0))->validate(''));
    }

    public function testMinItemsOneFailsForNonArray(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertFalse((new MinItems(1))->validate(''));
    }

    public function testMinItemsReturnsError(): void
    {
        Functions\when('_n')->returnArg(2);
        Functions\when('__')->alias(fn($s) => $s);
        $this->assertNotEmpty((new MinItems(2))->getError());
    }

    // -------------------------------------------------------------------------
    // MaxItems
    // -------------------------------------------------------------------------

    public function testMaxItemsValidWithFewEnoughItems(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertTrue((new MaxItems(3))->validate([1, 2]));
        $this->assertTrue((new MaxItems(3))->validate([1, 2, 3]));
    }

    public function testMaxItemsFailsWithTooManyItems(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertFalse((new MaxItems(2))->validate([1, 2, 3]));
    }

    public function testMaxItemsFiltersEmptyValues(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertTrue((new MaxItems(2))->validate([1, '', 2, null]));
        $this->assertFalse((new MaxItems(2))->validate([1, 2, 3, '']));
    }

    public function testMaxItemsPassesForNonArray(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertTrue((new MaxItems(3))->validate('not-an-array'));
    }

    public function testMaxItemsPassesForEmptyArray(): void
    {
        Functions\when('_n')->returnArg(2);
        $this->assertTrue((new MaxItems(3))->validate([]));
    }

    public function testMaxItemsReturnsError(): void
    {
        Functions\when('_n')->returnArg(2);
        Functions\when('__')->alias(fn($s) => $s);
        $this->assertNotEmpty((new MaxItems(3))->getError());
    }
}
