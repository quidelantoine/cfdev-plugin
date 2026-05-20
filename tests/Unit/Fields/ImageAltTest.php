<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\ImageAlt;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class ImageAltTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): ImageAlt
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('esc_html')->returnArg(1);
        Functions\when('esc_url')->returnArg(1);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('__')->returnArg(1);

        $defaults = [
            'type'  => 'image_alt',
            'name'  => 'my_image',
            'label' => 'My Image',
        ];

        return new ImageAlt(array_merge($defaults, $overrides), 'my_metabox');
    }

    private function mockImageSrc(int $id): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/img.jpg', 800, 600]);
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

    public function testOutputRendersWrapper(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('cfdev-image-alt-wrap', $output);
    }

    public function testOutputRendersHiddenInput(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringContainsString('cfdev-hidden', $output);
    }

    public function testOutputRendersUploadButton(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('js-cfdev-upload', $output);
        $this->assertStringContainsString('data-cfdev-media-type="image"', $output);
    }

    public function testOutputRendersPreviewSpan(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('cfdev-preview', $output);
    }

    public function testOutputRendersAltInputAndLabel(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('cfdev-image-alt-text', $output);
        $this->assertStringContainsString('cfdev-image-alt-label', $output);
    }

    public function testOutputInputNamesContainFieldId(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('my_image][id]', $output);
        $this->assertStringContainsString('my_image][alt]', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — values
    // -------------------------------------------------------------------------

    public function testOutputFillsImageId(): void
    {
        $this->mockImageSrc(5);
        $output = $this->makeField()->outputHtml(['id' => 5, 'alt' => '']);
        $this->assertStringContainsString('value="5"', $output);
    }

    public function testOutputFillsAltText(): void
    {
        $this->mockImageSrc(5);
        $output = $this->makeField()->outputHtml(['id' => 5, 'alt' => 'My custom alt']);
        $this->assertStringContainsString('value="My custom alt"', $output);
    }

    public function testOutputHiddenInputEmptyWhenNoId(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringContainsString('value=""', $output);
    }

    public function testOutputShowsRemoveLinkWhenValueSet(): void
    {
        $this->mockImageSrc(5);
        $output = $this->makeField()->outputHtml(['id' => 5, 'alt' => '']);
        $this->assertStringContainsString('js-cfdev-remove-media', $output);
        $this->assertStringContainsString('Remove current image', $output);
    }

    public function testOutputNoRemoveLinkWhenNoValue(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringNotContainsString('js-cfdev-remove-media', $output);
    }

    public function testOutputShowsPreviewImageWhenIdSet(): void
    {
        $this->mockImageSrc(5);
        $output = $this->makeField()->outputHtml(['id' => 5, 'alt' => '']);
        $this->assertStringContainsString('<img src=', $output);
    }

    public function testOutputNoPreviewImageWhenNoId(): void
    {
        $output = $this->makeField()->outputHtml([]);
        $this->assertStringNotContainsString('<img', $output);
    }

    public function testOutputHandlesJsonStringValue(): void
    {
        $this->mockImageSrc(3);
        $output = $this->makeField()->outputHtml('{"id":3,"alt":"Alt from JSON"}');
        $this->assertStringContainsString('value="3"', $output);
        $this->assertStringContainsString('value="Alt from JSON"', $output);
    }

    public function testOutputHandlesEmptyStringGracefully(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-image-alt-wrap', $output);
        $this->assertStringNotContainsString('<img', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Choose an image']);
        $output = $field->outputHtml([]);
        $this->assertStringContainsString('Choose an image', $output);
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

    public function testSaveValueSanitizesAlt(): void
    {
        $result = $this->makeField()->saveValue(['id' => 5, 'alt' => 'My alt']);
        $this->assertIsArray($result);
        $this->assertSame('My alt', $result['alt']);
    }

    public function testSaveValueCastsIdToInt(): void
    {
        $result = $this->makeField()->saveValue(['id' => '42', 'alt' => '']);
        $this->assertIsArray($result);
        $this->assertSame(42, $result['id']);
    }

    public function testSaveValueRejectsNegativeId(): void
    {
        $result = $this->makeField()->saveValue(['id' => -5, 'alt' => '']);
        $this->assertIsArray($result);
        $this->assertSame(0, $result['id']);
    }

    public function testSaveValueReturnsDefaultArrayForNonArray(): void
    {
        $result = $this->makeField()->saveValue('not-an-array');
        $this->assertIsArray($result);
        $this->assertSame(0, $result['id']);
        $this->assertSame('', $result['alt']);
    }

    public function testSaveValueHandlesMissingKeys(): void
    {
        $result = $this->makeField()->saveValue([]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('alt', $result);
        $this->assertSame(0, $result['id']);
        $this->assertSame('', $result['alt']);
    }
}