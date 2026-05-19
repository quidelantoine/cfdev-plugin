<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Range;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class RangeTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Range
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = ['type' => 'range', 'name' => 'my_range', 'label' => 'My Range'];

        return new Range(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction
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

    public function testHasRangeCssClasses(): void
    {
        $classes = $this->makeField()->css_classes;
        $this->assertContains('cfdev-range', $classes);
        $this->assertContains('js-cfdev-range', $classes);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure
    // -------------------------------------------------------------------------

    public function testOutputRendersRangeInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="range"', $output);
    }

    public function testOutputWrappedInRangeWrap(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-range-wrap', $output);
    }

    public function testOutputContainsOutputElement(): void
    {
        $output = $this->makeField()->outputHtml('50');
        $this->assertStringContainsString('<output', $output);
        $this->assertStringContainsString('cfdev-range-output', $output);
        $this->assertStringContainsString('js-cfdev-range-output', $output);
    }

    public function testOutputDisplaysValueInOutputElement(): void
    {
        $output = $this->makeField()->outputHtml('75');
        $this->assertStringContainsString('value="75"', $output);
        // output element should also contain the value as text
        $this->assertMatchesRegularExpression('/<output[^>]*>75<\/output>/', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => '30']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('value="30"', $output);
    }

    public function testOutputDefaultsToZeroWhenNoValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value="0"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — min / max / step
    // -------------------------------------------------------------------------

    public function testOutputRendersDefaultMinMaxStep(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('min="0"', $output);
        $this->assertStringContainsString('max="100"', $output);
        $this->assertStringContainsString('step="1"', $output);
    }

    public function testOutputRendersCustomMinMaxStep(): void
    {
        $field  = $this->makeField(['args' => ['min' => 10, 'max' => 50, 'step' => 5]]);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('min="10"', $output);
        $this->assertStringContainsString('max="50"', $output);
        $this->assertStringContainsString('step="5"', $output);
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
        $this->assertSame('3.5', $this->makeField()->saveValue('3.5'));
    }

    public function testSaveValueNonNumericReturnsEmpty(): void
    {
        $this->assertSame('', $this->makeField()->saveValue('bad'));
    }

    public function testSaveValueArray(): void
    {
        $result = $this->makeField()->saveValue(['10', '50', 'bad']);
        $this->assertSame(['10', '50', ''], $result);
    }
}
