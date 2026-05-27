<?php

namespace Weblitzer\CFDev\Tests\Unit\Field;

use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Fields\Checkbox;
use Weblitzer\CFDev\Fields\Color;
use Weblitzer\CFDev\Fields\Date;
use Weblitzer\CFDev\Fields\Datetime;
use Weblitzer\CFDev\Fields\Email;
use Weblitzer\CFDev\Fields\File;
use Weblitzer\CFDev\Fields\Gallery;
use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Fields\Link;
use Weblitzer\CFDev\Fields\MultiSelect;
use Weblitzer\CFDev\Fields\Number;
use Weblitzer\CFDev\Fields\PostCheckboxes;
use Weblitzer\CFDev\Fields\PostSelect;
use Weblitzer\CFDev\Fields\Radios;
use Weblitzer\CFDev\Fields\Range;
use Weblitzer\CFDev\Fields\Select;
use Weblitzer\CFDev\Fields\Tel;
use Weblitzer\CFDev\Fields\TermCheckboxes;
use Weblitzer\CFDev\Fields\TermSelect;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Fields\Textarea;
use Weblitzer\CFDev\Fields\Time;
use Weblitzer\CFDev\Fields\Toggle;
use Weblitzer\CFDev\Fields\Url;
use Weblitzer\CFDev\Fields\UserCheckboxes;
use Weblitzer\CFDev\Fields\UserSelect;
use Weblitzer\CFDev\Fields\Wysiwyg;
use Weblitzer\CFDev\Fields\Yesno;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * Tests for Field::fieldIconHtml().
 *
 * Pure method — no DB, no WP calls beyond esc_attr() (already stubbed in CFDevTestCase).
 */
class FieldIconTest extends CFDevTestCase
{
    /** @param class-string<Field> $class */
    private function make(string $class): Field
    {
        return new $class(['type' => 'test', 'label' => 'Test'], null);
    }

    // ── HTML structure ────────────────────────────────────────────────────────

    public function testOutputContainsCfdevFieldIconClass(): void
    {
        $html = $this->make(Email::class)->fieldIconHtml();
        $this->assertStringContainsString('cfdev-field-icon', $html);
    }

    public function testOutputContainsAriaHidden(): void
    {
        $html = $this->make(Email::class)->fieldIconHtml();
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function testOutputContainsDashiconsBaseClass(): void
    {
        $html = $this->make(Email::class)->fieldIconHtml();
        $this->assertStringContainsString('dashicons', $html);
    }

    // ── Correct dashicon per type ─────────────────────────────────────────────

    public function testEmailUsesEmailAltDashicon(): void
    {
        $this->assertStringContainsString('dashicons-email-alt', $this->make(Email::class)->fieldIconHtml());
    }

    public function testImageUsesFormatImageDashicon(): void
    {
        $this->assertStringContainsString('dashicons-format-image', $this->make(Image::class)->fieldIconHtml());
    }

    public function testDateUsesCalendarAltDashicon(): void
    {
        $this->assertStringContainsString('dashicons-calendar-alt', $this->make(Date::class)->fieldIconHtml());
    }

    public function testToggleUsesYesAltDashicon(): void
    {
        $this->assertStringContainsString('dashicons-yes-alt', $this->make(Toggle::class)->fieldIconHtml());
    }

    public function testUserSelectUsesAdminUsersDashicon(): void
    {
        $this->assertStringContainsString('dashicons-admin-users', $this->make(UserSelect::class)->fieldIconHtml());
    }

    // ── Correct color class per category ─────────────────────────────────────

    public function testTextCategoryHasTextColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--text', $this->make(Text::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--text', $this->make(Textarea::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--text', $this->make(Wysiwyg::class)->fieldIconHtml());
    }

    public function testDateCategoryHasDateColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--date', $this->make(Date::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--date', $this->make(Datetime::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--date', $this->make(Time::class)->fieldIconHtml());
    }

    public function testMediaCategoryHasMediaColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--media', $this->make(Image::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--media', $this->make(Gallery::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--media', $this->make(File::class)->fieldIconHtml());
    }

    public function testContactCategoryHasContactColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--contact', $this->make(Email::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--contact', $this->make(Tel::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--contact', $this->make(Url::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--contact', $this->make(Link::class)->fieldIconHtml());
    }

    public function testBoolCategoryHasBoolColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--bool', $this->make(Toggle::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--bool', $this->make(Yesno::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--bool', $this->make(Checkbox::class)->fieldIconHtml());
    }

    public function testChoiceCategoryHasChoiceColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--choice', $this->make(Select::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--choice', $this->make(Radios::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--choice', $this->make(MultiSelect::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--choice', $this->make(Color::class)->fieldIconHtml());
    }

    public function testNumberCategoryHasNumberColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--number', $this->make(Number::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--number', $this->make(Range::class)->fieldIconHtml());
    }

    public function testRelationCategoryHasRelationColorClass(): void
    {
        $this->assertStringContainsString('cfdev-icon--relation', $this->make(PostSelect::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--relation', $this->make(PostCheckboxes::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--relation', $this->make(TermSelect::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--relation', $this->make(TermCheckboxes::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--relation', $this->make(UserSelect::class)->fieldIconHtml());
        $this->assertStringContainsString('cfdev-icon--relation', $this->make(UserCheckboxes::class)->fieldIconHtml());
    }

    // ── Unknown type returns empty ─────────────────────────────────────────────

    public function testBaseFieldWithUnknownTypeReturnsEmptyString(): void
    {
        // Field base class itself is not in the icon map → empty string
        $field = new Field(['type' => 'unknown', 'label' => 'X'], null);
        $this->assertSame('', $field->fieldIconHtml());
    }

    // ── All mapped types produce non-empty HTML ───────────────────────────────

    /**
     * @param class-string<Field> $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('mappedFieldClassProvider')]
    public function testAllMappedTypesReturnNonEmptyHtml(string $class): void
    {
        $this->assertNotSame('', $this->make($class)->fieldIconHtml());
    }

    /** @return array<string, array{class-string<Field>}> */
    public static function mappedFieldClassProvider(): array
    {
        return [
            'Text'           => [Text::class],
            'Textarea'       => [Textarea::class],
            'Wysiwyg'        => [Wysiwyg::class],
            'Number'         => [Number::class],
            'Range'          => [Range::class],
            'Email'          => [Email::class],
            'Tel'            => [Tel::class],
            'Url'            => [Url::class],
            'Color'          => [Color::class],
            'Date'           => [Date::class],
            'Datetime'       => [Datetime::class],
            'Time'           => [Time::class],
            'Image'          => [Image::class],
            'Gallery'        => [Gallery::class],
            'File'           => [File::class],
            'Link'           => [Link::class],
            'Toggle'         => [Toggle::class],
            'Yesno'          => [Yesno::class],
            'Checkbox'       => [Checkbox::class],
            'MultiSelect'    => [MultiSelect::class],
            'Select'         => [Select::class],
            'Radios'         => [Radios::class],
            'PostSelect'     => [PostSelect::class],
            'PostCheckboxes' => [PostCheckboxes::class],
            'TermSelect'     => [TermSelect::class],
            'TermCheckboxes' => [TermCheckboxes::class],
            'UserSelect'     => [UserSelect::class],
            'UserCheckboxes' => [UserCheckboxes::class],
        ];
    }
}
