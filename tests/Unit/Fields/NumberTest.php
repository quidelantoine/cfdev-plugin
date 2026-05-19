<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Number;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class NumberTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Number
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type'  => 'number',
            'name'  => 'my_number',
            'label' => 'My Number',
        ];

        return new Number(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsRepeatable(): void
    {
        $this->assertTrue($this->makeField()->supports_repeatable);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure
    // -------------------------------------------------------------------------

    public function testOutputRendersNumberInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="number"', $output);
    }

    public function testOutputContainsValue(): void
    {
        $output = $this->makeField()->outputHtml('42');
        $this->assertStringContainsString('value="42"', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => '10']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('value="10"', $output);
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field  = $this->makeField(['default_value' => '10']);
        $output = $field->outputHtml('99');
        $this->assertStringContainsString('value="99"', $output);
        $this->assertStringNotContainsString('value="10"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — args min / max / step
    // -------------------------------------------------------------------------

    public function testOutputRendersMinAttribute(): void
    {
        $output = $this->makeField(['args' => ['min' => 0]])->outputHtml('');
        $this->assertStringContainsString('min="0"', $output);
    }

    public function testOutputRendersMaxAttribute(): void
    {
        $output = $this->makeField(['args' => ['max' => 100]])->outputHtml('');
        $this->assertStringContainsString('max="100"', $output);
    }

    public function testOutputRendersStepAttribute(): void
    {
        $output = $this->makeField(['args' => ['step' => 0.5]])->outputHtml('');
        $this->assertStringContainsString('step="0.5"', $output);
    }

    public function testOutputNoMinMaxStepByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('min=', $output);
        $this->assertStringNotContainsString('max=', $output);
        $this->assertStringNotContainsString('step=', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueInteger(): void
    {
        $this->assertSame('42', $this->makeField()->saveValue('42'));
    }

    public function testSaveValueFloat(): void
    {
        $this->assertSame('3.14', $this->makeField()->saveValue('3.14'));
    }

    public function testSaveValueNegative(): void
    {
        $this->assertSame('-5', $this->makeField()->saveValue('-5'));
    }

    public function testSaveValueEmptyString(): void
    {
        $this->assertSame('', $this->makeField()->saveValue(''));
    }

    public function testSaveValueNonNumericReturnsEmpty(): void
    {
        $this->assertSame('', $this->makeField()->saveValue('not-a-number'));
    }

    public function testSaveValueXssReturnsEmpty(): void
    {
        $this->assertSame('', $this->makeField()->saveValue('<script>1</script>'));
    }

    public function testSaveValueArray(): void
    {
        $result = $this->makeField()->saveValue(['1', '2.5', 'bad']);
        $this->assertSame(['1', '2.5', ''], $result);
    }
}
