<?php declare(strict_types=1);

namespace Temant\Cache\Adapter {

    use FilesystemIterator;
    use Temant\Cache\Interface\CacheAdapterInterface;
    use Temant\Cache\Interface\CacheItemInterface;
    use Temant\Cache\Exception\CacheException;
    use Temant\Cache\CacheItem;

    /**
     * Class FileSystemCacheAdapter
     * 
     * A file-based implementation of PSR-6 CacheItemPoolInterface. This class handles storing
     * cache items as serialized files on the filesystem with support for expiration and deferred saving.
     */
    class FileSystemCacheAdapter implements CacheAdapterInterface
    {
        /**
         * @var string The directory where cache files are stored.
         */
        private string $cacheDir;

        /**
         * @var array<string, CacheItemInterface> An array to store deferred cache items for batch saving.
         */
        private array $deferred = [];

        /**
         * FileSystemCacheAdapter constructor.
         *
         * @param string $cacheDir The directory where cache files will be stored.
         * @throws CacheException If the cache directory cannot be created.
         */
        public function __construct(string $cacheDir)
        {
            $this->cacheDir = rtrim($cacheDir, '/');
            if (!is_dir($this->cacheDir)) {
                if (!mkdir($this->cacheDir, 0777, true)) {
                    throw new CacheException("Failed to create cache directory: {$this->cacheDir}");
                }
            }
        }

        /**
         * Fetches a cache item by key.
         * 
         * If the item exists and hasn't expired, it returns a CacheItem containing the value. 
         * Otherwise, it returns a new CacheItem with no value.
         *
         * @param string $key The cache item key.
         * @return CacheItemInterface The cache item.
         */
        public function getItem(string $key): CacheItemInterface
        {
            $filePath = $this->getFilePath($key);

            if (file_exists($filePath) && $content = file_get_contents($filePath)) {
                $data = unserialize($content);

                if (is_array($data)) {
                    // If the expiration exists and the item is expired, delete it
                    if ($data['expiration'] && $data['expiration'] < time()) {
                        $this->deleteItem($key); // Remove expired items
                        return new CacheItem($key); // Return a new, empty item for a miss
                    }

                    // If expiration exists, convert timestamp back to DateTime. Otherwise, set it to null.
                    $expiration = $data['expiration'] !== null ? (new \DateTime())->setTimestamp((int) $data['expiration']) : null;

                    return new CacheItem($key, $data['value'], true, $expiration);
                }
            }

            return new CacheItem($key); // Cache miss: return a new item
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
         * Determines whether a cache item exists in the pool and is not expired.
         * 
         * @param string $key The cache item key.
         * @return bool True if the cache item exists and is not expired, false otherwise.
         */
        public function hasItem(string $key): bool
        {
            return $this->getItem($key)->isHit();
        }

        /**
         * Clears all cache items from the cache directory.
         * 
         * @return bool True on success, false on failure.
         */
        public function clear(): bool
        {
            foreach (new FilesystemIterator($this->cacheDir) as $file) {
                unlink(strval($file));
            }
            return true;
        }

        /**
         * Deletes a cache item from the pool by its key.
         * 
         * @param string $key The cache item key.
         * @return bool True if the cache item was deleted, false if the item did not exist.
         */
        public function deleteItem(string $key): bool
        {
            $filePath = $this->getFilePath($key);
            if (file_exists($filePath)) {
                return unlink($filePath);
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
         * Serializes the cache item and stores it in a file with its expiration time.
         *
         * @param CacheItemInterface $item The cache item to save.
         * @return bool True on success, false on failure.
         */
        public function save(\Psr\Cache\CacheItemInterface $item): bool
        {
            $filePath = $this->getFilePath($item->getKey());

            // Use getExpirationTime() to retrieve the expiration time (if any)
            $expiration = $item->getExpirationTime()?->getTimestamp();

            $data = [
                'value' => $item->get(),
                'expiration' => $expiration,
            ];

            // Save serialized data to the file
            return file_put_contents($filePath, serialize($data)) !== false;
        }

        /**
         * Saves a cache item for later persistence (deferred save).
         * 
         * The item is not immediately saved but is held in memory for batch saving.
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
         * Generates the file path for a given cache key.
         * 
         * The key is hashed using SHA-256.
         * 
         * @param string $key The cache item key.
         * @return string The full path to the cache file.
         */
        private function getFilePath(string $key): string
        {
            return $this->cacheDir . '/' . hash('sha256', $key) . '.cache';
        }

        /**
         * Get the total size of the cache directory in bytes.
         * 
         * @return int The size of the cache directory in bytes.
         */
        public function getCacheSize(): int
        {
            $size = 0;
            foreach (new FilesystemIterator($this->cacheDir) as $file) {
                if ($file instanceof \SplFileInfo) {
                    $size += $file->getSize();
                }
            }
            return $size;
        }

        /**
         * Get the number of items in the cache directory.
         * 
         * @return int The number of cached items.
         */
        public function getItemCount(): int
        {
            return iterator_count(new FilesystemIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS));
        }
    }
}