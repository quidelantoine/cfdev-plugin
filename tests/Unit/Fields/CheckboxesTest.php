<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Checkboxes;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class CheckboxesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Checkboxes
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type' => 'checkboxes',
            'name' => 'my_checkboxes',
            'label' => 'My Checkboxes',
            'options' => [
                'php' => 'PHP',
                'javascript' => 'JavaScript',
                'python' => 'Python',
            ],
        ];

        return new Checkboxes(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    public function testDefaultValueIsCastToArray(): void
    {
        $field = $this->makeField(['default_value' => 'php']);
        $this->assertIsArray($field->default_value);
    }

    public function testAfterHasArraySuffix(): void
    {
        $this->assertStringContainsString('[]', $this->makeField()->after);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
        $this->assertStringContainsString('cfdev-padding-wrap', $output);
    }

    public function testOutputRendersOneCheckboxPerOption(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertEquals(3, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputRendersOptionValues(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('value="php"', $output);
        $this->assertStringContainsString('value="javascript"', $output);
        $this->assertStringContainsString('value="python"', $output);
    }

    public function testOutputRendersOptionLabels(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringContainsString('PHP', $output);
        $this->assertStringContainsString('JavaScript', $output);
        $this->assertStringContainsString('Python', $output);
    }

    public function testOutputEmptyWhenNoOptions(): void
    {
        $output = $this->makeField(['options' => []])->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, 'type="checkbox"'));
    }

    public function testOutputLabelForMatchesInputId(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('for="' . $field->id . '_php"', $output);
        $this->assertStringContainsString('id="' . $field->id . '_php"', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value array
    // -------------------------------------------------------------------------

    public function testOutputChecksMatchingValues(): void
    {
        $output = $this->makeField()->outputHtml(['php', 'python']);
        $this->assertEquals(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksSingleValue(): void
    {
        $output = $this->makeField()->outputHtml(['javascript']);
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksAllValues(): void
    {
        $output = $this->makeField()->outputHtml(['php', 'javascript', 'python']);
        $this->assertEquals(3, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenValueNotInOptions(): void
    {
        $output = $this->makeField()->outputHtml(['ruby']);
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenValueIsEmptyArray(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value = '-1'
    // -------------------------------------------------------------------------

    public function testOutputNoCheckedWhenValueIsMinusOne(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputChecksDefaultValuesWhenValueNotArrayAndNotMinusOne(): void
    {
        $field = $this->makeField(['default_value' => ['php', 'python']]);
        $output = $field->outputHtml('');
        $this->assertEquals(2, substr_count($output, 'checked="checked"'));
    }

    public function testOutputChecksSingleDefaultValue(): void
    {
        $field = $this->makeField(['default_value' => ['javascript']]);
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field = $this->makeField(['default_value' => ['php']]);
        $output = $field->outputHtml(['python']);
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
        $python_pos = strpos($output, 'value="python"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $python_pos);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueReturnsValueWhenNotEmpty(): void
    {
        $result = $this->makeField()->saveValue(['php', 'python']);
        $this->assertSame(['php', 'python'], $result);
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
        $result = $this->makeField()->saveValue(['javascript']);
        $this->assertSame(['javascript'], $result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Pick your languages']);
        $output = $field->outputHtml('-1');
        $this->assertStringContainsString('Pick your languages', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('-1');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
