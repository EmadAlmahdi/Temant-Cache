<?php declare(strict_types=1);

namespace Temant\Cache\Adapter {

    use Temant\Cache\CacheItem;
    use Temant\Cache\Exception\CacheException;
    use Temant\Cache\Interface\CacheAdapterInterface;
    use Temant\Cache\Interface\CacheItemInterface;

    /**
     * Class SingleFilePHPCacheAdapter
     *
     * A file-based cache implementation that stores all cache items in a single PHP file.
     */
    class SingleFileCacheAdapter implements CacheAdapterInterface
    {
        /**
         * @var string The path to the single PHP file where cache items are stored.
         */
        private string $cacheFile;

        /**
         * @var array<string, array{value: mixed, expiration: ?int}> The in-memory cache data loaded from the PHP file.
         */
        private array $cache = [];

        /**
         * @var array<string, CacheItemInterface> An array to store deferred cache items for batch saving.
         */
        private array $deferred = [];

        /**
         * Constructor.
         *
         * @param string $cacheFile The PHP file where cache items will be stored.
         * @throws CacheException If the cache file cannot be created.
         */
        public function __construct(string $cacheFile)
        {
            $this->cacheFile = $cacheFile;
            if (!file_exists($cacheFile)) {
                if (!file_put_contents($cacheFile, "<?php return [];")) {
                    throw new CacheException("Failed to create cache file: {$cacheFile}");
                }
            }

            $this->cache = $this->loadCache();
        }

        /**
         * Fetches a cache item by key.
         *
         * @param string $key The cache item key.
         * @return CacheItemInterface The cache item.
         */
        public function getItem(string $key): CacheItemInterface
        {
            $this->purgeExpiredItem($key);

            if (isset($this->cache[$key])) {
                $data = $this->cache[$key];
                $expiration = $data['expiration'] !== null ? (new \DateTimeImmutable())->setTimestamp($data['expiration']) : null;

                return new CacheItem($key, $data['value'], true, $expiration);
            }

            return new CacheItem($key); // Cache miss: return a new empty item
        }

        /**
         * Retrieves multiple cache items by their keys.
         *
         * @param array<string> $keys An array of cache keys.
         * @return array<string, CacheItemInterface> An array of cache items.
         */
        public function getItems(array $keys = []): array
        {
            $items = [];
            foreach ($keys as $key) {
                $items[$key] = $this->getItem($key);
            }
            return $items;
        }

        /**
         * Determines whether a cache item exists and is not expired.
         *
         * @param string $key The cache item key.
         * @return bool True if the cache item exists and is not expired, false otherwise.
         */
        public function hasItem(string $key): bool
        {
            return $this->getItem($key)->isHit();
        }

        /**
         * Clears all cache items from the file.
         *
         * @return bool True on success, false on failure.
         */
        public function clear(): bool
        {
            $this->cache = [];
            return $this->saveCache();
        }

        /**
         * Deletes a cache item from the pool by its key.
         *
         * @param string $key The cache item key.
         * @return bool True if the cache item was deleted, false if the item did not exist.
         */
        public function deleteItem(string $key): bool
        {
            if (isset($this->cache[$key])) {
                unset($this->cache[$key]);
                return $this->saveCache();
            }
            return false;
        }

        /**
         * Deletes multiple cache items from the pool by their keys.
         *
         * @param array<string> $keys An array of cache keys.
         * @return bool True on success, false on failure.
         */
        public function deleteItems(array $keys): bool
        {
            foreach ($keys as $key) {
                $this->deleteItem($key);
            }
            return true;
        }

        /**
         * Persists a cache item in the storage.
         *
         * @param CacheItemInterface $item The cache item to save.
         * @return bool True on success, false on failure.
         */
        public function save(\Psr\Cache\CacheItemInterface $item): bool
        {
            $this->cache[$item->getKey()] = [
                'value' => $item->get(),
                'expiration' => $item->getExpirationTime()?->getTimestamp()
            ];

            return $this->saveCache();
        }

        /**
         * Saves a cache item for later persistence (deferred save).
         *
         * @param CacheItemInterface $item The cache item to defer saving.
         * @return bool True on success.
         */
        public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool
        {
            $this->deferred[$item->getKey()] = $item;
            return true;
        }

        /**
         * Commits all deferred cache items to the storage.
         *
         * @return bool True on success.
         */
        public function commit(): bool
        {
            foreach ($this->deferred as $item) {
                $this->save($item);
            }
            $this->deferred = [];
            return true;
        }

        /**
         * Retrieves the total size of the cache file in bytes.
         *
         * @return int The total size of the cache file.
         */
        public function getCacheSize(): int
        {
            return file_exists($this->cacheFile) ? filesize($this->cacheFile) ?: 0 : 0;
        }

        /**
         * Retrieves the total number of items in the cache.
         *
         * @return int The number of cached items.
         */
        public function getItemCount(): int
        {
            return count($this->cache);
        }

        /**
         * Loads the cache data from the PHP file.
         *
         * @return array<string, array{value: mixed, expiration: ?int}> The cache data.
         */
        private function loadCache(): array
        {
            if (!file_exists($this->cacheFile)) {
                return [];
            }

            $cache = file_get_contents($this->cacheFile);

            // Attempt to unserialize the content if it exists
            if ($cache !== false) {
                $unserializedData = @unserialize($cache);
                // Check if unserialization was successful and the data is in the expected format
                if (is_array($unserializedData)) {
                    foreach ($unserializedData as $key => $value) {
                        if (!is_array($value) || !isset($value['value']) || !array_key_exists('expiration', $value)) {
                            // Invalidate the whole cache if any item is malformed
                            return [];
                        }
                    }
                    return $unserializedData;
                }
            }

            // Return an empty array if the file content is invalid or empty
            return [];
        }

        /**
         * Saves the in-memory cache to the PHP file.
         *
         * @return bool True on success, false on failure.
         */
        private function saveCache(): bool
        {
            $data = serialize($this->cache);
            return $this->writeFileWithLock($this->cacheFile, $data);
        }

        /**
         * Writes the cache file with an exclusive lock to prevent concurrency issues.
         *
         * @param string $file The file to write.
         * @param string $data The data to write.
         * @return bool True on success, false on failure.
         */
        private function writeFileWithLock(string $file, string $data): bool
        {
            $fileHandle = fopen($file, 'c');
            if (!$fileHandle) {
                return false;
            }

            if (!flock($fileHandle, LOCK_EX)) {
                fclose($fileHandle);
                return false;
            }

            ftruncate($fileHandle, 0);
            fwrite($fileHandle, $data);
            fflush($fileHandle); // Ensure data is written to disk

            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);

            return true;
        }

        /**
         * Purges an expired item from the cache.
         * 
         * @param string $key The cache item key.
         */
        private function purgeExpiredItem(string $key): void
        {
            if (isset($this->cache[$key])) {
                $expiration = $this->cache[$key]['expiration'];
                if ($expiration !== null && $expiration < time()) {
                    unset($this->cache[$key]);
                    $this->saveCache(); // Save cache after purging
                }
            }
        }
    }
}