<?php

namespace Weblitzer\CFDev\Tests\Unit\Validation\Rules;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Weblitzer\CFDev\Validation\Rules\FileExtension;
use Weblitzer\CFDev\Validation\Rules\FileMime;
use Weblitzer\CFDev\Validation\Rules\ImageExactDimensions;
use Weblitzer\CFDev\Validation\Rules\ImageMinDimensions;

class WpAttachmentRulesTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Mock a valid WP attachment with given properties.
     *
     * @param array<string, int> $meta
     */
    private function mockAttachment(
        int $id,
        string $mime = 'image/jpeg',
        string $filepath = '/uploads/photo.jpg',
        array $meta = ['width' => 1200, 'height' => 630]
    ): void {
        Functions\when('get_post_type')->justReturn('attachment');
        Functions\when('get_post_mime_type')->justReturn($mime);
        Functions\when('get_attached_file')->justReturn($filepath);
        Functions\when('wp_get_attachment_metadata')->justReturn($meta);
    }

    private function mockNonAttachment(): void
    {
        Functions\when('get_post_type')->justReturn('post');
    }

    // -------------------------------------------------------------------------
    // File_Mime
    // -------------------------------------------------------------------------

    public function testFileMimeValid(): void
    {
        $this->mockAttachment(42, 'image/jpeg');
        $this->assertTrue((new FileMime(['image/jpeg', 'image/png']))->validate(42));
    }

    public function testFileMimeInvalid(): void
    {
        $this->mockAttachment(42, 'image/gif');
        $this->assertFalse((new FileMime(['image/jpeg', 'image/png']))->validate(42));
    }

    public function testFileMimeNotAnAttachment(): void
    {
        $this->mockNonAttachment();
        $this->assertFalse((new FileMime(['image/jpeg']))->validate(42));
    }

    public function testFileMimeZeroId(): void
    {
        $this->assertFalse((new FileMime(['image/jpeg']))->validate(0));
    }

    public function testFileMimeNegativeId(): void
    {
        $this->assertFalse((new FileMime(['image/jpeg']))->validate(-1));
    }

    public function testFileMimeGetError(): void
    {
        $error = (new FileMime(['image/jpeg', 'image/png']))->getError();
        $this->assertStringContainsString('image/jpeg', $error);
        $this->assertStringContainsString('image/png', $error);
    }

    // -------------------------------------------------------------------------
    // File_Extension
    // -------------------------------------------------------------------------

    public function testFileExtensionValid(): void
    {
        $this->mockAttachment(42, filepath: '/uploads/photo.jpg');
        $this->assertTrue((new FileExtension(['jpg', 'png']))->validate(42));
    }

    public function testFileExtensionInvalid(): void
    {
        $this->mockAttachment(42, filepath: '/uploads/document.pdf');
        $this->assertFalse((new FileExtension(['jpg', 'png']))->validate(42));
    }

    public function testFileExtensionCaseInsensitive(): void
    {
        $this->mockAttachment(42, filepath: '/uploads/photo.JPG');
        $this->assertTrue((new FileExtension(['jpg']))->validate(42));
    }

    public function testFileExtensionNotAnAttachment(): void
    {
        $this->mockNonAttachment();
        $this->assertFalse((new FileExtension(['jpg']))->validate(42));
    }

    public function testFileExtensionGetError(): void
    {
        $error = (new FileExtension(['jpg', 'png']))->getError();
        $this->assertStringContainsString('jpg', $error);
        $this->assertStringContainsString('png', $error);
    }

    // -------------------------------------------------------------------------
    // Image_Min_Dimensions
    // -------------------------------------------------------------------------

    public function testImageMinDimensionsBothValid(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1200, 'height' => 630]);
        $this->assertTrue((new ImageMinDimensions(800, 600))->validate(42));
    }

    public function testImageMinDimensionsExactMatch(): void
    {
        $this->mockAttachment(42, meta: ['width' => 800, 'height' => 600]);
        $this->assertTrue((new ImageMinDimensions(800, 600))->validate(42));
    }

    public function testImageMinDimensionsWidthTooSmall(): void
    {
        $this->mockAttachment(42, meta: ['width' => 400, 'height' => 630]);
        $this->assertFalse((new ImageMinDimensions(800, 600))->validate(42));
    }

    public function testImageMinDimensionsHeightTooSmall(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1200, 'height' => 300]);
        $this->assertFalse((new ImageMinDimensions(800, 600))->validate(42));
    }

    public function testImageMinDimensionsWidthOnly(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1200, 'height' => 100]);
        $this->assertTrue((new ImageMinDimensions(width: 800))->validate(42));
    }

    public function testImageMinDimensionsHeightOnly(): void
    {
        $this->mockAttachment(42, meta: ['width' => 100, 'height' => 800]);
        $this->assertTrue((new ImageMinDimensions(height: 600))->validate(42));
    }

    public function testImageMinDimensionsMissingMeta(): void
    {
        $this->mockAttachment(42, meta: []);
        $this->assertFalse((new ImageMinDimensions(800, 600))->validate(42));
    }

    public function testImageMinDimensionsGetErrorBothDimensions(): void
    {
        $error = (new ImageMinDimensions(800, 600))->getError();
        $this->assertStringContainsString('800', $error);
        $this->assertStringContainsString('600', $error);
    }

    public function testImageMinDimensionsGetErrorWidthOnly(): void
    {
        $error = (new ImageMinDimensions(width: 800))->getError();
        $this->assertStringContainsString('800', $error);
    }

    public function testImageMinDimensionsGetErrorHeightOnly(): void
    {
        $error = (new ImageMinDimensions(height: 600))->getError();
        $this->assertStringContainsString('600', $error);
    }

    // -------------------------------------------------------------------------
    // Image_Exact_Dimensions
    // -------------------------------------------------------------------------

    public function testImageExactDimensionsValid(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1200, 'height' => 630]);
        $this->assertTrue((new ImageExactDimensions(1200, 630))->validate(42));
    }

    public function testImageExactDimensionsWrongWidth(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1100, 'height' => 630]);
        $this->assertFalse((new ImageExactDimensions(1200, 630))->validate(42));
    }

    public function testImageExactDimensionsWrongHeight(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1200, 'height' => 600]);
        $this->assertFalse((new ImageExactDimensions(1200, 630))->validate(42));
    }

    public function testImageExactDimensionsWidthOnly(): void
    {
        $this->mockAttachment(42, meta: ['width' => 1200, 'height' => 9999]);
        $this->assertTrue((new ImageExactDimensions(width: 1200))->validate(42));
    }

    public function testImageExactDimensionsNotAnAttachment(): void
    {
        $this->mockNonAttachment();
        $this->assertFalse((new ImageExactDimensions(1200, 630))->validate(42));
    }

    public function testImageExactDimensionsGetErrorBothDimensions(): void
    {
        $error = (new ImageExactDimensions(1200, 630))->getError();
        $this->assertStringContainsString('1200', $error);
        $this->assertStringContainsString('630', $error);
    }

    public function testImageExactDimensionsGetErrorWidthOnly(): void
    {
        $error = (new ImageExactDimensions(width: 1200))->getError();
        $this->assertStringContainsString('1200', $error);
    }

    public function testImageExactDimensionsGetErrorHeightOnly(): void
    {
        $error = (new ImageExactDimensions(height: 630))->getError();
        $this->assertStringContainsString('630', $error);
    }
}
