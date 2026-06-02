<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use PHPUnit\Framework\Attributes\DataProvider;
use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Fields\Checkbox;
use Weblitzer\CFDev\Fields\Color;
use Weblitzer\CFDev\Fields\Date;
use Weblitzer\CFDev\Fields\Datetime;
use Weblitzer\CFDev\Fields\Email;
use Weblitzer\CFDev\Fields\Gallery;
use Weblitzer\CFDev\Fields\Hidden;
use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Fields\ImageAlt;
use Weblitzer\CFDev\Fields\Link;
use Weblitzer\CFDev\Fields\Number;
use Weblitzer\CFDev\Fields\PostSelect;
use Weblitzer\CFDev\Fields\Range;
use Weblitzer\CFDev\Fields\Select;
use Weblitzer\CFDev\Fields\Tel;
use Weblitzer\CFDev\Fields\TermSelect;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Fields\Textarea;
use Weblitzer\CFDev\Fields\Time;
use Weblitzer\CFDev\Fields\Toggle;
use Weblitzer\CFDev\Fields\Url;
use Weblitzer\CFDev\Fields\UserSelect;
use Weblitzer\CFDev\Fields\Wysiwyg;
use Weblitzer\CFDev\Fields\Yesno;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class RepeatableOutputTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // supports_repeatable flag — DataProvider sur tous les types
    // -------------------------------------------------------------------------

    /** @return array<string, array{class-string<Field>, bool}> */
    public static function supportsRepeatableProvider(): array
    {
        return [
            'text'        => [Text::class,       true],
            'textarea'    => [Textarea::class,    true],
            'number'      => [Number::class,      true],
            'email'       => [Email::class,       true],
            'tel'         => [Tel::class,         true],
            'url'         => [Url::class,         true],
            'color'       => [Color::class,       true],
            'range'       => [Range::class,       true],
            'date'        => [Date::class,        true],
            'time'        => [Time::class,        true],
            'datetime'    => [Datetime::class,    true],
            'select'      => [Select::class,      true],
            'image'       => [Image::class,       true],
            'post_select' => [PostSelect::class,  true],
            'term_select' => [TermSelect::class,  true],
            'user_select' => [UserSelect::class,  true],
            'link'        => [Link::class,        false],
            'gallery'     => [Gallery::class,     false],
            'image_alt'   => [ImageAlt::class,    false],
            'hidden'      => [Hidden::class,      false],
            'wysiwyg'     => [Wysiwyg::class,     false],
            'toggle'      => [Toggle::class,      false],
            'yesno'       => [Yesno::class,       false],
            'checkbox'    => [Checkbox::class,    false],
        ];
    }

    /**
     * @param class-string<Field> $class
     */
    #[DataProvider('supportsRepeatableProvider')]
    public function testSupportsRepeatableFlag(string $class, bool $expected): void
    {
        $field = new $class(['id' => 'f', 'type' => 'text', 'label' => 'L'], 'post');

        $this->assertSame($expected, $field->supports_repeatable);
    }

    // -------------------------------------------------------------------------
    // Comportement output() — type supporté → wrapper sortable
    // -------------------------------------------------------------------------

    public function testOutputWithRepeatableOnSupportedTypeProducesSortableWrapper(): void
    {
        $field = new Text(
            ['id' => 'tags', 'type' => 'text', 'label' => 'Tags', 'repeatable' => true],
            'post'
        );

        $output = $field->output('hello');

        $this->assertStringContainsString('cfdev-sortable-item', $output);
        $this->assertStringContainsString('js-cfdev-sortable-item', $output);
        $this->assertStringContainsString('<fieldset>', $output);
    }

    public function testOutputWithRepeatableOnSupportedTypeWrapsEachItemSeparately(): void
    {
        $field = new Text(
            ['id' => 'tags', 'type' => 'text', 'label' => 'Tags', 'repeatable' => true],
            'post'
        );

        $output = $field->output(['php', 'js', 'go']);

        // One <fieldset> per item, regardless of class-name substring overlaps
        $this->assertSame(3, substr_count($output, '<fieldset>'));
    }

    public function testOutputWithRepeatableOnSupportedTypeUsesArraySuffix(): void
    {
        $field = new Text(
            ['id' => 'tags', 'type' => 'text', 'label' => 'Tags', 'repeatable' => true],
            'post'
        );

        $output = $field->output('val');

        $this->assertStringContainsString('cfdev[tags][]', $output);
    }

    // -------------------------------------------------------------------------
    // Comportement output() — type non supporté → outputHtml() direct, pas de wrapper
    // -------------------------------------------------------------------------

    public function testOutputWithRepeatableOnUnsupportedTypeIgnoresRepeatable(): void
    {
        $field = new Hidden(
            ['id' => 'token', 'type' => 'hidden', 'label' => 'Token', 'repeatable' => true],
            'post'
        );

        $output = $field->output('abc');

        $this->assertStringNotContainsString('cfdev-sortable-item', $output);
        $this->assertStringContainsString('type="hidden"', $output);
    }

    public function testOutputWithRepeatableOnUnsupportedTypeDoesNotUseArraySuffix(): void
    {
        $field = new Hidden(
            ['id' => 'token', 'type' => 'hidden', 'label' => 'Token', 'repeatable' => true],
            'post'
        );

        $output = $field->output('abc');

        $this->assertStringNotContainsString('cfdev[token][]', $output);
        $this->assertStringContainsString('cfdev[token]', $output);
    }
}
