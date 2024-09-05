<?php declare(strict_types=1);

namespace Temant\Cache\Adapter {

    use DateTime;
    use Exception;
    use Redis;
    use Temant\Cache\Interface\CacheAdapterInterface;
    use Temant\Cache\CacheItem;
    use Temant\Cache\Interface\CacheItemInterface;

    /**
     * Class RedisCacheAdapter
     * 
     * A Redis-based implementation of PSR-6 CacheItemPoolInterface. This class handles storing
     * cache items using Redis as the backend. It supports expiration and deferred saving.
     */
    class RedisCacheAdapter implements CacheAdapterInterface
    {
        /**
         * @var Redis The Redis connection instance.
         */
        private Redis $redis;

        /**
         * @var array<string, CacheItemInterface> An array to store deferred cache items for batch saving.
         */
        private array $deferred = [];

        /**
         * RedisCacheAdapter constructor.
         *
         * Initializes the Redis connection.
         *
         * @param string $host The Redis host.
         * @param int $port The Redis port.
         */
        public function __construct(string $host = '127.0.0.1', int $port = 6379)
        {
            $this->redis = new Redis();
            $this->redis->connect($host, $port);
        }

        public function getItem(string $key): CacheItemInterface
        {
            $data = $this->redis->get($key);

            if (is_string($data)) {
                $data = unserialize($data);

                if (is_array($data) && isset($data['value'])) {
                    $expiration = null;

                    if (isset($data['expiration']) && is_int($data['expiration'])) {
                        $expiration = (new DateTime())->setTimestamp($data['expiration']);
                    }

                    return new CacheItem($key, $data['value'], true, $expiration);
                }
            }

            return new CacheItem($key); // Cache miss: return a new empty item
        }


        /**
         * Retrieves multiple cache items by their keys.
         * 
         * @param array<string> $keys An array of cache keys.
         * @return array<CacheItemInterface> An array of cache items.
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
            return (int) $this->redis->exists($key) > 0;
        }

        /**
         * Clears all cache items from Redis.
         * 
         * @return bool True on success, false on failure.
         */
        public function clear(): bool
        {
            return $this->redis->flushDB();
        }

        /**
         * Deletes a cache item from the pool by its key.
         * 
         * @param string $key The cache item key.
         * @return bool True if the cache item was deleted, false if the item did not exist.
         */
        public function deleteItem(string $key): bool
        {
            return (int) $this->redis->del($key) > 0;
        }

        /**
         * Deletes multiple cache items from the pool by their keys.
         * 
         * @param array<string> $keys An array of cache keys.
         * @return bool True on success, false on failure.
         */
        public function deleteItems(array $keys): bool
        {
            return (int) $this->redis->del($keys) > 0;
        }

        /**
         * Persists a cache item in Redis.
         * 
         * Serializes the cache item and stores it with an optional expiration time.
         *
         * @param CacheItemInterface $item The cache item to save.
         * @return bool True on success, false on failure.
         */
        public function save(\Psr\Cache\CacheItemInterface $item): bool
        {
            $data = [
                'value' => $item->get(),
                'expiration' => $item->getExpirationTime()?->getTimestamp(),
            ];

            // Serialize the cache data
            $serialized = serialize($data);

            // If expiration exists, set it
            if ($item->getExpirationTime() !== null) {
                $ttl = $item->getExpirationTime()->getTimestamp() - time();
                return $this->redis->setex($item->getKey(), $ttl, $serialized);
            }

            // Otherwise, save without expiration
            return $this->redis->set($item->getKey(), $serialized);
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
         * Retrieves the total size of the cache in bytes.
         *
         * This method fetches memory usage information from the Redis server and returns
         * the total memory being used by Redis for the current database.
         *
         * @return int The total size of the Redis cache in bytes.
         */
        public function getCacheSize(): int
        {
            $info = $this->redis->info();
            return isset($info['used_memory']) ? (int) $info['used_memory'] : 0;
        }

        /**
         * Retrieves the total number of items in the Redis cache.
         *
         * This method counts the number of keys in the current Redis database.
         *
         * @return int The number of items in the cache.
         */
        public function getItemCount(): int
        {
            return (int) $this->redis->dbSize();
        }
    }
}