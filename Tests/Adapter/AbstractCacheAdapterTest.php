<?php declare(strict_types=1);

namespace Tests\Temant\Cache\Adapter {

    use DateInterval;
    use PHPUnit\Framework\TestCase;
    use Temant\Cache\CacheItem;
    use Temant\Cache\Interface\CacheAdapterInterface;

    /**
     * Class AbstractCacheAdapterTest
     *
     * Abstract test class for common cache adapter tests.
     */
    abstract class AbstractCacheAdapterTest extends TestCase
    {
        protected CacheAdapterInterface $cachePool;

        protected function tearDown(): void
        {
            $this->cachePool->clear();
        }

        /**
         * Test getting the total number of cache items.
         */
        public function testGetItemCount(): void
        {
            $this->cachePool->clear();
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $this->assertEquals(2, $this->cachePool->getItemCount(), 'Item count should be equal to 2.');
        }

        /**
         * Test saving and retrieving an item from the cache.
         */
        public function testSaveAndRetrieveItem(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $this->cachePool->save($item);

            $retrievedItem = $this->cachePool->getItem('test_key');
            $this->assertTrue($retrievedItem->isHit(), 'The cache item should be a hit.');
            $this->assertEquals('test_value', $retrievedItem->get(), 'The cache value should match the stored value.');
        }

        /**
         * Test item expiration functionality.
         */
        public function testItemExpiration(): void
        {
            $item = new CacheItem('expiring_key', 'expiring_value');
            $item->expiresAfter(0);
            $this->cachePool->save($item);

            $retrievedItem = $this->cachePool->getItem('expiring_key');
            $this->assertFalse($retrievedItem->isHit(), 'The cache item should have expired.');
        }

        /**
         * Test deleting a single cache item.
         */
        public function testDeleteItem(): void
        {
            $item = new CacheItem('delete_key', 'delete_value');
            $this->cachePool->save($item);

            $this->assertTrue($this->cachePool->hasItem('delete_key'), 'Cache item should exist before deletion.');
            $this->cachePool->deleteItem('delete_key');
            $this->assertFalse($this->cachePool->hasItem('delete_key'), 'Cache item should not exist after deletion.');

            $this->assertFalse($this->cachePool->deleteItem('delete_key'));
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

            $this->cachePool->deleteItems(['key1', 'key2']);

            $this->assertFalse($this->cachePool->hasItem('key1'), 'Item 1 should be deleted.');
            $this->assertFalse($this->cachePool->hasItem('key2'), 'Item 2 should be deleted.');
        }

        /**
         * Test clearing the entire cache.
         */
        public function testClearCache(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            $this->cachePool->clear();

            // Ensure the cache is cleared
            $this->assertFalse($this->cachePool->hasItem('key1'), 'First cache item should be cleared.');
            $this->assertFalse($this->cachePool->hasItem('key2'), 'Second cache item should be cleared.');
        }

        /**
         * Test deferred saving of cache items and committing them to memory.
         */
        public function testDeferredSaveAndCommit(): void
        {
            $item = new CacheItem('deferred_key', 'deferred_value');
            $this->cachePool->saveDeferred($item);

            // Item should not exist until commit is called
            $this->assertFalse($this->cachePool->hasItem('deferred_key'), 'Deferred cache item should not exist before commit.');

            // Commit deferred items to memory
            $this->cachePool->commit();

            // Now the item should exist
            $this->assertTrue($this->cachePool->hasItem('deferred_key'), 'Deferred cache item should exist after commit.');
            $this->assertEquals('deferred_value', $this->cachePool->getItem('deferred_key')->get(), 'Deferred cache item should have the correct value.');
        }

        /**
         * Test retrieving multiple cache items at once.
         */
        public function testGetItems(): void
        {
            $item1 = new CacheItem('key1', 'value1');
            $item2 = new CacheItem('key2', 'value2');
            $this->cachePool->save($item1);
            $this->cachePool->save($item2);

            // Fetch multiple items
            $items = (array) $this->cachePool->getItems(['key1', 'key2', 'non_existent_key']);

            // Check keys in the returned array
            $this->assertArrayHasKey('key1', $items, 'First cache item should be in the result.');
            $this->assertArrayHasKey('key2', $items, 'Second cache item should be in the result.');
            $this->assertArrayHasKey('non_existent_key', $items, 'Non-existent cache item should be in the result.');

            // Validate the values and hit statuses
            $this->assertTrue($items['key1']->isHit(), 'First cache item should be a hit.');
            $this->assertEquals('value1', $items['key1']->get(), 'First cache item value should match.');

            $this->assertTrue($items['key2']->isHit(), 'Second cache item should be a hit.');
            $this->assertEquals('value2', $items['key2']->get(), 'Second cache item value should match.');

            $this->assertFalse($items['non_existent_key']->isHit(), 'Non-existent cache item should not be a hit.');
        }

        /**
         * Test that the correct cache size is retrieved.
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
         * Test retrieving a non-existent item.
         */
        public function testGetNonExistentItem(): void
        {
            $item = $this->cachePool->getItem('non_existent');
            $this->assertFalse($item->isHit(), 'Non-existent item should not be a hit.');
            $this->assertNull($item->get(), 'Non-existent item should return null as its value.');
        }

        /**
         * Test saving an item without an expiration.
         */
        public function testSaveItemWithoutExpiration(): void
        {
            $item = new CacheItem('key_no_expiration', 'value_no_expiration');
            $this->cachePool->save($item);

            $retrievedItem = $this->cachePool->getItem('key_no_expiration');
            $this->assertTrue($retrievedItem->isHit(), 'Item without expiration should be a hit.');
            $this->assertEquals('value_no_expiration', $retrievedItem->get(), 'Value should match for item without expiration.');
        }

        /**
         * Test accuracy of item expiration with millisecond precision simulation.
         */
        public function testExpirationAccuracy(): void
        {
            $item = new CacheItem('expiring_soon', 'expiring_value');

            // Set expiration to 1 second (since expiresAfter doesn't support milliseconds)
            $item->expiresAfter(1); // Expires after 1 second
            $this->cachePool->save($item);

            // Assert that the item is still a hit 
            $this->assertTrue($this->cachePool->getItem('expiring_soon')->isHit(), 'Item should be a hit.');

            // Simulate time passing just beyond the expiration time  
            sleep(2);
            $this->assertFalse($this->cachePool->getItem('expiring_soon')->isHit(), 'Item should expire after 2 seconds.');
        }

    }
}