<?php

namespace Weblitzer\CFDev\Tests\Unit\Config;

use Weblitzer\CFDev\Config\Assets\AssetLoader;
use Weblitzer\CFDev\Config\Config;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Brain\Monkey\Functions;

class AssetLoaderTest extends CFDevTestCase
{
    private function config(): Config
    {
        return new Config('1.2.3', '/plugin/', 'https://example.com/plugin', '/plugin/src/');
    }

    private function stubLocalize(): void
    {
        Functions\when('get_home_url')->justReturn('https://site.com');
        Functions\when('admin_url')->justReturn('https://site.com/wp-admin/admin-ajax.php');
        Functions\when('get_option')->justReturn('Y-m-d');
        Functions\when('get_bloginfo')->justReturn('6.0');
        Functions\when('wp_create_nonce')->justReturn('test-nonce');
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterHooksAllFourActions(): void
    {
        \Brain\Monkey\Actions\expectAdded('admin_init')->twice();
        \Brain\Monkey\Actions\expectAdded('admin_print_styles')->once();
        \Brain\Monkey\Actions\expectAdded('admin_enqueue_scripts')->once();

        (new AssetLoader($this->config()))->register();
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // registerStyles()
    // -------------------------------------------------------------------------

    public function testRegisterStylesBuildsUrlFromConfig(): void
    {
        $urls = [];
        Functions\when('wp_register_style')->alias(function (string $handle, string $src) use (&$urls): void {
            $urls[$handle] = $src;
        });

        (new AssetLoader($this->config()))->registerStyles();

        $this->assertStringContainsString('https://example.com/plugin', $urls['cfdev-jquery-ui']);
        $this->assertStringContainsString('https://example.com/plugin', $urls['cfdev']);
    }

    public function testRegisterStylesUsesConfigVersion(): void
    {
        $versions = [];
        Functions\when('wp_register_style')->alias(
            function (string $handle, string $src, array $deps, string $ver) use (&$versions): void {
                $versions[$handle] = $ver;
            }
        );

        (new AssetLoader($this->config()))->registerStyles();

        $this->assertSame('1.2.3', $versions['cfdev']);
        $this->assertSame('1.2.3', $versions['cfdev-jquery-ui']);
    }

    public function testRegisterStylesRegistersExactlyTwoHandles(): void
    {
        $handles = [];
        Functions\when('wp_register_style')->alias(function (string $handle) use (&$handles): void {
            $handles[] = $handle;
        });

        (new AssetLoader($this->config()))->registerStyles();

        $this->assertCount(2, $handles);
        $this->assertContains('cfdev', $handles);
        $this->assertContains('cfdev-jquery-ui', $handles);
    }

    // -------------------------------------------------------------------------
    // enqueueStyles()
    // -------------------------------------------------------------------------

    public function testEnqueueStylesIncludesAllRequiredHandles(): void
    {
        $enqueued = [];
        Functions\when('wp_enqueue_style')->alias(function (string $handle) use (&$enqueued): void {
            $enqueued[] = $handle;
        });

        (new AssetLoader($this->config()))->enqueueStyles();

        $this->assertContains('cfdev', $enqueued);
        $this->assertContains('cfdev-jquery-ui', $enqueued);
        $this->assertContains('wp-color-picker', $enqueued);
        $this->assertContains('thickbox', $enqueued);
    }

    // -------------------------------------------------------------------------
    // registerScripts()
    // -------------------------------------------------------------------------

    public function testRegisterScriptsBuildsUrlFromConfig(): void
    {
        $urls = [];
        Functions\when('wp_register_script')->alias(function (string $handle, string $src) use (&$urls): void {
            $urls[$handle] = $src;
        });

        (new AssetLoader($this->config()))->registerScripts();

        $this->assertStringContainsString('https://example.com/plugin', $urls['cfdev']);
        $this->assertStringContainsString('https://example.com/plugin', $urls['jquery-timepicker']);
    }

    public function testRegisterScriptsUsesConfigVersion(): void
    {
        $versions = [];
        Functions\when('wp_register_script')->alias(
            function (string $handle, string $src, array $deps, string $ver) use (&$versions): void {
                $versions[$handle] = $ver;
            }
        );

        (new AssetLoader($this->config()))->registerScripts();

        $this->assertSame('1.2.3', $versions['cfdev']);
        $this->assertSame('1.2.3', $versions['jquery-timepicker']);
    }

    public function testCfdevScriptDependsOnJquery(): void
    {
        $deps = [];
        Functions\when('wp_register_script')->alias(
            function (string $handle, string $src, array $d) use (&$deps): void {
                $deps[$handle] = $d;
            }
        );

        (new AssetLoader($this->config()))->registerScripts();

        $this->assertContains('jquery', $deps['cfdev']);
    }

    public function testCfdevScriptIsLoadedInFooter(): void
    {
        $inFooter = [];
        Functions\when('wp_register_script')->alias(
            function (string $handle, string $src, array $d, string $ver, bool $footer) use (&$inFooter): void {
                $inFooter[$handle] = $footer;
            }
        );

        (new AssetLoader($this->config()))->registerScripts();

        $this->assertTrue($inFooter['cfdev']);
    }

    // -------------------------------------------------------------------------
    // enqueueScripts()
    // -------------------------------------------------------------------------

    public function testEnqueueScriptsEnqueuesCfdev(): void
    {
        $this->stubLocalize();
        Functions\when('wp_enqueue_media')->justReturn();
        Functions\when('wp_localize_script')->justReturn();

        $enqueued = [];
        Functions\when('wp_enqueue_script')->alias(function (string $handle) use (&$enqueued): void {
            $enqueued[] = $handle;
        });

        (new AssetLoader($this->config()))->enqueueScripts('');

        $this->assertContains('cfdev', $enqueued);
    }

    public function testEnqueueScriptsLocalizesCfdevWithAjaxUrl(): void
    {
        $this->stubLocalize();
        Functions\when('wp_enqueue_media')->justReturn();
        Functions\when('wp_enqueue_script')->justReturn();

        $localized = [];
        Functions\when('wp_localize_script')->alias(
            function (string $handle, string $name, array $data) use (&$localized): void {
                $localized = $data;
            }
        );

        (new AssetLoader($this->config()))->enqueueScripts('');

        $this->assertArrayHasKey('ajax_url', $localized);
        $this->assertArrayHasKey('nonce', $localized);
        $this->assertArrayHasKey('home_url', $localized);
    }

    public function testEnqueueScriptsLocalizesCfdevHandle(): void
    {
        $this->stubLocalize();
        Functions\when('wp_enqueue_media')->justReturn();
        Functions\when('wp_enqueue_script')->justReturn();

        $handle = null;
        Functions\when('wp_localize_script')->alias(
            function (string $h) use (&$handle): void {
                $handle = $h;
            }
        );

        (new AssetLoader($this->config()))->enqueueScripts('');

        $this->assertSame('cfdev', $handle);
    }
}
