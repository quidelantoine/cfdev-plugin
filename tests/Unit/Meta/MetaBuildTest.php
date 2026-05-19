<?php

namespace Weblitzer\CFDev\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Fields\Accordion;
use Weblitzer\CFDev\Fields\Bundle;
use Weblitzer\CFDev\Fields\Tabs;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Meta;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class MetaBuildTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('sanitize_title')->alias(function (string $s): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'));
        });
    }

    /** @param array<mixed> $data */
    private function makeMetaBox(array $data = []): MetaBox
    {
        return new MetaBox('test_mb', 'Test', 'post', $data);
    }

    /** @return array<string, mixed> */
    private function textFieldDef(string $name): array
    {
        return ['type' => 'text', 'name' => $name, 'label' => ucfirst($name)];
    }

    // -------------------------------------------------------------------------
    // Static helpers — isTabs / isBundle / isAccordion
    // -------------------------------------------------------------------------

    public function testIsTabsReturnsTrueForTabsMarker(): void
    {
        $this->assertTrue(Meta::isTabs(['tabs', []]));
    }

    public function testIsTabsReturnsFalseForOtherData(): void
    {
        $this->assertFalse(Meta::isTabs([['type' => 'text']]));
        $this->assertFalse(Meta::isTabs(['bundle', []]));
        $this->assertFalse(Meta::isTabs([]));
    }

    public function testIsAccordionReturnsTrueForAccordionMarker(): void
    {
        $this->assertTrue(Meta::isAccordion(['accordion', []]));
    }

    public function testIsBundleReturnsTrueForBundleMarker(): void
    {
        $this->assertTrue(Meta::isBundle(['bundle', []]));
    }

    public function testIsBundleReturnsFalseForOtherData(): void
    {
        $this->assertFalse(Meta::isBundle([['type' => 'text']]));
        $this->assertFalse(Meta::isBundle(['tabs', []]));
    }

    // -------------------------------------------------------------------------
    // build() — flat fields
    // -------------------------------------------------------------------------

    public function testBuildEmptyDataReturnsEmptyArray(): void
    {
        $mb = $this->makeMetaBox([]);
        $this->assertIsArray($mb->data);
        $this->assertEmpty($mb->data);
    }

    public function testBuildSingleFieldReturnsArrayWithOneField(): void
    {
        $mb = $this->makeMetaBox([$this->textFieldDef('title')]);
        $this->assertIsArray($mb->data);
        $this->assertCount(1, $mb->data);
        $this->assertContainsOnlyInstancesOf(Text::class, $mb->data);
    }

    public function testBuildSetsMetaTypeOnEachField(): void
    {
        $mb = $this->makeMetaBox([$this->textFieldDef('title')]);
        $field = array_values($mb->data)[0];
        $this->assertSame('post', $field->meta_type);
    }

    public function testBuildPopulatesFieldsProperty(): void
    {
        $mb = $this->makeMetaBox([
            $this->textFieldDef('title'),
            $this->textFieldDef('subtitle'),
        ]);
        $this->assertCount(2, $mb->fields);
    }

    public function testBuildSilentlySkipsUnknownFieldType(): void
    {
        $mb = $this->makeMetaBox([
            ['type' => 'nonexistent_type', 'name' => 'foo', 'label' => 'Foo'],
            $this->textFieldDef('title'),
        ]);
        $this->assertCount(1, $mb->data);
        $this->assertContainsOnlyInstancesOf(Text::class, $mb->data);
    }

    public function testBuildFieldIdUsedAsArrayKey(): void
    {
        $mb    = $this->makeMetaBox([$this->textFieldDef('title')]);
        $field = array_values($mb->data)[0];
        $this->assertArrayHasKey($field->id, $mb->data);
    }

    // -------------------------------------------------------------------------
    // build() — bundle
    // -------------------------------------------------------------------------

    public function testBuildReturnsBundleInstanceForBundleData(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->textFieldDef('name')]]);
        $this->assertInstanceOf(Bundle::class, $mb->data);
    }

    public function testBuildBundleMarksBundleFieldsAsInBundle(): void
    {
        $mb    = $this->makeMetaBox(['bundle', 'details', [$this->textFieldDef('name')]]);
        $field = array_values($mb->fields)[0];
        $this->assertTrue($field->in_bundle);
    }

    public function testBuildBundleFieldsAlsoRegisteredOnMeta(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->textFieldDef('name')]]);
        $this->assertCount(1, $mb->fields);
    }

    public function testBuildBundleSetsMetaTypeOnBundle(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'details', [$this->textFieldDef('name')]]);
        /** @var Bundle $bundle */
        $bundle = $mb->data;
        $this->assertSame('post', $bundle->meta_type);
    }

    public function testBuildBundleUsesExplicitId(): void
    {
        $mb = $this->makeMetaBox(['bundle', 'my_details', [$this->textFieldDef('name')]]);
        /** @var Bundle $bundle */
        $bundle = $mb->data;
        $this->assertSame('_my_details', $bundle->id);
    }

    // -------------------------------------------------------------------------
    // build() — tabs
    // -------------------------------------------------------------------------

    public function testBuildReturnsTabsInstanceForTabsData(): void
    {
        $mb = $this->makeMetaBox(['tabs', ['General' => [$this->textFieldDef('title')]]]);
        $this->assertInstanceOf(Tabs::class, $mb->data);
    }

    public function testBuildTabsCreatesOneTabPerSection(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'General'  => [$this->textFieldDef('title')],
            'Advanced' => [$this->textFieldDef('slug')],
        ]]);
        /** @var Tabs $tabs */
        $tabs = $mb->data;
        $this->assertCount(2, $tabs->tabs);
    }

    public function testBuildTabsFieldsRegisteredOnMeta(): void
    {
        $mb = $this->makeMetaBox(['tabs', [
            'General' => [$this->textFieldDef('title'), $this->textFieldDef('subtitle')],
        ]]);
        $this->assertCount(2, $mb->fields);
    }

    public function testBuildTabsSetsMetaTypeOnTabs(): void
    {
        $mb = $this->makeMetaBox(['tabs', ['General' => [$this->textFieldDef('title')]]]);
        /** @var Tabs $tabs */
        $tabs = $mb->data;
        $this->assertSame('post', $tabs->meta_type);
    }

    // -------------------------------------------------------------------------
    // build() — accordion
    // -------------------------------------------------------------------------

    public function testBuildReturnsAccordionInstanceForAccordionData(): void
    {
        $mb = $this->makeMetaBox(['accordion', ['Section' => [$this->textFieldDef('title')]]]);
        $this->assertInstanceOf(Accordion::class, $mb->data);
    }

    public function testBuildAccordionCreatesOneSectionPerGroup(): void
    {
        $mb = $this->makeMetaBox(['accordion', [
            'Section A' => [$this->textFieldDef('a')],
            'Section B' => [$this->textFieldDef('b')],
        ]]);
        /** @var Accordion $accordion */
        $accordion = $mb->data;
        $this->assertCount(2, $accordion->tabs);
    }
}
