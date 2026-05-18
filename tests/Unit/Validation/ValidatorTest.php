<?php

namespace Weblitzer\CFDev\Tests\Unit\Validation;

use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Weblitzer\CFDev\Validation\Rules\Max;
use Weblitzer\CFDev\Validation\Rules\Min;
use Weblitzer\CFDev\Validation\Rules\MinLength;
use Weblitzer\CFDev\Validation\Rules\Required;
use Weblitzer\CFDev\Validation\Validator;

class ValidatorTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // passes()
    // -------------------------------------------------------------------------

    public function testPassesWithNoRules(): void
    {
        $this->assertTrue((new Validator('anything', []))->passes());
    }

    public function testPassesWhenAllRulesPass(): void
    {
        $this->assertTrue(
            (new Validator('hello', [new Required(), new MinLength(3)]))->passes()
        );
    }

    public function testFailsWhenOneRuleFails(): void
    {
        $this->assertFalse(
            (new Validator('', [new Required()]))->passes()
        );
    }

    public function testFailsWhenAllRulesFail(): void
    {
        $this->assertFalse(
            (new Validator('', [new Required(), new MinLength(5)]))->passes()
        );
    }

    public function testFailsWhenOnlyLastRuleFails(): void
    {
        // Required passes ('hi'), MinLength(5) fails (length 2)
        $this->assertFalse(
            (new Validator('hi', [new Required(), new MinLength(5)]))->passes()
        );
    }

    // -------------------------------------------------------------------------
    // errors()
    // -------------------------------------------------------------------------

    public function testErrorsEmptyWhenAllPass(): void
    {
        $this->assertSame(
            [],
            (new Validator('hello', [new Required()]))->errors()
        );
    }

    public function testErrorsContainsMessageFromFailedRule(): void
    {
        $errors = (new Validator('', [new Required()]))->errors();

        $this->assertCount(1, $errors);
        $this->assertNotEmpty($errors[0]);
    }

    public function testErrorsCollectsAllFailures(): void
    {
        // Both Min(10) and Max(5) fail for value 7 — wait, value 7 passes Min(5) but fails Max(5)
        // Let's use value -1: fails Min(0) and fails Positive
        $errors = (new Validator(-1, [new Min(0), new Max(-5)]))->errors();

        // Min(0) fails (-1 < 0), Max(-5) fails (-1 > -5)... wait
        // Min(0)->validate(-1) = is_numeric(-1) && -1 >= 0 = false ✓
        // Max(-5)->validate(-1) = is_numeric(-1) && -1 <= -5 = false ✓
        $this->assertCount(2, $errors);
    }

    public function testErrorsOnlyContainsFailedRulesNotPassed(): void
    {
        // Required passes, MinLength(10) fails for 'hi'
        $errors = (new Validator('hi', [new Required(), new MinLength(10)]))->errors();

        $this->assertCount(1, $errors);
    }

    // -------------------------------------------------------------------------
    // Validation runs immediately on construction
    // -------------------------------------------------------------------------

    public function testValidationRunsAtConstruction(): void
    {
        $v = new Validator('', [new Required()]);

        // No need to call run() — already evaluated
        $this->assertFalse($v->passes());
        $this->assertCount(1, $v->errors());
    }
}
