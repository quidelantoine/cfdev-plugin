<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class ImageTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Image
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        Functions\when('esc_attr')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');

        $defaults = [
            'type' => 'image',
            'name' => 'my_image',
            'label' => 'My Image',
        ];

        return new Image(array_merge($defaults, $overrides), 'my_metabox');
    }

    // -------------------------------------------------------------------------
    // Construction / defaults
    // -------------------------------------------------------------------------

    public function testSupportsRepeatable(): void
    {
        $this->assertTrue($this->makeField()->supports_repeatable);
    }

    public function testSupportsAjax(): void
    {
        $this->assertTrue($this->makeField()->supports_ajax);
    }

    public function testSupportsBundle(): void
    {
        $this->assertTrue($this->makeField()->supports_bundle);
    }

    public function testHasCfdevHiddenClass(): void
    {
        $this->assertContains('cfdev-hidden', $this->makeField()->css_classes);
    }

    public function testHasCfdevInputClass(): void
    {
        $this->assertContains('cfdev-input', $this->makeField()->css_classes);
    }

    // -------------------------------------------------------------------------
    // outputHtml — hidden input
    // -------------------------------------------------------------------------

    public function testOutputRendersHiddenInput(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('type="hidden"', $output);
    }

    public function testOutputHiddenInputContainsValue(): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/img.jpg', 800, 600]);

        $output = $this->makeField()->outputHtml('42');
        $this->assertStringContainsString('value="42"', $output);
    }

    public function testOutputHiddenInputEmptyValueWhenNoImage(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('value=""', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — upload button
    // -------------------------------------------------------------------------

    public function testOutputRendersUploadButton(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('js-cfdev-upload', $output);
    }

    public function testOutputButtonHasMediaTypeImage(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('data-cfdev-media-type="image"', $output);
    }

    public function testOutputButtonHasSelectImageLabel(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('Select image', $output);
    }

    public function testOutputButtonNoPreviewSizeAttributeByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('data-cfdev-media-preview-size', $output);
    }

    public function testOutputButtonHasPreviewSizeWhenArgSet(): void
    {
        $field = $this->makeField(['args' => ['preview_size' => 'thumbnail']]);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('data-cfdev-media-preview-size="thumbnail"', $output);
    }

    public function testOutputButtonPreviewSizeArrayIsJsonEncoded(): void
    {
        $field = $this->makeField(['args' => ['preview_size' => [200, 200]]]);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('data-cfdev-media-preview-size=', $output);
        $this->assertStringContainsString('200', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — remove link
    // -------------------------------------------------------------------------

    public function testOutputShowsRemoveLinkWhenValueSet(): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/img.jpg', 800, 600]);

        $output = $this->makeField()->outputHtml('42');
        $this->assertStringContainsString('js-cfdev-remove-media', $output);
        $this->assertStringContainsString('Remove current image', $output);
    }

    public function testOutputNoRemoveLinkWhenNoValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('js-cfdev-remove-media', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — image preview
    // -------------------------------------------------------------------------

    public function testOutputRendersPreviewSpan(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-preview', $output);
    }

    public function testOutputRendersImgTagWhenValueSet(): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/photo.jpg', 800, 600]);

        $output = $this->makeField()->outputHtml('42');
        $this->assertStringContainsString('<img src="https://example.com/photo.jpg"', $output);
    }

    public function testOutputNoImgTagWhenNoValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('<img', $output);
    }

    public function testOutputUsesPreviewSizeArg(): void
    {
        Functions\expect('wp_get_attachment_image_src')
            ->once()
            ->with(\Mockery::any(), 'thumbnail')
            ->andReturn(['https://example.com/thumb.jpg', 150, 150]);

        $this->addToAssertionCount(1);

        $field = $this->makeField(['args' => ['preview_size' => 'thumbnail']]);
        $field->outputHtml('42');
    }

    public function testOutputUsesMediumAsDefaultPreviewSize(): void
    {
        Functions\expect('wp_get_attachment_image_src')
            ->once()
            ->with(\Mockery::any(), 'medium')
            ->andReturn(['https://example.com/medium.jpg', 300, 200]);

        $this->addToAssertionCount(1);

        $this->makeField()->outputHtml('42');
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Upload an image']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Upload an image', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
