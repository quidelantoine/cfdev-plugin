<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Yesno;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class YesnoTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Yesno
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('checked')->alias(function (mixed $checked, mixed $current, bool $echo = true): string {
            $result = $checked == $current ? ' checked="checked"' : '';
            if ($echo) {
                echo $result; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            return $result;
        });

        $defaults = [
            'type' => 'yesno',
            'name' => 'my_yesno',
            'label' => 'My Yesno',
        ];

        return new Yesno(array_merge($defaults, $overrides), 'my_metabox');
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

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-checkbox-wrap', $output);
    }

    public function testOutputRendersTwoRadioInputs(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertEquals(2, substr_count($output, 'type="radio"'));
    }

    public function testOutputHasYesValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value="yes"', $output);
    }

    public function testOutputHasNoValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value="no"', $output);
    }

    public function testOutputHasYesLabel(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('Yes', $output);
    }

    public function testOutputHasNoLabel(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('No', $output);
    }

    public function testOutputLabelsHaveCfdevLabelClass(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertEquals(2, substr_count($output, 'cfdev-label'));
    }

    public function testOutputYesIdSuffix(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString($field->id . '_yes', $output);
    }

    public function testOutputNoIdSuffix(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString($field->id . '_no', $output);
    }

    public function testOutputLabelForYesMatchesInputId(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('for="' . $field->id . '_yes"', $output);
    }

    public function testOutputLabelForNoMatchesInputId(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('for="' . $field->id . '_no"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec valeur
    // -------------------------------------------------------------------------

    public function testOutputYesCheckedWhenValueIsYes(): void
    {
        $output = $this->makeField()->outputHtml('yes');
        // checked="checked" doit apparaître une seule fois (sur yes)
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
        // Le radio yes doit être avant le premier checked
        $yes_pos = strpos($output, 'value="yes"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $yes_pos);
    }

    public function testOutputNoCheckedWhenValueIsNo(): void
    {
        $output = $this->makeField()->outputHtml('no');
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
        $no_pos = strpos($output, 'value="no"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $no_pos);
    }

    public function testOutputNoneCheckedWhenValueEmpty(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value
    // -------------------------------------------------------------------------

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field = $this->makeField(['default_value' => 'yes']);
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field = $this->makeField(['default_value' => 'yes']);
        $output = $field->outputHtml('no');
        // no doit être checké, pas yes
        $no_pos = strpos($output, 'value="no"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $no_pos);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Choose yes or no']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Choose yes or no', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
