<?php

namespace Weblitzer\CFDev\Tests\Unit\Updater;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;
use Weblitzer\CFDev\Updater\GitHubUpdater;

class GitHubUpdaterTest extends CFDevTestCase
{
    private const PLUGIN_FILE = '/app/wp-content/plugins/cfdev-plugin/cfdev-plugin.php';
    private const BASENAME    = 'cfdev-plugin/cfdev-plugin.php';
    private const VERSION     = '1.0.7';

    private function makeUpdater(string $version = self::VERSION): GitHubUpdater
    {
        return new GitHubUpdater(self::PLUGIN_FILE, $version);
    }

    private function makeTransient(): \stdClass
    {
        $t           = new \stdClass();
        $t->checked  = [self::BASENAME => self::VERSION];
        $t->response = [];
        return $t;
    }

    /** @return array{tag_name: string, published_at: string, body: string, download_url: string} */
    private function releaseData(string $tag = 'v1.0.8', string $url = 'https://example.com/cfdev-plugin-1.0.8.zip'): array
    {
        return [
            'tag_name'     => $tag,
            'published_at' => '2026-06-01T00:00:00Z',
            'body'         => 'Changelog for ' . $tag,
            'download_url' => $url,
        ];
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterAddsBothFilters(): void
    {
        Filters\expectAdded('pre_set_site_transient_update_plugins')->once();
        Filters\expectAdded('plugins_api')->once();
        $this->makeUpdater()->register();
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // injectUpdate() — early returns
    // -------------------------------------------------------------------------

    public function testInjectUpdateReturnsUnchangedTransientWhenCheckedIsEmpty(): void
    {
        $t          = new \stdClass();
        $t->checked = [];

        $result = $this->makeUpdater()->injectUpdate($t);

        $this->assertSame($t, $result);
        $this->assertFalse(isset($result->response));
    }

    public function testInjectUpdateDoesNotCallRemoteWhenCheckedIsEmpty(): void
    {
        Functions\expect('get_transient')->never();
        $t          = new \stdClass();
        $t->checked = [];
        $this->makeUpdater()->injectUpdate($t);
        $this->addToAssertionCount(1);
    }

    public function testInjectUpdateLeavesResponseEmptyWhenCachedFailure(): void
    {
        Functions\when('get_transient')->justReturn([]); // [] = cached failure sentinel
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertEmpty($t->response);
    }

    // -------------------------------------------------------------------------
    // injectUpdate() — version comparison
    // -------------------------------------------------------------------------

    public function testInjectUpdateDoesNotInjectWhenVersionIsEqual(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.7'));
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertArrayNotHasKey(self::BASENAME, $t->response);
    }

    public function testInjectUpdateDoesNotInjectWhenVersionIsOlder(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.6'));
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertArrayNotHasKey(self::BASENAME, $t->response);
    }

    // -------------------------------------------------------------------------
    // injectUpdate() — successful injection
    // -------------------------------------------------------------------------

    public function testInjectUpdateAddsResponseForNewerVersion(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertArrayHasKey(self::BASENAME, $t->response);
    }

    public function testInjectUpdateSetsNewVersion(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.8'));
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertSame('1.0.8', $t->response[self::BASENAME]->new_version);
    }

    public function testInjectUpdateSetsSlug(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertSame('cfdev-plugin', $t->response[self::BASENAME]->slug);
    }

    public function testInjectUpdateSetsPackageUrl(): void
    {
        $url = 'https://example.com/cfdev-plugin-1.0.8.zip';
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.8', $url));
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);
        $this->assertSame($url, $t->response[self::BASENAME]->package);
    }

    public function testInjectUpdateInitializesResponseWhenPropertyAbsent(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        $t          = new \stdClass();
        $t->checked = [self::BASENAME => self::VERSION];
        // deliberately no ->response
        $this->makeUpdater()->injectUpdate($t);
        $this->assertArrayHasKey(self::BASENAME, $t->response);
    }

    // -------------------------------------------------------------------------
    // pluginInfo() — pass-through cases
    // -------------------------------------------------------------------------

    public function testPluginInfoPassesThroughOnWrongAction(): void
    {
        $result = $this->makeUpdater()->pluginInfo(false, 'query_plugins', (object) ['slug' => 'cfdev-plugin']);
        $this->assertFalse($result);
    }

    public function testPluginInfoPassesThroughOnSlugMismatch(): void
    {
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'other-plugin']);
        $this->assertFalse($result);
    }

    public function testPluginInfoPassesThroughWhenReleaseNull(): void
    {
        Functions\when('get_transient')->justReturn([]); // cached failure
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertFalse($result);
    }

    public function testPluginInfoPassesThroughExistingObjectResult(): void
    {
        $existing = (object) ['name' => 'Other Plugin'];
        $result   = $this->makeUpdater()->pluginInfo($existing, 'query_plugins', (object) ['slug' => 'other-plugin']);
        $this->assertSame($existing, $result);
    }

    // -------------------------------------------------------------------------
    // pluginInfo() — returns plugin info
    // -------------------------------------------------------------------------

    public function testPluginInfoReturnsObject(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertIsObject($result);
    }

    public function testPluginInfoSetsVersion(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.8'));
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertIsObject($result);
        $data = (array) $result;
        $this->assertSame('1.0.8', $data['version']);
    }

    public function testPluginInfoSetsSlug(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertIsObject($result);
        $data = (array) $result;
        $this->assertSame('cfdev-plugin', $data['slug']);
    }

    public function testPluginInfoSetsDownloadLink(): void
    {
        $url = 'https://example.com/cfdev-plugin-1.0.8.zip';
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.8', $url));
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertIsObject($result);
        $data = (array) $result;
        $this->assertSame($url, $data['download_link']);
    }

    public function testPluginInfoSetsLastUpdated(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertIsObject($result);
        $data = (array) $result;
        $this->assertSame('2026-06-01T00:00:00Z', $data['last_updated']);
    }

    public function testPluginInfoSetsChangelogInSections(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData('v1.0.8'));
        $result = $this->makeUpdater()->pluginInfo(false, 'plugin_information', (object) ['slug' => 'cfdev-plugin']);
        $this->assertIsObject($result);
        $data = (array) $result;
        $this->assertIsArray($data['sections']);
        $this->assertArrayHasKey('changelog', $data['sections']);
        $this->assertSame('Changelog for v1.0.8', $data['sections']['changelog']);
    }

    // -------------------------------------------------------------------------
    // fetchRelease() via injectUpdate() — cache
    // -------------------------------------------------------------------------

    public function testFetchReleaseUsesCacheAndSkipsRemoteRequest(): void
    {
        Functions\when('get_transient')->justReturn($this->releaseData());
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        Functions\expect('wp_remote_get')->never();
        $this->makeUpdater()->injectUpdate($this->makeTransient());
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // fetchRelease() via injectUpdate() — remote request
    // -------------------------------------------------------------------------

    public function testFetchReleaseCachesFailureOnWpError(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn(new \WP_Error('http_error', 'Connection failed'));
        Functions\when('is_wp_error')->justReturn(true);
        Functions\expect('set_transient')->once()->with('cfdev_github_release', [], 300);
        $this->makeUpdater()->injectUpdate($this->makeTransient());
        $this->addToAssertionCount(1);
    }

    public function testFetchReleaseCachesFailureOnNon200Response(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn(['status' => 404]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(404);
        Functions\expect('set_transient')->once()->with('cfdev_github_release', [], 300);
        $this->makeUpdater()->injectUpdate($this->makeTransient());
        $this->addToAssertionCount(1);
    }

    public function testFetchReleaseCachesFailureWhenTagNameMissing(): void
    {
        $body = (string) json_encode(['name' => 'CFDev']); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\expect('set_transient')->once()->with('cfdev_github_release', [], 300);
        $this->makeUpdater()->injectUpdate($this->makeTransient());
        $this->addToAssertionCount(1);
    }

    public function testFetchReleaseCachesSuccessWithTtl(): void
    {
        $body = (string) json_encode([ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
            'tag_name'     => 'v1.0.8',
            'published_at' => '2026-06-01T00:00:00Z',
            'body'         => '',
            'assets'       => [],
        ]);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\when('plugin_basename')->justReturn(self::BASENAME);
        Functions\expect('set_transient')->once()->with('cfdev_github_release', \Mockery::type('array'), 43200);
        $this->makeUpdater()->injectUpdate($this->makeTransient());
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // fetchRelease() — download URL resolution
    // -------------------------------------------------------------------------

    public function testFetchReleaseFallsBackToConstructedUrl(): void
    {
        $body = (string) json_encode([ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
            'tag_name'     => 'v1.0.8',
            'published_at' => '',
            'body'         => '',
            'assets'       => [],
        ]);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('plugin_basename')->justReturn(self::BASENAME);

        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);

        $this->assertStringContainsString(
            'cfdev-plugin-1.0.8.zip',
            $t->response[self::BASENAME]->package
        );
    }

    public function testFetchReleasePrefersAssetOverConstructedUrl(): void
    {
        $assetUrl = 'https://objects.githubusercontent.com/asset/cfdev-plugin-1.0.8.zip';
        $body     = (string) json_encode([ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
            'tag_name'     => 'v1.0.8',
            'published_at' => '',
            'body'         => '',
            'assets'       => [
                [
                    'name'                 => 'cfdev-plugin-1.0.8.zip',
                    'browser_download_url' => $assetUrl,
                ],
            ],
        ]);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('plugin_basename')->justReturn(self::BASENAME);

        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);

        $this->assertSame($assetUrl, $t->response[self::BASENAME]->package);
    }

    public function testFetchReleaseIgnoresNonZipAssets(): void
    {
        $body = (string) json_encode([ // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
            'tag_name'     => 'v1.0.8',
            'published_at' => '',
            'body'         => '',
            'assets'       => [
                [
                    'name'                 => 'checksums.txt',
                    'browser_download_url' => 'https://example.com/checksums.txt',
                ],
            ],
        ]);
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_get')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('plugin_basename')->justReturn(self::BASENAME);

        $t = $this->makeTransient();
        $this->makeUpdater()->injectUpdate($t);

        $this->assertStringContainsString(
            'cfdev-plugin-1.0.8.zip',
            $t->response[self::BASENAME]->package
        );
        $this->assertStringNotContainsString('checksums.txt', $t->response[self::BASENAME]->package);
    }
}
