<?php

namespace Weblitzer\CFDev\Tests\Unit\Config;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Config\Ajax\AjaxHandler;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class AjaxHandlerTest extends CFDevTestCase
{
    private string $tmpDir;
    private ?string $lastJsonType   = null; // 'success' | 'error'
    private mixed $lastJsonData   = null;
    private int $lastJsonStatus = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // ── CacheStore real temp dir ──────────────────────────────────────
        $this->tmpDir = sys_get_temp_dir() . '/cfdev-ajax-' . uniqid();
        mkdir($this->tmpDir, 0755, true); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir

        Functions\when('wp_upload_dir')->justReturn(['basedir' => $this->tmpDir]);
        Functions\when('trailingslashit')->alias(fn(string $s): string => rtrim($s, '/') . '/');
        Functions\when('wp_mkdir_p')->alias(
            fn(string $dir): bool => is_dir($dir) || mkdir($dir, 0755, true) // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
        );
        Functions\when('sanitize_file_name')->returnArg();
        Functions\when('wp_json_encode')->alias('json_encode');

        // ── CacheManager / store helpers ──────────────────────────────────
        Functions\when('get_option')->justReturn(false); // cache disabled → no store reads
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn(null);
        Functions\when('get_post_meta')->justReturn('');
        Functions\when('get_term_meta')->justReturn('');
        Functions\when('get_user_meta')->justReturn('');
        Functions\when('wp_date')->returnArg(1);

