<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Time;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class TimeTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Time
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);

        $defaults = [
            'type'  => 'time',
            'name'  => 'my_time',
            'label' => 'My Time',
        ];

        return new Time(array_merge($defaults, $overrides), 'my_metabox');
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
    // CSS classes
    // -------------------------------------------------------------------------

    public function testHasJsTimepickerClass(): void
    {
        $this->assertContains('js-cfdev-timepicker', $this->makeField()->css_classes);
    }

    public function testHasCfdevTimepickerClass(): void
    {
        $this->assertContains('cfdev-timepicker', $this->makeField()->css_classes);
    }

    public function testHasTimepickerClass(): void
    {
        $this->assertContains('timepicker', $this->makeField()->css_classes);
    }

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    public function testHasExactlyFourDefaultClasses(): void
    {
        $this->assertCount(4, $this->makeField()->css_classes);
    }

    // -------------------------------------------------------------------------
    // Construction — data_attributes / time-format
    // -------------------------------------------------------------------------

    public function testDataAttributesHasTimeFormatKey(): void
    {
        $this->assertArrayHasKey('time-format', $this->makeField()->data_attributes);
    }

    public function testDefaultTimeFormatIsJqueryHHMm(): void
    {
        // PHP 'H:i' → jQuery 'HH:mm'
        $this->assertSame('HH:mm', $this->makeField()->data_attributes['time-format']);
    }

    public function testCustomTimeFormatIsParsed(): void
    {
        // PHP 'g:i a' → jQuery 'h:mm tt'
        $field = $this->makeField(['args' => ['time_format' => 'g:i a']]);
        $this->assertSame('h:mm tt', $field->data_attributes['time-format']);
    }

    // -------------------------------------------------------------------------
    // outputHtml — structure HTML
    // -------------------------------------------------------------------------

    public function testOutputRendersInputTag(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString('type="text"', $output);
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

    public function testOutputContainsDataTimeFormat(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('data-time-format=', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — valeur formatée
    // -------------------------------------------------------------------------

    public function testOutputFormatsTimestampWithDefaultFormat(): void
    {
        $timestamp = mktime(14, 30, 0); // 14:30
        $output    = $this->makeField()->outputHtml((string) $timestamp);
        $this->assertStringContainsString(gmdate('H:i', (int) $timestamp), $output);
    }

    public function testOutputFormatsTimestampWithCustomFormat(): void
    {
        $timestamp = mktime(9, 5, 0); // 09:05
        $field     = $this->makeField(['args' => ['time_format' => 'H:i:s']]);
        $output    = $field->outputHtml((string) $timestamp);
        $this->assertStringContainsString(gmdate('H:i:s', (int) $timestamp), $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => '08:00']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('08:00', $output);
    }

    public function testOutputUsesDefaultValueWhenZero(): void
    {
        $field  = $this->makeField(['default_value' => '00:00']);
        $output = $field->outputHtml('0');
        $this->assertStringContainsString('00:00', $output);
    }

    public function testOutputUsesDefaultValueWhenNonNumeric(): void
    {
        $field  = $this->makeField(['default_value' => '12:00']);
        $output = $field->outputHtml('not-a-timestamp');
        $this->assertStringContainsString('12:00', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Enter a time']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Enter a time', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueConvertsTimeStringToTimestamp(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('14:30');
        $this->assertEquals(strtotime('14:30'), $result);
    }

    public function testSaveValueMidnight(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('00:00');
        $this->assertEquals(strtotime('00:00'), $result);
    }

    public function testSaveValueReturnsInteger(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('08:00');
        $this->assertIsNumeric($result);
    }
}
