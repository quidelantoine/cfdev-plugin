<?php

namespace Weblitzer\CFDev\Tests\Unit;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\OptionsPage;
use Weblitzer\CFDev\Registry;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class OptionsPageTest extends CFDevTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('add_action')->justReturn(null);
        Functions\when('sanitize_title')->alias(
            fn(string $s) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $s))
        );
        Functions\when('apply_filters')->returnArg(2);
        Functions\when('wp_json_encode')->alias('json_encode');
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    /** @param array<mixed> $data */
    private function makePage(string $id = 'site_settings', string $title = 'Site Settings', array $data = []): OptionsPage
    {
        return new OptionsPage($id, $title, $data);
    }

    /** @return array<string, mixed> */
    private function fieldDef(string $id, string $type = 'text'): array
    {
        return ['type' => $type, 'id' => $id, 'name' => $id, 'label' => ucfirst($id)];
    }

    private function stubRedirect(): void
    {
        Functions\when('wp_get_referer')->justReturn(false);
        Functions\when('admin_url')->alias(
            fn(string $path = '') => 'https://example.com/wp-admin/' . $path
        );
        Functions\when('add_query_arg')->alias(
            fn(string $k, string $v, string $url): string => $url . '&' . $k . '=' . $v
        );
    }

    // ─── Construction ─────────────────────────────────────────────────────────

    public function testConstructSetsId(): void
    {
        $page = $this->makePage('my_settings');
        $this->assertSame('my_settings', $page->id);
    }

    public function testConstructSetsTitle(): void
    {
        $page = $this->makePage('my_settings', 'My Settings');
        $this->assertSame('My Settings', $page->title);
    }

    public function testConstructWithArrayTitleSetsDescription(): void
    {
        $page = new OptionsPage('settings', ['Site Settings', 'Configure your site']);
        $this->assertSame('Configure your site', $page->description);
    }

    public function testConstructRegistersInRegistry(): void
    {
        $this->makePage('site_opts');
        $this->assertCount(1, Registry::all());
    }

    // ─── metaType / resolveObjectId ───────────────────────────────────────────

    public function testMetaTypeIsOption(): void
    {
        $this->makePage();
        $this->assertSame('option', Registry::all()[0]['meta_type']);
    }

    public function testResolveObjectIdReturnsZero(): void
    {
        $page = $this->makePage();
        $m    = new \ReflectionMethod($page, 'resolveObjectId');
        $this->assertSame(0, $m->invoke($page));
    }

    // ─── asSubmenu ────────────────────────────────────────────────────────────

    public function testAsSubmenuReturnsSelf(): void
    {
        $page   = $this->makePage();
        $result = $page->asSubmenu('options-general.php');
        $this->assertSame($page, $result);
    }

    public function testAsSubmenuStoresParentSlug(): void
    {
        $page = $this->makePage();
        $page->asSubmenu('options-general.php');

        $p = new \ReflectionProperty($page, 'parent_slug');
        $this->assertSame('options-general.php', $p->getValue($page));
    }

    // ─── addSubPage ────────────────────────────────────────────────────────────

    public function testAddSubPageRegistersChildInRegistry(): void
    {
        $page = $this->makePage('parent', 'Parent');
        $page->addSubPage('child', 'Child');

        $ids = array_column(Registry::all(), 'id');
        $this->assertContains('child', $ids);
    }

    public function testAddSubPageReturnsSelf(): void
    {
        $page   = $this->makePage('parent', 'Parent');
        $result = $page->addSubPage('child', 'Child');
        $this->assertSame($page, $result);
    }

    public function testAddSubPageChildHasOptionMetaType(): void
    {
        $this->makePage('parent', 'Parent')->addSubPage('child', 'Child');

        $child = array_values(array_filter(Registry::all(), fn($e) => $e['id'] === 'child'));
        $this->assertCount(1, $child);
        $this->assertSame('option', $child[0]['meta_type']);
    }

    // ─── registerMenu ─────────────────────────────────────────────────────────

    public function testRegisterMenuCallsAddMenuPageForTopLevel(): void
    {
        Functions\expect('add_menu_page')->once()->andReturn('');

        $this->makePage('my_page')->registerMenu();
        $this->addToAssertionCount(1);
    }

    public function testRegisterMenuTopLevelUsesSlugWithCfdevPrefix(): void
    {
        $slug_received = '';
        Functions\when('add_menu_page')->alias(
            function ($pt, $mt, $cap, string $slug, $cb, $icon, $pos) use (&$slug_received): string {
                $slug_received = $slug;
                return '';
            }
        );

        $this->makePage('my_page')->registerMenu();

        $this->assertSame('cfdev-my_page', $slug_received);
    }

    public function testRegisterMenuCallsAddSubmenuPageWhenParentSlugSet(): void
    {
        Functions\expect('add_submenu_page')->once()->andReturn('');

        $page = $this->makePage('my_page');
        $page->asSubmenu('options-general.php');
        $page->registerMenu();
        $this->addToAssertionCount(1);
    }

    public function testRegisterMenuSubmenuPassesCorrectParentSlug(): void
    {
        $parent_received = '';
        Functions\when('add_submenu_page')->alias(
            function (string $parent, $pt, $mt, $cap, $slug, $cb) use (&$parent_received): string {
                $parent_received = $parent;
                return '';
            }
        );

        $page = $this->makePage('child');
        $page->asSubmenu('cfdev-parent');
        $page->registerMenu();

        $this->assertSame('cfdev-parent', $parent_received);
    }

    // ─── saveOptions — auth & nonce guards ───────────────────────────────────

    public function testSaveOptionsWpDiesWhenUserLacksCapability(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('wp_die')->once()->andThrow(new \RuntimeException('wp_die'));

        $this->expectException(\RuntimeException::class);
        $_POST = ['cfdev_options_nonce' => 'x'];
        $this->makePage()->saveOptions();
    }

    public function testSaveOptionsWpDiesWhenNonceIsMissing(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\expect('wp_die')->once()->andThrow(new \RuntimeException('wp_die'));

        $this->expectException(\RuntimeException::class);
        $_POST = []; // nonce key absent
        $this->makePage()->saveOptions();
    }

    public function testSaveOptionsWpDiesWhenNonceIsInvalid(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(false);
        Functions\expect('wp_die')->once()->andThrow(new \RuntimeException('wp_die'));

        $this->expectException(\RuntimeException::class);
        $_POST = ['cfdev_options_nonce' => 'bad'];
        $this->makePage()->saveOptions();
    }

    // ─── saveOptions — validation errors ─────────────────────────────────────

    public function testSaveOptionsPushesErrorsToTransientWhenValidationFails(): void
    {
        $pushed = false;

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_transient')->justReturn([]);
        Functions\when('set_transient')->alias(function () use (&$pushed): bool {
            $pushed = true;
            return true;
        });
        $this->stubRedirect();
        Functions\when('wp_safe_redirect')->alias(function (): void {
            throw new \RuntimeException('redirect');
        });

        $page = new OptionsPage(
            'settings',
            'Settings',
            [['type' => 'text', 'id' => 'site_name', 'name' => 'site_name', 'label' => 'Site Name', 'required' => true]]
        );

        $_POST = [
            'cfdev_options_nonce' => 'valid',
            'cfdev'               => ['site_name' => ''], // empty → Required fails
        ];

        try {
            $page->saveOptions();
        } catch (\RuntimeException) {
            // expected: redirect after push
        }

        $this->assertTrue($pushed, 'ErrorBag::push must store errors via set_transient');
    }

    public function testSaveOptionsRedirectsToPageUrlOnValidationError(): void
    {
        $redirect_url = '';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('get_current_user_id')->justReturn(1);
        Functions\when('get_transient')->justReturn([]);
        Functions\when('set_transient')->justReturn(true);
        $this->stubRedirect();
        Functions\when('wp_safe_redirect')->alias(function (string $url) use (&$redirect_url): void {
            $redirect_url = $url;
            throw new \RuntimeException('redirect');
        });

        $page = new OptionsPage(
            'settings',
            'Settings',
            [['type' => 'text', 'id' => 'site_name', 'name' => 'site_name', 'label' => 'Site Name', 'required' => true]]
        );

        $_POST = [
            'cfdev_options_nonce' => 'valid',
            'cfdev'               => ['site_name' => ''],
        ];

        try {
            $page->saveOptions();
        } catch (\RuntimeException) {
        }

        $this->assertStringContainsString('cfdev-settings', $redirect_url);
    }

    // ─── saveOptions — happy path ─────────────────────────────────────────────

    public function testSaveOptionsCallsSaveOnSuccess(): void
    {
        $update_option_called = false;

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_option')->alias(function () use (&$update_option_called): bool {
            $update_option_called = true;
            return true;
        });
        $this->stubRedirect();
        Functions\when('wp_safe_redirect')->alias(function (): void {
            throw new \RuntimeException('redirect');
        });

        $page = $this->makePage('settings', 'Settings', [$this->fieldDef('site_name')]);

        $_POST = [
            'cfdev_options_nonce' => 'valid',
            'cfdev'               => ['site_name' => 'My Site'],
        ];

        try {
            $page->saveOptions();
        } catch (\RuntimeException) {
        }

        $this->assertTrue($update_option_called, 'Field::save() must call update_option');
    }

    public function testSaveOptionsRedirectsWithUpdatedFlagOnSuccess(): void
    {
        $redirect_url = '';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(1);
        Functions\when('update_option')->justReturn(true);
        $this->stubRedirect();
        Functions\when('wp_safe_redirect')->alias(function (string $url) use (&$redirect_url): void {
            $redirect_url = $url;
            throw new \RuntimeException('redirect');
        });

        $page = $this->makePage('settings', 'Settings', [$this->fieldDef('site_name')]);

        $_POST = [
            'cfdev_options_nonce' => 'valid',
            'cfdev'               => ['site_name' => 'My Site'],
        ];

        try {
            $page->saveOptions();
        } catch (\RuntimeException) {
        }

        $this->assertStringContainsString('cfdev-updated', $redirect_url);
    }

    // ─── save() — flat fields ─────────────────────────────────────────────────

    public function testSaveFlatFieldsCallsUpdateOption(): void
    {
        $stored = [];
        Functions\when('update_option')->alias(function (string $key, mixed $val) use (&$stored): bool {
            $stored[$key] = $val;
            return true;
        });

        $page = $this->makePage('settings', 'Settings', [$this->fieldDef('site_name')]);
        $page->save(0, ['site_name' => 'My Site']);

        $this->assertArrayHasKey('site_name', $stored);
        $this->assertSame('My Site', $stored['site_name']);
    }

    public function testSaveFlatFieldsSavesMultipleFields(): void
    {
        $stored = [];
        Functions\when('update_option')->alias(function (string $key, mixed $val) use (&$stored): bool {
            $stored[$key] = $val;
            return true;
        });

        $page = $this->makePage('settings', 'Settings', [
            $this->fieldDef('field_a'),
            $this->fieldDef('field_b'),
        ]);
        $page->save(0, ['field_a' => 'Alpha', 'field_b' => 'Beta']);

        $this->assertSame('Alpha', $stored['field_a']);
        $this->assertSame('Beta', $stored['field_b']);
    }

    public function testSaveFlatFieldsUsesEmptyStringForMissingValue(): void
    {
        $stored = 'NOT_SET';
        Functions\when('update_option')->alias(function (string $key, mixed $val) use (&$stored): bool {
            $stored = $val;
            return true;
        });

        $page = $this->makePage('settings', 'Settings', [$this->fieldDef('site_name')]);
        $page->save(0, []); // key absent → fallback to ''

        $this->assertSame('', $stored);
    }

    public function testSaveFlatFieldsSkipsBundleFields(): void
    {
        $called = false;
        Functions\when('update_option')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $page = $this->makePage('settings', 'Settings', [
            'bundle', '_rows', [$this->fieldDef('name')],
        ]);

        // saveFlatFields() iterates $this->fields and skips in_bundle=true
        $page->save(0, ['name' => 'Alice']);

        $this->assertFalse($called, 'update_option must not be called for in_bundle fields via saveFlatFields');
    }

    // ─── save() — bundle layout ───────────────────────────────────────────────

    public function testSaveBundleLayoutDelegatesToBundleSave(): void
    {
        $called = false;
        Functions\when('update_option')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $page = $this->makePage('settings', 'Settings', [
            'bundle', '_rows', [$this->fieldDef('name')],
        ]);

        $page->save(0, ['_rows' => [['name' => 'Alice'], ['name' => 'Bob']]]);

        $this->assertTrue($called, 'Bundle::save() must call update_option for each row');
    }

    public function testSaveBundleSkipsWhenBundleKeyNotInValues(): void
    {
        $called = false;
        Functions\when('update_option')->alias(function () use (&$called): bool {
            $called = true;
            return true;
        });

        $page = $this->makePage('settings', 'Settings', [
            'bundle', '_rows', [$this->fieldDef('name')],
        ]);

        $page->save(0, []); // bundle key '_rows' absent → no save

        $this->assertFalse($called);
    }

    // ─── registerRestOptions ──────────────────────────────────────────────────

    public function testRegisterRestOptionsSkipsWhenGlobalOptionDisabled(): void
    {
        Functions\when('get_option')->justReturn('0');
        Functions\expect('register_setting')->never();

        $page = $this->makePage('settings', 'Settings', [
            ['type' => 'text', 'id' => 'api_key', 'name' => 'api_key', 'label' => 'API Key', 'rest' => true],
        ]);
        $page->registerRestOptions();
        $this->addToAssertionCount(1);
    }

    public function testRegisterRestOptionsRegistersFieldWithRestTrue(): void
    {
        Functions\when('get_option')->justReturn('1');
        Functions\expect('register_setting')->once();

        $page = $this->makePage('settings', 'Settings', [
            ['type' => 'text', 'id' => 'api_key', 'name' => 'api_key', 'label' => 'API Key', 'rest' => true],
        ]);
        $page->registerRestOptions();
        $this->addToAssertionCount(1);
    }

    public function testRegisterRestOptionsSkipsFieldWithoutRestTrue(): void
    {
        Functions\when('get_option')->justReturn('1');
        Functions\expect('register_setting')->never();

        $page = $this->makePage('settings', 'Settings', [$this->fieldDef('site_name')]);
        $page->registerRestOptions();
        $this->addToAssertionCount(1);
    }

    public function testRegisterRestOptionsSkipsInBundleFieldsEvenWhenRest(): void
    {
        Functions\when('get_option')->justReturn('1');
        // in_bundle fields must never be individually registered — bundle itself handles REST
        Functions\expect('register_setting')->never();

        $page = $this->makePage('settings', 'Settings', [
            'bundle', '_rows', [
                array_merge($this->fieldDef('name'), ['rest' => true]),
            ],
        ]);
        $page->registerRestOptions();
        $this->addToAssertionCount(1);
    }

    public function testRegisterRestOptionsRegistersBundleMarkedAsRest(): void
    {
        Functions\when('get_option')->justReturn('1');
        Functions\expect('register_setting')->once();

        $page = $this->makePage('settings', 'Settings', [
            'bundle', '_rows', [$this->fieldDef('name')], ['rest' => true],
        ]);
        $page->registerRestOptions();
        $this->addToAssertionCount(1);
    }

    public function testRegisterRestOptionsRegistersMultipleRestFields(): void
    {
        Functions\when('get_option')->justReturn('1');
        Functions\expect('register_setting')->twice();

        $page = $this->makePage('settings', 'Settings', [
            ['type' => 'text', 'id' => 'field_a', 'name' => 'field_a', 'label' => 'A', 'rest' => true],
            ['type' => 'text', 'id' => 'field_b', 'name' => 'field_b', 'label' => 'B', 'rest' => true],
        ]);
        $page->registerRestOptions();
        $this->addToAssertionCount(1);
    }

    // ─── showValidationNotice — no-op ─────────────────────────────────────────

    public function testShowValidationNoticeIsNoOp(): void
    {
        $this->makePage()->showValidationNotice();
        $this->addToAssertionCount(1); // reaching here = no exception/call
    }
}
