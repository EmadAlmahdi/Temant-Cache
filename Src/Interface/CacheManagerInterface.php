<?php declare(strict_types=1);

namespace Temant\Cache\Interface {
    /**
     * Interface CacheManagerInterface
     *
     * Defines the required methods for a cache manager to handle various cache operations.
     */
    interface CacheManagerInterface
    {
        /**
         * Retrieves a cache item by its unique key.
         *
         * @param string $key The unique cache key.
         * @return CacheItemInterface|\Psr\Cache\CacheItemInterface The cache item associated with the provided key.
         */
        public function getItem(string $key): CacheItemInterface|\Psr\Cache\CacheItemInterface;

        /**
         * Retrieves multiple cache items by their keys.
         *
         * @param array<string> $keys An array of cache keys.
         * @return iterable<string, CacheItemInterface> An array of cache items.
         */
        public function getItems(array $keys = []): iterable;

        /**
         * Determines whether a cache item exists and is not expired.
         *
         * @param string $key The cache key to check.
         * @return bool True if the cache item exists, false otherwise.
         */
        public function hasItem(string $key): bool;

        /**
         * Clears all cache items from the storage.
         *
         * @return bool True on success, false on failure.
         */
        public function clear(): bool;

        /**
         * Deletes a cache item by its key.
         *
         * @param string $key The cache key to delete.
         * @return bool True on success, false on failure.
         */
        public function deleteItem(string $key): bool;

        /**
         * Deletes multiple cache items by their keys.
         *
         * @param array<string> $keys An array of cache keys to delete.
         * @return bool True on success, false on failure.
         */
        public function deleteItems(array $keys): bool;

        /**
         * Saves a cache item to the storage.
         *
         * @param CacheItemInterface $item The cache item to save.
         * @return bool True on success, false on failure.
         */
        public function save(CacheItemInterface $item): bool;

        /**
         * Saves a cache item for deferred storage.
         *
         * @param CacheItemInterface $item The cache item to defer.
         * @return bool True on success.
         */
        public function saveDeferred(CacheItemInterface $item): bool;

        /**
         * Commits all deferred cache items to storage.
         *
         * @return bool True on success, false on failure.
         */
        public function commit(): bool;

        /**
         * Sets the cache adapter to be used.
         *
         * @param CacheAdapterInterface $adapter The cache adapter to set.
         * @return void
         */
        public function setAdapter(CacheAdapterInterface $adapter): void;

        /**
         * Retrieves the current cache adapter.
         *
         * @return CacheAdapterInterface The current cache adapter.
         */
        public function getAdapter(): CacheAdapterInterface;
    }
}