<?php

namespace Weblitzer\CFDev\Tests\Unit\Meta;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class RegisterRestMetaTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
        Functions\when('get_option')->justReturn(true); // cfdev_rest_enabled = true
        Functions\when('register_meta')->justReturn(true);
    }

    // -------------------------------------------------------------------------
    // MetaBox — rest_api_init hooked for fields with rest:true
    // -------------------------------------------------------------------------

    public function testMetaBoxHooksRestApiInitWhenFieldHasRestTrue(): void
    {
        Actions\expectAdded('rest_api_init')->atLeast()->once();

        new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_title', 'name' => 'Title', 'rest' => true],
        ]);

        $this->addToAssertionCount(1);
    }

    public function testMetaBoxHooksRestApiInitEvenWhenNoRestFields(): void
    {
        // rest_api_init is still added; the callback simply does nothing
        Actions\expectAdded('rest_api_init')->atLeast()->once();

        new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_note', 'name' => 'Note'],
        ]);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // TermMeta
    // -------------------------------------------------------------------------

    public function testTermMetaHooksRestApiInit(): void
    {
        Actions\expectAdded('rest_api_init')->atLeast()->once();

        new TermMeta('genre', '', [
            ['type' => 'text', 'id' => '_color', 'name' => 'Color', 'rest' => true],
        ]);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // UserMeta
    // -------------------------------------------------------------------------

    public function testUserMetaHooksRestApiInit(): void
    {
        Actions\expectAdded('rest_api_init')->atLeast()->once();

        new UserMeta('user_section', 'User Section', [
            ['type' => 'text', 'id' => '_bio', 'name' => 'Bio', 'rest' => true],
        ]);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // register_meta() called inside the closure
    // -------------------------------------------------------------------------

    public function testRegisterMetaCalledForRestField(): void
    {
        $registered = [];
        Functions\when('register_meta')->alias(
            function (string $type, string $key, array $args) use (&$registered): bool {
                $registered[] = ['type' => $type, 'key' => $key, 'args' => $args];
                return true;
            }
        );

        $box = new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_subtitle', 'name' => 'Subtitle', 'rest' => true],
            ['type' => 'text', 'id' => '_note',     'name' => 'Note'],
        ]);

        $box->doRegisterRestMeta('post', 'post');

        $keys = array_column($registered, 'key');
        $this->assertContains('_subtitle', $keys);
        $this->assertNotContains('_note', $keys);
    }

    public function testRegisterMetaUsesCorrectObjectType(): void
    {
        $registered = [];
        Functions\when('register_meta')->alias(
            function (string $type, string $key, array $args) use (&$registered): bool {
                $registered[] = ['type' => $type, 'key' => $key];
                return true;
            }
        );

        $box = new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_pages', 'name' => 'Pages', 'rest' => true],
        ]);

        $box->doRegisterRestMeta('post', 'book');

        $this->assertSame('post', $registered[0]['type']);
    }

    public function testRegisterMetaSetsObjectSubtype(): void
    {
        $args_captured = [];
        Functions\when('register_meta')->alias(
            function (string $type, string $key, array $args) use (&$args_captured): bool {
                $args_captured = $args;
                return true;
            }
        );

        $box = new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_pages', 'name' => 'Pages', 'rest' => true],
        ]);

        $box->doRegisterRestMeta('post', 'book');

        $this->assertSame('book', $args_captured['object_subtype']);
        $this->assertTrue($args_captured['show_in_rest']);
        $this->assertTrue($args_captured['single']);
    }

    // -------------------------------------------------------------------------
    // Condition filters
    // -------------------------------------------------------------------------

    public function testAddRestConditionFilterHooksWhenOnlyForIdSet(): void
    {
        Functions\when('register_meta')->justReturn(true);
        \Brain\Monkey\Filters\expectAdded('rest_prepare_page')->once();

        $box = new MetaBox('box', 'Box', 'page', [
            ['type' => 'text', 'id' => '_title', 'name' => 'Title', 'rest' => true],
        ]);
        $box->onlyForId(42);

        $box->doRegisterRestMeta('post', 'page');
        $this->addToAssertionCount(1);
    }

    public function testAddRestConditionFilterHooksWhenOnlyForTemplateSet(): void
    {
        Functions\when('register_meta')->justReturn(true);
        \Brain\Monkey\Filters\expectAdded('rest_prepare_page')->once();

        $box = new MetaBox('box', 'Box', 'page', [
            ['type' => 'text', 'id' => '_title', 'name' => 'Title', 'rest' => true],
        ]);
        $box->onlyForTemplate('template-home.php');

        $box->doRegisterRestMeta('post', 'page');
        $this->addToAssertionCount(1);
    }

    public function testNoConditionFilterWhenNoConditionSet(): void
    {
        Functions\when('register_meta')->justReturn(true);
        \Brain\Monkey\Filters\expectAdded('rest_prepare_post')->never();

        $box = new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_title', 'name' => 'Title', 'rest' => true],
        ]);
        $box->doRegisterRestMeta('post', 'post');
        $this->addToAssertionCount(1);
    }

    public function testTermMetaConditionFilterHooksWhenOnlyIfParentSet(): void
    {
        Functions\when('register_meta')->justReturn(true);
        \Brain\Monkey\Filters\expectAdded('rest_prepare_category')->once();

        $term = new TermMeta('category', '', [
            ['type' => 'text', 'id' => '_color', 'name' => 'Color', 'rest' => true],
        ]);
        $term->onlyIfParent(5);

        $term->doRegisterRestMeta('term', 'category');
        $this->addToAssertionCount(1);
    }

    public function testRegisterMetaSkippedWhenRestDisabled(): void
    {
        $called = false;
        Functions\when('get_option')->justReturn(false); // REST disabled
        Functions\when('register_meta')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $box = new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => '_pub', 'name' => 'Pub', 'rest' => true],
        ]);

        $box->doRegisterRestMeta('post', 'post');

        $this->assertFalse($called);
    }
}
