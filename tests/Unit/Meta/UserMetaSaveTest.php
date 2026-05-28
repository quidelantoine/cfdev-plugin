<?php

namespace Weblitzer\CFDev\Tests\Unit\Meta;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Fields\Text;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class UserMetaSaveTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sanitize_title')->alias(function (string $s): string {
            return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $s), '-'));
        });
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    private function makeUserMeta(): UserMeta
    {
        return new UserMeta('profile', 'Profile', []);
    }

    private function makeTextField(string $name): Text
    {
        return new Text(['type' => 'text', 'name' => $name, 'label' => ucfirst($name), 'underscore' => false], 'profile');
    }

    // -------------------------------------------------------------------------
    // saveUser() — early returns
    // -------------------------------------------------------------------------

    public function testSaveUserReturnsEarlyWhenNonceIsMissing(): void
    {
        $_POST = [];
        $this->makeUserMeta()->saveUser(1);
        $this->addToAssertionCount(1);
    }

    public function testSaveUserReturnsEarlyOnInvalidNonce(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(false);

        $_POST = ['cfdev_nonce' => 'bad_nonce'];
        $this->makeUserMeta()->saveUser(1);
        $this->addToAssertionCount(1);
    }

    public function testSaveUserSkipsWhenCfdevDataIsEmpty(): void
    {
        Functions\when('wp_verify_nonce')->justReturn(1);

        $_POST = ['cfdev_nonce' => 'valid'];
        $this->makeUserMeta()->saveUser(1);
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // UserMeta::save() — flat field loop
    // -------------------------------------------------------------------------

    public function testSaveLoopIteratesAllFields(): void
    {
        $saved = [];
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_user_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $um = $this->makeUserMeta();

        $f1 = $this->makeTextField('firstname');
        $f1->meta_type = 'user';
        $um->fields[$f1->id] = $f1;

        $f2 = $this->makeTextField('lastname');
        $f2->meta_type = 'user';
        $um->fields[$f2->id] = $f2;

        $um->save(7, [$f1->id => 'Alice', $f2->id => 'Smith']);

        $this->assertArrayHasKey($f1->id, $saved);
        $this->assertArrayHasKey($f2->id, $saved);
        $this->assertSame('Alice', $saved[$f1->id]);
        $this->assertSame('Smith', $saved[$f2->id]);
    }

    public function testSaveLoopSkipsBundleFields(): void
    {
        $called = false;
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_user_meta')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $um = $this->makeUserMeta();
        $f  = $this->makeTextField('bio');
        $f->meta_type = 'user';
        $f->in_bundle = true;
        $um->fields[$f->id] = $f;

        $um->save(7, [$f->id => 'Hello']);

        $this->assertFalse($called);
    }

    public function testSaveLoopUsesEmptyStringForMissingValue(): void
    {
        $stored = 'NOT_SET';
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_user_meta')->alias(function (int $id, string $key, mixed $val) use (&$stored): bool {
            $stored = $val;
            return true;
        });

        $um = $this->makeUserMeta();
        $f  = $this->makeTextField('bio');
        $f->meta_type = 'user';
        $um->fields[$f->id] = $f;

        $um->save(7, []); // field absent → ''

        $this->assertSame('', $stored);
    }

    // -------------------------------------------------------------------------
    // saveUser() — happy path (flat dispatch → UserMeta::save())
    // -------------------------------------------------------------------------

    public function testSaveUserHappyPathSavesFieldValue(): void
    {
        $stored = null;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_user_meta')->alias(function (int $id, string $key, mixed $val) use (&$stored): bool {
            $stored = $val;
            return true;
        });

        $um = $this->makeUserMeta();
        $f  = $this->makeTextField('bio');
        $f->meta_type = 'user';
        $um->fields[$f->id] = $f;
        $um->data = $um->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$f->id => 'Developer']];

        $um->saveUser(7);

        $this->assertSame('Developer', $stored);
    }

    // -------------------------------------------------------------------------
    // saveUser() — validation
    // -------------------------------------------------------------------------

    public function testSaveUserPushesValidationErrorsWhenFieldFails(): void
    {
        $pushed = false;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_transient')->justReturn([]);
        Functions\when('set_transient')->alias(function () use (&$pushed): bool {
            $pushed = true;
            return true;
        });
        Functions\when('update_user_meta')->justReturn(true);

        $um = $this->makeUserMeta();
        $f  = new Text(
            ['type' => 'text', 'name' => 'bio', 'label' => 'Bio', 'underscore' => false, 'required' => true],
            'profile'
        );
        $f->meta_type = 'user';
        $um->fields[$f->id] = $f;
        $um->data = $um->fields;

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$f->id => '']]; // empty → Required fails

        $um->saveUser(7);

        $this->assertTrue($pushed);
    }

    // -------------------------------------------------------------------------
    // saveUser() — Bundle dispatch
    // -------------------------------------------------------------------------

    public function testSaveUserBundlePathCallsBundleSave(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_user_meta')->justReturn(true);
        Functions\when('update_user_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $um = new UserMeta('profile', 'Profile', ['bundle', 'details', [
            ['type' => 'text', 'name' => 'bio', 'label' => 'Bio'],
        ]]);
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $um->data;
        $field  = array_values($bundle->fields)[0];

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$bundle->id => [[$field->id => 'Developer']]],
        ];

        $um->saveUser(9);

        $this->assertArrayHasKey($bundle->id, $saved);
    }

    public function testSaveUserBundleMissingKeyDoesNotCallSave(): void
    {
        $called = false;
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_user_meta')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $um = new UserMeta('profile', 'Profile', ['bundle', 'details', [
            ['type' => 'text', 'name' => 'bio', 'label' => 'Bio'],
        ]]);

        $_POST = ['cfdev_nonce' => 'valid', 'cfdev' => [$um->id => 'not-the-bundle']]; // bundle key absent

        $um->saveUser(9);

        $this->assertFalse($called);
    }

    // -------------------------------------------------------------------------
    // saveUser() — Tabs flat dispatch
    // -------------------------------------------------------------------------

    public function testSaveUserTabsFlatPathSavesEachTabField(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('update_user_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $um = new UserMeta('profile', 'Profile', ['tabs', [
            'General' => [['type' => 'text', 'name' => 'bio', 'label' => 'Bio']],
        ]]);
        $field = array_values($um->fields)[0];

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$field->id => 'Developer'],
        ];

        $um->saveUser(9);

        $this->assertArrayHasKey($field->id, $saved);
        $this->assertSame('Developer', $saved[$field->id]);
    }

    // -------------------------------------------------------------------------
    // saveUser() — Tabs + Bundle dispatch
    // -------------------------------------------------------------------------

    public function testSaveUserTabsBundlePathCallsBundleSave(): void
    {
        $saved = [];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('delete_user_meta')->justReturn(true);
        Functions\when('update_user_meta')->alias(function (int $id, string $key, mixed $val) use (&$saved): bool {
            $saved[$key] = $val;
            return true;
        });

        $um = new UserMeta('profile', 'Profile', ['tabs', [
            'Links' => [['bundle', 'links', [['type' => 'text', 'name' => 'url', 'label' => 'URL']]]],
        ]]);

        /** @var \Weblitzer\CFDev\Fields\Tabs $tabs */
        $tabs   = $um->data;
        $tab    = array_values((array) $tabs->tabs)[0];
        /** @var \Weblitzer\CFDev\Fields\Bundle $bundle */
        $bundle = $tab->fields;
        $field  = array_values($bundle->fields)[0];

        $_POST = [
            'cfdev_nonce' => 'valid',
            'cfdev'       => [$bundle->id => [[$field->id => 'https://example.com']]],
        ];

        $um->saveUser(9);

        $this->assertArrayHasKey($bundle->id, $saved);
    }
}
