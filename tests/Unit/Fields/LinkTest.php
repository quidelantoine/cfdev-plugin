<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Link;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class LinkTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Link
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_url_raw')->returnArg(1);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');

        $defaults = [
            'type'  => 'link',
            'name'  => 'my_link',
            'label' => 'My Link',
        ];

        return new Link(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsRepeatableIsFalse(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    public function testSupportsBundleIsTrue(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testSupportsAjaxIsFalse(): void
    {
        $this->assertFalse($this->makeField()->supports_ajax);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure
    // -------------------------------------------------------------------------

    public function testOutputRendersLinkWrap(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('cfdev-link-wrap', $output);
    }

    public function testOutputRendersUrlInput(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('type="url"', $output);
        $this->assertStringContainsString('cfdev-link-url', $output);
    }

    public function testOutputRendersTextInput(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('type="text"', $output);
        $this->assertStringContainsString('cfdev-link-text', $output);
    }

    public function testOutputRendersTargetSelect(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('cfdev-link-target', $output);
        $this->assertStringContainsString('_self', $output);
        $this->assertStringContainsString('_blank', $output);
    }

    public function testOutputInputNamesContainFieldId(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('my_link][url]', $output);
        $this->assertStringContainsString('my_link][text]', $output);
        $this->assertStringContainsString('my_link][target]', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — values
    // -------------------------------------------------------------------------

    public function testOutputFillsUrlValue(): void
    {
        $output = $this->makeField()->outputHtml(['url' => 'https://example.com', 'text' => '', 'target' => '_self']);
        $this->assertStringContainsString('value="https://example.com"', $output);
    }

    public function testOutputFillsTextValue(): void
    {
        $output = $this->makeField()->outputHtml(['url' => '', 'text' => 'Click here', 'target' => '_self']);
        $this->assertStringContainsString('value="Click here"', $output);
    }

    public function testOutputSelectsBlankTarget(): void
    {
        $output = $this->makeField()->outputHtml(['url' => '', 'text' => '', 'target' => '_blank']);
        $this->assertStringContainsString('value="_blank" selected', $output);
    }

    public function testOutputSelectsSelfTargetByDefault(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('value="_self" selected', $output);
    }

    public function testOutputHandlesStringValueGracefully(): void
    {
        $output = $this->makeField()->outputHtml('not-an-array');
        $this->assertStringContainsString('cfdev-link-wrap', $output);
        $this->assertStringContainsString('value=""', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Enter a link']);
        $output = $field->outputHtml([]);
        $this->assertStringContainsString('Enter a link', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueSanitizesUrl(): void
    {
        $result = $this->makeField()->saveValue(['url' => 'https://example.com', 'text' => '', 'target' => '_self']);
        $this->assertIsArray($result);
        $this->assertSame('https://example.com', $result['url']);
    }

    public function testSaveValueSanitizesText(): void
    {
        $result = $this->makeField()->saveValue(['url' => '', 'text' => 'My link', 'target' => '_self']);
        $this->assertIsArray($result);
        $this->assertSame('My link', $result['text']);
    }

    public function testSaveValueKeepsValidTarget(): void
    {
        $result = $this->makeField()->saveValue(['url' => '', 'text' => '', 'target' => '_blank']);
        $this->assertIsArray($result);
        $this->assertSame('_blank', $result['target']);
    }

    public function testSaveValueDefaultsInvalidTarget(): void
    {
        $result = $this->makeField()->saveValue(['url' => '', 'text' => '', 'target' => 'evil']);
        $this->assertIsArray($result);
        $this->assertSame('_self', $result['target']);
    }

    public function testSaveValueReturnsDefaultArrayForNonArray(): void
    {
        $result = $this->makeField()->saveValue('not-an-array');
        $this->assertIsArray($result);
        $this->assertSame('', $result['url']);
        $this->assertSame('', $result['text']);
        $this->assertSame('_self', $result['target']);
    }

    public function testSaveValueHandlesMissingKeys(): void
    {
        $result = $this->makeField()->saveValue([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('target', $result);
    }
}
