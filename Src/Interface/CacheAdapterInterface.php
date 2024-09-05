<?php declare(strict_types=1);

namespace Temant\Cache\Interface {
    /**
     * Interface CacheAdapterInterface
     *
     * This interface defines the contract for any cache adapter implementation.
     * It provides a simple key-value storage mechanism with optional TTL (Time to Live) support.
     * Implementations of this interface can vary from file-based storage, in-memory cache, 
     * Redis, Memcached, or other caching systems.
     */
    interface CacheAdapterInterface extends \Psr\Cache\CacheItemPoolInterface
    {
        /**
         * Custom method to get the size of the cache directory.
         *
         * @return int The size in bytes.
         */
        public function getCacheSize(): int;

        /**
         * Custom method to get the number of items in the cache directory.
         *
         * @return int The number of cached items.
         */
        public function getItemCount(): int;
    }
}