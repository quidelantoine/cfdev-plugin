<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Textarea;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class TextareaTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Textarea
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type' => 'textarea',
            'name' => 'my_textarea',
            'label' => 'My Textarea',
        ];

        return new Textarea(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testDefaultCssClass(): void
    {
        $field = $this->makeField();
        $this->assertContains('cfdev-input', $field->css_classes);
    }

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

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersTextareaTag(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('Hello');
        $this->assertStringContainsString('<textarea', $output);
        $this->assertStringContainsString('</textarea>', $output);
    }

    public function testOutputContainsValue(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('My content');
        $this->assertStringContainsString('My content', $output);
    }

    public function testOutputContainsNameAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('value');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsIdAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('value');
        $this->assertStringContainsString('id=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsCssClass(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('value');
        $this->assertStringContainsString('cfdev-input', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — valeur vs default_value
    // -------------------------------------------------------------------------

    public function testOutputUsesValueWhenNotEmpty(): void
    {
        $field = $this->makeField(['default_value' => 'default text']);
        $output = $field->outputHtml('actual value');
        $this->assertStringContainsString('actual value', $output);
        $this->assertStringNotContainsString('default text', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field = $this->makeField(['default_value' => 'default text']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('default text', $output);
    }

    public function testOutputEmptyWhenNoValueAndNoDefault(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertMatchesRegularExpression('/<textarea[^>]*><\/textarea>/', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Enter a description']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Enter a description', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationWhenRepeatable(): void
    {
        $field = $this->makeField([
            'explanation' => 'Should not appear',
            'repeatable' => true,
        ]);
        $output = $field->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
