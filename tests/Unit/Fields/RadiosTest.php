<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Radios;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class RadiosTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Radios
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
        Functions\when('maybe_unserialize')->returnArg(1);

        $defaults = [
            'type' => 'radios',
            'name' => 'my_radios',
            'label' => 'My Radios',
            'options' => [
                'small' => 'Small',
                'medium' => 'Medium',
                'large' => 'Large',
            ],
        ];

        return new Radios(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testAfterHasArraySuffix(): void
    {
        $this->assertStringContainsString('[]', $this->makeField()->after);
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
        $field = $this->makeField(['default_value' => 'medium']);
        $this->assertSame('medium', $field->data_attributes['default-value']);
    }

    public function testDataAttributesDefaultValueIsEmptyWhenNoDefault(): void
    {
        $this->assertSame('', $this->makeField()->data_attributes['default-value']);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersWrapperDiv(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
    }

    public function testOutputWrapperHasDataAttributes(): void
    {
        $field = $this->makeField(['default_value' => 'small']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('data-default-value=', $output);
    }

    public function testOutputRendersOneRadioPerOption(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertEquals(3, substr_count($output, 'type="radio"'));
    }

    public function testOutputRendersOptionValues(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value="small"', $output);
        $this->assertStringContainsString('value="medium"', $output);
        $this->assertStringContainsString('value="large"', $output);
    }

    public function testOutputRendersOptionLabels(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('Small', $output);
        $this->assertStringContainsString('Medium', $output);
        $this->assertStringContainsString('Large', $output);
    }

    public function testOutputEmptyWhenNoOptions(): void
    {
        $field = $this->makeField(['options' => []]);
        $output = $field->outputHtml('');
        $this->assertEquals(0, substr_count($output, 'type="radio"'));
    }

    public function testOutputLabelForMatchesInputId(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('for="' . $field->id . '_small"', $output);
        $this->assertStringContainsString('id="' . $field->id . '_small"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec value (array)
    // -------------------------------------------------------------------------

    public function testOutputChecksMatchingValue(): void
    {
        $output = $this->makeField()->outputHtml(['medium']);
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
    }

    public function testOutputCheckedOptionIsCorrect(): void
    {
        $output = $this->makeField()->outputHtml(['large']);
        $large_pos = strpos($output, 'value="large"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $large_pos);
    }

    public function testOutputNoCheckedWhenValueNotInOptions(): void
    {
        $output = $this->makeField()->outputHtml(['xlarge']);
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    public function testOutputNoCheckedWhenValueIsEmptyString(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — checked state avec default_value (value vide)
    // -------------------------------------------------------------------------

    public function testOutputChecksDefaultValueWhenValueEmpty(): void
    {
        $field = $this->makeField(['default_value' => 'small']);
        $output = $field->outputHtml('');
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
        $small_pos = strpos($output, 'value="small"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $small_pos);
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field = $this->makeField(['default_value' => 'small']);
        $output = $field->outputHtml(['large']);
        $this->assertEquals(1, substr_count($output, 'checked="checked"'));
        $large_pos = strpos($output, 'value="large"');
        $checked_pos = strpos($output, 'checked="checked"');
        $this->assertLessThan($checked_pos, $large_pos);
    }

    // -------------------------------------------------------------------------
    // outputHtml — maybe_unserialize
    // -------------------------------------------------------------------------

    public function testOutputHandlesSerializedValue(): void
    {
        // maybe_unserialize est mocké avec returnArg(1), on passe un array directement
        $output = $this->makeField()->outputHtml(['medium']);
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testOutputHandlesNonArrayAfterUnserialize(): void
    {
        // Si maybe_unserialize retourne une string (pas un array),
        // le code utilise array() donc aucun item ne sera coché
        Functions\when('maybe_unserialize')->justReturn('medium');
        $output = $this->makeField()->outputHtml('medium');
        $this->assertEquals(0, substr_count($output, 'checked="checked"'));
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Pick a size']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Pick a size', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
