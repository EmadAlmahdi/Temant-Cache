<?php declare(strict_types=1);

namespace Tests\Temant\Cache\Adapter {

    use PHPUnit\Framework\TestCase;
    use Temant\Cache\Adapter\FileSystemCacheAdapter;
    use Temant\Cache\CacheItem;
    use Temant\Cache\Exception\CacheException;
    use org\bovigo\vfs\vfsStream;

    /**
     * Class FileSystemCacheAdapterTest
     *
     * Unit tests for the FileSystemCacheAdapter class, covering saving, retrieving, expiration handling, cache hits,
     * deferred saves, and cache clearing.
     */
    class FileSystemCacheAdapterTest extends TestCase
    {
        private string $cacheDir;
        private FileSystemCacheAdapter $cachePool;

        protected function setUp(): void
        {
            // Set up a temporary directory for testing
            $this->cacheDir = sys_get_temp_dir() . '/file_cache_test';
            $this->cachePool = new FileSystemCacheAdapter($this->cacheDir);

            // Ensure the directory is clean before running tests
            if (is_dir($this->cacheDir)) {
                $this->deleteDirectory($this->cacheDir);
            }
            mkdir($this->cacheDir, 0777, true);
        }

        protected function tearDown(): void
        {
            // Clean up the temporary directory after tests
            $this->deleteDirectory($this->cacheDir);
        }

        private function deleteDirectory(string $dir): void
        {
            if (!is_dir($dir)) {
                return;
            }

            $files = array_diff((array) scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $filePath = "$dir/$file";
                is_dir($filePath) ? $this->deleteDirectory($filePath) : unlink($filePath);
            }

            rmdir($dir);
        }

        /**
         * Test that the constructor throws CacheException when the cache directory cannot be created.
         */
        public function testConstructorThrowsExceptionOnDirectoryCreationFailure(): void
        {
            // Set up the virtual file system with a read-only root directory
            vfsStream::setup('root', 0444); // Read-only permission (no write access)

            // Expect the CacheException to be thrown due to inability to create directory
            $this->expectException(CacheException::class);
            $this->expectExceptionMessage("Failed to create cache directory: vfs://root/cache_test");

            // Try to instantiate FileSystemCacheAdapter with a directory that cannot be created
            new FileSystemCacheAdapter(vfsStream::url('root/cache_test'));
        }

        /**
         * Test that a cache item can be saved and retrieved correctly.
         */
        public function testSaveAndRetrieveItem(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $this->cachePool->save($item);

            $retrievedItem = $this->cachePool->getItem('test_key');
            $this->assertTrue($retrievedItem->isHit(), 'Cache item should be a hit.');
            $this->assertEquals('test_value', $retrievedItem->get(), 'Cache value should match the saved value.');
        }

        /**
         * Test that a cache item expires after the given time.
         */
        public function testItemExpiration(): void
        {
            $item = new CacheItem('expiring_key', 'expiring_value');
            $item->expiresAfter(1); // Expires after 1 second
            $this->cachePool->save($item);

            sleep(2); // Wait 2 seconds to let the cache expire

            $retrievedItem = $this->cachePool->getItem('expiring_key');
            $this->assertFalse($retrievedItem->isHit(), 'Cache item should have expired.');
        }

        /**
         * Test that a cache item can be deleted.
         */
        public function testDeleteItem(): void
        {
            $item = new CacheItem('delete_key', 'delete_value');
            $this->cachePool->save($item);

            $this->assertTrue($this->cachePool->hasItem('delete_key'), 'Item should exist in the cache.');
            $this->cachePool->deleteItem('delete_key');
            $this->assertFalse($this->cachePool->hasItem('delete_key'), 'Item should be deleted from the cache.');

            $this->assertFalse($this->cachePool->hasItem('delete_key'), 'Item should be deleted from the cache.');
            $this->assertFalse($this->cachePool->deleteItem('delete_key'));

            /** NOT FOUND */
        }

        /**
         * Test that all cache items can be cleared.
         */
        public function testClearCache(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $this->assertTrue($this->cachePool->hasItem('key1'), 'Item key1 should exist.');
            $this->assertTrue($this->cachePool->hasItem('key2'), 'Item key2 should exist.');

            $this->cachePool->clear();

            $this->assertFalse($this->cachePool->hasItem('key1'), 'Item key1 should be cleared.');
            $this->assertFalse($this->cachePool->hasItem('key2'), 'Item key2 should be cleared.');
        }

