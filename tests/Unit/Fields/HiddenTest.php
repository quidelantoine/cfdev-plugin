<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Hidden;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class HiddenTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Hidden
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type'  => 'hidden',
            'name'  => 'my_hidden',
            'label' => 'My Hidden',
        ];

        return new Hidden(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testDoesNotSupportRepeatable(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    public function testDoesNotSupportAjax(): void
    {
        $this->assertFalse($this->makeField()->supports_ajax);
    }

    public function testDoesNotSupportBundle(): void
    {
        $this->assertFalse($this->makeField()->supports_bundle);
    }

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersHiddenInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="hidden"', $output);
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

    public function testOutputContainsClassAttribute(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-input', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — valeur vs default_value
    // -------------------------------------------------------------------------

    public function testOutputContainsValue(): void
    {
        $output = $this->makeField()->outputHtml('my-token');
        $this->assertStringContainsString('value="my-token"', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => 'default-token']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('value="default-token"', $output);
    }

    public function testOutputValueTakesPriorityOverDefault(): void
    {
        $field  = $this->makeField(['default_value' => 'default-token']);
        $output = $field->outputHtml('actual-token');
        $this->assertStringContainsString('value="actual-token"', $output);
        $this->assertStringNotContainsString('default-token', $output);
    }

    public function testOutputEmptyValueAttributeWhenNoValueAndNoDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value=""', $output);
    }

    public function testOutputNumericValue(): void
    {
        $output = $this->makeField()->outputHtml('42');
        $this->assertStringContainsString('value="42"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — data_attributes
    // -------------------------------------------------------------------------

    public function testOutputRendersCustomDataAttributes(): void
    {
        $field = $this->makeField();
        $field->data_attributes['foo'] = 'bar';
        $output = $field->outputHtml('');
        $this->assertStringContainsString('data-foo="bar"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Internal use only']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Internal use only', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
