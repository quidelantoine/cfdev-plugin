<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin\Export;

use PHPUnit\Framework\Attributes\DataProvider;
use Weblitzer\CFDev\Admin\Export\FieldExporter;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * @covers \Weblitzer\CFDev\Admin\Export\FieldExporter
 */
class FieldExporterTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! defined('CFDEV_VERSION')) {
            define('CFDEV_VERSION', '1.0.6');
        }
        \Brain\Monkey\Functions\when('wp_json_encode')->alias('json_encode');
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    /** @return array<int, array<string, mixed>> */
    private function sampleGroups(): array
    {
        return [
            [
                'id'        => 'product_info',
                'title'     => 'Product Info',
                'meta_type' => 'post',
                'targets'   => ['product'],
                'layout'    => 'flat',
                'fields'    => [
                    ['id' => 'price', 'type' => 'number', 'label' => 'Price', 'required' => true, 'args' => ['min' => 0, 'max' => 9999]],
                    ['id' => 'photo', 'type' => 'image', 'label' => 'Photo'],
                ],
            ],
            [
                'id'        => 'genre_info',
                'title'     => 'Genre Info',
                'meta_type' => 'term',
                'targets'   => ['genre'],
                'layout'    => 'flat',
                'fields'    => [
                    ['id' => 'color', 'type' => 'color', 'label' => 'Color', 'default_value' => '#ff0000'],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // toJson — structure
    // -------------------------------------------------------------------------

    public function testToJsonReturnsValidJson(): void
    {
        $result = FieldExporter::toJson($this->sampleGroups());
        $this->assertNotFalse(json_decode($result));
    }

    public function testToJsonContainsVersionKey(): void
    {
        $data = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $this->assertArrayHasKey('version', $data);
        $this->assertSame(CFDEV_VERSION, $data['version']);
    }

    public function testToJsonContainsExportedAtKey(): void
    {
        $data = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $this->assertArrayHasKey('exported_at', $data);
        $this->assertNotEmpty($data['exported_at']);
    }

    public function testToJsonContainsGroupsArray(): void
    {
        $data = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $this->assertArrayHasKey('groups', $data);
        $this->assertIsArray($data['groups']);
        $this->assertCount(2, $data['groups']);
    }

    public function testToJsonGroupHasRequiredKeys(): void
    {
        $data  = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $group = $data['groups'][0];
        foreach (['id', 'title', 'meta_type', 'targets', 'layout', 'fields'] as $key) {
            $this->assertArrayHasKey($key, $group, "Group missing key: $key");
        }
    }

    public function testToJsonGroupValuesMatchInput(): void
    {
        $data  = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $group = $data['groups'][0];
        $this->assertSame('product_info', $group['id']);
        $this->assertSame('Product Info', $group['title']);
        $this->assertSame('post', $group['meta_type']);
        $this->assertSame(['product'], $group['targets']);
        $this->assertSame('flat', $group['layout']);
    }

    public function testToJsonFieldContainsTypeAndLabel(): void
    {
        $data  = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $field = $data['groups'][0]['fields'][0];
        $this->assertSame('number', $field['type']);
        $this->assertSame('Price', $field['label']);
    }

    public function testToJsonFieldPreservesRequiredFlag(): void
    {
        $data  = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $field = $data['groups'][0]['fields'][0];
        $this->assertTrue($field['required']);
    }

    public function testToJsonFieldPreservesArgs(): void
    {
        $data  = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $field = $data['groups'][0]['fields'][0];
        $this->assertSame(['min' => 0, 'max' => 9999], $field['args']);
    }

    public function testToJsonFieldPreservesDefaultValue(): void
    {
        $data  = json_decode(FieldExporter::toJson($this->sampleGroups()), true);
        $field = $data['groups'][1]['fields'][0];
        $this->assertSame('#ff0000', $field['default_value']);
    }

    public function testToJsonEmptyGroupsProducesValidPayload(): void
    {
        $data = json_decode(FieldExporter::toJson([]), true);
        $this->assertSame([], $data['groups']);
    }

    public function testToJsonIsPrettyPrinted(): void
    {
        $result = FieldExporter::toJson($this->sampleGroups());
        $this->assertStringContainsString("\n", $result);
    }

    public function testToJsonHandlesUnicodeWithoutEscaping(): void
    {
        $groups = [[
            'id' => 'test', 'title' => 'Champs éàü', 'meta_type' => 'post',
            'targets' => [], 'layout' => 'flat', 'fields' => [],
        ]];
        $result = FieldExporter::toJson($groups);
        $this->assertStringContainsString('éàü', $result);
    }

    // -------------------------------------------------------------------------
    // toPhp — structure
    // -------------------------------------------------------------------------

    public function testToPhpStartsWithPhpOpenTag(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringStartsWith('<?php', $result);
    }

    public function testToPhpContainsReturnStatement(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString('return [', $result);
    }

    public function testToPhpContainsCfdevHeader(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString('CFDev', $result);
        $this->assertStringContainsString('1.0.6', $result);
    }

    public function testToPhpContainsVersionValue(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString('1.0.6', $result);
    }

    public function testToPhpContainsGroupId(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString("'product_info'", $result);
    }

    public function testToPhpContainsFieldType(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString("'number'", $result);
    }

    public function testToPhpContainsMetaType(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString("'post'", $result);
    }

    public function testToPhpContainsBoolTrue(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringContainsString('true', $result);
    }

    public function testToPhpEndsWithNewline(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringEndsWith("\n", $result);
    }

    public function testToPhpEmptyGroupsProducesReturnEmptyArray(): void
    {
        $result = FieldExporter::toPhp([]);
        $this->assertStringContainsString('return []', $result);
    }

    // -------------------------------------------------------------------------
    // toPhp — PHP array syntax
    // -------------------------------------------------------------------------

    public function testToPhpUsesSquareBracketSyntax(): void
    {
        $result = FieldExporter::toPhp($this->sampleGroups());
        $this->assertStringNotContainsString('array(', $result);
    }

    public function testToPhpSerialisesBooleanFalse(): void
    {
        $groups = [['id' => 'x', 'title' => 'X', 'meta_type' => 'post', 'targets' => [], 'layout' => 'flat', 'fields' => [], 'active' => false]];
        $result = FieldExporter::toPhp($groups);
        $this->assertStringContainsString('false', $result);
    }

    public function testToPhpSerialisesNull(): void
    {
        $groups = [['id' => 'x', 'title' => 'X', 'meta_type' => 'post', 'targets' => [], 'layout' => 'flat', 'fields' => [], 'conditions' => null]];
        $result = FieldExporter::toPhp($groups);
        $this->assertStringContainsString('null', $result);
    }

    public function testToPhpSerialisesIntegerValues(): void
    {
        $groups = [['id' => 'x', 'title' => 'X', 'meta_type' => 'post', 'targets' => [], 'layout' => 'flat', 'fields' => [
            ['id' => 'qty', 'type' => 'number', 'label' => 'Qty', 'args' => ['min' => 1]],
        ]]];
        $result = FieldExporter::toPhp($groups);
        $this->assertStringContainsString("'min' => 1", $result);
    }

    public function testToPhpEscapesSingleQuotesInStrings(): void
    {
        $groups = [['id' => "it's", 'title' => "it's", 'meta_type' => 'post', 'targets' => [], 'layout' => 'flat', 'fields' => []]];
        $result = FieldExporter::toPhp($groups);
        $this->assertStringContainsString("\\'", $result);
    }

    // -------------------------------------------------------------------------
    // DataProvider — both formats produce something non-empty
    // -------------------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function formatProvider(): array
    {
        return [
            'json' => ['json'],
            'php'  => ['php'],
        ];
    }

    #[DataProvider('formatProvider')]
    public function testOutputIsNotEmptyForBothFormats(string $format): void
    {
        $result = $format === 'json'
            ? FieldExporter::toJson($this->sampleGroups())
            : FieldExporter::toPhp($this->sampleGroups());

        $this->assertNotEmpty($result);
        $this->addToAssertionCount(1);
    }
}
