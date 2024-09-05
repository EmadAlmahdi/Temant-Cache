<?php declare(strict_types=1);

namespace Temant\Cache\Adapter {

    use Memcached;
    use Temant\Cache\CacheItem;
    use Temant\Cache\Interface\CacheAdapterInterface;
    use Temant\Cache\Interface\CacheItemInterface;

    /**
     * Class MemcachedCacheAdapter
     *
     * A Memcached-based implementation of PSR-6 CacheItemPoolInterface.
     */
    class MemcachedCacheAdapter implements CacheAdapterInterface
    {
        /**
         * @var Memcached The Memcached connection instance.
         */
        private Memcached $memcached;

        /**
         * @var array<string, CacheItemInterface> An array to store deferred cache items for batch saving.
         */
        private array $deferred = [];

        /**
         * MemcachedCacheAdapter constructor.
         *
         * Initializes the Memcached connection.
         *
         * @param string $host The Memcached host.
         * @param int $port The Memcached port.
         */
        public function __construct(string $host = '127.0.0.1', int $port = 11211)
        {
            $this->memcached = new Memcached();
            $this->memcached->addServer($host, $port);
        }

        /**
         * @inheritDoc
         */
        public function getItem(string $key): CacheItemInterface
        {
            $data = $this->memcached->get($key);

            if (is_array($data)) {
                if (isset($data['expiration']) && is_int($data['expiration'])) {
                    $expiration = (new \DateTime())->setTimestamp($data['expiration']);
                }

                return new CacheItem($key, $data['value'], true, $expiration ?? null);
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
         * @inheritDoc
         */
        public function hasItem(string $key): bool
        {
            return $this->memcached->get($key) !== false;
        }

        /**
         * @inheritDoc
         */
        public function clear(): bool
        {
            return $this->memcached->flush();
        }

        /**
         * @inheritDoc
         */
        public function deleteItem(string $key): bool
        {
            return $this->memcached->delete($key);
        }

        /**
         * @inheritDoc
         */
        public function deleteItems(array $keys): bool
        {
            return !empty($this->memcached->deleteMulti($keys));
        }

        /**
         * Persists a cache item in Memcached.
         * 
         * Serializes the cache item and stores it with an optional expiration time.
         *
         * @param CacheItemInterface $item The cache item to save.
         * @return bool True on success, false on failure.
         */
        public function save(\Psr\Cache\CacheItemInterface $item): bool
        {
            $expiration = $item->getExpirationTime()?->getTimestamp() ?? 0;

            $data = [
                'value' => $item->get(),
                'expiration' => $expiration > 0 ? $expiration : null, // Ensure no-expiration is handled
            ];

            return $this->memcached->set($item->getKey(), $data, $expiration > 0 ? $expiration : 0);
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
         * @inheritDoc
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
         * @inheritDoc
         */
        public function getCacheSize(): int
        {
            $stats = $this->memcached->getStats();
            $serverStats = reset($stats);
            return $serverStats['bytes'] ?? 0;
        }

        /**
         * @inheritDoc
         */
        public function getItemCount(): int
        {
            $stats = $this->memcached->getStats();
            $serverStats = reset($stats);
            return $serverStats['curr_items'] ?? 0;
        }
    }
}