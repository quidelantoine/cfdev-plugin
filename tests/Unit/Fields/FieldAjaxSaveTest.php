<?php

namespace Weblitzer\CFDev\Tests\Unit\Fields;

use Brain\Monkey\Functions;
use RuntimeException;
use Weblitzer\CFDev\Field;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class FieldAjaxSaveTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_key')->returnArg(1);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed> */
    private function validPost(array $overrides = []): array
    {
        return array_merge([
            'nonce'     => 'valid-nonce',
            'object_id' => '5',
            'field_id'  => 'my_field',
            'value'     => 'hello',
            'meta_type' => 'post',
        ], $overrides);
    }

    private function registerAjaxTextField(string $id = 'my_field', string $post_type = 'post'): void
    {
        new MetaBox('box', 'Box', $post_type, [
            ['type' => 'text', 'id' => $id, 'name' => $id, 'label' => ucfirst($id), 'ajax' => true],
        ]);
    }

    // -------------------------------------------------------------------------
    // Guard — early exits
    // -------------------------------------------------------------------------

    public function testBadRequestWhenNoPostData(): void
    {
        $_POST = [];
        Functions\expect('wp_send_json_error')
            ->once()->with(['message' => 'Invalid request.'], 400)
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }

    public function testForbiddenWhenNonceInvalid(): void
    {
        $_POST = ['cfdev' => $this->validPost()];
        Functions\expect('wp_verify_nonce')->once()->with('valid-nonce', 'cfdev_ajax_save')->andReturn(false);
        Functions\expect('wp_send_json_error')
            ->once()->with(['message' => 'Invalid nonce.'], 403)
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }

    public function testBadRequestWhenObjectIdIsZero(): void
    {
        $_POST = ['cfdev' => $this->validPost(['object_id' => '0'])];
        Functions\expect('wp_verify_nonce')->once()->andReturn(true);
        Functions\expect('wp_send_json_error')
            ->once()->with(['message' => 'Missing required fields.'], 400)
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }

    public function testBadRequestWhenFieldIdEmpty(): void
    {
        $_POST = ['cfdev' => $this->validPost(['field_id' => ''])];
        Functions\expect('wp_verify_nonce')->once()->andReturn(true);
        Functions\expect('wp_send_json_error')
            ->once()->with(['message' => 'Missing required fields.'], 400)
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }

    public function testForbiddenWhenUserCannotEditPost(): void
    {
        $_POST = ['cfdev' => $this->validPost()];
        Functions\expect('wp_verify_nonce')->once()->andReturn(true);
        Functions\expect('current_user_can')->once()->with('edit_post', 5)->andReturn(false);
        Functions\expect('wp_send_json_error')
            ->once()->with(['message' => 'Insufficient permissions.'], 403)
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }

    public function testBadRequestWhenFieldRegisteredWithoutAjaxFlag(): void
    {
        // Field exists but ajax=false — must not grant write access
        new MetaBox('box', 'Box', 'post', [
            ['type' => 'text', 'id' => 'my_field', 'name' => 'my_field', 'label' => 'My Field'],
        ]);

        $_POST = ['cfdev' => $this->validPost()];
        Functions\expect('wp_verify_nonce')->once()->andReturn(true);
        Functions\expect('current_user_can')->once()->andReturn(true);
        Functions\expect('wp_send_json_error')
            ->once()->with(['message' => 'Unknown field.'], 400)
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    public function testSavesPostMetaAndRespondsSuccess(): void
    {
        $this->registerAjaxTextField('my_field', 'post');
        $_POST = ['cfdev' => $this->validPost()];

        Functions\expect('wp_verify_nonce')->once()->andReturn(true);
        Functions\expect('current_user_can')->once()->with('edit_post', 5)->andReturn(true);
        Functions\expect('update_post_meta')->once()->with(5, 'my_field', 'hello')->andReturn(true);
        Functions\expect('wp_send_json_success')
            ->once()
            ->andReturnUsing(fn() => throw new RuntimeException());

        $this->expectException(RuntimeException::class);
        Field::ajaxSave();
    }
}
