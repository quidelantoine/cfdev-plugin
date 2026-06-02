<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\CachePage;
use Weblitzer\CFDev\Cache\CacheManager;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class CachePageTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_unslash')->returnArg(1);
        Functions\when('get_option')->justReturn(false);
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('checked')->justReturn('');
        Functions\when('_n')->returnArg(1);
        Functions\when('esc_attr_e')->alias(function (string $text, string $domain = ''): void {
            echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        });
        Functions\when('esc_html_e')->alias(function (string $text, string $domain = ''): void {
            echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        });
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/admin.php');
        Functions\when('settings_errors')->justReturn(null);
        Functions\when('trailingslashit')->alias(fn(string $p) => rtrim($p, '/') . '/');
        Functions\when('wp_json_encode')->alias('json_encode');
        // CacheStore filesystem stubs (redefined via patchwork.json)
        Functions\when('wp_upload_dir')->justReturn(['basedir' => '/tmp/cfdev-test']);
        Functions\when('wp_mkdir_p')->justReturn(true);
        Functions\when('is_dir')->justReturn(true);
        Functions\when('file_exists')->justReturn(true);
        Functions\when('glob')->justReturn([]);
        Functions\when('sanitize_file_name')->returnArg(1);
        Functions\when('unlink')->justReturn(true);
        Functions\when('wp_date')->returnArg(1);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    private function captureRender(): string
    {
        ob_start();
        CachePage::render();
        return (string) ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Capability guard
    // -------------------------------------------------------------------------

    public function testRenderReturnsEarlyWhenUserLacksCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);

        $this->assertSame('', $this->captureRender());
    }

    // -------------------------------------------------------------------------
    // Page structure — empty cache
    // -------------------------------------------------------------------------

    public function testRenderOutputsWrapClass(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('class="wrap"', $output);
    }

    public function testRenderOutputsToggleCheckbox(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('id="cfdev_cache_enabled"', $output);
    }

    public function testRenderOutputsFlushAllForm(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('flush_all', $output);
    }

    public function testRenderShowsPlaceholderWhenCacheIsEmpty(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-placeholder', $output);
    }

    // -------------------------------------------------------------------------
    // Toggle label states
    // -------------------------------------------------------------------------

    public function testRenderShowsCacheActiveLabelWhenEnabled(): void
    {
        Functions\when('get_option')->justReturn('1');

        $output = $this->captureRender();

        $this->assertStringContainsString('Cache active', $output);
        $this->assertStringNotContainsString('Cache inactive', $output);
    }

    public function testRenderShowsCacheInactiveLabelWhenDisabled(): void
    {
        Functions\when('get_option')->justReturn('0');

        $output = $this->captureRender();

        $this->assertStringContainsString('Cache inactive', $output);
    }

    // -------------------------------------------------------------------------
    // POST handling — toggle option
    // -------------------------------------------------------------------------

    public function testPostToggleWithValidNonceSavesOptionEnabled(): void
    {
        $_POST = [
            'cfdev_cache_option_nonce' => 'test-nonce',
            'cfdev_cache_enabled'      => '1',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('add_settings_error')->justReturn(null);
        Functions\expect('update_option')
            ->once()
            ->with(CachePage::OPTION_CACHE, '1');
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    public function testPostToggleWithValidNonceSavesOptionDisabledWhenUnchecked(): void
    {
        $_POST = [
            'cfdev_cache_option_nonce' => 'test-nonce',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('add_settings_error')->justReturn(null);
        Functions\expect('update_option')
            ->once()
            ->with(CachePage::OPTION_CACHE, '0');
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    public function testPostToggleWithInvalidNonceDoesNotSaveOption(): void
    {
        $_POST = [
            'cfdev_cache_option_nonce' => 'bad-nonce',
            'cfdev_cache_enabled'      => '1',
        ];
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\expect('update_option')->never();
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    // -------------------------------------------------------------------------
    // POST handling — flush actions
    // -------------------------------------------------------------------------

    public function testPostFlushAllWithValidNonceShowsSuccessNotice(): void
    {
        $_POST = [
            'cfdev_cache_action' => 'flush_all',
            'cfdev_cache_nonce'  => 'flush-nonce',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $output = $this->captureRender();

        $this->assertStringContainsString('notice-success', $output);
    }

    public function testPostFlushOneWithValidNonceShowsSuccessNotice(): void
    {
        $_POST = [
            'cfdev_cache_action' => 'flush_one',
            'cfdev_cache_nonce'  => 'flush-nonce',
            'cfdev_cache_key'    => 'post_42',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);

        $output = $this->captureRender();

        $this->assertStringContainsString('notice-success', $output);
    }

    public function testPostFlushWithInvalidNonceDoesNotShowSuccessNotice(): void
    {
        $_POST = [
            'cfdev_cache_action' => 'flush_all',
            'cfdev_cache_nonce'  => 'bad-nonce',
        ];
        Functions\when('wp_verify_nonce')->justReturn(false);

        $output = $this->captureRender();

        $this->assertStringNotContainsString('notice-success', $output);
    }

    // -------------------------------------------------------------------------
    // Cache table with files
    // -------------------------------------------------------------------------

    public function testRenderShowsCacheTableWhenFilesExist(): void
    {
        Functions\when('glob')->justReturn(['/tmp/cfdev-test/cfdev-cache/post_42.tmp']);
        Functions\when('filemtime')->justReturn(time());
        Functions\when('filesize')->justReturn(512);
        Functions\when('file_get_contents')->justReturn('{"groups":{}}');
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn(null);
        Functions\when('get_the_title')->justReturn('Test Post');

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-cache-table', $output);
        $this->assertStringContainsString('post_42', $output);
    }

    public function testRenderMarksCacheEntryAsStaleWhenTtlExceeded(): void
    {
        Functions\when('glob')->justReturn(['/tmp/cfdev-test/cfdev-cache/post_42.tmp']);
        Functions\when('filemtime')->justReturn(time() - 90000); // 25 hours ago
        Functions\when('filesize')->justReturn(512);
        Functions\when('file_get_contents')->justReturn('{"groups":{}}');
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn(null);
        Functions\when('get_the_title')->justReturn('Test Post');

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-stale', $output);
        $this->assertStringContainsString('cfdev-badge-stale', $output);
    }

    public function testRenderShowsDeleteButtonForEachFile(): void
    {
        Functions\when('glob')->justReturn(['/tmp/cfdev-test/cfdev-cache/post_42.tmp']);
        Functions\when('filemtime')->justReturn(time());
        Functions\when('filesize')->justReturn(256);
        Functions\when('file_get_contents')->justReturn('{"groups":{}}');
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('get_post_type_object')->justReturn(null);
        Functions\when('get_the_title')->justReturn('Test Post');

        $output = $this->captureRender();

        $this->assertStringContainsString('flush_one', $output);
        $this->assertStringContainsString('cfdev-btn-del', $output);
    }

    // -------------------------------------------------------------------------
    // formatSize — via reflection
    // -------------------------------------------------------------------------

    private function invokeFormatSize(int $bytes): string
    {
        $method = new \ReflectionMethod(CachePage::class, 'formatSize');
        $method->setAccessible(true);
        return (string) $method->invoke(null, $bytes);
    }

    public function testFormatSizeDisplaysBytesAsOctet(): void
    {
        $this->assertSame('512 o', $this->invokeFormatSize(512));
    }

    public function testFormatSizeDisplaysOneKilobyte(): void
    {
        $this->assertSame('1 Ko', $this->invokeFormatSize(1024));
    }

    public function testFormatSizeDisplaysDecimalKilobytes(): void
    {
        $this->assertSame('1.5 Ko', $this->invokeFormatSize(1536));
    }

    public function testFormatSizeDisplaysMegabytes(): void
    {
        $this->assertSame('1 Mo', $this->invokeFormatSize(1048576));
    }

    // -------------------------------------------------------------------------
    // formatAge — via reflection
    // -------------------------------------------------------------------------

    private function invokeFormatAge(int $seconds): string
    {
        $method = new \ReflectionMethod(CachePage::class, 'formatAge');
        $method->setAccessible(true);
        return (string) $method->invoke(null, $seconds);
    }

    public function testFormatAgeDisplaysSeconds(): void
    {
        $result = $this->invokeFormatAge(30);
        $this->assertStringContainsString('30', $result);
        $this->assertStringContainsString('s', $result);
    }

    public function testFormatAgeDisplaysMinutes(): void
    {
        $result = $this->invokeFormatAge(90);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('min', $result);
    }

    public function testFormatAgeDisplaysHours(): void
    {
        $result = $this->invokeFormatAge(7200);
        $this->assertStringContainsString('2', $result);
        $this->assertStringContainsString('h', $result);
    }

    public function testFormatAgeDaysDisplaysAsJ(): void
    {
        $result = $this->invokeFormatAge(172800);
        $this->assertStringContainsString('2', $result);
        $this->assertStringContainsString('j', $result);
    }
}
