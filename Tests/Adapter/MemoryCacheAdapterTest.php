<?php declare(strict_types=1);

namespace Tests\Temant\Cache\Adapter {

    use Temant\Cache\Adapter\MemoryCacheAdapter;

    /**
     * Class MemoryCacheAdapterTest
     *
     * Unit tests for the MemoryCacheAdapter class, extending the abstract cache adapter tests.
     */
    class MemoryCacheAdapterTest extends AbstractCacheAdapterTest
    {
        /**
         * Set up the Adapter before each test.
         */
        protected function setUp(): void
        {
            $this->cachePool = new MemoryCacheAdapter();
        }
    }
}