<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class RestFieldTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
    }

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Field
    {
        return new Field(array_merge(['type' => 'text', 'id' => 'my_field', 'name' => 'My Field'], $overrides), null);
    }

    // -------------------------------------------------------------------------
    // Default value
    // -------------------------------------------------------------------------

    public function testRestDefaultsFalse(): void
    {
        $field = $this->makeField();
        $this->assertFalse($field->rest);
    }

    // -------------------------------------------------------------------------
    // Opt-in
    // -------------------------------------------------------------------------

    public function testRestCanBeSetTrue(): void
    {
        $field = $this->makeField(['rest' => true]);
        $this->assertTrue($field->rest);
    }

    // -------------------------------------------------------------------------
    // restType()
    // -------------------------------------------------------------------------

    public function testRestTypeDefaultsToString(): void
    {
        $this->assertSame('string', $this->makeField(['type' => 'text'])->restType());
    }

    public function testRestTypeTextareaIsString(): void
    {
        $this->assertSame('string', $this->makeField(['type' => 'textarea'])->restType());
    }

    public function testRestTypeSelectIsString(): void
    {
        $this->assertSame('string', $this->makeField(['type' => 'select'])->restType());
    }

    public function testRestTypeNumberIsNumber(): void
    {
        $this->assertSame('number', $this->makeField(['type' => 'number'])->restType());
    }

    public function testRestTypeImageIsString(): void
    {
        $this->assertSame('string', $this->makeField(['type' => 'image'])->restType());
    }
}
