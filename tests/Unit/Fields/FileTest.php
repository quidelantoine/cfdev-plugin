<?php

namespace CFDev\Tests\Unit\Fields;

use CFDev\Fields\File;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class FileTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): File
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        $defaults = [
            'type' => 'file',
            'name' => 'my_file',
            'label' => 'My File',
        ];

        return new File(array_merge($defaults, $overrides), 'my_metabox');
    }

    private function mockAttachment(int $id, object $post): void
    {
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('attachment_url_to_postid')->justReturn($id);
        Functions\when('get_post')->justReturn($post);
    }

    private function mockAttachmentNotFound(): void
    {
        Functions\when('wp_cache_get')->justReturn(false);
        Functions\when('wp_cache_set')->justReturn(true);
        Functions\when('attachment_url_to_postid')->justReturn(0);
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
        $this->mockAttachment(5, (object)[
            'post_title'     => 'My Doc',
            'post_mime_type' => 'application/pdf',
        ]);

        $output = $this->makeField()->outputHtml('https://example.com/doc.pdf');
        $this->assertStringContainsString('value="https://example.com/doc.pdf"', $output);
    }

    public function testOutputHiddenInputEmptyWhenNoValue(): void
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

    public function testOutputButtonHasMediaTypeFile(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('data-cfdev-media-type="file"', $output);
    }

    public function testOutputButtonHasSelectFileLabel(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('Select file', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — remove link
    // -------------------------------------------------------------------------

    public function testOutputShowsRemoveLinkWhenValueSet(): void
    {
        $this->mockAttachmentNotFound();

        $output = $this->makeField()->outputHtml('https://example.com/doc.pdf');
        $this->assertStringContainsString('js-cfdev-remove-media', $output);
        $this->assertStringContainsString('Remove current file', $output);
    }

    public function testOutputNoRemoveLinkWhenNoValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('js-cfdev-remove-media', $output);
    }

    // -------------------------------------------------------------------------
    // outputHtml — file preview
    // -------------------------------------------------------------------------

    public function testOutputRendersPreviewSpan(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringContainsString('cfdev-preview', $output);
    }

    public function testOutputRendersFileLinkWhenValueSet(): void
    {
        $this->mockAttachment(5, (object)[
            'post_title'     => 'My Document',
            'post_mime_type' => 'application/pdf',
        ]);

        $output = $this->makeField()->outputHtml('https://example.com/doc.pdf');
        $this->assertStringContainsString('href="https://example.com/doc.pdf"', $output);
        $this->assertStringContainsString('My Document', $output);
    }

    public function testOutputNoLinkWhenNoValue(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('<a', $output);
    }

    public function testOutputPreviewContainsMimeClass(): void
    {
        $this->mockAttachment(5, (object)[
            'post_title'     => 'My Doc',
            'post_mime_type' => 'application/pdf',
        ]);

        $output = $this->makeField()->outputHtml('https://example.com/doc.pdf');
        $this->assertStringContainsString('mime-application_pdf', $output);
    }

    public function testOutputPreviewEmptyWhenAttachmentNotFound(): void
    {
        $this->mockAttachmentNotFound();

        $output = $this->makeField()->outputHtml('https://example.com/doc.pdf');
        $this->assertStringContainsString('cfdev-mime', $output);
        $this->assertStringNotContainsString('My Doc', $output);
    }

    // -------------------------------------------------------------------------
    // getAttachmentByUrl
    // -------------------------------------------------------------------------

    public function testGetAttachmentByUrlReturnsNullForEmptyUrl(): void
    {
        $result = File::getAttachmentByUrl('');
        $this->assertNull($result);
    }

    public function testGetAttachmentByUrlReturnsPostWhenFound(): void
    {
        $post = (object)['post_title' => 'Report', 'post_mime_type' => 'application/pdf'];
        $this->mockAttachment(7, $post);

        $result = File::getAttachmentByUrl('https://example.com/report.pdf');
        $this->assertSame($post, $result);
    }

    public function testGetAttachmentByUrlReturnsNullWhenNotFound(): void
    {
        $this->mockAttachmentNotFound();

        $result = File::getAttachmentByUrl('https://example.com/missing.pdf');
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // outputHtml — explanation
    // -------------------------------------------------------------------------

    public function testOutputIncludesExplanation(): void
    {
        $field = $this->makeField(['explanation' => 'Upload a PDF']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('Upload a PDF', $output);
        $this->assertStringContainsString('cfdev-explanation', $output);
    }

    public function testOutputNoExplanationByDefault(): void
    {
        $output = $this->makeField()->outputHtml('');
        $this->assertStringNotContainsString('cfdev-explanation', $output);
    }
}
