<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\MultiSelect;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class MultiSelectTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $overrides = []): MultiSelect
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type' => 'multi_select',
            'name' => 'my_multi',
            'label' => 'My Multi',
            'options' => [
                'red' => 'Red',
                'green' => 'Green',
                'blue' => 'Blue',
            ],
        ];

        return new MultiSelect(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testDoesNotSupportAjax(): void
    {
        $this->assertFalse($this->makeField()->supports_ajax);
    }

    public function testHasCssClasses(): void
    {
        $this->assertContains('cfdev-input cfdev-select cfdev-multi-select', $this->makeField()->css_classes);
    }

    public function testDefaultValueIsCastToArray(): void
    {
        $field = $this->makeField(['default_value' => 'red']);
        $this->assertIsArray($field->default_value);
    }

    public function testAfterHasArraySuffix(): void
    {
        $this->assertStringContainsString('[]', $this->makeField()->after);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersSelectTag(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('</select>', $output);
    }

    public function testOutputHasMultipleAttribute(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('multiple="true"', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputRendersOneOptionPerEntry(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertEquals(3, substr_count($output, '<option'));
    }

    public function testOutputRendersOptionValues(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('value="red"', $output);
        $this->assertStringContainsString('value="green"', $output);
        $this->assertStringContainsString('value="blue"', $output);
    }

    public function testOutputRendersOptionLabels(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('Red', $output);
        $this->assertStringContainsString('Green', $output);
        $this->assertStringContainsString('Blue', $output);
    }

    public function testOutputEmptyWhenNoOptions(): void
    {
        $output = $this->makeField(['options' => []])->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, '<option'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec value array
    // -------------------------------------------------------------------------

    public function testOutputSelectsMatchingValues(): void
    {
        $output = $this->makeField()->outputHtml(['red', 'blue']);
        $this->assertEquals(2, substr_count($output, 'selected="selected"'));
    }

    public function testOutputSelectsSingleValue(): void
    {
        $output = $this->makeField()->outputHtml(['green']);
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
    }

    public function testOutputSelectsAllValues(): void
    {
        $output = $this->makeField()->outputHtml(['red', 'green', 'blue']);
        $this->assertEquals(3, substr_count($output, 'selected="selected"'));
    }

    public function testOutputNoSelectedWhenValueNotInOptions(): void
    {
        $output = $this->makeField()->outputHtml(['yellow']);
        $this->assertEquals(0, substr_count($output, 'selected="selected"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec value = '-1'
    // -------------------------------------------------------------------------

    public function testOutputNoSelectedWhenValueIsMinusOne(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, 'selected="selected"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputSelectsDefaultValuesWhenValueIsEmptyString(): void
    {
        $field = $this->makeField(['default_value' => ['red', 'green']]);
        $output = $field->outputHtml('');
        $this->assertEquals(2, substr_count($output, 'selected="selected"'));
    }

    public function testOutputSelectsDefaultValueSingle(): void
    {
        $field = $this->makeField(['default_value' => ['blue']]);
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field = $this->makeField(['default_value' => ['red']]);
        $output = $field->outputHtml(['blue']);
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
        $this->assertStringContainsString('value="blue"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — show_option_none
    // -------------------------------------------------------------------------

    public function testOutputRendersNoneOptionWhenArgSet(): void
    {
        $field = $this->makeField(['args' => ['show_option_none' => '— None —']]);
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('— None —', $output);
        $this->assertStringContainsString('value="0"', $output);
    }

    public function testOutputNoneOptionSelectedWhenValueContainsZero(): void
    {
        $field = $this->makeField(['args' => ['show_option_none' => '— None —']]);
        $output = $field->outputHtml([0]);
        $zero_pos = strpos($output, 'value="0"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertNotFalse($sel_pos);
        $this->assertLessThan($sel_pos, $zero_pos);
    }

    public function testOutputNoneOptionNotSelectedWhenValueIsMinusOne(): void
    {
        $field = $this->makeField(['args' => ['show_option_none' => '— None —']]);
        $output = $field->outputHtml('-1');
        $this->assertStringNotContainsString('selected="selected"', $output);
    }

    public function testOutputNoneOptionSelectedFromDefaultValue(): void
    {
        $field = $this->makeField([
            'args' => ['show_option_none' => '— None —'],
            'default_value' => [0],
        ]);
        $output = $field->outputHtml('');
        $zero_pos = strpos($output, 'value="0"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertNotFalse($sel_pos);
        $this->assertLessThan($sel_pos, $zero_pos);
    }

    public function testOutputNoNoneOptionByDefault(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringNotContainsString('value="0"', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueReturnsValueWhenNotEmpty(): void
    {
        $result = $this->makeField()->saveValue(['red', 'blue']);
        $this->assertSame(['red', 'blue'], $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyArray(): void
    {
        $result = $this->makeField()->saveValue([]);
        $this->assertSame('-1', $result);
    }

    public function testSaveValueReturnsMinusOneWhenEmptyString(): void
    {
        $result = $this->makeField()->saveValue('');
        $this->assertSame('-1', $result);
    }

    public function testSaveValueSingleItemArray(): void
    {
        $result = $this->makeField()->saveValue(['green']);
        $this->assertSame(['green'], $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Pick multiple colors']);
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('Pick multiple colors', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
