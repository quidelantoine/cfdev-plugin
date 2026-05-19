<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Tel;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class TelTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Tel
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('wp_strip_all_tags')->alias(fn(string $v): string => strip_tags($v)); // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter
        Functions\when('sanitize_text_field')->alias(fn(string $v): string => wp_strip_all_tags($v));

        $defaults = ['type' => 'tel', 'name' => 'my_tel', 'label' => 'Téléphone'];

        return new Tel(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testOutputRendersTelInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="tel"', $output);
    }

    public function testOutputContainsValue(): void
    {
        $output = $this->makeField()->outputHtml('+33 6 12 34 56 78');
        $this->assertStringContainsString('value="+33 6 12 34 56 78"', $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => '0600000000']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('value="0600000000"', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValuePhoneNumber(): void
    {
        $result = $this->makeField()->saveValue('+33 6 12 34 56 78');
        $this->assertSame('+33 6 12 34 56 78', $result);
    }

    public function testSaveValueStripsHtml(): void
    {
        $result = $this->makeField()->saveValue('<b>0600000000</b>');
        $this->assertSame('0600000000', $result);
    }

    public function testSaveValueArray(): void
    {
        $result = $this->makeField()->saveValue(['0600000000', '0700000000']);
        $this->assertIsArray($result);
        $this->assertSame('0600000000', $result[0]);
        $this->assertSame('0700000000', $result[1]);
    }

    // -------------------------------------------------------------------------
    // validate — no format rule, only Required if set
    // -------------------------------------------------------------------------

    public function testValidatePassesOnEmptyByDefault(): void
    {
        $validator = $this->makeField()->validate('');
        $this->assertTrue($validator->passes());
    }

    public function testValidatePassesOnAnyString(): void
    {
        $validator = $this->makeField()->validate('+33612345678');
        $this->assertTrue($validator->passes());
    }

    public function testValidateFailsOnEmptyWhenRequired(): void
    {
        $validator = $this->makeField(['required' => true])->validate('');
        $this->assertFalse($validator->passes());
    }
}
