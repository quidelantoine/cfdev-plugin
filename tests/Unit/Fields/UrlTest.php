<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Url;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class UrlTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Url
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_url_raw')->alias(function (string $v): string {
            return filter_var($v, FILTER_SANITIZE_URL) ?: '';
        });

        $defaults = ['type' => 'url', 'name' => 'my_url', 'label' => 'URL'];

        return new Url(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

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
    // outputHtml
    // -------------------------------------------------------------------------

    public function testOutputRendersUrlInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="url"', $output);
    }

    public function testOutputContainsValue(): void
    {
        $output = $this->makeField()->outputHtml('https://example.com');
        $this->assertStringContainsString('value="https://example.com"', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => 'https://default.com']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('value="https://default.com"', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueValidUrl(): void
    {
        $result = $this->makeField()->saveValue('https://example.com');
        $this->assertSame('https://example.com', $result);
    }

    public function testSaveValueArray(): void
    {
        $result = $this->makeField()->saveValue(['https://a.com', 'https://b.com']);
        $this->assertIsArray($result);
        $this->assertSame('https://a.com', $result[0]);
        $this->assertSame('https://b.com', $result[1]);
    }

    // -------------------------------------------------------------------------
    // validate — format rule auto-injected
    // -------------------------------------------------------------------------

    public function testValidatePassesOnEmptyValue(): void
    {
        $validator = $this->makeField()->validate('');
        $this->assertTrue($validator->passes());
    }

    public function testValidatePassesOnValidUrl(): void
    {
        $validator = $this->makeField()->validate('https://example.com');
        $this->assertTrue($validator->passes());
    }

    public function testValidateFailsOnInvalidUrl(): void
    {
        $validator = $this->makeField()->validate('not a url');
        $this->assertFalse($validator->passes());
        $this->assertNotEmpty($validator->errors());
    }

    public function testValidateFailsOnEmptyWhenRequired(): void
    {
        $validator = $this->makeField(['required' => true])->validate('');
        $this->assertFalse($validator->passes());
    }
}
