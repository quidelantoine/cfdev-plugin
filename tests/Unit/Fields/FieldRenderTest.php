<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Fields\Checkbox;
use Weblitzer\CFDev\Fields\Checkboxes;
use Weblitzer\CFDev\Fields\File;
use Weblitzer\CFDev\Fields\Heading;
use Weblitzer\CFDev\Fields\Hidden;
use Weblitzer\CFDev\Fields\Image;
use Weblitzer\CFDev\Fields\Link;
use Weblitzer\CFDev\Fields\Number;
use Weblitzer\CFDev\Fields\Radios;
use Weblitzer\CFDev\Fields\Select;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Fields\Textarea;
use Weblitzer\CFDev\Fields\Toggle;
use Weblitzer\CFDev\Fields\Wysiwyg;
use Weblitzer\CFDev\Fields\Yesno;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * Tests the outputHtml() method of every field type plus Field::output() routing.
 *
 * Pattern:
 *   $field->outputHtml($value)  → returns HTML string directly (no ob_start needed)
 *   $field->output($value)      → routes to outputHtml / repeatableOutput / ajaxOutput
 */
class FieldRenderTest extends CFDevTestCase
{
    // -------------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'))
        );
        Functions\when('checked')->alias(
            function (mixed $checked, mixed $current, bool $echo = true): string {
                $r = ($checked == $current) ? 'checked="checked"' : '';
                if ($echo) {
                    echo $r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                return $r;
            }
        );
        Functions\when('selected')->alias(
            function (mixed $selected, mixed $current, bool $echo = true): string {
                $r = ($selected == $current) ? ' selected="selected"' : '';
                if ($echo) {
                    echo $r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
                return $r;
            }
        );
    }

    /** @param array<string, mixed> $overrides */
    private function makeText(array $overrides = []): Text
    {
        return new Text(array_merge(['type' => 'text', 'id' => '_my_text', 'name' => 'my_text', 'label' => 'My Text'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeTextarea(array $overrides = []): Textarea
    {
        return new Textarea(array_merge(['type' => 'textarea', 'id' => '_ta', 'name' => 'ta', 'label' => 'TA'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeNumber(array $overrides = []): Number
    {
        return new Number(array_merge(['type' => 'number', 'id' => '_num', 'name' => 'num', 'label' => 'Num'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeSelect(array $overrides = []): Select
    {
        return new Select(array_merge([
            'type' => 'select', 'id' => '_sel', 'name' => 'sel', 'label' => 'Sel',
            'options' => ['a' => 'Alpha', 'b' => 'Beta'],
        ], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeCheckbox(array $overrides = []): Checkbox
    {
        return new Checkbox(array_merge(['type' => 'checkbox', 'id' => '_cb', 'name' => 'cb', 'label' => 'CB'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeCheckboxes(array $overrides = []): Checkboxes
    {
        return new Checkboxes(array_merge([
            'type' => 'checkboxes', 'id' => '_cbs', 'name' => 'cbs', 'label' => 'CBS',
            'options' => ['x' => 'X', 'y' => 'Y'],
        ], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeToggle(array $overrides = []): Toggle
    {
        return new Toggle(array_merge(['type' => 'toggle', 'id' => '_tog', 'name' => 'tog', 'label' => 'Tog'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeHeading(array $overrides = []): Heading
    {
        return new Heading(array_merge(['type' => 'heading', 'id' => 'hdg', 'name' => 'hdg', 'label' => 'Section Title'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeHidden(array $overrides = []): Hidden
    {
        return new Hidden(array_merge(['type' => 'hidden', 'id' => '_hid', 'name' => 'hid', 'label' => 'Hidden'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeRadios(array $overrides = []): Radios
    {
        return new Radios(array_merge([
            'type' => 'radios', 'id' => '_rad', 'name' => 'rad', 'label' => 'Rad',
            'options' => ['yes' => 'Yes', 'no' => 'No'],
        ], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeYesno(array $overrides = []): Yesno
    {
        return new Yesno(array_merge(['type' => 'yesno', 'id' => '_yn', 'name' => 'yn', 'label' => 'YN'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeLink(array $overrides = []): Link
    {
        return new Link(array_merge(['type' => 'link', 'id' => '_lnk', 'name' => 'lnk', 'label' => 'Link'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeImage(array $overrides = []): Image
    {
        return new Image(array_merge(['type' => 'image', 'id' => '_img', 'name' => 'img', 'label' => 'Img'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeFile(array $overrides = []): File
    {
        return new File(array_merge(['type' => 'file', 'id' => '_file', 'name' => 'file', 'label' => 'File'], $overrides), 'box');
    }

    /** @param array<string, mixed> $overrides */
    private function makeWysiwyg(array $overrides = []): Wysiwyg
    {
        Functions\when('wp_editor')->alias(
            fn(string $content, string $id): string => '<div class="wp-editor" id="' . htmlspecialchars($id, ENT_QUOTES) . '"></div>'
        );
        return new Wysiwyg(array_merge(['type' => 'wysiwyg', 'id' => '_wys', 'name' => 'wys', 'label' => 'Wys'], $overrides), 'box');
    }

    // =========================================================================
    // Field::output() routing
    // =========================================================================

    public function testOutputCallsOutputHtmlByDefault(): void
    {
        $field  = $this->makeText();
        $output = $field->output('hello');
        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString('hello', $output);
    }

    public function testOutputCallsRepeatableOutputWhenRepeatableIsTrue(): void
    {
        $field = $this->makeText(['repeatable' => true]);
        Functions\when('__')->returnArg(1);
        $output = $field->output('hello');
        $this->assertStringContainsString('cfdev-sortable-item', $output);
    }

    public function testOutputCallsAjaxOutputWhenAjaxIsTrue(): void
    {
        $field = $this->makeText(['ajax' => true]);
        Functions\when('__')->returnArg(1);
        $output = $field->output('hello');
        $this->assertStringContainsString('cfdev-ajax-save', $output);
    }

    // =========================================================================
    // outputExplanation()
    // =========================================================================

    public function testExplanationRenderedWhenSet(): void
    {
        $field  = $this->makeText(['explanation' => 'Helpful hint']);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('cfdev-explanation', $output);
        $this->assertStringContainsString('Helpful hint', $output);
    }

    public function testExplanationSuppressedWhenFieldIsRepeatable(): void
    {
        $field  = $this->makeText(['explanation' => 'Hint', 'repeatable' => true]);
        $output = $field->outputExplanation();
        $this->assertSame('', $output);
    }

    // =========================================================================
    // repeatableOutput() / ajaxOutput()
    // =========================================================================

    public function testRepeatableOutputContainsSortableItem(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeText(['repeatable' => true]);
        $output = $field->repeatableOutput('val');
        $this->assertStringContainsString('cfdev-sortable-item', $output);
        $this->assertStringContainsString('cfdev-handle-sortable', $output);
    }

    public function testRepeatableOutputWithMultipleItemsShowsRemoveButton(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeText(['repeatable' => true]);
        $output = $field->repeatableOutput(['a', 'b']);
        $this->assertStringContainsString('cfdev-remove-sortable', $output);
    }

    public function testAjaxOutputContainsAjaxSaveButton(): void
    {
        Functions\when('__')->returnArg(1);
        $field  = $this->makeText(['ajax' => true]);
        $output = $field->ajaxOutput('val');
        $this->assertStringContainsString('cfdev-ajax-save', $output);
        $this->assertStringContainsString('js-cfdev-ajax-save', $output);
    }

    // =========================================================================
    // Text — base Field::outputHtml()
    // =========================================================================

    public function testTextOutputHtmlContainsInputTag(): void
    {
        $output = $this->makeText()->outputHtml('');
        $this->assertStringContainsString('<input', $output);
        $this->assertStringContainsString('type="text"', $output);
    }

    public function testTextOutputHtmlContainsNameAttribute(): void
    {
        $output = $this->makeText()->outputHtml('');
        $this->assertStringContainsString('name="cfdev[_my_text]"', $output);
    }

    public function testTextOutputHtmlContainsIdAttribute(): void
    {
        $output = $this->makeText()->outputHtml('');
        $this->assertStringContainsString('id="_my_text"', $output);
    }

    public function testTextOutputHtmlContainsValue(): void
    {
        $output = $this->makeText()->outputHtml('hello world');
        $this->assertStringContainsString('hello world', $output);
    }

    public function testTextOutputHtmlUsesDefaultValueWhenValueIsEmpty(): void
    {
        $output = $this->makeText(['default_value' => 'default'])->outputHtml('');
        $this->assertStringContainsString('default', $output);
    }

    // =========================================================================
    // Textarea
    // =========================================================================

    public function testTextareaOutputHtmlContainsTextareaTag(): void
    {
        $output = $this->makeTextarea()->outputHtml('');
        $this->assertStringContainsString('<textarea', $output);
        $this->assertStringContainsString('</textarea>', $output);
    }

    public function testTextareaOutputHtmlContainsValue(): void
    {
        $output = $this->makeTextarea()->outputHtml('some content');
        $this->assertStringContainsString('some content', $output);
    }

    public function testTextareaOutputHtmlContainsNameAttribute(): void
    {
        $output = $this->makeTextarea()->outputHtml('');
        $this->assertStringContainsString('name="cfdev[_ta]"', $output);
    }

    // =========================================================================
    // Number
    // =========================================================================

    public function testNumberOutputHtmlContainsTypeNumber(): void
    {
        $output = $this->makeNumber()->outputHtml('');
        $this->assertStringContainsString('type="number"', $output);
    }

    public function testNumberOutputHtmlContainsValue(): void
    {
        $output = $this->makeNumber()->outputHtml('42');
        $this->assertStringContainsString('value="42"', $output);
    }

    public function testNumberOutputHtmlContainsMinWhenArgSet(): void
    {
        $output = $this->makeNumber(['args' => ['min' => 0]])->outputHtml('');
        $this->assertStringContainsString('min="0"', $output);
    }

    public function testNumberOutputHtmlContainsMaxWhenArgSet(): void
    {
        $output = $this->makeNumber(['args' => ['max' => 100]])->outputHtml('');
        $this->assertStringContainsString('max="100"', $output);
    }

    public function testNumberOutputHtmlContainsStepWhenArgSet(): void
    {
        $output = $this->makeNumber(['args' => ['step' => 0.5]])->outputHtml('');
        $this->assertStringContainsString('step="0.5"', $output);
    }

    // =========================================================================
    // Hidden
    // =========================================================================

    public function testHiddenOutputHtmlContainsTypeHidden(): void
    {
        $output = $this->makeHidden()->outputHtml('');
        $this->assertStringContainsString('type="hidden"', $output);
    }

    public function testHiddenOutputHtmlContainsValue(): void
    {
        $output = $this->makeHidden()->outputHtml('secret');
        $this->assertStringContainsString('value="secret"', $output);
    }

    // =========================================================================
    // Heading
    // =========================================================================

    public function testHeadingOutputHtmlContainsH3(): void
    {
        $output = $this->makeHeading()->outputHtml('');
        $this->assertStringContainsString('<h3', $output);
        $this->assertStringContainsString('cfdev-heading', $output);
    }

    public function testHeadingOutputHtmlContainsLabel(): void
    {
        $output = $this->makeHeading()->outputHtml('');
        $this->assertStringContainsString('Section Title', $output);
    }

    public function testHeadingOutputHtmlContainsDescriptionParagraphWhenSet(): void
    {
        $output = $this->makeHeading(['description' => 'Detailed description'])->outputHtml('');
        $this->assertStringContainsString('<p', $output);
        $this->assertStringContainsString('Detailed description', $output);
    }

    // =========================================================================
    // Toggle
    // =========================================================================

    public function testToggleOutputHtmlContainsToggleWrap(): void
    {
        $output = $this->makeToggle()->outputHtml('');
        $this->assertStringContainsString('cfdev-toggle-wrap', $output);
    }

    public function testToggleOutputHtmlCheckedWhenValueIsOn(): void
    {
        $output = $this->makeToggle()->outputHtml('on');
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testToggleOutputHtmlNotCheckedWhenValueIsEmpty(): void
    {
        $output = $this->makeToggle()->outputHtml('');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    public function testToggleOutputHtmlContainsTypeCheckbox(): void
    {
        $output = $this->makeToggle()->outputHtml('');
        $this->assertStringContainsString('type="checkbox"', $output);
        $this->assertStringContainsString('value="on"', $output);
    }

    // =========================================================================
    // Checkbox (uses WP checked())
    // =========================================================================

    public function testCheckboxOutputHtmlContainsTypeCheckbox(): void
    {
        $output = $this->makeCheckbox()->outputHtml('');
        $this->assertStringContainsString('type="checkbox"', $output);
    }

    public function testCheckboxOutputHtmlContainsCfdevCheckboxWrap(): void
    {
        $output = $this->makeCheckbox()->outputHtml('');
        $this->assertStringContainsString('cfdev-checkbox-wrap', $output);
    }

    public function testCheckboxOutputHtmlCheckedWhenValueIsOn(): void
    {
        $output = $this->makeCheckbox()->outputHtml('on');
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testCheckboxOutputHtmlNotCheckedWhenValueIsEmpty(): void
    {
        $output = $this->makeCheckbox()->outputHtml('');
        $this->assertStringNotContainsString('checked="checked"', $output);
    }

    // =========================================================================
    // Yesno (uses WP checked())
    // =========================================================================

    public function testYesnoOutputHtmlContainsBothYesAndNoRadios(): void
    {
        $output = $this->makeYesno()->outputHtml('');
        $this->assertStringContainsString('value="yes"', $output);
        $this->assertStringContainsString('value="no"', $output);
    }

    public function testYesnoOutputHtmlContainsCfdevCheckboxWrap(): void
    {
        $output = $this->makeYesno()->outputHtml('');
        $this->assertStringContainsString('cfdev-checkbox-wrap', $output);
    }

    public function testYesnoOutputHtmlContainsTypeRadio(): void
    {
        $output = $this->makeYesno()->outputHtml('');
        $this->assertStringContainsString('type="radio"', $output);
    }

    // =========================================================================
    // Select (uses WP selected())
    // =========================================================================

    public function testSelectOutputHtmlContainsSelectTag(): void
    {
        $output = $this->makeSelect()->outputHtml('');
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('</select>', $output);
    }

    public function testSelectOutputHtmlContainsOneOptionPerEntry(): void
    {
        $output = $this->makeSelect()->outputHtml('');
        $this->assertSame(2, substr_count($output, '<option'));
    }

    public function testSelectOutputHtmlMarksSelectedOption(): void
    {
        $output = $this->makeSelect()->outputHtml('b');
        $this->assertStringContainsString('selected="selected"', $output);
    }

    public function testSelectOutputHtmlShowsNoneOptionWhenArgShowOptionNoneSet(): void
    {
        $field  = $this->makeSelect(['args' => ['show_option_none' => '— choose —']]);
        $output = $field->outputHtml('');
        $this->assertStringContainsString('— choose —', $output);
        $this->assertSame(3, substr_count($output, '<option'));
    }

    // =========================================================================
    // Checkboxes
    // =========================================================================

    public function testCheckboxesOutputHtmlContainsCheckboxesWrap(): void
    {
        $output = $this->makeCheckboxes()->outputHtml([]);
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
    }

    public function testCheckboxesOutputHtmlRendersOneCheckboxPerOption(): void
    {
        $output = $this->makeCheckboxes()->outputHtml([]);
        $this->assertSame(2, substr_count($output, 'type="checkbox"'));
    }

    public function testCheckboxesOutputHtmlChecksMatchingValues(): void
    {
        $output = $this->makeCheckboxes()->outputHtml(['x']);
        $this->assertStringContainsString('checked="checked"', $output);
    }

    public function testCheckboxesOutputHtmlEmptyWhenNoOptions(): void
    {
        $field  = $this->makeCheckboxes(['options' => []]);
        $output = $field->outputHtml([]);
        $this->assertStringNotContainsString('type="checkbox"', $output);
    }

    // =========================================================================
    // Radios
    // =========================================================================

    public function testRadiosOutputHtmlContainsOneRadioPerOption(): void
    {
        $output = $this->makeRadios()->outputHtml(['yes']);
        $this->assertSame(2, substr_count($output, 'type="radio"'));
    }

    public function testRadiosOutputHtmlContainsCfdevCheckboxesWrap(): void
    {
        $output = $this->makeRadios()->outputHtml(['yes']);
        $this->assertStringContainsString('cfdev-checkboxes-wrap', $output);
    }

    public function testRadiosOutputHtmlEmptyWhenNoOptions(): void
    {
        $field  = $this->makeRadios(['options' => []]);
        $output = $field->outputHtml('');
        $this->assertStringNotContainsString('type="radio"', $output);
    }

    // =========================================================================
    // Link
    // =========================================================================

    public function testLinkOutputHtmlContainsCfdevLinkWrap(): void
    {
        $output = $this->makeLink()->outputHtml('');
        $this->assertStringContainsString('cfdev-link-wrap', $output);
    }

    public function testLinkOutputHtmlContainsUrlInput(): void
    {
        $output = $this->makeLink()->outputHtml('');
        $this->assertStringContainsString('type="url"', $output);
        $this->assertStringContainsString('[url]', $output);
    }

    public function testLinkOutputHtmlContainsTextInput(): void
    {
        $output = $this->makeLink()->outputHtml('');
        $this->assertStringContainsString('[text]', $output);
    }

    public function testLinkOutputHtmlContainsTargetSelect(): void
    {
        $output = $this->makeLink()->outputHtml('');
        $this->assertStringContainsString('[target]', $output);
        $this->assertStringContainsString('_blank', $output);
        $this->assertStringContainsString('_self', $output);
    }

    public function testLinkOutputHtmlPopulatesUrlFromArrayValue(): void
    {
        $output = $this->makeLink()->outputHtml(['url' => 'https://example.com', 'text' => '', 'target' => '_self']);
        $this->assertStringContainsString('https://example.com', $output);
    }

    public function testLinkOutputHtmlBlankTargetMarkedSelected(): void
    {
        $output = $this->makeLink()->outputHtml(['url' => '', 'text' => '', 'target' => '_blank']);
        $this->assertStringContainsString('value="_blank" selected', $output);
    }

    // =========================================================================
    // Image
    // =========================================================================

    public function testImageOutputHtmlContainsHiddenInput(): void
    {
        $output = $this->makeImage()->outputHtml('');
        $this->assertStringContainsString('type="hidden"', $output);
    }

    public function testImageOutputHtmlContainsUploadButton(): void
    {
        $output = $this->makeImage()->outputHtml('');
        $this->assertStringContainsString('js-cfdev-upload', $output);
        $this->assertStringContainsString('data-cfdev-media-type="image"', $output);
    }

    public function testImageOutputHtmlContainsPreviewSpan(): void
    {
        $output = $this->makeImage()->outputHtml('');
        $this->assertStringContainsString('cfdev-preview', $output);
    }

    public function testImageOutputHtmlWithValueContainsRemoveButton(): void
    {
        Functions\when('wp_get_attachment_image_src')->justReturn(['https://example.com/img.jpg', 800, 600, true]);
        $output = $this->makeImage()->outputHtml('42');
        $this->assertStringContainsString('js-cfdev-remove-media', $output);
        $this->assertStringContainsString('https://example.com/img.jpg', $output);
    }

    public function testImageOutputHtmlNoRemoveButtonWhenValueEmpty(): void
    {
        $output = $this->makeImage()->outputHtml('');
        $this->assertStringNotContainsString('js-cfdev-remove-media', $output);
    }

    // =========================================================================
    // File
    // =========================================================================

    public function testFileOutputHtmlContainsHiddenInput(): void
    {
        $output = $this->makeFile()->outputHtml('');
        $this->assertStringContainsString('type="hidden"', $output);
    }

    public function testFileOutputHtmlContainsUploadButton(): void
    {
        $output = $this->makeFile()->outputHtml('');
        $this->assertStringContainsString('js-cfdev-upload', $output);
        $this->assertStringContainsString('data-cfdev-media-type="file"', $output);
    }

    public function testFileOutputHtmlNoRemoveLinkWhenValueIsEmpty(): void
    {
        $output = $this->makeFile()->outputHtml('');
        $this->assertStringNotContainsString('js-cfdev-remove-media', $output);
    }

    public function testFileOutputHtmlContainsPreviewSpan(): void
    {
        $output = $this->makeFile()->outputHtml('');
        $this->assertStringContainsString('cfdev-preview', $output);
    }

    public function testFileOutputHtmlWithAttachmentIdContainsRemoveButton(): void
    {
        Functions\when('wp_get_attachment_url')->justReturn('https://example.com/doc.pdf');
        Functions\when('get_post')->justReturn(null);
        $output = $this->makeFile()->outputHtml('99');
        $this->assertStringContainsString('js-cfdev-remove-media', $output);
    }

    // =========================================================================
    // Wysiwyg
    // =========================================================================

    public function testWysiwygOutputHtmlCallsWpEditorWithCorrectId(): void
    {
        $field  = $this->makeWysiwyg();
        $output = $field->outputHtml('some content');
        $this->assertStringContainsString('wp-editor', $output);
        $this->assertStringContainsString($field->id, $output);
    }

    public function testWysiwygOutputHtmlReturnsMockedEditorMarkup(): void
    {
        $output = $this->makeWysiwyg()->outputHtml('');
        $this->assertStringContainsString('wp-editor', $output);
    }

    public function testWysiwygOutputHtmlPassesContentToEditor(): void
    {
        // wp_editor is mocked to embed content in a div for testability
        Functions\when('wp_editor')->alias(
            fn(string $content): string => '<div id="wp-ed">' . htmlspecialchars($content, ENT_QUOTES) . '</div>'
        );
        $field  = new Wysiwyg(['type' => 'wysiwyg', 'id' => '_wys', 'name' => 'wys', 'label' => 'Wys'], 'box');
        $output = $field->outputHtml('Hello editor');
        $this->assertStringContainsString('Hello editor', $output);
    }
}
