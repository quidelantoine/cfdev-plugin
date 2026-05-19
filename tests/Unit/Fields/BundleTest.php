<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Fields\Bundle;
use Weblitzer\CFDev\Fields\Gallery;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class BundleTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    private function makeBundle(string $id = 'details', array $data = []): Bundle
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });
        return new Bundle($id, $data);
    }

    private function makeText(string $name): Text
    {
        return new Text(['type' => 'text', 'name' => $name, 'label' => ucfirst($name), 'underscore' => false], null);
    }

    private function captureOutput(Bundle $bundle, object $post): string
    {
        ob_start();
        $bundle->output($post);
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // buildId
    // -------------------------------------------------------------------------

    public function testBuildIdPrependsUnderscore(): void
    {
        $bundle = $this->makeBundle('details');
        $this->assertSame('_details', $bundle->id);
    }

    public function testBuildIdKeepsExistingLeadingUnderscore(): void
    {
        $bundle = $this->makeBundle('_details');
        $this->assertSame('_details', $bundle->id);
    }

    // -------------------------------------------------------------------------
    // save() — meta type dispatch
    // -------------------------------------------------------------------------

    public function testSavePostCallsDeleteAndUpdatePostMeta(): void
    {
        $updated = false;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('update_post_meta')->alias(function () use (&$updated): bool {
            $updated = true;
            return true;
        });

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');
        $bundle->save(1, [['title' => 'Hello']]);

        $this->assertTrue($updated);
    }

    public function testSaveUserCallsDeleteAndUpdateUserMeta(): void
    {
        $updated = false;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_user_meta')->justReturn(true);
        Functions\when('update_user_meta')->alias(function () use (&$updated): bool {
            $updated = true;
            return true;
        });

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'user';
        $bundle->fields['title'] = $this->makeText('title');
        $bundle->save(5, [['title' => 'Hello']]);

        $this->assertTrue($updated);
    }

    public function testSaveTermCallsDeleteAndUpdateTermMeta(): void
    {
        $updated = false;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_term_meta')->justReturn(true);
        Functions\when('update_term_meta')->alias(function () use (&$updated): bool {
            $updated = true;
            return true;
        });

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'term';
        $bundle->fields['title'] = $this->makeText('title');
        $bundle->save(3, [['title' => 'Hello']]);

        $this->assertTrue($updated);
    }

    // -------------------------------------------------------------------------
    // save() — value processing
    // -------------------------------------------------------------------------

    public function testSaveFiltersNonArrayRows(): void
    {
        $stored = null;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias(function (mixed $v) use (&$stored): string|false {
            $stored = $v;
            return json_encode($v); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
        });
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('update_post_meta')->justReturn(true);

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');
        $bundle->save(1, [['title' => 'Row A'], 'not-an-array', 42, ['title' => 'Row B']]);

        $this->assertIsArray($stored);
        $this->assertCount(2, $stored);
    }

    public function testSaveCallsSaveValueOnEachField(): void
    {
        $stored = null;
        Functions\when('sanitize_text_field')->alias(fn(string $v): string => strtoupper($v));
        Functions\when('wp_json_encode')->alias(function (mixed $v) use (&$stored): string|false {
            $stored = $v;
            return json_encode($v); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
        });
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('update_post_meta')->justReturn(true);

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');
        $bundle->save(1, [['title' => 'hello']]);

        $this->assertSame('HELLO', $stored[0]['title']);
    }

    public function testSavePreservesRowsWithUnknownFieldIds(): void
    {
        $stored = null;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias(function (mixed $v) use (&$stored): string|false {
            $stored = $v;
            return json_encode($v); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
        });
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('update_post_meta')->justReturn(true);

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        // No fields attached — unknown keys kept as-is
        $bundle->save(1, [['color' => 'red', 'size' => 'L']]);

        $this->assertSame('red', $stored[0]['color']);
        $this->assertSame('L', $stored[0]['size']);
    }

    public function testSaveStoresJsonEncodedValue(): void
    {
        $stored = null;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_post_meta')->justReturn(true);
        Functions\when('update_post_meta')->alias(function (int $id, string $key, string $value) use (&$stored): bool {
            $stored = $value;
            return true;
        });

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');
        $bundle->save(1, [['title' => 'Hello']]);

        $decoded = json_decode((string) $stored, true); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_decode_json_decode
        $this->assertIsArray($decoded);
        $this->assertSame('Hello', $decoded[0]['title']);
    }

    public function testSaveSkipsDatabaseWhenJsonEncodeFails(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->justReturn(false);

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->save(1, [['title' => 'Hello']]);

        // delete_post_meta / update_post_meta must not be called — they are not mocked,
        // so any call would trigger a Brain\Monkey exception and fail the test.
        $this->expectNotToPerformAssertions();
    }

    // -------------------------------------------------------------------------
    // output() — meta state branches
    // -------------------------------------------------------------------------

    public function testOutputWithEmptyMetaRendersOneEmptyItem(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertSame(1, substr_count($output, 'js-cfdev-sortable-item'));
        $this->assertStringNotContainsString('cfdev-remove-sortable', $output);
    }

    public function testOutputWithSingleRowRendersNoRemoveButton(): void
    {
        Functions\when('get_post_meta')->justReturn('[{"title":"Hello"}]');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertSame(1, substr_count($output, 'js-cfdev-sortable-item'));
        $this->assertStringNotContainsString('cfdev-remove-sortable', $output);
    }

    public function testOutputWithMultipleRowsRendersRemoveButtons(): void
    {
        Functions\when('get_post_meta')->justReturn('[{"title":"Row A"},{"title":"Row B"}]');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertSame(2, substr_count($output, 'js-cfdev-sortable-item'));
        $this->assertStringContainsString('cfdev-remove-sortable', $output);
    }

    public function testOutputUsesUserMetaForUserType(): void
    {
        Functions\when('get_user_meta')->justReturn('[{"title":"User Row"}]');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'user';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 7]);

        $this->assertSame(1, substr_count($output, 'js-cfdev-sortable-item'));
    }

    public function testOutputUsesTermMetaForTermType(): void
    {
        Functions\when('get_term_meta')->justReturn('[{"title":"Term Row"}]');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'term';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 3]);

        $this->assertSame(1, substr_count($output, 'js-cfdev-sortable-item'));
    }

    public function testOutputWithDefaultValueRendersDefaultItems(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $bundle = $this->makeBundle('details', ['default_value' => [['title' => 'Default']]]);
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertSame(1, substr_count($output, 'js-cfdev-sortable-item'));
    }

    // -------------------------------------------------------------------------
    // output() — renderField structure
    // -------------------------------------------------------------------------

    public function testOutputRendersFieldInTableRow(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertStringContainsString('<tr', $output);
        $this->assertStringContainsString('cfdev-th', $output);
        $this->assertStringContainsString('cfdev-td', $output);
        $this->assertStringContainsString('type="text"', $output);
    }

    public function testOutputWithUnsupportedFieldRendersMessage(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['photos'] = new Gallery(
            ['type' => 'gallery', 'name' => 'photos', 'label' => 'Photos', 'underscore' => false],
            null
        );

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertStringContainsString("doesn't support", $output);
    }

    public function testOutputRendersFieldValuesFromMeta(): void
    {
        Functions\when('get_post_meta')->justReturn('[{"title":"Stored Value"}]');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        $this->assertStringContainsString('Stored Value', $output);
    }

    public function testOutputFieldNameIncludesBundleIdAndRowIndex(): void
    {
        Functions\when('get_post_meta')->justReturn('');

        $bundle = $this->makeBundle();
        $bundle->meta_type = 'post';
        $bundle->fields['title'] = $this->makeText('title');

        $output = $this->captureOutput($bundle, (object) ['ID' => 1]);

        // name="cfdev[_details][0][title]"
        $this->assertStringContainsString('[_details][0][title]', $output);
    }
}