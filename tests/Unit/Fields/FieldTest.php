<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class FieldTest extends CFDevTestCase
{
    /** @param array<string, mixed> $overrides */
    private function makeField(array $overrides = []): Text
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(function (string $title): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        });

        return new Text(array_merge(['type' => 'text', 'name' => 'my_field', 'label' => 'My Field', 'underscore' => false], $overrides), 'my_mb');
    }

    // -------------------------------------------------------------------------
    // decodeMetaValue — pure string passthrough
    // -------------------------------------------------------------------------

    public function testDecodePlainStringReturnsUnchanged(): void
    {
        $this->assertSame('hello', Field::decodeMetaValue('hello'));
    }

    public function testDecodeEmptyStringReturnsEmpty(): void
    {
        $this->assertSame('', Field::decodeMetaValue(''));
    }

    public function testDecodeMinusOneReturnsMinusOne(): void
    {
        $this->assertSame('-1', Field::decodeMetaValue('-1'));
    }

    public function testDecodeNumericStringReturnsUnchanged(): void
    {
        $this->assertSame('42', Field::decodeMetaValue('42'));
    }

    public function testDecodeNonStringReturnsUnchanged(): void
    {
        $this->assertSame(42, Field::decodeMetaValue(42));
        $this->assertNull(Field::decodeMetaValue(null));
        $this->assertFalse(Field::decodeMetaValue(false));
    }

    // -------------------------------------------------------------------------
    // decodeMetaValue — JSON arrays
    // -------------------------------------------------------------------------

    public function testDecodeJsonArrayReturnsArray(): void
    {
        $result = Field::decodeMetaValue('["a","b","c"]');
        $this->assertSame(['a', 'b', 'c'], $result);
    }

    public function testDecodeJsonObjectReturnsArray(): void
    {
        $result = Field::decodeMetaValue('{"url":"https://example.com","text":"Click","target":"_blank"}');
        $this->assertIsArray($result);
        $this->assertSame('https://example.com', $result['url']);
        $this->assertSame('_blank', $result['target']);
    }

    public function testDecodeEmptyJsonArrayReturnsEmptyArray(): void
    {
        $result = Field::decodeMetaValue('[]');
        $this->assertSame([], $result);
    }

    public function testDecodeJsonArrayOfIds(): void
    {
        $result = Field::decodeMetaValue('[1,2,3]');
        $this->assertSame([1, 2, 3], $result);
    }

    public function testDecodeInvalidJsonReturnsOriginalString(): void
    {
        Functions\when('wp_unslash')->returnArg(1);
        $result = Field::decodeMetaValue('[not valid json');
        $this->assertSame('[not valid json', $result);
    }

    public function testDecodeStringNotStartingWithBracketIsNotDecoded(): void
    {
        // Strings not starting with [ or { are returned as-is — no JSON parse attempt
        $result = Field::decodeMetaValue('some text with [brackets]');
        $this->assertSame('some text with [brackets]', $result);
    }

    // -------------------------------------------------------------------------
    // Field::save() — meta type dispatch
    // -------------------------------------------------------------------------

    public function testSavePostCallsUpdatePostMeta(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\expect('update_post_meta')->once()->with(1, 'my_mb_my_field', 'value')->andReturn(true);

        $field            = $this->makeField();
        $field->meta_type = 'post';
        $this->assertTrue($field->save(1, 'value'));
    }

    public function testSaveUserCallsUpdateUserMeta(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\expect('update_user_meta')->once()->with(5, 'my_mb_my_field', 'value')->andReturn(true);

        $field            = $this->makeField();
        $field->meta_type = 'user';
        $this->assertTrue($field->save(5, 'value'));
    }

    public function testSaveTermCallsUpdateTermMeta(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\expect('update_term_meta')->once()->with(3, 'my_mb_my_field', 'value')->andReturn(true);

        $field            = $this->makeField();
        $field->meta_type = 'term';
        $this->assertTrue($field->save(3, 'value'));
    }

    public function testSaveUnknownMetaTypeReturnsFalse(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');

        $field            = $this->makeField();
        $field->meta_type = 'unknown';
        $result           = $field->save(1, 'value');

        $this->assertFalse($result);
    }

    public function testSaveDefaultMetaTypeReturnsFalse(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias('json_encode');

        // meta_type is '' by default — hits default => false
        $field  = $this->makeField();
        $result = $field->save(1, 'value');

        $this->assertFalse($result);
    }

    public function testSaveJsonEncodesArrayValue(): void
    {
        $encoded = null;
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_json_encode')->alias(function (mixed $v) use (&$encoded): string {
            $encoded = $v;
            return (string) json_encode($v); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
        });
        Functions\when('update_post_meta')->justReturn(true);

        $field            = $this->makeField();
        $field->meta_type = 'post';
        $field->save(1, ['a', 'b']);

        $this->assertSame(['a', 'b'], $encoded);
    }

    // -------------------------------------------------------------------------
    // Field::output() — dispatch logic
    // -------------------------------------------------------------------------

    public function testOutputCallsOutputHtmlByDefault(): void
    {
        $field  = $this->makeField();
        $output = $field->output('hello');
        $this->assertStringContainsString('type="text"', $output);
        $this->assertStringContainsString('value="hello"', $output);
    }

    public function testOutputRepeatableWrapsInSortableList(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeField(['repeatable' => true]);
        $output = $field->output(['one', 'two']);
        $this->assertStringContainsString('cfdev-sortable-item', $output);
        $this->assertSame(2, substr_count($output, 'js-cfdev-sortable-item"'));
    }

    public function testOutputRepeatableSingleItemHasNoRemoveButton(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeField(['repeatable' => true]);
        $output = $field->output(['only']);
        $this->assertStringNotContainsString('cfdev-remove-sortable', $output);
    }

    public function testOutputRepeatableMultipleItemsHaveRemoveButton(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeField(['repeatable' => true]);
        $output = $field->output(['one', 'two']);
        $this->assertStringContainsString('cfdev-remove-sortable', $output);
    }

    public function testOutputAjaxAddsAjaxSaveButton(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeField(['ajax' => true]);
        $output = $field->output('hello');
        $this->assertStringContainsString('js-cfdev-ajax-save', $output);
    }

    public function testOutputRepeatableTakesPriorityOverAjax(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeField(['repeatable' => true, 'ajax' => true]);
        $output = $field->output(['one']);
        $this->assertStringContainsString('cfdev-sortable-item', $output);
        $this->assertStringNotContainsString('js-cfdev-ajax-save', $output);
    }
}
