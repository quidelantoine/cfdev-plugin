<?php

namespace CFDev\Tests\Unit\Validation\Rules;

use Brain\Monkey\Functions;
use CFDev\Tests\Unit\CFDevTestCase;
use CFDev\Validation\Rules\Alpha;
use CFDev\Validation\Rules\AlphaNumeric;
use CFDev\Validation\Rules\Between;
use CFDev\Validation\Rules\Contains;
use CFDev\Validation\Rules\Email;
use CFDev\Validation\Rules\EndsWith;
use CFDev\Validation\Rules\ExactLength;
use CFDev\Validation\Rules\Max;
use CFDev\Validation\Rules\MaxLength;
use CFDev\Validation\Rules\Min;
use CFDev\Validation\Rules\MinLength;
use CFDev\Validation\Rules\Numeric;
use CFDev\Validation\Rules\Positive;
use CFDev\Validation\Rules\Regex;
use CFDev\Validation\Rules\Required;
use CFDev\Validation\Rules\Slug;
use CFDev\Validation\Rules\StartsWith;
use CFDev\Validation\Rules\Url;
use CFDev\Validation\Rules\Uuid;

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

    public function testMaxValid(): void
    {
        $this->assertTrue((new Max(100))->validate(100));
        $this->assertTrue((new Max(100))->validate(50));
    }

    public function testMaxInvalid(): void
    {
        $this->assertFalse((new Max(100))->validate(101));
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

    // -------------------------------------------------------------------------
    // Numeric
    // -------------------------------------------------------------------------

    public function testNumericValidInteger(): void
    {
        $this->assertTrue((new Numeric())->validate(42));
    }

    public function testNumericValidFloat(): void
    {
        $this->assertTrue((new Numeric())->validate(3.14));
    }

    public function testNumericValidStringNumber(): void
    {
        $this->assertTrue((new Numeric())->validate('42'));
    }

    public function testNumericInvalidString(): void
    {
        $this->assertFalse((new Numeric())->validate('abc'));
    }

    public function testNumericEmptyString(): void
    {
        $this->assertFalse((new Numeric())->validate(''));
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

    public function testStartsWithValid(): void
    {
        $this->assertTrue((new StartsWith('https://'))->validate('https://example.com'));
    }

    public function testStartsWithInvalid(): void
    {
        $this->assertFalse((new StartsWith('https://'))->validate('http://example.com'));
    }

    public function testEndsWithValid(): void
    {
        $this->assertTrue((new EndsWith('.pdf'))->validate('document.pdf'));
    }

    public function testEndsWithInvalid(): void
    {
        $this->assertFalse((new EndsWith('.pdf'))->validate('document.docx'));
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
}
