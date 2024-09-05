<?php declare(strict_types=1);

namespace Tests\Temant\Cache\Adapter {

    use Temant\Cache\Adapter\RedisCacheAdapter;

    /**
     * Class RedisCacheAdapterTest
     *
     * Unit tests for the RedisCacheAdapter class, extending the abstract cache adapter tests.
     */
    class RedisCacheAdapterTest extends AbstractCacheAdapterTest
    {
        /**
         * Set up the Adapter before each test.
         */
        protected function setUp(): void
        {
            $this->cachePool = new RedisCacheAdapter('127.0.0.1', 6379);
        }
    }
}