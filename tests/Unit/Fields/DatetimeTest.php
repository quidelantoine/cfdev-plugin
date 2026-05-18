<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\Datetime;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;
use CFDev\Support\DateFormatHelper;

class DatetimeTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeField(array $overrides = []): Datetime
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);

        $defaults = [
            'type'  => 'datetime',
            'name'  => 'my_datetime',
            'label' => 'My Datetime',
        ];

        return new Datetime(array_merge($defaults, $overrides), 'my_metabox');
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

    public function testHasJsDatetimepickerClass(): void
    {
        $this->assertContains('js-cfdev-datetimepicker', $this->makeField()->css_classes);
    }

    public function testHasCfdevDatetimepickerClass(): void
    {
        $this->assertContains('cfdev-datetimepicker', $this->makeField()->css_classes);
    }

    public function testHasDatetimepickerClass(): void
    {
        $this->assertContains('datetimepicker', $this->makeField()->css_classes);
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
    // Construction — data_attributes
    // -------------------------------------------------------------------------

    public function testDataAttributesHasDateFormatKey(): void
    {
        $this->assertArrayHasKey('date-format', $this->makeField()->data_attributes);
    }

    public function testDataAttributesHasTimeFormatKey(): void
    {
        $this->assertArrayHasKey('time-format', $this->makeField()->data_attributes);
    }

    public function testDefaultDateFormatIsJqueryMmDdYy(): void
    {
        // PHP 'm/d/Y' → jQuery 'mm/dd/yy'
        $this->assertSame('mm/dd/yy', $this->makeField()->data_attributes['date-format']);
    }

    public function testDefaultTimeFormatIsJqueryHHMm(): void
    {
        // PHP 'H:i' → jQuery 'HH:mm'
        $this->assertSame('HH:mm', $this->makeField()->data_attributes['time-format']);
    }

    public function testCustomDateFormatIsParsed(): void
    {
        $field = $this->makeField(['args' => ['date_format' => 'd/m/Y']]);
        $this->assertSame('dd/mm/yy', $field->data_attributes['date-format']);
    }

    public function testCustomTimeFormatIsParsed(): void
    {
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

    public function testOutputContainsDataAttributes(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('data-date-format=', $output);
        $this->assertStringContainsString('data-time-format=', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — valeur formatée
    // -------------------------------------------------------------------------

    public function testOutputFormatsTimestampWithDefaultFormat(): void
    {
        $timestamp = mktime(14, 30, 0, 6, 15, 2024);
        $output    = $this->makeField()->outputHtml((string) $timestamp);
        $this->assertStringContainsString(gmdate('m/d/Y H:i', $timestamp), $output);
    }

    public function testOutputFormatsTimestampWithCustomDateAndTimeFormat(): void
    {
        $timestamp = mktime(9, 5, 0, 3, 1, 2024);
        $field     = $this->makeField(['args' => [
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i:s',
        ]]);
        $output = $field->outputHtml((string) $timestamp);
        $this->assertStringContainsString(gmdate('d/m/Y H:i:s', $timestamp), $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field  = $this->makeField(['default_value' => '01/01/2024 00:00']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('01/01/2024 00:00', $output);
    }

    public function testOutputUsesDefaultValueWhenZero(): void
    {
        $field  = $this->makeField(['default_value' => '01/01/2024 00:00']);
        $output = $field->outputHtml('0');
        $this->assertStringContainsString('01/01/2024 00:00', $output);
    }

    public function testOutputUsesDefaultValueWhenNonNumeric(): void
    {
        $field  = $this->makeField(['default_value' => '01/01/2024 00:00']);
        $output = $field->outputHtml('not-a-timestamp');
        $this->assertStringContainsString('01/01/2024 00:00', $output);
    }

    public function testOutputUsesDefaultValueWhenNegative(): void
    {
        $field  = $this->makeField(['default_value' => '01/01/2024 00:00']);
        $output = $field->outputHtml('-1000');
        $this->assertStringContainsString('01/01/2024 00:00', $output);
    }

    // -------------------------------------------------------------------------
    // formatDatetime (via ReflectionMethod car protected)
    // -------------------------------------------------------------------------

    private function callFormatDatetime($field, mixed $value): string
    {
        $method = new \ReflectionMethod($field, 'formatDatetime');
        $method->setAccessible(true);
        return $method->invoke($field, $value);
    }

    public function testFormatDatetimeUsesDefaultFormat(): void
    {
        $timestamp = mktime(10, 0, 0, 1, 15, 2024);
        $field     = $this->makeField();
        $result    = $this->callFormatDatetime($field, $timestamp);
        $this->assertSame(gmdate('m/d/Y H:i', $timestamp), $result);
    }

    public function testFormatDatetimeUsesCustomFormatsWhenBothSet(): void
    {
        $timestamp = mktime(8, 30, 0, 12, 25, 2024);
        $field     = $this->makeField(['args' => [
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        ]]);
        $result = $this->callFormatDatetime($field, $timestamp);
        $this->assertSame(gmdate('d/m/Y H:i', $timestamp), $result);
    }

    public function testFormatDatetimeReturnsDefaultValueWhenNonNumeric(): void
    {
        $field  = $this->makeField(['default_value' => 'N/A']);
        $result = $this->callFormatDatetime($field, 'invalid');
        $this->assertSame('N/A', $result);
    }

    public function testFormatDatetimeReturnsDefaultValueWhenZero(): void
    {
        $field  = $this->makeField(['default_value' => 'N/A']);
        $result = $this->callFormatDatetime($field, 0);
        $this->assertSame('N/A', $result);
    }

    public function testFormatDatetimeReturnsDefaultValueWhenNegative(): void
    {
        $field  = $this->makeField(['default_value' => 'N/A']);
        $result = $this->callFormatDatetime($field, -500);
        $this->assertSame('N/A', $result);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueConvertsDatetimeStringToTimestamp(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('06/15/2024 14:30');
        $this->assertEquals(strtotime('06/15/2024 14:30'), $result);
    }

    public function testSaveValueReturnsNumeric(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('01/01/2024 00:00');
        $this->assertIsNumeric($result);
    }

    // -------------------------------------------------------------------------
    // parse_date_format
    // -------------------------------------------------------------------------

    public function testParseDateFormatMDY(): void
    {
        $this->assertSame('mm/dd/yy', DateFormatHelper::parse('m/d/Y'));
    }

    public function testParseDateFormatDMY(): void
    {
        $this->assertSame('dd/mm/yy', DateFormatHelper::parse('d/m/Y'));
    }

    public function testParseDateFormatYMD(): void
    {
        $this->assertSame('yy/mm/dd', DateFormatHelper::parse('Y/m/d'));
    }

    public function testParseDateFormatHI(): void
    {
        $this->assertSame('HH:mm', DateFormatHelper::parse('H:i'));
    }

    public function testParseDateFormatGIA(): void
    {
        $this->assertSame('h:mm tt', DateFormatHelper::parse('g:i a'));
    }

    public function testParseDateFormatUnknownCharPassedThrough(): void
    {
        $this->assertStringContainsString('/', DateFormatHelper::parse('m/d/Y'));
    }

    public function testParseDateFormatEmptyString(): void
    {
        $this->assertSame('', DateFormatHelper::parse(''));
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field  = $this->makeField(['explanation' => 'Enter a date and time']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Enter a date and time', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
