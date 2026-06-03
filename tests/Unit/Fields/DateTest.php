<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use PHPUnit\Framework\Attributes\DataProvider;
use Weblitzer\CFDev\Fields\Date;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class DateTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Date
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);

        $defaults = [
            'type' => 'date',
            'name' => 'my_date',
            'label' => 'My Date',
        ];

        return new Date(array_merge($defaults, $overrides), 'my_metabox');
    }

    private function callFormatDateValue(Date $field, mixed $value): string
    {
        $method = new \ReflectionMethod($field, 'formatDateValue');
        $method->setAccessible(true);
        return $method->invoke($field, $value);
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

    public function testSupportsRepeatable(): void
    {
        $this->assertTrue($this->makeField()->supports_repeatable);
    }

    // -------------------------------------------------------------------------
    // CSS classes
    // -------------------------------------------------------------------------

    public function testHasJsDatepickerClass(): void
    {
        $this->assertContains('js-cfdev-datepicker', $this->makeField()->css_classes);
    }

    public function testHasCfdevDatepickerClass(): void
    {
        $this->assertContains('cfdev-datepicker', $this->makeField()->css_classes);
    }

    public function testHasDatepickerClass(): void
    {
        $this->assertContains('datepicker', $this->makeField()->css_classes);
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

    public function testDefaultDateFormatIsJqueryMmDdYy(): void
    {
        // PHP 'm/d/Y' → jQuery 'mm/dd/yy'
        $this->assertSame('mm/dd/yy', $this->makeField()->data_attributes['date-format']);
    }

    public function testCustomDateFormatIsParsed(): void
    {
        $field = $this->makeField(['args' => ['date_format' => 'd/m/Y']]);
        $this->assertSame('dd/mm/yy', $field->data_attributes['date-format']);
    }

    public function testCustomDateFormatYMD(): void
    {
        $field = $this->makeField(['args' => ['date_format' => 'Y-m-d']]);
        $this->assertSame('yy-mm-dd', $field->data_attributes['date-format']);
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
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('name=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsIdAttribute(): void
    {
        $field = $this->makeField();
        $output = $field->outputHtml('');
        $this->assertStringContainsString('id=', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testOutputContainsDataDateFormat(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('data-date-format=', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — valeur formatée
    // -------------------------------------------------------------------------

    public function testOutputFormatsTimestampWithDefaultFormat(): void
    {
        $timestamp = mktime(0, 0, 0, 6, 15, 2024);
        $output = $this->makeField()->outputHtml((string)$timestamp);
        $this->assertStringContainsString(gmdate('m/d/Y', (int) $timestamp), $output);
    }

    public function testOutputFormatsTimestampWithCustomFormat(): void
    {
        $timestamp = mktime(0, 0, 0, 3, 1, 2024);
        $field = $this->makeField(['args' => ['date_format' => 'd/m/Y']]);
        $output = $field->outputHtml((string)$timestamp);
        $this->assertStringContainsString(gmdate('d/m/Y', (int) $timestamp), $output);
    }

    public function testOutputUsesDefaultValueWhenEmpty(): void
    {
        $field = $this->makeField(['default_value' => '01/01/2024']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('01/01/2024', $output);
    }

    public function testOutputUsesDefaultValueWhenNonNumeric(): void
    {
        $field = $this->makeField(['default_value' => '01/01/2024']);
        $output = $field->outputHtml('not-a-timestamp');
        $this->assertStringContainsString('01/01/2024', $output);
    }

    // -------------------------------------------------------------------------
    // _format_date_value (via ReflectionMethod car protected)
    // -------------------------------------------------------------------------

    public function testFormatDateValueUsesDefaultFormat(): void
    {
        $timestamp = mktime(0, 0, 0, 1, 15, 2024);
        $field = $this->makeField();
        $result = $this->callFormatDateValue($field, $timestamp);
        $this->assertSame(gmdate('m/d/Y', (int) $timestamp), $result);
    }

    public function testFormatDateValueUsesCustomFormat(): void
    {
        $timestamp = mktime(0, 0, 0, 12, 25, 2024);
        $field = $this->makeField(['args' => ['date_format' => 'd/m/Y']]);
        $result = $this->callFormatDateValue($field, $timestamp);
        $this->assertSame(gmdate('d/m/Y', (int) $timestamp), $result);
    }

    public function testFormatDateValueReturnsDefaultWhenEmpty(): void
    {
        $field = $this->makeField(['default_value' => 'N/A']);
        $result = $this->callFormatDateValue($field, '');
        $this->assertSame('N/A', $result);
    }

    public function testFormatDateValueReturnsDefaultWhenNonNumeric(): void
    {
        $field = $this->makeField(['default_value' => 'N/A']);
        $result = $this->callFormatDateValue($field, 'invalid');
        $this->assertSame('N/A', $result);
    }

    // -------------------------------------------------------------------------
    // saveValue
    // -------------------------------------------------------------------------

    public function testSaveValueConvertsDateStringToTimestamp(): void
    {
        $field = $this->makeField();
        $result = $field->saveValue('06/15/2024');
        $this->assertEquals(strtotime('06/15/2024'), $result);
    }

    public function testSaveValueReturnsNumeric(): void
    {
        $field = $this->makeField();
        $result = $field->saveValue('01/01/2024');
        $this->assertIsNumeric($result);
    }

    public function testSaveValueRespectsDmyFormat(): void
    {
        $field  = $this->makeField(['args' => ['date_format' => 'd/m/Y']]);
        $result = $field->saveValue('15/03/2024');
        $this->assertEquals(strtotime('03/15/2024'), $result);
    }

    public function testSaveValueReturnsEmptyOnGibberish(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue('not-a-date');
        $this->assertSame('', $result);
    }

    public function testSaveValueStoresMidnightTimestamp(): void
    {
        $field     = $this->makeField();
        $result    = (int) $field->saveValue('06/15/2024');
        $midnight  = (int) strtotime('06/15/2024');
        $this->assertSame($midnight, $result);
    }

    /** @return array<string, array{string, string, int}> */
    public static function saveFormatProvider(): array
    {
        return [
            'm/d/Y default'  => ['m/d/Y', '06/15/2024', (int) strtotime('06/15/2024')],
            'd/m/Y european' => ['d/m/Y', '15/06/2024', (int) strtotime('06/15/2024')],
            'Y-m-d ISO'      => ['Y-m-d', '2024-06-15', (int) strtotime('06/15/2024')],
        ];
    }

    #[DataProvider('saveFormatProvider')]
    public function testSaveValueFormats(string $format, string $input, int $expected): void
    {
        $field  = $this->makeField(['args' => ['date_format' => $format]]);
        $result = (int) $field->saveValue($input);
        $this->assertSame($expected, $result);
    }

    public function testSaveValueArrayMapsEachElement(): void
    {
        $field  = $this->makeField();
        $result = $field->saveValue(['06/15/2024', '01/01/2024']);
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsNumeric($result[0]);
        $this->assertIsNumeric($result[1]);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Enter a date']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Enter a date', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
