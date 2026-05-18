<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Checkbox;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class CheckboxTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Checkbox
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('checked')->alias(function (mixed $checked, mixed $current, bool $echo = true): string {
            $result = $checked == $current ? ' checked="checked"' : '';
            if ($echo) {
                echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return $result;
        });

        $defaults = [
            'type' => 'checkbox',
            'name' => 'my_checkbox',
            'label' => 'My Checkbox',
        ];

        return new Checkbox(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-checkbox-wrap', $output);
    }

    public function testOutputRendersCheckboxInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="checkbox"', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsIdAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('id=', $output);
        $this->assertStringContainsString($field->id, $output);
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

    public function testOutputNotCheckedWhenValueIsOff(): void
    {
        $output = $this->makeField()->outputHtml('off');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    public function testOutputNotCheckedWhenValueIsEmpty(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputCheckedFromDefaultValueWhenValueEmpty(): void
    {
        $field = $this->makeField(['default_value' => 'on']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testOutputNotCheckedFromDefaultValueWhenDefaultIsOff(): void
    {
        $field = $this->makeField(['default_value' => 'off']);
        $output = $field->outputHtml('');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        // value='off' avec default='on' → pas coché
        $field = $this->makeField(['default_value' => 'on']);
        $output = $field->outputHtml('off');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    public function testOutputValueOnTakesPriorityOverEmptyDefault(): void
    {
        $field = $this->makeField(['default_value' => '']);
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

    public function testSaveValueReturnsValueWhenNotEmpty(): void
    {
        $result = $this->makeField()->saveValue('custom');
        $this->assertSame('custom', $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Check to enable']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Check to enable', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
