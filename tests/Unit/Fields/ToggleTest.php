<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Toggle;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class ToggleTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $overrides = []): Toggle
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type'  => 'toggle',
            'name'  => 'my_toggle',
            'label' => 'My Toggle',
        ];

        return new Toggle(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testDoesNotSupportRepeatable(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
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
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersToggleWrapper(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-toggle-wrap', $output);
    }

    public function testOutputRendersSwitchLabel(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-switch', $output);
    }

    public function testOutputRendersSliderSpan(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-switch-slider', $output);
    }

    public function testOutputRendersCheckboxInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="checkbox"', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field  = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsIdAttributeOnInput(): void
    {
        $field  = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('id="' . $field->id . '"', $output);
        $this->assertStringContainsString('type="checkbox"', $output);
    }

    public function testOutputContainsCssClass(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-input', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value
    // -------------------------------------------------------------------------

    public function testOutputCheckedWhenValueIsOn(): void
    {
        $output = $this->makeField()->outputHtml('on');
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testOutputNotCheckedWhenValueIsEmpty(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    public function testOutputNotCheckedWhenValueIsMinusOne(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputCheckedFromDefaultValueWhenValueEmpty(): void
    {
        $field  = $this->makeField(['default_value' => 'on']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testOutputNotCheckedFromDefaultValueWhenDefaultIsOff(): void
    {
        $field  = $this->makeField(['default_value' => 'off']);
        $output = $field->outputHtml('');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    public function testOutputValueOnTakesPriorityOverEmptyDefault(): void
    {
        $field  = $this->makeField(['default_value' => '']);
        $output = $field->outputHtml('on');
        $this->assertStringContainsString('checked="checked"', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueReturnsValueWhenOn(): void
    {
        $result = $this->makeField()->saveValue('on');
        $this->assertSame('on', $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyString(): void
    {
        $result = $this->makeField()->saveValue('');
        $this->assertSame('-1', $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyArray(): void
    {
        $result = $this->makeField()->saveValue([]);
        $this->assertSame('-1', $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Enable this option']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Enable this option', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
