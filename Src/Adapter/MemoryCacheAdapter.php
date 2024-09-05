<?php declare(strict_types=1);

namespace Temant\Cache\Adapter;

use DateTime;
use Temant\Cache\Interface\CacheAdapterInterface;
use Temant\Cache\CacheItem;
use Temant\Cache\Interface\CacheItemInterface;

/**
 * Class MemoryCacheAdapter
 * 
 * A memory-based implementation of CacheAdapterInterface.
 * Stores cache items in memory using PHP arrays. This is non-persistent and items
 * only exist for the lifetime of the application.
 */
class MemoryCacheAdapter implements CacheAdapterInterface
{
    /**
     * @var array<string, array{value: mixed, expiration: ?int}> Internal storage for cache items.
     */
    private array $cache = [];

    /**
     * @var array<string, CacheItemInterface> An array to store deferred cache items for batch saving.
     */
    private array $deferred = [];

    /**
     * Fetches a cache item by key.
     * 
     * @param string $key The cache item key.
     * @return CacheItemInterface The cache item.
     */
    public function getItem(string $key): CacheItemInterface
    {
        if (isset($this->cache[$key])) {
            $data = $this->cache[$key];

            // Check if the item has expired
            if ($data['expiration'] !== null && $data['expiration'] < time()) {
                $this->deleteItem($key); // Remove expired item
                return new CacheItem($key); // Return a miss
            }

            // If not expired, return the item
            $expiration = $data['expiration'] !== null ? (new DateTime())->setTimestamp($data['expiration']) : null;
            return new CacheItem($key, $data['value'], true, $expiration);
        }

        // Cache miss
        return new CacheItem($key);
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
     * Clears all cache items from the memory.
     * 
     * @return bool True on success.
     */
    public function clear(): bool
    {
        $this->cache = [];
        return true;
    }

    /**
     * Deletes a cache item by its key.
     * 
     * @param string $key The cache item key.
     * @return bool True if the item was deleted, false otherwise.
     */
    public function deleteItem(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            return true;
        }
        return false;
    }

    /**
     * Deletes multiple cache items by their keys.
     * 
     * @param array<string> $keys An array of cache keys.
     * @return bool True on success.
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    /**
     * Persists a cache item in memory.
     * 
     * @param CacheItemInterface $item The cache item to save.
     * @return bool True on success, false on failure.
     */
    public function save(\Psr\Cache\CacheItemInterface $item): bool
    {
        $expiration = $item->getExpirationTime()?->getTimestamp();
        $this->cache[$item->getKey()] = [
            'value' => $item->get(),
            'expiration' => $expiration,
        ];

        return true;
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
     * Commits all deferred cache items to the memory.
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
     * Retrieves the total size of the cache in memory.
     * 
     * Since this is memory-based, the size is measured as the number of items.
     * 
     * @return int The total number of items in memory.
     */
    public function getCacheSize(): int
    {
        return count($this->cache);
    }

    /**
     * Retrieves the total number of items in the cache.
     * 
     * @return int The number of cache items.
     */
    public function getItemCount(): int
    {
        return count($this->cache);
    }
}