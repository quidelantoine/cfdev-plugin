<?php

namespace CFDev\Tests\Unit\Validation\Rules;

use CFDev\Tests\Unit\CFDevTestCase;
use CFDev\Validation\Rules\DateAfter;
use CFDev\Validation\Rules\DateAfterToday;
use CFDev\Validation\Rules\DateBefore;

class DateRulesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Date_After
    // -------------------------------------------------------------------------

    public function testDateAfterValid(): void
    {
        $this->assertTrue((new DateAfter('2020-01-01'))->validate('2021-01-01'));
    }

    public function testDateAfterSameDay(): void
    {
        $this->assertFalse((new DateAfter('2020-01-01'))->validate('2020-01-01'));
    }

    public function testDateAfterBefore(): void
    {
        $this->assertFalse((new DateAfter('2020-01-01'))->validate('2019-12-31'));
    }

    public function testDateAfterInvalidValue(): void
    {
        $this->assertFalse((new DateAfter('2020-01-01'))->validate('not-a-date'));
    }

    // -------------------------------------------------------------------------
    // Date_Before
    // -------------------------------------------------------------------------

    public function testDateBeforeValid(): void
    {
        $this->assertTrue((new DateBefore('2030-01-01'))->validate('2025-01-01'));
    }

    public function testDateBeforeSameDay(): void
    {
        $this->assertFalse((new DateBefore('2030-01-01'))->validate('2030-01-01'));
    }

    public function testDateBeforeAfter(): void
    {
        $this->assertFalse((new DateBefore('2030-01-01'))->validate('2031-01-01'));
    }

    // -------------------------------------------------------------------------
    // Date_After_Today
    // -------------------------------------------------------------------------

    public function testDateAfterTodayValid(): void
    {
        $future = gmdate('Y-m-d', strtotime('+1 day'));
        $this->assertTrue((new DateAfterToday())->validate($future));
    }

    public function testDateAfterTodayToday(): void
    {
        $this->assertFalse((new DateAfterToday())->validate(gmdate('Y-m-d')));
    }

    public function testDateAfterTodayPast(): void
    {
        $past = gmdate('Y-m-d', strtotime('-1 day'));
        $this->assertFalse((new DateAfterToday())->validate($past));
    }
}
