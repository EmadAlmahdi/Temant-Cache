<?php declare(strict_types=1);

namespace Tests\Temant\Cache {

    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use Temant\Cache\CacheManager;
    use Temant\Cache\CacheItem;
    use Temant\Cache\Interface\CacheAdapterInterface;
    use InvalidArgumentException;

    /**
     * Class CacheManagerTest
     *
     * Unit tests for the CacheManager class.
     */
    class CacheManagerTest extends TestCase
    {
        /** @var MockObject&CacheAdapterInterface */
        private $defaultAdapter;

        private CacheManager $cacheManager;

        protected function setUp(): void
        {
            $this->defaultAdapter = $this->createMock(CacheAdapterInterface::class);

            $this->cacheManager = new CacheManager($this->defaultAdapter);
        }

        /**
         * Test that the default adapter is set in the constructor.
         */
        public function testConstructorSetsDefaultAdapter(): void
        {
            $this->assertSame($this->defaultAdapter, $this->cacheManager->getAdapter());
        }

        /**
         * Test that a cache item can be retrieved.
         */
        public function testGetItem(): void
        {
            $cacheItem = new CacheItem('test_key', 'test_value');

            $this->defaultAdapter
                ->expects($this->once())
                ->method('getItem')
                ->with('test_key')
                ->willReturn($cacheItem);

            $result = $this->cacheManager->getItem('test_key');

            $this->assertSame($cacheItem, $result);
        }

        /**
         * Test that multiple cache items can be retrieved.
         */
        public function testGetItems(): void
        {
            $items = ['key1' => new CacheItem('key1', 'value1'), 'key2' => new CacheItem('key2', 'value2')];

            $this->defaultAdapter
                ->expects($this->once())
                ->method('getItems')
                ->with(['key1', 'key2'])
                ->willReturn($items);

            $result = $this->cacheManager->getItems(['key1', 'key2']);

            $this->assertSame($items, $result);
        }

        /**
         * Test that the hasItem method works correctly.
         */
        public function testHasItem(): void
        {
            $this->defaultAdapter
                ->expects($this->once())
                ->method('hasItem')
                ->with('test_key')
                ->willReturn(true);

            $result = $this->cacheManager->hasItem('test_key');

            $this->assertTrue($result);
        }

        /**
         * Test that the clear method works correctly.
         */
        public function testClear(): void
        {
            $this->defaultAdapter
                ->expects($this->once())
                ->method('clear')
                ->willReturn(true);

            $result = $this->cacheManager->clear();

            $this->assertTrue($result);
        }

        /**
         * Test that a cache item can be deleted.
         */
        public function testDeleteItem(): void
        {
            $this->defaultAdapter
                ->expects($this->once())
                ->method('deleteItem')
                ->with('test_key')
                ->willReturn(true);

            $result = $this->cacheManager->deleteItem('test_key');

            $this->assertTrue($result);
        }

        /**
         * Test that multiple cache items can be deleted.
         */
        public function testDeleteItems(): void
        {
            $this->defaultAdapter
                ->expects($this->once())
                ->method('deleteItems')
                ->with(['key1', 'key2'])
                ->willReturn(true);

            $result = $this->cacheManager->deleteItems(['key1', 'key2']);

            $this->assertTrue($result);
        }

        /**
         * Test that a cache item can be saved.
         */
        public function testSave(): void
        {
            $cacheItem = new CacheItem('test_key', 'test_value');

            $this->defaultAdapter
                ->expects($this->once())
                ->method('save')
                ->with($cacheItem)
                ->willReturn(true);

            $result = $this->cacheManager->save($cacheItem);

            $this->assertTrue($result);
        }

        /**
         * Test that a cache item can be deferred.
         */
        public function testSaveDeferred(): void
        {
            $cacheItem = new CacheItem('test_key', 'test_value');

            $this->defaultAdapter
                ->expects($this->once())
                ->method('saveDeferred')
                ->with($cacheItem)
                ->willReturn(true);

            $result = $this->cacheManager->saveDeferred($cacheItem);

            $this->assertTrue($result);
        }

        /**
         * Test that deferred cache items can be committed.
         */
        public function testCommit(): void
        {
            $this->defaultAdapter
                ->expects($this->once())
                ->method('commit')
                ->willReturn(true);

            $result = $this->cacheManager->commit();

            $this->assertTrue($result);
        }

        /**
         * Test that an adapter can be added.
         */
        public function testAddAdapter(): void
        {
            $newAdapter = $this->createMock(CacheAdapterInterface::class);

            $this->cacheManager->addAdapter('new_adapter', $newAdapter);

            $this->assertSame($newAdapter, $this->cacheManager->getAdapters()['new_adapter']);
        }

        /**
         * Test that adding an adapter with the same name throws an exception.
         */
        public function testAddAdapterThrowsExceptionIfAlreadyExists(): void
        {
            $newAdapter = $this->createMock(CacheAdapterInterface::class);

            $this->cacheManager->addAdapter('new_adapter', $newAdapter);

            $this->expectException(InvalidArgumentException::class);

            $this->expectExceptionMessage("Adapter 'new_adapter' already exists.");

            $this->cacheManager->addAdapter('new_adapter', $newAdapter);
        }

        /**
         * Test that switching to an existing adapter works correctly.
         */
        public function testSwitchAdapter(): void
        {
            $newAdapter = $this->createMock(CacheAdapterInterface::class);

            $this->cacheManager->addAdapter('new_adapter', $newAdapter);

            $this->cacheManager->switchAdapter('new_adapter');

            $this->assertSame($newAdapter, $this->cacheManager->getAdapter());
        }

        /**
         * Test that switching to a non-existent adapter throws an exception.
         */
        public function testSwitchAdapterThrowsExceptionIfNotFound(): void
        {
            $this->expectException(InvalidArgumentException::class);

            $this->expectExceptionMessage("Adapter 'non_existent_adapter' not found.");

            $this->cacheManager->switchAdapter('non_existent_adapter');
        }

        /**
         * Test that the setAdapter method correctly sets a new adapter.
         */
        public function testSetAdapter(): void
        {
            $newAdapter = $this->createMock(CacheAdapterInterface::class);

            $this->cacheManager->setAdapter($newAdapter);

            $this->assertSame($newAdapter, $this->cacheManager->getAdapter());
        }
    }
}
