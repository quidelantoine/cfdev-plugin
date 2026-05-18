<?php

namespace CFDev\Tests\Unit\Meta;

use CFDev\Meta\MetaBox;
use CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class MetaBoxTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    private function makeMetaBox(): MetaBox
    {
        return new MetaBox('my_mb', 'My Meta Box', 'post', []);
    }

    // -------------------------------------------------------------------------
    // savePost — early returns
    // -------------------------------------------------------------------------

    public function testSavePostReturnsEarlyWhenNonceIsMissing(): void
    {
        $_POST = [];
        $this->makeMetaBox()->savePost(1);
        // no wp_verify_nonce call expected — reaching here = early return worked
        $this->assertTrue(true);
    }

    public function testSavePostReturnsEarlyWhenNonceIsInvalid(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $_POST = ['cfdev_nonce' => 'bad_nonce'];
        $this->makeMetaBox()->savePost(1);
        $this->assertTrue(true);
    }

    public function testSavePostReturnsEarlyForWrongPostType(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_post_type')->justReturn('page');

        $_POST = ['cfdev_nonce' => 'valid_nonce'];
        $this->makeMetaBox()->savePost(1);
        $this->assertTrue(true);
    }

    public function testSavePostReturnsEarlyWhenUserCannotEdit(): void
    {
        $cap            = new \stdClass();
        $cap->edit_post = 'edit_posts';
        $postTypeObj    = new \stdClass();
        $postTypeObj->cap = $cap;

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn($postTypeObj);
        Functions\when('current_user_can')->justReturn(false);

        $_POST = ['cfdev_nonce' => 'valid_nonce'];
        $this->makeMetaBox()->savePost(1);
        $this->assertTrue(true);
    }

    public function testSavePostSkipsWhenCfdevDataIsEmpty(): void
    {
        $cap            = new \stdClass();
        $cap->edit_post = 'edit_posts';
        $postTypeObj    = new \stdClass();
        $postTypeObj->cap = $cap;

        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn($postTypeObj);
        Functions\when('current_user_can')->justReturn(true);

        // cfdev key absent: $values will be [] → !empty($values) is false → no save
        $_POST = ['cfdev_nonce' => 'valid_nonce'];
        $this->makeMetaBox()->savePost(1);
        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testConstructSetsId(): void
    {
        $mb = $this->makeMetaBox();
        $this->assertSame('my_mb', $mb->id);
    }

    public function testConstructSetsPostType(): void
    {
        $mb = $this->makeMetaBox();
        $this->assertContains('post', $mb->post_types);
    }

    public function testConstructSetsDefaultContext(): void
    {
        $mb = $this->makeMetaBox();
        $this->assertSame('normal', $mb->context);
    }

    public function testConstructSetsDefaultPriority(): void
    {
        $mb = $this->makeMetaBox();
        $this->assertSame('default', $mb->priority);
    }
}
