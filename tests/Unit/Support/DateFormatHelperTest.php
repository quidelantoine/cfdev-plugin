<?php

namespace CFDev\Tests\Unit\Support;

use CFDev\Support\DateFormatHelper;
use CFDev\Tests\Unit\CFDevTestCase;

class DateFormatHelperTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Individual token conversions
    // -------------------------------------------------------------------------

    public function testYearFourDigit(): void
    {
        $this->assertSame('yy', DateFormatHelper::parse('Y'));
    }

    public function testYearTwoDigit(): void
    {
        $this->assertSame('y', DateFormatHelper::parse('y'));
    }

    public function testMonthWithLeadingZero(): void
    {
        $this->assertSame('mm', DateFormatHelper::parse('m'));
    }

    public function testMonthWithoutLeadingZero(): void
    {
        $this->assertSame('m', DateFormatHelper::parse('n'));
    }

    public function testFullMonthName(): void
    {
        $this->assertSame('MM', DateFormatHelper::parse('F'));
    }

    public function testShortMonthName(): void
    {
        $this->assertSame('M', DateFormatHelper::parse('M'));
    }

    public function testDayWithLeadingZero(): void
    {
        $this->assertSame('dd', DateFormatHelper::parse('d'));
    }

    public function testDayWithoutLeadingZero(): void
    {
        $this->assertSame('d', DateFormatHelper::parse('j'));
    }

    public function testFullDayName(): void
    {
        $this->assertSame('DD', DateFormatHelper::parse('l'));
    }

    public function testShortDayName(): void
    {
        $this->assertSame('D', DateFormatHelper::parse('D'));
    }

    // -------------------------------------------------------------------------
    // Characters with no jQuery UI equivalent → empty string
    // -------------------------------------------------------------------------

    public function testNoEquivalentBecomesEmpty(): void
    {
        $this->assertSame('', DateFormatHelper::parse('N'));
        $this->assertSame('', DateFormatHelper::parse('W'));
        $this->assertSame('', DateFormatHelper::parse('t'));
    }

    // -------------------------------------------------------------------------
    // Unknown characters pass through unchanged
    // -------------------------------------------------------------------------

    public function testSeparatorsPassThrough(): void
    {
        $this->assertSame('-', DateFormatHelper::parse('-'));
        $this->assertSame('/', DateFormatHelper::parse('/'));
        $this->assertSame('.', DateFormatHelper::parse('.'));
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', DateFormatHelper::parse(''));
    }

    // -------------------------------------------------------------------------
    // Combined formats
    // -------------------------------------------------------------------------

    public function testIsoFormat(): void
    {
        $this->assertSame('yy-mm-dd', DateFormatHelper::parse('Y-m-d'));
    }

    public function testEuropeanFormat(): void
    {
        $this->assertSame('dd/mm/yy', DateFormatHelper::parse('d/m/Y'));
    }

    public function testUsFormat(): void
    {
        $this->assertSame('mm/dd/yy', DateFormatHelper::parse('m/d/Y'));
    }

    // -------------------------------------------------------------------------
    // Escaped characters (PHP backslash → jQuery UI single-quoted literal)
    // -------------------------------------------------------------------------

    public function testEscapedCharacterBecomesLiteral(): void
    {
        // \d in PHP format → 'd' literal in jQuery UI
        $this->assertSame("'d'", DateFormatHelper::parse('\\d'));
    }

    public function testEscapedCharacterInCombinedFormat(): void
    {
        // Y-\m-d → yy-'m'-dd  (literal 'm', not month token)
        $this->assertSame("yy-'m'-dd", DateFormatHelper::parse('Y-\\m-d'));
    }
}
