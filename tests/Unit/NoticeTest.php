<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Weblitzer\CFDev\Notice;
use Brain\Monkey\Functions;

class NoticeTest extends CFDevTestCase
{
    private function render(Notice $notice): string
    {
        ob_start();
        $notice->render();
        return ob_get_clean() ?: '';
    }

    // -------------------------------------------------------------------------
    // render() — string message
    // -------------------------------------------------------------------------

    public function testStringMessageRendersInParagraph(): void
    {
        $output = $this->render(new Notice('Something went wrong', 'error'));

        $this->assertStringContainsString('Something went wrong', $output);
        $this->assertStringContainsString('<p>', $output);
        $this->assertStringNotContainsString('<ul>', $output);
    }

    public function testStringMessageEscapesHtml(): void
    {
        Functions\when('esc_html')->alias('htmlspecialchars');

        $output = $this->render(new Notice('<script>alert(1)</script>', 'info'));

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    // -------------------------------------------------------------------------
    // render() — array message (list)
    // -------------------------------------------------------------------------

    public function testArrayMessageRendersAsList(): void
    {
        Functions\when('wp_kses_post')->returnArg();

        $output = $this->render(new Notice(['Header', 'Item one', 'Item two'], 'error'));

        $this->assertStringContainsString('<ul', $output);
        $this->assertStringContainsString('<li>', $output);
        $this->assertStringContainsString('Header', $output);
        $this->assertStringContainsString('Item one', $output);
        $this->assertStringContainsString('Item two', $output);
    }

    public function testArrayMessageDoesNotRenderParagraph(): void
    {
        Functions\when('wp_kses_post')->returnArg();

        $output = $this->render(new Notice(['Header', 'Item'], 'error'));

        $this->assertStringNotContainsString('<p>', $output);
    }

    public function testArrayMessageRendersHtmlViaKses(): void
    {
        Functions\when('wp_kses_post')->alias(fn($v) => $v);

        $output = $this->render(new Notice(
            ['<strong>Titre</strong> <abbr title="_titre">ⓘ</abbr> : Ce champ est requis.'],
            'error'
        ));

        $this->assertStringContainsString('<strong>Titre</strong>', $output);
        $this->assertStringContainsString('title="_titre"', $output);
    }

    public function testAnchorLinkIsPreservedInListItem(): void
    {
        Functions\when('wp_kses_post')->alias(fn($v) => $v);

        $item = '<a href="#_prix"><strong>Prix</strong></a> <abbr title="_prix" style="cursor:help">ⓘ</abbr> : Ce champ est requis.';
        $output = $this->render(new Notice(
            ['2 champs nécessitent votre attention', $item],
            'error',
            true
        ));

        $this->assertStringContainsString('href="#_prix"', $output);
        $this->assertStringContainsString('<strong>Prix</strong>', $output);
    }

    public function testBundleAnchorPointsToParent(): void
    {
        Functions\when('wp_kses_post')->alias(fn($v) => $v);

        // Bundle field key "details.0._titre" → anchor should be "#details"
        $item = '<a href="#details"><strong>Titre (ligne 1)</strong></a> <abbr title="details.0._titre" style="cursor:help">ⓘ</abbr> : Ce champ est requis.';
        $output = $this->render(new Notice(['1 champ nécessite votre attention', $item], 'error'));

        $this->assertStringContainsString('href="#details"', $output);
        $this->assertStringContainsString('title="details.0._titre"', $output);
        $this->assertStringNotContainsString('href="#details.0._titre"', $output);
    }

    public function testBundleAbbrIsAfterAnchorInSameListItem(): void
    {
        Functions\when('wp_kses_post')->alias(fn($v) => $v);

        // JS uses $link.siblings('abbr[title]') — <a> and <abbr> must be siblings in the same <li>
        $item   = '<a href="#details"><strong>Titre</strong></a> <abbr title="details.0._titre" style="cursor:help">ⓘ</abbr> : requis.';
        $output = $this->render(new Notice(['1 erreur', $item], 'error'));

        $aPos    = strpos($output, 'href="#details"');
        $abbrPos = strpos($output, 'title="details.0._titre"');
        $this->assertNotFalse($aPos);
        $this->assertNotFalse($abbrPos);
        $this->assertLessThan($abbrPos, $aPos, '<a> must appear before <abbr> within the same <li>');
    }

    public function testMultipleBundleRowsShareAnchorButDistinctAbbrTitles(): void
    {
        Functions\when('wp_kses_post')->alias(fn($v) => $v);

        // Two errors in the same bundle (different rows) → same href but unique abbr titles
        // JS parses "bundle.rowIndex.fieldId" from abbr title to find the exact input (#fieldId_rowIndex)
        $item1 = '<a href="#details"><strong>Titre (ligne 1)</strong></a> <abbr title="details.0._titre" style="cursor:help">ⓘ</abbr> : requis.';
        $item2 = '<a href="#details"><strong>Prix (ligne 3)</strong></a> <abbr title="details.2._prix" style="cursor:help">ⓘ</abbr> : requis.';
        $output = $this->render(new Notice(
            ['2 champs nécessitent votre attention', $item1, $item2],
            'error'
        ));

        $this->assertSame(2, substr_count($output, 'href="#details"'));
        $this->assertStringContainsString('title="details.0._titre"', $output);
        $this->assertStringContainsString('title="details.2._prix"', $output);
    }

    public function testArrayItemCountMatchesListItems(): void
    {
        Functions\when('wp_kses_post')->returnArg();

        $output = $this->render(new Notice(['A', 'B', 'C'], 'error'));

        $this->assertSame(3, substr_count($output, '<li>'));
    }

    // -------------------------------------------------------------------------
    // render() — CSS classes
    // -------------------------------------------------------------------------

    public function testNoticeTypeClassIsApplied(): void
    {
        $output = $this->render(new Notice('All good', 'success'));
        $this->assertStringContainsString('notice-success', $output);
    }

    public function testErrorTypeClassIsApplied(): void
    {
        $output = $this->render(new Notice('Oops', 'error'));
        $this->assertStringContainsString('notice-error', $output);
    }

    public function testDismissibleClassAddedWhenEnabled(): void
    {
        $output = $this->render(new Notice('Hey', 'warning', true));
        $this->assertStringContainsString('is-dismissible', $output);
    }

    public function testNoDismissibleClassByDefault(): void
    {
        $output = $this->render(new Notice('Hey', 'warning'));
        $this->assertStringNotContainsString('is-dismissible', $output);
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterAddsAdminNoticesHook(): void
    {
        \Brain\Monkey\Actions\expectAdded('admin_notices')->once();
        (new Notice('Test', 'info'))->register();
        $this->addToAssertionCount(1);
    }

    public function testConstructorDoesNotRegisterHookByItself(): void
    {
        \Brain\Monkey\Actions\expectAdded('admin_notices')->never();
        $notice = new Notice('Test', 'info');
        unset($notice);
        $this->addToAssertionCount(1);
    }
}
