<?php

namespace Weblitzer\CFDev\Tests\Unit\Meta;

use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class MetaBoxTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('wp_doing_ajax')->justReturn(false);
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

    private function makeTextField(string $name): Text
    {
        // sanitize_title is not auto-stubbed — mock it before calling this helper
        return new Text(['type' => 'text', 'name' => $name, 'label' => ucfirst($name), 'underscore' => false], 'my_mb');
    }

    /** Stubs all functions required to build post_type / capability guard in savePost() */
    private function stubSavePostGuards(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn(
            (object) ['cap' => (object) ['edit_post' => 'edit_posts']]
        );
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_kses_post_deep')->returnArg();
        Functions\when('wp_json_encode')->alias('json_encode');
    }

    // -------------------------------------------------------------------------
    // savePost — early returns
    // -------------------------------------------------------------------------

    public function testSavePostReturnsEarlyWhenNonceIsMissing(): void
    {
        $_POST = [];
        $this->makeMetaBox()->savePost(1);
        // no wp_verify_nonce call expected — reaching here = early return worked
        $this->addToAssertionCount(1);
    }

    public function testSavePostReturnsEarlyWhenNonceIsInvalid(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $_POST = ['cfdev_nonce' => 'bad_nonce'];
        $this->makeMetaBox()->savePost(1);
        $this->addToAssertionCount(1);
    }

    public function testSavePostReturnsEarlyForWrongPostType(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_post_type')->justReturn('page');

        $_POST = ['cfdev_nonce' => 'valid_nonce'];
        $this->makeMetaBox()->savePost(1);
        $this->addToAssertionCount(1);
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
        $this->addToAssertionCount(1);
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
        $this->addToAssertionCount(1);
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

    // -------------------------------------------------------------------------
    // MetaBox::save() — flat field loop
    // -------------------------------------------------------------------------

    public function testSaveLoopIteratesAllFields(): void
    {
        $saved = [];
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s)));
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $mb = $this->makeMetaBox();

        $f1 = $this->makeTextField('title');
        $f1->meta_type = 'post';
        $mb->fields[$f1->id] = $f1;

        $f2 = $this->makeTextField('subtitle');
        $f2->meta_type = 'post';
        $mb->fields[$f2->id] = $f2;

        $mb->save(1, [$f1->id => 'Hello', $f2->id => 'World']);

        $this->assertArrayHasKey($f1->id, $saved);
        $this->assertArrayHasKey($f2->id, $saved);
        $this->assertSame('Hello', $saved[$f1->id]);
        $this->assertSame('World', $saved[$f2->id]);
    }

    public function testSaveLoopSkipsBundleFields(): void
    {
        $called = false;
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s)));
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_post_meta')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $mb = $this->makeMetaBox();
        $f  = $this->makeTextField('title');
        $f->meta_type = 'post';
        $f->in_bundle = true;
        $mb->fields[$f->id] = $f;

        $mb->save(1, [$f->id => 'Hello']);

        $this->assertFalse($called);
    }

    public function testSaveLoopUsesEmptyStringForMissingValue(): void
    {
        $stored = 'NOT_SET';
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s)));
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $val) use (&$stored): bool {
            $stored = $val;
            return true;
        });

        $mb = $this->makeMetaBox();
        $f  = $this->makeTextField('title');
        $f->meta_type = 'post';
        $mb->fields[$f->id] = $f;

        $mb->save(1, []); // field key absent → value defaults to ''

        $this->assertSame('', $stored);
    }

    // -------------------------------------------------------------------------
    // MetaBox::savePost() — happy path
    // -------------------------------------------------------------------------

    public function testSavePostHappyPathSavesFieldValue(): void
    {
        $stored = null;
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s)));
        $this->stubSavePostGuards();
        Functions\when('update_post_meta')->alias(function (int $id, string $key, mixed $val) use (&$stored): bool {
            $stored = $val;
            return true;
        });

        $mb = $this->makeMetaBox();
        $f  = $this->makeTextField('title');
        $f->meta_type = 'post';
        $mb->fields[$f->id] = $f;
        $mb->data = $mb->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$f->id => 'Hello World']];

        $mb->savePost(1);

        $this->assertSame('Hello World', $stored);
    }

    // -------------------------------------------------------------------------
    // MetaBox::savePost() — validation errors
    // -------------------------------------------------------------------------

    public function testSavePostPushesValidationErrorsWhenFieldFails(): void
    {
        $pushed = false;
        Functions\when('sanitize_title')->alias(fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s)));
        $this->stubSavePostGuards();
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_transient')->justReturn([]);
        Functions\when('set_transient')->alias(function () use (&$pushed): bool {
            $pushed = true;
            return true;
        });
        Functions\when('update_post_meta')->justReturn(true);

        $mb = $this->makeMetaBox();
        $f  = new Text(
            ['type' => 'text', 'name' => 'title', 'label' => 'Title', 'underscore' => false, 'required' => true],
            'my_mb'
        );
        $f->meta_type = 'post';
        $mb->fields[$f->id] = $f;
        $mb->data = $mb->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$f->id => '']]; // empty → Required fails

        $mb->savePost(1);

        $this->assertTrue($pushed);
    }

    // -------------------------------------------------------------------------
    // addMetaBox() + matchesConditions()
    // -------------------------------------------------------------------------

    public function testAddMetaBoxRegistersMetaBoxWhenNoConditions(): void
    {
        $mb = $this->makeMetaBox();
        Functions\expect('add_meta_box')->once();
        $mb->addMetaBox('post');
        $this->addToAssertionCount(1);
    }

    public function testAddMetaBoxSkipsWhenPostIdConditionNotMet(): void
    {
        $mb       = $this->makeMetaBox();
        $mb->onlyForId(42);
        $post     = new \WP_Post();
        $post->ID = 99;

        Functions\expect('add_meta_box')->never();
        $mb->addMetaBox('post', $post);
        $this->addToAssertionCount(1);
    }

    public function testAddMetaBoxRegistersWhenPostIdConditionMatches(): void
    {
        $mb       = $this->makeMetaBox();
        $mb->onlyForId(42);
        $post     = new \WP_Post();
        $post->ID = 42;

        Functions\expect('add_meta_box')->once();
        $mb->addMetaBox('post', $post);
        $this->addToAssertionCount(1);
    }

    public function testAddMetaBoxSkipsWhenTemplateConditionNotMet(): void
    {
        $mb       = $this->makeMetaBox();
        $mb->onlyForTemplate('template-home.php');
        $post     = new \WP_Post();
        $post->ID = 1;
        Functions\when('get_page_template_slug')->justReturn('template-other.php');

        Functions\expect('add_meta_box')->never();
        $mb->addMetaBox('post', $post);
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // onlyWhen()
    // -------------------------------------------------------------------------

    public function testOnlyWhenReturnsSelf(): void
    {
        $mb = $this->makeMetaBox();
        $this->assertSame($mb, $mb->onlyWhen(fn(\WP_Post $p) => true));
    }

    public function testOnlyWhenStoresLabel(): void
    {
        $mb = $this->makeMetaBox();
        $mb->onlyWhen(fn(\WP_Post $p) => true, 'Admins only');
        $this->assertSame(['Admins only'], $mb->only_when_labels);
    }

    public function testOnlyWhenEmptyLabelStoredAsEmptyString(): void
    {
        $mb = $this->makeMetaBox();
        $mb->onlyWhen(fn(\WP_Post $p) => true);
        $this->assertSame([''], $mb->only_when_labels);
    }

    public function testAddMetaBoxSkipsWhenOnlyWhenReturnsFalse(): void
    {
        $mb       = $this->makeMetaBox();
        $mb->onlyWhen(fn(\WP_Post $p) => false);
        $post     = new \WP_Post();
        $post->ID = 1;

        Functions\expect('add_meta_box')->never();
        $mb->addMetaBox('post', $post);
        $this->addToAssertionCount(1);
    }

    public function testAddMetaBoxRegistersWhenOnlyWhenReturnsTrue(): void
    {
        $mb       = $this->makeMetaBox();
        $mb->onlyWhen(fn(\WP_Post $p) => true);
        $post     = new \WP_Post();
        $post->ID = 1;

        Functions\expect('add_meta_box')->once();
        $mb->addMetaBox('post', $post);
        $this->addToAssertionCount(1);
    }

    public function testOnlyWhenMultipleCallablesAreAnded(): void
    {
        $mb       = $this->makeMetaBox();
        $mb->onlyWhen(fn(\WP_Post $p) => true);
        $mb->onlyWhen(fn(\WP_Post $p) => false);
        $post     = new \WP_Post();
        $post->ID = 1;

        Functions\expect('add_meta_box')->never();
        $mb->addMetaBox('post', $post);
        $this->addToAssertionCount(1);
    }

    public function testSavePostSkipsWhenOnlyWhenReturnsFalse(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post')->alias(function (int $id): \WP_Post {
            $p     = new \WP_Post();
            $p->ID = $id;
            return $p;
        });

        $stored = 'NOT_SET';
        Functions\when('update_post_meta')->alias(function () use (&$stored): bool {
            $stored = 'SAVED';
            return true;
        });

        $mb = $this->makeMetaBox();
        $mb->onlyWhen(fn(\WP_Post $p) => false);

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => ['title' => 'hello']];
        $mb->savePost(1);

        $this->assertSame('NOT_SET', $stored);
    }

    // -------------------------------------------------------------------------
    // resolveObjectId()
    // -------------------------------------------------------------------------

    public function testResolveObjectIdReadsFromGetParam(): void
    {
        Functions\when('absint')->alias('intval');
        $_GET['post'] = '42';

        $mb     = $this->makeMetaBox();
        $method = new \ReflectionMethod(MetaBox::class, 'resolveObjectId');
        $method->setAccessible(true);
        $result = $method->invoke($mb);

        unset($_GET['post']);
        $this->assertSame(42, $result);
    }

    public function testResolveObjectIdFallsBackToGetTheId(): void
    {
        Functions\when('absint')->alias('intval');
        Functions\when('get_the_ID')->justReturn(7);
        unset($_GET['post']);

        $mb     = $this->makeMetaBox();
        $method = new \ReflectionMethod(MetaBox::class, 'resolveObjectId');
        $method->setAccessible(true);

        $this->assertSame(7, $method->invoke($mb));
    }
}
