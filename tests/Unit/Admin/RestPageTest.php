<?php

namespace Weblitzer\CFDev\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Admin\RestPage;
use Weblitzer\CFDev\Meta\MetaBox;
use Weblitzer\CFDev\Meta\TermMeta;
use Weblitzer\CFDev\Meta\UserMeta;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class RestPageTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('wp_unslash')->returnArg(1);
        Functions\when('get_option')->justReturn(true);
        Functions\when('get_home_url')->justReturn('https://example.com');
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('checked')->justReturn('');
        Functions\when('_n')->returnArg(1);
        Functions\when('esc_attr_e')->alias(function (string $text, string $domain = ''): void {
            echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        });
        Functions\when('esc_html_e')->alias(function (string $text, string $domain = ''): void {
            echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        });
        Functions\when('get_post_type_object')->justReturn(null);
        Functions\when('get_taxonomy')->justReturn(null);
        Functions\when('admin_url')->justReturn('https://example.com/wp-admin/admin.php');
        Functions\when('wp_json_encode')->alias('json_encode');
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    private function captureRender(): string
    {
        ob_start();
        RestPage::render();
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
    // Page structure — empty registry
    // -------------------------------------------------------------------------

    public function testRenderOutputsRestPageWrapper(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-rest-page', $output);
    }

    public function testRenderOutputsRestToggleForm(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev_rest_enabled', $output);
    }

    public function testRenderOutputsApiToggleForm(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev_api_enabled', $output);
    }

    public function testRenderAlwaysOutputsBundleModal(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-rest-bundle-modal', $output);
    }

    public function testRenderShowsPlaceholderWhenNoRestFields(): void
    {
        $output = $this->captureRender();
        $this->assertStringContainsString('cfdev-placeholder', $output);
    }

    // -------------------------------------------------------------------------
    // Toggle label states
    // -------------------------------------------------------------------------

    public function testRenderShowsRestActiveLabelWhenEnabled(): void
    {
        Functions\when('get_option')->alias(function (string $opt, mixed $default = null): mixed {
            return ($opt === RestPage::OPTION_REST) ? '1' : $default;
        });

        $output = $this->captureRender();

        $this->assertStringContainsString('Native WP REST active', $output);
        $this->assertStringNotContainsString('Native WP REST inactive', $output);
    }

    public function testRenderShowsRestInactiveLabelWhenDisabled(): void
    {
        Functions\when('get_option')->alias(function (string $opt, mixed $default = null): mixed {
            return ($opt === RestPage::OPTION_REST) ? '0' : '1';
        });

        $output = $this->captureRender();

        $this->assertStringContainsString('Native WP REST inactive', $output);
        $this->assertStringNotContainsString('Native WP REST active', $output);
    }

    public function testRenderShowsApiActiveLabelWhenEnabled(): void
    {
        Functions\when('get_option')->alias(function (string $opt, mixed $default = null): mixed {
            return ($opt === RestPage::OPTION_API) ? '1' : $default;
        });

        $output = $this->captureRender();

        $this->assertStringContainsString('CFDev API active', $output);
    }

    public function testRenderShowsApiInactiveLabelWhenDisabled(): void
    {
        Functions\when('get_option')->alias(function (string $opt, mixed $default = null): mixed {
            return ($opt === RestPage::OPTION_API) ? '0' : '1';
        });

        $output = $this->captureRender();

        $this->assertStringContainsString('CFDev API inactive', $output);
    }

    // -------------------------------------------------------------------------
    // POST handling — toggle saves
    // -------------------------------------------------------------------------

    public function testPostWithValidNonceSavesRestOptionEnabled(): void
    {
        $_POST = [
            'cfdev_rest_option_nonce' => 'test-nonce',
            'cfdev_rest_which'        => 'rest',
            'cfdev_rest_enabled'      => '1',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('update_option')
            ->once()
            ->with(RestPage::OPTION_REST, '1');
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    public function testPostWithValidNonceSavesRestOptionDisabledWhenUnchecked(): void
    {
        $_POST = [
            'cfdev_rest_option_nonce' => 'test-nonce',
            'cfdev_rest_which'        => 'rest',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('update_option')
            ->once()
            ->with(RestPage::OPTION_REST, '0');
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    public function testPostWithValidNonceSavesApiOption(): void
    {
        $_POST = [
            'cfdev_rest_option_nonce' => 'test-nonce',
            'cfdev_rest_which'        => 'api',
            'cfdev_api_enabled'       => '1',
        ];
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\expect('update_option')
            ->once()
            ->with(RestPage::OPTION_API, '1');
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    public function testPostWithInvalidNonceDoesNotSaveOption(): void
    {
        $_POST = [
            'cfdev_rest_option_nonce' => 'bad-nonce',
            'cfdev_rest_which'        => 'rest',
            'cfdev_rest_enabled'      => '1',
        ];
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\expect('update_option')->never();
        $this->addToAssertionCount(1);

        $this->captureRender();
    }

    // -------------------------------------------------------------------------
    // With REST fields registered
    // -------------------------------------------------------------------------

    private function setupMetaBoxMocks(): void
    {
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('register_meta')->justReturn(true);
    }

    public function testRenderShowsTabNavWhenPostRestFieldsExist(): void
    {
        $this->setupMetaBoxMocks();
        new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_subtitle', 'name' => 'Subtitle', 'rest' => true],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-tabs-nav', $output);
        $this->assertStringNotContainsString('cfdev-placeholder', $output);
    }

    public function testRenderShowsTotalCountBadgeWhenRestFieldsExist(): void
    {
        $this->setupMetaBoxMocks();
        new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_subtitle', 'name' => 'Subtitle', 'rest' => true],
            ['type' => 'text', 'id' => '_isbn',     'name' => 'ISBN',     'rest' => true],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-rest-total', $output);
    }

    public function testRenderShowsTermTabWhenTermRestFieldsExist(): void
    {
        $this->setupMetaBoxMocks();
        new TermMeta('genre', 'Genre', [
            ['type' => 'text', 'id' => '_desc', 'name' => 'Desc', 'rest' => true],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-rest-tab-terms', $output);
    }

    public function testRenderShowsUserTabWhenUserRestFieldsExist(): void
    {
        $this->setupMetaBoxMocks();
        new UserMeta('profile_extra', 'Extra', [
            ['type' => 'text', 'id' => '_bio', 'name' => 'Bio', 'rest' => true],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-rest-tab-users', $output);
    }

    public function testRenderShowsFieldTableWithMetaKeyColumn(): void
    {
        $this->setupMetaBoxMocks();
        new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_subtitle', 'name' => 'Subtitle', 'rest' => true],
        ]);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-rest-table', $output);
        $this->assertStringContainsString('_subtitle', $output);
    }

    public function testRenderShowsConditionBadgeWhenRestEntryHasCondition(): void
    {
        $this->setupMetaBoxMocks();
        $mb = new MetaBox('box', 'Box', 'book', [
            ['type' => 'text', 'id' => '_subtitle', 'name' => 'Subtitle', 'rest' => true],
        ]);
        $mb->onlyForId(42);

        $output = $this->captureRender();

        $this->assertStringContainsString('cfdev-condition-badge', $output);
        $this->assertStringContainsString('ID : 42', $output);
    }
}
