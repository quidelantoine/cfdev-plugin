<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Fields\Tab;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Fields\Hidden;
use Weblitzer\CFDev\Fields\Heading;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

/**
 * Tests Tab::output() and its private cascade:
 *   renderTable() → resolveValue() → renderRow() → renderFieldOutput()
 *
 * Tab is a layout container rendered inside a meta box.
 * output() echoes HTML, so all assertions use ob_start / ob_get_clean.
 */
class TabRenderTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'))
        );
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_transient')->justReturn(false);
        Functions\when('__')->returnArg(1);
    }

    private function makeTab(string $title = 'My Tab'): Tab
    {
        return new Tab($title);
    }

    /** @param array<string, mixed> $overrides */
    private function makeTextField(string $id, array $overrides = []): Text
    {
        return new Text(array_merge(
            ['type' => 'text', 'id' => $id, 'name' => $id, 'label' => ucfirst($id)],
            $overrides
        ), 'box');
    }

    private function makePost(int $id = 1): \WP_Post
    {
        $post     = new \WP_Post();
        $post->ID = $id;
        return $post;
    }

    // -------------------------------------------------------------------------
    // output() — tabs layout (covers renderTable, resolveValue, renderRow, renderFieldOutput)
    // -------------------------------------------------------------------------

    public function testOutputTabsLayoutRendersFieldTable(): void
    {
        $tab         = $this->makeTab('Info');
        $tab->fields = ['_title' => $this->makeTextField('_title')];

        ob_start();
        $tab->output($this->makePost(), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('cfdev-table', $output);
        $this->assertStringContainsString('cfdev-th', $output);
        $this->assertStringContainsString('cfdev-td', $output);
        $this->assertStringContainsString('<input', $output); // renderFieldOutput → Text::outputHtml
    }

    public function testOutputAccordionLayoutRendersH3Header(): void
    {
        $tab         = $this->makeTab('Pricing');
        $tab->fields = ['_price' => $this->makeTextField('_price')];

        ob_start();
        $tab->output($this->makePost(), 'accordion');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<h3>', $output);
        $this->assertStringContainsString('Pricing', $output);
    }

    public function testOutputRendersHeadingFieldInSpanningRow(): void
    {
        $heading         = new Heading(['type' => 'heading', 'id' => 'hdg', 'name' => 'hdg', 'label' => 'Section'], 'box');
        $tab             = $this->makeTab('Tab');
        $tab->fields     = ['hdg' => $heading];

        ob_start();
        $tab->output($this->makePost(), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('cfdev-heading-row', $output);
        $this->assertStringContainsString('Section', $output);
    }

    public function testOutputRendersHiddenFieldWithoutTrWrapper(): void
    {
        Functions\when('apply_filters')->returnArg(2);
        $hidden      = new Hidden(['type' => 'hidden', 'id' => '_h', 'name' => '_h', 'label' => 'H'], 'box');
        $tab         = $this->makeTab('Tab');
        $tab->fields = ['_h' => $hidden];

        ob_start();
        $tab->output($this->makePost(), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('type="hidden"', $output);
        $this->assertStringNotContainsString('cfdev-th', $output); // no <th> for hidden fields
    }

    // -------------------------------------------------------------------------
    // resolveValue() — all meta_type branches
    // -------------------------------------------------------------------------

    public function testResolveValueUsesGetUserMetaForUserMetaType(): void
    {
        Functions\when('get_user_meta')->justReturn('user-val');

        $tab            = $this->makeTab('Tab');
        $tab->meta_type = 'user';
        $tab->fields    = ['_bio' => $this->makeTextField('_bio')];

        ob_start();
        $tab->output($this->makePost(3), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('user-val', $output);
    }

    public function testResolveValueUsesGetTermMetaForTermMetaType(): void
    {
        Functions\when('get_term_meta')->justReturn('term-val');

        $tab            = $this->makeTab('Tab');
        $tab->meta_type = 'term';
        $tab->fields    = ['_color' => $this->makeTextField('_color')];

        ob_start();
        $tab->output($this->makePost(5), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('term-val', $output);
    }

    public function testResolveValueUsesGetOptionForOptionMetaType(): void
    {
        Functions\when('get_option')->justReturn('opt-val');

        $tab            = $this->makeTab('Tab');
        $tab->meta_type = 'option';
        $tab->fields    = ['_name' => $this->makeTextField('_name')];

        ob_start();
        $tab->output($this->makePost(), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('opt-val', $output);
    }

    // -------------------------------------------------------------------------
    // renderFieldOutput() — repeatable branch
    // -------------------------------------------------------------------------

    public function testOutputRendersRepeatableWrapperForRepeatableField(): void
    {
        $field            = $this->makeTextField('_items', ['repeatable' => true]);
        $tab              = $this->makeTab('Tab');
        $tab->fields      = ['_items' => $field];

        ob_start();
        $tab->output($this->makePost(), 'tabs');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('js-cfdev-sortable', $output);
        $this->assertStringContainsString('js-cfdev-add-sortable', $output);
    }
}
