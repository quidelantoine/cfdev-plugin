<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class TextTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Text
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('wp_strip_all_tags')->alias(function (string $value): string {
            return strip_tags($value); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter
        });
        Functions\when('sanitize_text_field')->alias(function (string $value): string {
            return wp_strip_all_tags($value);
        });

        $defaults = [
            'type'  => 'text',
            'name'  => 'my_field',
            'label' => 'My Field',
        ];

        return new Text(array_merge($defaults, $overrides), 'my_metabox');
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
        $field = $this->makeField();
        $this->assertTrue($field->supports_repeatable);
    }

    public function testSupportsBundle(): void
    {
        $field = $this->makeField();
        $this->assertTrue($field->supports_bundle);
    }

    public function testSupportsAjax(): void
    {
        $field = $this->makeField();
        $this->assertTrue($field->supports_ajax);
    }

    public function testLabelIsSet(): void
    {
        $field = $this->makeField(['label' => 'First name']);
        $this->assertSame('First name', $field->label);
    }

    public function testRequiredFlagAddsRequiredRule(): void
    {
        $field = $this->makeField(['required' => true]);
        $this->assertNotEmpty($field->validate('')->errors());
    }

    public function testNotRequiredByDefault(): void
    {
        $field = $this->makeField();
        $this->assertEmpty($field->validate('')->errors());
    }

    // -------------------------------------------------------------------------
    // saveValue — string
    // -------------------------------------------------------------------------

    public function testSaveValueStripsHtmlTags(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('<script>alert("xss")</script>');
        $this->assertSame('alert("xss")', $result);
    }

    public function testSaveValuePlainStringUnchanged(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('Hello World');
        $this->assertSame('Hello World', $result);
    }

    public function testSaveValueAmpersandPreserved(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('Tom & Jerry');
        $this->assertSame('Tom & Jerry', $result);
    }

    public function testSaveValueSingleQuotePreserved(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue("l'apostrophe");
        $this->assertSame("l'apostrophe", $result);
    }

    public function testSaveValueDoubleQuotePreserved(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('"quoted"');
        $this->assertSame('"quoted"', $result);
    }

    public function testSaveValueEmptyString(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('');
        $this->assertSame('', $result);
    }

    // -------------------------------------------------------------------------
    // saveValue — array (repeatable)
    // -------------------------------------------------------------------------

    public function testSaveValueArrayStripsTagsFromEachItem(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue(['<b>bold</b>', 'plain', '"quoted"']);

        $this->assertIsArray($result);
        $this->assertSame('bold', $result[0]);
        $this->assertSame('plain', $result[1]);
        $this->assertSame('"quoted"', $result[2]);
    }

    public function testSaveValueNestedArrayStripsTagsRecursively(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue([['<script>', 'safe'], ['<img>']]);

        $this->assertSame('', $result[0][0]);
        $this->assertSame('safe', $result[0][1]);
        $this->assertSame('', $result[1][0]);
    }

    public function testSaveValueEmptyArray(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue([]);
        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // outputName / outputId
    // -------------------------------------------------------------------------

    public function testOutputNameContainsFieldId(): void
    {
        $field = $this->makeField();
        $this->assertStringContainsString($field->id, $field->outputName());
    }

    public function testOutputNameOverwrite(): void
    {
        $field = $this->makeField();
        $this->assertSame('name="custom_name"', $field->outputName('custom_name'));
    }

    public function testOutputIdContainsFieldId(): void
    {
        $field = $this->makeField();
        $this->assertStringContainsString($field->id, $field->outputId());
    }

    public function testOutputIdOverwrite(): void
    {
        $field = $this->makeField();
        $this->assertSame('id="custom_id"', $field->outputId('custom_id'));
    }

    // -------------------------------------------------------------------------
    // outputCssClass
    // -------------------------------------------------------------------------

    public function testOutputCssClassContainsDefault(): void
    {
        $field = $this->makeField();
        $this->assertStringContainsString('cfdev-input', $field->outputCssClass());
    }

    public function testOutputCssClassMergesExtra(): void
    {
        $field  = $this->makeField();
        $output = $field->outputCssClass(['extra-class']);
        $this->assertStringContainsString('cfdev-input', $output);
        $this->assertStringContainsString('extra-class', $output);
    }

    // -------------------------------------------------------------------------
    // outputExplanation
    // -------------------------------------------------------------------------

    public function testOutputExplanationEmptyWhenNoExplanation(): void
    {
        $field = $this->makeField();
        $this->assertSame('', $field->outputExplanation());
    }

    public function testOutputExplanationRendersWhenSet(): void
    {
        $field = $this->makeField(['explanation' => 'Enter your name']);
        $this->assertStringContainsString('Enter your name', $field->outputExplanation());
        $this->assertStringContainsString('cfdev-explanation', $field->outputExplanation());
    }

    public function testOutputExplanationEmptyWhenRepeatable(): void
    {
        $field = $this->makeField([
            'explanation' => 'Should not appear',
            'repeatable'  => true,
        ]);
        $this->assertSame('', $field->outputExplanation());
    }
}