        /**
         * Test deferred saving and committing.
         */
        public function testDeferredSaveAndCommit(): void
        {
            $item = new CacheItem('deferred_key', 'deferred_value');
            $this->cachePool->saveDeferred($item);

            // Item should not be saved yet
            $this->assertFalse($this->cachePool->hasItem('deferred_key'), 'Deferred item should not be saved yet.');

            // Commit deferred items
            $this->cachePool->commit();

            // Now the item should be saved
            $this->assertTrue($this->cachePool->hasItem('deferred_key'), 'Deferred item should be saved after commit.');
            $this->assertEquals('deferred_value', $this->cachePool->getItem('deferred_key')->get(), 'Deferred value should match after commit.');
        }

        /**
         * Test that multiple cache items can be retrieved at once.
         */
        public function testGetItems(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $items = $this->cachePool->getItems(['key1', 'key2', 'non_existent_key']);

            $this->assertArrayHasKey('key1', $items, 'Retrieved items should include key1.');
            $this->assertArrayHasKey('key2', $items, 'Retrieved items should include key2.');
            $this->assertArrayHasKey('non_existent_key', $items, 'Retrieved items should include non_existent_key.');

            $this->assertTrue($items['key1']->isHit(), 'Key1 should be a hit.');
            $this->assertTrue($items['key2']->isHit(), 'Key2 should be a hit.');
            $this->assertFalse($items['non_existent_key']->isHit(), 'Non-existent key should not be a hit.');
        }

        /**
         * Test that multiple cache items can be deleted at once.
         */
        public function testDeleteItems(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $this->assertTrue($this->cachePool->hasItem('key1'), 'Key1 should exist.');
            $this->assertTrue($this->cachePool->hasItem('key2'), 'Key2 should exist.');

            // Delete multiple items
            $this->cachePool->deleteItems(['key1', 'key2']);

            $this->assertFalse($this->cachePool->hasItem('key1'), 'Key1 should be deleted.');
            $this->assertFalse($this->cachePool->hasItem('key2'), 'Key2 should be deleted.');
        }

        /**
         * Test that retrieving a non-existent item returns a cache miss.
         */
        public function testGetNonExistentItem(): void
        {
            $item = $this->cachePool->getItem('non_existent');
            $this->assertFalse($item->isHit(), 'Non-existent item should not be a hit.');
            $this->assertNull($item->get(), 'Non-existent item should return null.');
        }

        /**
         * Test that a cache item can be saved without an expiration time.
         */
        public function testSaveItemWithoutExpiration(): void
        {
            $item = new CacheItem('key_no_expiration', 'value_no_expiration');
            $this->cachePool->save($item);

            $retrievedItem = $this->cachePool->getItem('key_no_expiration');
            $this->assertTrue($retrievedItem->isHit(), 'Cache item should be a hit.');
            $this->assertEquals('value_no_expiration', $retrievedItem->get(), 'Retrieved value should match the saved value.');
        }

        /**
         * Test that expiration is handled accurately, including precise timing.
         */
        public function testExpirationAccuracy(): void
        {
            $item = new CacheItem('expiring_soon', 'expiring_value');
            $item->expiresAfter(2); // Expires after 2 seconds
            $this->cachePool->save($item);

            sleep(1); // Wait 1 second (item should still be valid)
            $this->assertTrue($this->cachePool->getItem('expiring_soon')->isHit(), 'Item should still be valid after 1 second.');

            sleep(2); // Wait another 2 seconds (item should be expired)
            $this->assertFalse($this->cachePool->getItem('expiring_soon')->isHit(), 'Item should be expired after 3 seconds.');
        }

        /**
         * Test that the cache size is correctly calculated.
         */
        public function testGetCacheSize(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $cacheSize = $this->cachePool->getCacheSize();
            $this->assertGreaterThan(0, $cacheSize, 'Cache size should be greater than 0 after items are saved.');
        }

        /**
         * Test that the correct number of cache items is reported.
         */
        public function testGetItemCount(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $itemCount = $this->cachePool->getItemCount();
            $this->assertEquals(2, $itemCount, 'Item count should be 2 after two items are saved.');
        }
    }
}
