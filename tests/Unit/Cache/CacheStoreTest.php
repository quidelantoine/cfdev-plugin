<?php

namespace Weblitzer\CFDev\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use Weblitzer\CFDev\Cache\CacheStore;
use Weblitzer\CFDev\Tests\Unit\CFDevTestCase;

class CacheStoreTest extends CFDevTestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = sys_get_temp_dir() . '/cfdev-store-' . uniqid();
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

    private function makeStore(): CacheStore
    {
        return new CacheStore();
    }

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function testConstructorCreatesCacheSubDirectory(): void
    {
        $this->makeStore();
        $this->assertDirectoryExists($this->tmpDir . '/cfdev-cache');
    }

    public function testConstructorWritesHtaccessFile(): void
    {
        $this->makeStore();
        $this->assertFileExists($this->tmpDir . '/cfdev-cache/.htaccess');
    }

    public function testDirReturnsTrailingSlashedCacheDir(): void
    {
        $store = $this->makeStore();
        $this->assertSame($this->tmpDir . '/cfdev-cache/', $store->dir());
    }

    // -------------------------------------------------------------------------
    // write() + read()
    // -------------------------------------------------------------------------

    public function testWriteAndReadRoundtrip(): void
    {
        $store = $this->makeStore();
        $data  = ['post_id' => 42, 'title' => 'Hello', 'groups' => ['g1' => ['field' => 'val']]];

        $store->write('post_42', $data);
        $result = $store->read('post_42');

        $this->assertSame($data, $result);
    }

    public function testReadReturnsNullForMissingKey(): void
    {
        $store = $this->makeStore();
        $this->assertNull($store->read('non_existent'));
    }

    public function testWriteOverwritesExistingFile(): void
    {
        $store = $this->makeStore();
        $store->write('key_x', ['v' => 1]);
        $store->write('key_x', ['v' => 2]);

        $this->assertSame(['v' => 2], $store->read('key_x'));
    }

    // -------------------------------------------------------------------------
    // exists()
    // -------------------------------------------------------------------------

    public function testExistsReturnsFalseForMissingKey(): void
    {
        $store = $this->makeStore();
        $this->assertFalse($store->exists('missing'));
    }

    public function testExistsReturnsTrueAfterWrite(): void
    {
        $store = $this->makeStore();
        $store->write('post_1', ['x' => 1]);
        $this->assertTrue($store->exists('post_1'));
    }

    // -------------------------------------------------------------------------
    // age()
    // -------------------------------------------------------------------------

    public function testAgeReturnsPhpIntMaxForMissingKey(): void
    {
        $store = $this->makeStore();
        $this->assertSame(PHP_INT_MAX, $store->age('missing'));
    }

    public function testAgeIsSmallImmediatelyAfterWrite(): void
    {
        $store = $this->makeStore();
        $store->write('fresh', ['data' => true]);
        $this->assertLessThan(5, $store->age('fresh'));
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function testDeleteRemovesExistingFile(): void
    {
        $store = $this->makeStore();
        $store->write('to_delete', ['x' => 1]);
        $store->delete('to_delete');
        $this->assertFalse($store->exists('to_delete'));
    }

    public function testDeleteOnNonExistentKeyDoesNotThrow(): void
    {
        $store = $this->makeStore();
        $store->delete('no_such_key'); // must not throw
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // deleteAll()
    // -------------------------------------------------------------------------

    public function testDeleteAllReturnsCountOfDeletedFiles(): void
    {
        $store = $this->makeStore();
        $store->write('a', ['x' => 1]);
        $store->write('b', ['x' => 2]);
        $store->write('c', ['x' => 3]);

        $count = $store->deleteAll();

        $this->assertSame(3, $count);
    }

    public function testDeleteAllRemovesAllTmpFiles(): void
    {
        $store = $this->makeStore();
        $store->write('p1', ['x' => 1]);
        $store->write('p2', ['x' => 2]);
        $store->deleteAll();

        $this->assertFalse($store->exists('p1'));
        $this->assertFalse($store->exists('p2'));
    }

    public function testDeleteAllOnEmptyDirReturnsZero(): void
    {
        $store = $this->makeStore();
        $this->assertSame(0, $store->deleteAll());
    }

    // -------------------------------------------------------------------------
    // listAll()
    // -------------------------------------------------------------------------

    public function testListAllReturnsOneEntryPerWrittenFile(): void
    {
        $store = $this->makeStore();
        $store->write('post_1', ['x' => 1]);
        $store->write('post_2', ['x' => 2]);

        $list = $store->listAll();

        $this->assertCount(2, $list);
    }

    public function testListAllEntryHasExpectedKeys(): void
    {
        $store = $this->makeStore();
        $store->write('post_10', ['x' => 1]);

        $entry = $store->listAll()[0];

        $this->assertArrayHasKey('key', $entry);
        $this->assertArrayHasKey('path', $entry);
        $this->assertArrayHasKey('size', $entry);
        $this->assertArrayHasKey('age', $entry);
        $this->assertArrayHasKey('modified', $entry);
        $this->assertSame('post_10', $entry['key']);
    }

    public function testListAllIsSortedByModifiedDescending(): void
    {
        $store = $this->makeStore();
        $store->write('old_entry', ['v' => 1]);

        // Set mtime in the past so 'old_entry' is older
        $oldPath = $this->tmpDir . '/cfdev-cache/old_entry.tmp';
        touch($oldPath, time() - 100); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_touch

        $store->write('new_entry', ['v' => 2]);

        $list = $store->listAll();

        $this->assertSame('new_entry', $list[0]['key']);
        $this->assertSame('old_entry', $list[1]['key']);
    }

    public function testListAllReturnsEmptyArrayWhenNoFiles(): void
    {
        $store = $this->makeStore();
        $this->assertSame([], $store->listAll());
    }
}
