<?php declare(strict_types=1);

namespace Tests\Temant\Cache\Adapter {

    use Temant\Cache\Adapter\MemcachedCacheAdapter;

    /**
     * Class MemcachedCacheAdapterTest
     *
     * Unit tests for the MemcachedCacheAdapter class.
     */
    class MemcachedCacheAdapterTest extends AbstractCacheAdapterTest
    {
        /**
         * Set up the MemcachedCacheAdapter before each test.
         */
        protected function setUp(): void
        {
            $this->cachePool = new MemcachedCacheAdapter('127.0.0.1', 11211);
            $this->cachePool->clear();
        }
    }
}