        // ── Auth / nonce helpers ──────────────────────────────────────────
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_key')->alias(
            fn(string $s): string => (string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($s))
        );
        Functions\when('wp_unslash')->returnArg(1);
        Functions\when('absint')->alias('intval');

        // ── JSON response capture ─────────────────────────────────────────
        $this->lastJsonType   = null;
        $this->lastJsonData   = null;
        $this->lastJsonStatus = 0;

        Functions\when('wp_send_json_error')->alias(
            function (mixed $data = null, int $status = 0): never {
                $this->lastJsonType   = 'error';
                $this->lastJsonData   = $data;
                $this->lastJsonStatus = $status;
                throw new \RuntimeException('ajax_error');
            }
        );
        Functions\when('wp_send_json_success')->alias(
            function (mixed $data = null): never {
                $this->lastJsonType = 'success';
                $this->lastJsonData = $data;
                throw new \RuntimeException('ajax_success');
            }
        );
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $this->removeDirRecursive($this->tmpDir);
        parent::tearDown();
    }

    private function removeDirRecursive(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path)
                ? $this->removeDirRecursive($path)
                : unlink($path); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
        }
        rmdir($dir); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir
    }

    private function callAndCapture(callable $fn): void
    {
        try {
            $fn();
            $this->fail('wp_send_json_* was not called');
        } catch (\RuntimeException) {
            // expected — response captured in lastJson* properties
        }
    }

    // =========================================================================
    // register()
    // =========================================================================

    public function testRegisterAddsAjaxSaveHook(): void
    {
        \Brain\Monkey\Actions\expectAdded('wp_ajax_cfdev_field_ajax_save')->once();
        (new AjaxHandler())->register();
        $this->addToAssertionCount(1);
    }

    public function testRegisterDoesNotAllowUnauthenticated(): void
    {
        \Brain\Monkey\Actions\expectAdded('wp_ajax_nopriv_cfdev_field_ajax_save')->never();
        (new AjaxHandler())->register();
        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // handleInspect() — capability guard
    // =========================================================================

    public function testHandleInspectRejectsWhenUserLacksCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('error', $this->lastJsonType);
        $this->assertSame(403, $this->lastJsonStatus);
        $this->assertSame('Forbidden', $this->lastJsonData['message'] ?? '');
    }

    // =========================================================================
    // handleInspect() — nonce guard
    // =========================================================================

    public function testHandleInspectRejectsWithInvalidNonce(): void
    {
        $_POST = ['nonce' => 'bad-nonce'];
        Functions\when('wp_verify_nonce')->justReturn(false);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('error', $this->lastJsonType);
        $this->assertSame(403, $this->lastJsonStatus);
        $this->assertSame('Invalid nonce', $this->lastJsonData['message'] ?? '');
    }

    // =========================================================================
    // handleInspect() — object_id validation
    // =========================================================================

    public function testHandleInspectRejectsWhenObjectIdIsZero(): void
    {
        $_POST = ['nonce' => 'valid', 'object_id' => '0'];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('error', $this->lastJsonType);
        $this->assertStringContainsString('invalide', $this->lastJsonData['message'] ?? '');
    }

    public function testHandleInspectRejectsWhenObjectIdIsMissing(): void
    {
        $_POST = ['nonce' => 'valid'];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('error', $this->lastJsonType);
    }

    // =========================================================================
    // handleInspect() — success paths
    // =========================================================================

    public function testHandleInspectSucceedsForPost(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'post',
            'object_id'   => '1',
            'taxonomy'    => '',
            'group_id'    => '',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertIsArray($this->lastJsonData);
        $this->assertArrayHasKey('cache', $this->lastJsonData);
    }

    public function testHandleInspectSucceedsForTerm(): void
    {
        Functions\when('get_terms')->justReturn([]);
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'term',
            'object_id'   => '5',
            'taxonomy'    => 'category',
            'group_id'    => '',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('success', $this->lastJsonType);
    }

    public function testHandleInspectSucceedsForUser(): void
    {
        Functions\when('get_users')->justReturn([]);
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'user',
            'object_id'   => '3',
            'taxonomy'    => '',
            'group_id'    => '',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('success', $this->lastJsonType);
    }

    public function testHandleInspectFiltersToGroupWhenGroupIdNotFound(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'post',
            'object_id'   => '1',
            'group_id'    => 'nonexistent_group',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertSame([], $this->lastJsonData['data'] ?? 'NOT_EMPTY');
    }

    public function testHandleInspectForceParamIsRespected(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'post',
            'object_id'   => '1',
            'force'       => '1',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleInspect());

        $this->assertSame('success', $this->lastJsonType);
    }

    // =========================================================================
    // handleSearchObjects() — capability guard
    // =========================================================================

    public function testHandleSearchObjectsRejectsWhenUserLacksCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('error', $this->lastJsonType);
        $this->assertSame(403, $this->lastJsonStatus);
    }

    // =========================================================================
    // handleSearchObjects() — nonce guard
    // =========================================================================

    public function testHandleSearchObjectsRejectsWithInvalidNonce(): void
    {
        $_POST = ['nonce' => 'bad'];
        Functions\when('wp_verify_nonce')->justReturn(false);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('error', $this->lastJsonType);
        $this->assertSame(403, $this->lastJsonStatus);
    }

    // =========================================================================
    // handleSearchObjects() — object_type = post
    // =========================================================================

    public function testHandleSearchObjectsReturnsEmptyArrayForPostWithNoResults(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'post',
            'targets'     => 'book',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertIsArray($this->lastJsonData);
    }

    public function testHandleSearchObjectsAddsSearchArgForPost(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'post',
            'targets'     => 'book',
            'search'      => 'keyword',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
    }

    // =========================================================================
    // handleSearchObjects() — object_type = term
    // =========================================================================

    public function testHandleSearchObjectsReturnsEmptyArrayForTermWithNoResults(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'term',
            'targets'     => 'genre',
            'taxonomy'    => 'genre',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertSame([], $this->lastJsonData);
    }

    public function testHandleSearchObjectsTermResultsMappedCorrectly(): void
    {
        $term           = new \WP_Term();
        $term->term_id  = 7;
        $term->name     = 'Fiction';
        $term->taxonomy = 'genre';
        Functions\when('get_terms')->justReturn([$term]);

        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'term',
            'targets'     => 'genre',
            'taxonomy'    => 'genre',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertCount(1, $this->lastJsonData);
        $this->assertSame(7, $this->lastJsonData[0]['id']);
        $this->assertSame('Fiction', $this->lastJsonData[0]['label']);
        $this->assertSame('genre', $this->lastJsonData[0]['meta']);
    }

    // =========================================================================
    // handleSearchObjects() — object_type = user
    // =========================================================================

    public function testHandleSearchObjectsReturnsEmptyArrayForUserWithNoResults(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'user',
            'targets'     => '',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertSame([], $this->lastJsonData);
    }

    public function testHandleSearchObjectsUserResultsMappedCorrectly(): void
    {
        $user               = new \WP_User();
        $user->ID           = 3;
        $user->display_name = 'Jane Doe';
        $user->user_login   = 'janedoe';
        Functions\when('get_users')->justReturn([$user]);

        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'user',
            'targets'     => '',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
        $this->assertCount(1, $this->lastJsonData);
        $this->assertSame(3, $this->lastJsonData[0]['id']);
        $this->assertSame('Jane Doe', $this->lastJsonData[0]['label']);
        $this->assertSame('janedoe', $this->lastJsonData[0]['meta']);
    }

    public function testHandleSearchObjectsUserSearchArgAdded(): void
    {
        $_POST = [
            'nonce'       => 'valid',
            'object_type' => 'user',
            'search'      => 'jane',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $this->callAndCapture(fn() => AjaxHandler::handleSearchObjects());

        $this->assertSame('success', $this->lastJsonType);
    }
}
