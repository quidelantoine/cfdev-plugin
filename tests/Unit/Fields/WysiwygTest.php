<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Wysiwyg;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class WysiwygTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Wysiwyg
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type'  => 'wysiwyg',
            'name'  => 'my_wysiwyg',
            'label' => 'My Wysiwyg',
        ];

        return new Wysiwyg(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testDoesNotSupportRepeatable(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    // -------------------------------------------------------------------------
    // Construction — args par défaut
    // -------------------------------------------------------------------------

    public function testArgsHasTextareaName(): void
    {
        $field = $this->makeField();
        $this->assertArrayHasKey('textarea_name', $field->args);
    }

    public function testArgsTextareaNameContainsFieldId(): void
    {
        $field = $this->makeField();
        $this->assertStringContainsString($field->id, $field->args['textarea_name']);
    }

    public function testArgsTextareaNameHasCfdevPrefix(): void
    {
        $field = $this->makeField();
        $this->assertStringStartsWith('cfdev', $field->args['textarea_name']);
    }

    public function testArgsHasEditorClass(): void
    {
        $field = $this->makeField();
        $this->assertArrayHasKey('editor_class', $field->args);
    }

    public function testArgsEditorClassContainsCfdevInput(): void
    {
        $field = $this->makeField();
        $this->assertStringContainsString('cfdev-input', $field->args['editor_class']);
    }

    public function testArgsCustomEditorClassIsMerged(): void
    {
        $field = $this->makeField(['args' => ['editor_class' => 'my-custom-class']]);
        $this->assertStringContainsString('my-custom-class', $field->args['editor_class']);
        $this->assertStringContainsString('cfdev-input', $field->args['editor_class']);
    }

    public function testArgsCustomTextareaNameIsOverridden(): void
    {
        $field = $this->makeField(['args' => ['textarea_name' => 'custom_name']]);
        $this->assertSame('custom_name', $field->args['textarea_name']);
    }

    // -------------------------------------------------------------------------
    // outputHtml — appel wp_editor
    // -------------------------------------------------------------------------

    public function testOutputCallsWpEditorWithValue(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_editor')
            ->once()
            ->with('my content', \Mockery::any(), \Mockery::any())
            ->andReturn('');

        $this->makeField()->outputHtml('my content');
    }

    public function testOutputCallsWpEditorWithDefaultValueWhenEmpty(): void
    {
        $this->addToAssertionCount(1);

        Functions\expect('wp_editor')
            ->once()
            ->with('default content', \Mockery::any(), \Mockery::any())
            ->andReturn('');

        $this->makeField(['default_value' => 'default content'])->outputHtml('');
    }

    public function testOutputCallsWpEditorWithFieldId(): void
    {
        $this->addToAssertionCount(1);

        $field = $this->makeField();

        Functions\expect('wp_editor')
            ->once()
            ->with(\Mockery::any(), $field->id, \Mockery::any())
            ->andReturn('');

        $field->outputHtml('value');
    }

    public function testOutputPassesArgsWithCfdevInputClass(): void
    {
        $this->addToAssertionCount(1);

        $field = $this->makeField();

        Functions\expect('wp_editor')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::on(function (array $args): bool {
                return isset($args['editor_class'])
                    && str_contains($args['editor_class'], 'cfdev-input');
            }))
            ->andReturn('');

        $field->outputHtml('value');
    }

    public function testOutputTextareaNameUpdatedOnOutput(): void
    {
        $this->addToAssertionCount(1);

        $field = $this->makeField();

        Functions\expect('wp_editor')
            ->once()
            ->with(\Mockery::any(), \Mockery::any(), \Mockery::on(function (array $args) use ($field): bool {
                return isset($args['textarea_name'])
                    && str_contains($args['textarea_name'], $field->id);
            }))
            ->andReturn('');

        $field->outputHtml('value');
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        Functions\when('wp_editor')->justReturn('');

        $field  = $this->makeField(['explanation' => 'Write your content here']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Write your content here', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        Functions\when('wp_editor')->justReturn('');

        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
