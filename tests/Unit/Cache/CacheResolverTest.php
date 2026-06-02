<?php

namespace Weblitzer\CFDev\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Cache\CacheResolver;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class CacheResolverTest extends CFDevTestCase
{
    private CacheResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new CacheResolver();
    }

    // -------------------------------------------------------------------------
    // field() — passthrough for empty / falsy raw values
    // -------------------------------------------------------------------------

    public function testFieldEmptyStringIsReturnedAsIs(): void
    {
        $this->assertSame('', $this->resolver->field('text', ''));
    }

    public function testFieldNullIsReturnedAsIs(): void
    {
        $this->assertNull($this->resolver->field('text', null));
    }

    public function testFieldFalseIsReturnedAsIs(): void
    {
        $this->assertFalse($this->resolver->field('text', false));
    }

    // -------------------------------------------------------------------------
    // field() — default types (text, number, date, …) return raw
    // -------------------------------------------------------------------------

    public function testFieldTextReturnsRawString(): void
    {
        $this->assertSame('hello world', $this->resolver->field('text', 'hello world'));
    }

    public function testFieldNumberReturnsRaw(): void
    {
        $this->assertSame('42', $this->resolver->field('number', '42'));
    }

    public function testFieldColorReturnsRaw(): void
    {
        $this->assertSame('#ff6600', $this->resolver->field('color', '#ff6600'));
    }

    public function testFieldDateReturnsRaw(): void
    {
        $this->assertSame('2024-12-31', $this->resolver->field('date', '2024-12-31'));
    }

    // -------------------------------------------------------------------------
    // field() — link
    // -------------------------------------------------------------------------

    public function testFieldLinkArrayIsReturnedAsIs(): void
    {
        $link = ['url' => 'https://example.com', 'text' => 'Click', 'target' => '_blank'];
        $this->assertSame($link, $this->resolver->field('link', $link));
    }

    public function testFieldLinkJsonStringIsDecoded(): void
    {
        $json   = '{"url":"https://example.com","text":"Click","target":"_blank"}';
        $result = $this->resolver->field('link', $json);

        $this->assertIsArray($result);
        $this->assertSame('https://example.com', $result['url']);
        $this->assertSame('Click', $result['text']);
    }

    public function testFieldLinkPlainStringIsWrappedInStructure(): void
    {
        $result = $this->resolver->field('link', 'https://example.com');

        $this->assertIsArray($result);
        $this->assertSame('https://example.com', $result['url']);
        $this->assertSame('', $result['text']);
        $this->assertSame('', $result['target']);
    }

    // -------------------------------------------------------------------------
    // field() — multiValue (checkboxes, multi_select, post/term/user_checkboxes)
    // -------------------------------------------------------------------------

    public function testFieldCheckboxesReturnsReindexedValues(): void
    {
        $result = $this->resolver->field('checkboxes', ['key_a' => 'red', 'key_b' => 'blue']);
        $this->assertSame(['red', 'blue'], $result);
    }

    public function testFieldMultiSelectDecodesJsonString(): void
    {
        $result = $this->resolver->field('multi_select', '["option1","option2"]');
        $this->assertSame(['option1', 'option2'], $result);
    }

    public function testFieldPostCheckboxesWithArrayReturnsValues(): void
    {
        $result = $this->resolver->field('post_checkboxes', [42, 7]);
        $this->assertSame([42, 7], $result);
    }

    public function testFieldTermCheckboxesWithArrayReturnsValues(): void
    {
        $result = $this->resolver->field('term_checkboxes', ['5', '9']);
        $this->assertSame(['5', '9'], $result);
    }

    public function testFieldUserCheckboxesWithArrayReturnsValues(): void
    {
        $result = $this->resolver->field('user_checkboxes', [1, 2, 3]);
        $this->assertSame([1, 2, 3], $result);
    }

    // -------------------------------------------------------------------------
    // field() — singleValue (radios)
    // -------------------------------------------------------------------------

    public function testFieldRadiosWithArrayReturnsFirstElement(): void
    {
        $result = $this->resolver->field('radios', ['yes', 'no']);
        $this->assertSame('yes', $result);
    }

    public function testFieldRadiosDecodesJsonAndReturnsFirstElement(): void
    {
        $result = $this->resolver->field('radios', '["option_a","option_b"]');
        $this->assertSame('option_a', $result);
    }

    public function testFieldRadiosWithPlainStringReturnsItDirectly(): void
    {
        $result = $this->resolver->field('radios', 'option_x');
        $this->assertSame('option_x', $result);
    }

    // -------------------------------------------------------------------------
    // field() — image (private, exercised via field())
    // -------------------------------------------------------------------------

    public function testFieldImageWithZeroIdReturnsEmptyArray(): void
    {
        // id = 0 → early return before any WP function calls
        $this->assertSame([], $this->resolver->field('image', '0'));
    }

    public function testFieldImageWithValidIdReturnsStructuredArray(): void
    {
        Functions\when('get_post_meta')->justReturn('Alt text');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('image', '3');

        $this->assertIsArray($result);
        $this->assertSame(3, $result['id']);
        $this->assertSame('Alt text', $result['alt']);
        $this->assertSame('https://example.com/img.jpg', $result['full']);
    }

    public function testFieldImageFallsBackToPostTitleWhenAltIsEmpty(): void
    {
        $post             = new \stdClass();
        $post->post_title = 'Attachment title';
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn($post);
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('image', '5');

        $this->assertSame('Attachment title', $result['alt']);
    }

    public function testFieldImageResolvesAvailableSizes(): void
    {
        Functions\when('get_post_meta')->justReturn('Alt');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/full.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn([
            'sizes' => ['thumbnail' => [], 'medium' => []],
        ]);
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/thumb.jpg', 150, 150]);

        $result = $this->resolver->field('image', '2');

        $this->assertArrayHasKey('thumbnail', $result);
        $this->assertArrayHasKey('medium', $result);
        $this->assertSame('https://example.com/thumb.jpg', $result['thumbnail']);
    }

    // -------------------------------------------------------------------------
    // field() — image_alt
    // -------------------------------------------------------------------------

    public function testFieldImageAltWithEmptyArrayReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->resolver->field('image_alt', []));
    }

    public function testFieldImageAltJsonWithZeroIdReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->resolver->field('image_alt', '{"id":0,"alt":"test"}'));
    }

    public function testFieldImageAltCustomAltOverridesPostMeta(): void
    {
        Functions\when('get_post_meta')->justReturn('Post meta alt');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('image_alt', '{"id":5,"alt":"Custom alt"}');

        $this->assertSame('Custom alt', $result['alt']);
    }

    public function testFieldImageAltFallsBackToPostMetaAltWhenCustomAltIsEmpty(): void
    {
        Functions\when('get_post_meta')->justReturn('Post meta alt');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('image_alt', '{"id":5,"alt":""}');

        $this->assertSame('Post meta alt', $result['alt']);
    }

    // -------------------------------------------------------------------------
    // field() — file
    // -------------------------------------------------------------------------

    public function testFieldFileWithZeroReturnsEmptyArray(): void
    {
        // '0' is numeric (id=0) but also a non-empty string → legacy path calls attachment_url_to_postid
        Functions\when('attachment_url_to_postid')->justReturn(0);
        $this->assertSame([], $this->resolver->field('file', '0'));
    }

    public function testFieldFileWithValidIdReturnsStructuredArray(): void
    {
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/doc.pdf');
        Functions\when('get_attached_file')->justReturn('/var/www/uploads/doc.pdf');

        $result = $this->resolver->field('file', '7');

        $this->assertSame(7, $result['id']);
        $this->assertSame('https://example.com/doc.pdf', $result['url']);
        $this->assertSame('doc.pdf', $result['filename']);
    }

    // -------------------------------------------------------------------------
    // field() — gallery
    // -------------------------------------------------------------------------

    public function testFieldGalleryWithEmptyArrayReturnsEmpty(): void
    {
        $this->assertSame([], $this->resolver->field('gallery', []));
    }

    public function testFieldGalleryWithZeroIdsFiltersAllOut(): void
    {
        // intval('0') = 0 → falsy → removed by array_filter before image() is called
        $this->assertSame([], $this->resolver->field('gallery', ['0', '0']));
    }

    public function testFieldGalleryResolvesEachValidId(): void
    {
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn(null);
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('gallery', ['1', '2']);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame(2, $result[1]['id']);
    }

    // -------------------------------------------------------------------------
    // bundle()
    // -------------------------------------------------------------------------

    public function testBundleReturnsEmptyArrayForEmptyRaw(): void
    {
        $this->assertSame([], $this->resolver->bundle([], ''));
    }

    public function testBundleReturnsEmptyArrayForEmptyArrayRaw(): void
    {
        $defs = ['title' => ['type' => 'text', 'label' => 'Title', 'required' => false]];
        $this->assertSame([], $this->resolver->bundle($defs, []));
    }

    public function testBundleResolvesEachRowWithTextFields(): void
    {
        $defs = [
            'title' => ['type' => 'text', 'label' => 'Title', 'required' => false],
            'color' => ['type' => 'color', 'label' => 'Color', 'required' => false],
        ];
        $raw = [
            ['title' => 'Row one', 'color' => '#ff0000'],
            ['title' => 'Row two', 'color' => '#00ff00'],
        ];

        $result = $this->resolver->bundle($defs, $raw);

        $this->assertCount(2, $result);
        $this->assertSame('Row one', $result[0]['title']);
        $this->assertSame('#ff0000', $result[0]['color']);
        $this->assertSame('Row two', $result[1]['title']);
    }

    public function testBundleDecodesJsonStringOfRows(): void
    {
        $defs = ['name' => ['type' => 'text', 'label' => 'Name', 'required' => false]];
        $json = '[{"name":"Alice"},{"name":"Bob"}]';

        $result = $this->resolver->bundle($defs, $json);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('Bob', $result[1]['name']);
    }

    public function testBundleUsesFallbackEmptyStringForMissingRowKeys(): void
    {
        $defs = [
            'title' => ['type' => 'text', 'label' => 'Title', 'required' => false],
            'extra' => ['type' => 'text', 'label' => 'Extra', 'required' => false],
        ];
        $raw = [['title' => 'Hello']]; // 'extra' key missing

        $result = $this->resolver->bundle($defs, $raw);

        $this->assertSame('Hello', $result[0]['title']);
        $this->assertSame('', $result[0]['extra']);
    }

    public function testBundleReturnsIndexedArray(): void
    {
        $defs = ['val' => ['type' => 'text', 'label' => 'Val', 'required' => false]];
        $raw  = [5 => ['val' => 'a'], 10 => ['val' => 'b']]; // non-sequential keys

        $result = $this->resolver->bundle($defs, $raw);

        $this->assertSame(0, array_key_first($result));
        $this->assertSame(1, array_key_last($result));
    }

    // -------------------------------------------------------------------------
    // image() — uncovered branches
    // -------------------------------------------------------------------------

    public function testFieldImageAltRemainsEmptyWhenGetPostIsNull(): void
    {
        // alt_meta = '' AND get_post returns null → alt stays '' (line 87 false branch)
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_post')->justReturn(null);
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('image', '4');

        $this->assertSame('', $result['alt']);
    }

    public function testFieldImageSizeSkippedWhenWpGetAttachmentImageSrcReturnsFalse(): void
    {
        // wp_get_attachment_image_src returns false → size not added to result (line 96 false branch)
        Functions\when('get_post_meta')->justReturn('Alt');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn([
            'sizes' => ['thumbnail' => [], 'medium' => []],
        ]);
        Functions\when('wp_get_attachment_image_src')->justReturn(false);

        $result = $this->resolver->field('image', '6');

        $this->assertArrayNotHasKey('thumbnail', $result);
        $this->assertArrayNotHasKey('medium', $result);
        $this->assertArrayHasKey('full', $result);
    }

    // -------------------------------------------------------------------------
    // imageAlt() — array input path
    // -------------------------------------------------------------------------

    public function testFieldImageAltWithDirectArrayInputResolvesImage(): void
    {
        // raw is already an array, not a JSON string (line 107 true branch with non-empty data)
        Functions\when('get_post_meta')->justReturn('Post meta alt');
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/img.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn(['sizes' => []]);

        $result = $this->resolver->field('image_alt', ['id' => 5, 'alt' => 'Direct alt']);

        $this->assertSame(5, $result['id']);
        $this->assertSame('Direct alt', $result['alt']);
    }

    // -------------------------------------------------------------------------
    // file() — legacy URL path
    // -------------------------------------------------------------------------

    public function testFieldFileLegacyUrlStringCallsAttachmentUrlToPostId(): void
    {
        // Non-numeric URL → $id = 0 → enters legacy branch → attachment_url_to_postid called → still 0 → []
        Functions\when('attachment_url_to_postid')->justReturn(0);

        $result = $this->resolver->field('file', 'https://example.com/legacy-doc.pdf');

        $this->assertSame([], $result);
    }

    public function testFieldFileLegacyUrlWithValidIdReturnsStructure(): void
    {
        // Non-numeric URL → attachment_url_to_postid returns valid ID → structure returned
        Functions\when('attachment_url_to_postid')->justReturn(12);
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/legacy-doc.pdf');
        Functions\when('get_attached_file')->justReturn('/var/www/uploads/legacy-doc.pdf');

        $result = $this->resolver->field('file', 'https://example.com/legacy-doc.pdf');

        $this->assertSame(12, $result['id']);
        $this->assertSame('https://example.com/legacy-doc.pdf', $result['url']);
        $this->assertSame('legacy-doc.pdf', $result['filename']);
    }

    // -------------------------------------------------------------------------
    // toArray() — PHP serialized legacy path
    // -------------------------------------------------------------------------

    public function testBundleDecodesLegacyPhpSerializedRows(): void
    {
        // toArray() unserializes PHP-serialized data (lines 206-209)
        $defs       = ['name' => ['type' => 'text', 'label' => 'Name', 'required' => false]];
        $serialized = serialize([['name' => 'Alice'], ['name' => 'Bob']]); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

        $result = $this->resolver->bundle($defs, $serialized);

        $this->assertCount(2, $result);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame('Bob', $result[1]['name']);
    }

    // -------------------------------------------------------------------------
    // multiValue() → toArray() with non-JSON, non-serialized string
    // -------------------------------------------------------------------------

    public function testFieldCheckboxesWithNonJsonStringReturnsEmptyArray(): void
    {
        // multiValue('plain-text') → toArray('plain-text') → json_decode=null, no serialized match → []
        $result = $this->resolver->field('checkboxes', 'plain-text-value');

        $this->assertSame([], $result);
    }
}
