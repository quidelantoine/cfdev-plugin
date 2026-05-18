<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Select;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class SelectTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $overrides = []): Select
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('selected')->alias(function (mixed $selected, mixed $current, bool $echo = true): string {
            $result = $selected == $current ? ' selected="selected"' : '';
            if ($echo) {
                echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return $result;
        });

        $defaults = [
            'type'    => 'select',
            'name'    => 'my_select',
            'label'   => 'My Select',
            'options' => [
                'red'   => 'Red',
                'green' => 'Green',
                'blue'  => 'Blue',
            ],
        ];

        return new Select(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsRepeatable(): void
    {
        $this->assertTrue($this->makeField()->supports_repeatable);
    }

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testHasCfdevInputSelectClass(): void
    {
        $this->assertContains('cfdev-input cfdev-select', $this->makeField()->css_classes);
    }

    // -------------------------------------------------------------------------
    // Construction — data_attributes
    // -------------------------------------------------------------------------

    public function testDataAttributesHasDefaultValueKey(): void
    {
        $this->assertArrayHasKey('default-value', $this->makeField()->data_attributes);
    }

    public function testDataAttributesDefaultValueMatchesFieldDefault(): void
    {
        $field = $this->makeField(['default_value' => 'green']);
        $this->assertSame('green', $field->data_attributes['default-value']);
    }

    public function testDataAttributesDefaultValueIsNullWhenNoDefault(): void
    {
        $this->assertSame('', $this->makeField()->data_attributes['default-value']);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersSelectTag(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('</select>', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field  = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsIdAttribute(): void
    {
        $field  = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('id=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputRendersOneOptionPerEntry(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertEquals(3, substr_count($output, '<option'));
    }

    public function testOutputRendersOptionValues(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value="red"', $output);
        $this->assertStringContainsString('value="green"', $output);
        $this->assertStringContainsString('value="blue"', $output);
    }

    public function testOutputRendersOptionLabels(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('Red', $output);
        $this->assertStringContainsString('Green', $output);
        $this->assertStringContainsString('Blue', $output);
    }

    public function testOutputEmptyWhenNoOptions(): void
    {
        $field  = $this->makeField(['options' => []]);
        $output = $field->outputHtml('');
        $this->assertEquals(0, substr_count($output, '<option'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — show_option_none
    // -------------------------------------------------------------------------

    public function testOutputRendersNoneOptionWhenArgSet(): void
    {
        $field  = $this->makeField(['args' => ['show_option_none' => '— Select —']]);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('— Select —', $output);
        $this->assertStringContainsString('value="0"', $output);
    }

    public function testOutputNoneOptionIsSelectedWhenValueEmpty(): void
    {
        $field  = $this->makeField(['args' => ['show_option_none' => '— Select —']]);
        $output = $field->outputHtml('');
        // L'option 0 doit avoir selected="selected"
        $pos_zero     = strpos($output, 'value="0"');
        $pos_selected = strpos($output, 'selected="selected"');
        $this->assertNotFalse($pos_selected);
        $this->assertLessThan($pos_selected, $pos_zero);
    }

    public function testOutputNoneOptionNotSelectedWhenValueSet(): void
    {
        $field  = $this->makeField(['args' => ['show_option_none' => '— Select —']]);
        $output = $field->outputHtml('red');
        // value="0" ne doit pas être suivi de selected dans la même option
        $this->assertStringNotContainsString('value="0" selected="selected"', $output);
    }

    public function testOutputNoNoneOptionByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('value="0"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec value
    // -------------------------------------------------------------------------

    public function testOutputSelectsMatchingOption(): void
    {
        $output = $this->makeField()->outputHtml('green');
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
    }

    public function testOutputSelectedOptionIsCorrect(): void
    {
        $output   = $this->makeField()->outputHtml('blue');
        $blue_pos = strpos($output, 'value="blue"');
        $sel_pos  = strpos($output, 'selected="selected"');
        $this->assertLessThan($sel_pos, $blue_pos);
    }

    public function testOutputNoSelectedWhenValueNotInOptions(): void
    {
        $output = $this->makeField()->outputHtml('yellow');
        $this->assertEquals(0, substr_count($output, 'selected="selected"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — selected state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputSelectsDefaultValueWhenValueEmpty(): void
    {
        $field  = $this->makeField(['default_value' => 'red']);
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
        $red_pos = strpos($output, 'value="red"');
        $sel_pos = strpos($output, 'selected="selected"');
        $this->assertLessThan($sel_pos, $red_pos);
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field  = $this->makeField(['default_value' => 'red']);
        $output = $field->outputHtml('blue');
        // Seul blue doit être sélectionné
        $this->assertEquals(1, substr_count($output, 'selected="selected"'));
        $blue_pos = strpos($output, 'value="blue"');
        $sel_pos  = strpos($output, 'selected="selected"');
        $this->assertLessThan($sel_pos, $blue_pos);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Pick a color']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Pick a color', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
