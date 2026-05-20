<?php

namespace Weblitzer\CFDev\Tests\Unit\Cache;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Weblitzer\CFDev\Cache\CacheManager;
use Weblitzer\CFDev\Cache\CacheStore;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class CacheManagerTest extends CFDevTestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/cfdev-manager-' . uniqid();
        mkdir($this->tmpDir, 0755, true); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir

        Functions\when('wp_upload_dir')->justReturn(['basedir' => $this->tmpDir]);
        Functions\when('trailingslashit')->alias(fn(string $s): string => rtrim($s, '/') . '/');
        Functions\when('wp_mkdir_p')->alias(function (string $dir): bool {
            return is_dir($dir) || mkdir($dir, 0755, true); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
        });
        Functions\when('sanitize_file_name')->returnArg();
        Functions\when('wp_json_encode')->alias('json_encode');
    }

    protected function tearDown(): void
    {
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
            is_dir($path) ? $this->removeDirRecursive($path) : unlink($path); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
        }
        rmdir($dir); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_rmdir
    }

    private function makeManager(): CacheManager
    {
        return new CacheManager();
    }

    // -------------------------------------------------------------------------
    // store()
    // -------------------------------------------------------------------------

    public function testStoreReturnsCacheStoreInstance(): void
    {
        $this->assertInstanceOf(CacheStore::class, $this->makeManager()->store());
    }

    // -------------------------------------------------------------------------
    // register()
    // -------------------------------------------------------------------------

    public function testRegisterAddsHookForSavePost(): void
    {
        Actions\expectAdded('save_post')->once();
        $this->makeManager()->register();
        $this->addToAssertionCount(1);
    }

    public function testRegisterAddsHookForEditedTerm(): void
    {
        Actions\expectAdded('edited_term')->once();
        $this->makeManager()->register();
        $this->addToAssertionCount(1);
    }

    public function testRegisterAddsHookForDeleteTerm(): void
    {
        Actions\expectAdded('delete_term')->once();
        $this->makeManager()->register();
        $this->addToAssertionCount(1);
    }

    public function testRegisterAddsHookForProfileUpdate(): void
    {
        Actions\expectAdded('profile_update')->once();
        $this->makeManager()->register();
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // invalidatePost()
    // -------------------------------------------------------------------------

    public function testInvalidatePostDeletesCorrectCacheFile(): void
    {
        $manager = $this->makeManager();
        $manager->store()->write('post_42', ['post_id' => 42]);

        $this->assertTrue($manager->store()->exists('post_42'));

        $manager->invalidatePost(42);

        $this->assertFalse($manager->store()->exists('post_42'));
    }

    public function testInvalidatePostDoesNothingWhenFileAbsent(): void
    {
        $manager = $this->makeManager();
        $manager->invalidatePost(99); // must not throw
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // invalidateTerm()
    // -------------------------------------------------------------------------

    public function testInvalidateTermDeletesCorrectCacheFile(): void
    {
        $manager = $this->makeManager();
        $manager->store()->write('term_category_5', ['term_id' => 5]);

        $manager->invalidateTerm(5, 'category');

        $this->assertFalse($manager->store()->exists('term_category_5'));
    }

    // -------------------------------------------------------------------------
    // invalidateUser()
    // -------------------------------------------------------------------------

    public function testInvalidateUserDeletesCorrectCacheFile(): void
    {
        $manager = $this->makeManager();
        $manager->store()->write('user_7', ['user_id' => 7]);

        $manager->invalidateUser(7);

        $this->assertFalse($manager->store()->exists('user_7'));
    }

    // -------------------------------------------------------------------------
    // invalidateAll()
    // -------------------------------------------------------------------------

    public function testInvalidateAllDeletesAllCacheFiles(): void
    {
        $manager = $this->makeManager();
        $manager->store()->write('post_1', ['post_id' => 1]);
        $manager->store()->write('post_2', ['post_id' => 2]);
        $manager->store()->write('user_3', ['user_id' => 3]);

        $deleted = $manager->invalidateAll();

        $this->assertSame(3, $deleted);
        $this->assertFalse($manager->store()->exists('post_1'));
        $this->assertFalse($manager->store()->exists('post_2'));
        $this->assertFalse($manager->store()->exists('user_3'));
    }

    public function testInvalidateAllOnEmptyCacheReturnsZero(): void
    {
        $manager = $this->makeManager();
        $this->assertSame(0, $manager->invalidateAll());
    }

    // -------------------------------------------------------------------------
    // post() — cache disabled
    // -------------------------------------------------------------------------

    public function testPostWithCacheDisabledGeneratesDataWithoutWritingToStore(): void
    {
        Functions\when('get_option')->justReturn(false);   // cache disabled
        Functions\when('get_post_type')->justReturn('post');

        $manager = $this->makeManager();
        $result  = $manager->post(42);

        $this->assertArrayHasKey('post_id', $result);
        $this->assertSame(42, $result['post_id']);
        $this->assertFalse($manager->store()->exists('post_42'));
    }

    // -------------------------------------------------------------------------
    // post() — cache enabled, reading from store
    // -------------------------------------------------------------------------

    public function testPostWithCacheEnabledAndFreshFileReadsFromStore(): void
    {
        Functions\when('get_option')->justReturn(true);   // cache enabled

        $manager  = $this->makeManager();
        $expected = ['post_id' => 10, 'generated_at' => time(), 'groups' => ['hero' => ['title' => 'Test']]];
        $manager->store()->write('post_10', $expected);

        $result = $manager->post(10);

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // post() — cache enabled, writing to store
    // -------------------------------------------------------------------------

    public function testPostWithCacheEnabledWritesGeneratedDataToStore(): void
    {
        Functions\when('get_option')->justReturn(true);   // cache enabled
        Functions\when('get_post_type')->justReturn('post');

        $manager = $this->makeManager();
        $result  = $manager->post(55);

        $this->assertTrue($manager->store()->exists('post_55'));
        $this->assertSame(55, $result['post_id']);
    }

    // -------------------------------------------------------------------------
    // post() — force regeneration
    // -------------------------------------------------------------------------

    public function testPostWithForceTrueRegeneratesEvenWhenCacheExists(): void
    {
        Functions\when('get_option')->justReturn(true);
        Functions\when('get_post_type')->justReturn('post');

        $manager = $this->makeManager();

        // Pre-populate with stale data
        $manager->store()->write('post_7', ['post_id' => 7, 'groups' => ['old' => 'data'], 'generated_at' => time() - 1000]);

        $result = $manager->post(7, force: true);

        // Generated fresh — groups should be empty since Registry is empty
        $this->assertSame([], $result['groups']);
    }

    // -------------------------------------------------------------------------
    // term() — cache disabled
    // -------------------------------------------------------------------------

    public function testTermWithCacheDisabledGeneratesData(): void
    {
        Functions\when('get_option')->justReturn(false);

        $manager = $this->makeManager();
        $result  = $manager->term(3, 'category');

        $this->assertArrayHasKey('term_id', $result);
        $this->assertSame(3, $result['term_id']);
        $this->assertSame('category', $result['taxonomy']);
    }

    // -------------------------------------------------------------------------
    // user() — cache disabled
    // -------------------------------------------------------------------------

    public function testUserWithCacheDisabledGeneratesData(): void
    {
        Functions\when('get_option')->justReturn(false);

        $manager = $this->makeManager();
        $result  = $manager->user(8);

        $this->assertArrayHasKey('user_id', $result);
        $this->assertSame(8, $result['user_id']);
    }
}
