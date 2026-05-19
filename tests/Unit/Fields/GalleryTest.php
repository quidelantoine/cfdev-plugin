<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Gallery;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class GalleryTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Gallery
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');

        $defaults = [
            'type'  => 'gallery',
            'name'  => 'my_gallery',
            'label' => 'My Gallery',
        ];

        return new Gallery(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsRepeatableIsFalse(): void
    {
        $this->assertFalse($this->makeField()->supports_repeatable);
    }

    public function testSupportsBundleIsFalse(): void
    {
        $this->assertFalse($this->makeField()->supports_bundle);
    }

    public function testSupportsAjaxIsFalse(): void
    {
        $this->assertFalse($this->makeField()->supports_ajax);
    }

    // -------------------------------------------------------------------------
    // outputHtml — wrapper
    // -------------------------------------------------------------------------

    public function testOutputRendersGalleryWrap(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('js-cfdev-gallery', $output);
    }

    public function testOutputRendersItemsContainer(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('js-cfdev-gallery-items', $output);
    }

    public function testOutputRendersAddButton(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('js-cfdev-gallery-add', $output);
    }

    public function testOutputDataFieldNameAttribute(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('data-field-name=', $output);
        $this->assertStringContainsString('my_gallery', $output);
    }

    public function testOutputSortableClass(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('cfdev-sortable', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — items
    // -------------------------------------------------------------------------

    public function testOutputRendersNoItemsWhenValueEmpty(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringNotContainsString('class="cfdev-gallery-item js-cfdev-gallery-item"', $output);
    }

    public function testOutputRendersItemsForEachId(): void
    {
        Functions\when('wp_get_attachment_image_src')
            ->alias(function (int $id): array {
                return ['https://example.com/img-' . $id . '.jpg', 150, 150];
            });

        $output = $this->makeField()->outputHtml([10, 20]);
        $this->assertSame(2, substr_count($output, 'class="cfdev-gallery-item js-cfdev-gallery-item"'));
    }

    public function testOutputRendersHiddenInputForEachId(): void
    {
        Functions\when('wp_get_attachment_image_src')
            ->alias(function (int $id): array {
                return ['https://example.com/img-' . $id . '.jpg', 150, 150];
            });

        $output = $this->makeField()->outputHtml([42]);
        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('value="42"', $output);
    }

    public function testOutputRendersImgTagForEachItem(): void
    {
        Functions\when('wp_get_attachment_image_src')
            ->justReturn(['https://example.com/thumb.jpg', 150, 150]);

        $output = $this->makeField()->outputHtml([5]);
        $this->assertStringContainsString('<img src="https://example.com/thumb.jpg"', $output);
    }

    public function testOutputRendersRemoveLinkForEachItem(): void
    {
        Functions\when('wp_get_attachment_image_src')
            ->justReturn(['https://example.com/thumb.jpg', 150, 150]);

        $output = $this->makeField()->outputHtml([5]);
        $this->assertStringContainsString('js-cfdev-gallery-remove', $output);
    }

    public function testOutputSkipsItemWhenAttachmentSrcFalse(): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(false);

        $output = $this->makeField()->outputHtml([99]);
        $this->assertStringNotContainsString('class="cfdev-gallery-item js-cfdev-gallery-item"', $output);
    }

    public function testOutputIgnoresNonArrayValue(): void
    {
        $output = $this->makeField()->outputHtml('not-an-array');
        $this->assertStringNotContainsString('class="cfdev-gallery-item js-cfdev-gallery-item"', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Select gallery images']);
        $output = $field->outputHtml([]);
        $this->assertStringContainsString('Select gallery images', $output);
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

    public function testSaveValueFiltersInvalidIds(): void
    {
        $result = $this->makeField()->saveValue(['5', '0', 'abc', '12']);
        $this->assertSame([5, 12], $result);
    }

    public function testSaveValueCastsToInt(): void
    {
        $result = $this->makeField()->saveValue(['7']);
        $this->assertSame([7], $result);
    }

    public function testSaveValueReturnsEmptyArrayForNonArray(): void
    {
        $result = $this->makeField()->saveValue('not-an-array');
        $this->assertSame([], $result);
    }

    public function testSaveValueReturnsEmptyArrayForEmptyArray(): void
    {
        $result = $this->makeField()->saveValue([]);
        $this->assertSame([], $result);
    }

    public function testSaveValueReindexesArray(): void
    {
        $result = $this->makeField()->saveValue(['0', '3', '0', '7']);
        $this->assertSame([3, 7], $result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
    }
}
