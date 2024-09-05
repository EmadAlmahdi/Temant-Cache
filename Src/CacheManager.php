<?php declare(strict_types=1);

namespace Temant\Cache {

    use InvalidArgumentException;
    use Temant\Cache\Interface\CacheAdapterInterface;
    use Temant\Cache\Interface\CacheItemInterface;
    use Temant\Cache\Interface\CacheManagerInterface;

    /**
     * Class CacheManager
     * 
     * A flexible cache manager that supports multiple cache adapters for storage operations.
     */
    class CacheManager implements CacheManagerInterface
    {
        /**
         * @var CacheAdapterInterface The current active cache adapter.
         */
        private CacheAdapterInterface $adapter;

        /**
         * @var array<string, CacheAdapterInterface> An array of available adapters.
         */
        private array $adapters = [];

        /**
         * CacheManager constructor.
         *
         * Initializes the manager with a default adapter and registers it as "default".
         *
         * @param CacheAdapterInterface $adapter The default cache adapter to use.
         */
        public function __construct(CacheAdapterInterface $adapter)
        {
            $this->adapter = $adapter;
            $this->addAdapter('default', $adapter); // Register the default adapter
        }

        /**
         * Fetches a cache item by key.
         *
         * @param string $key The cache item key.
         * @return CacheItemInterface|\Psr\Cache\CacheItemInterface The cache item.
         */
        public function getItem(string $key): CacheItemInterface|\Psr\Cache\CacheItemInterface
        {
            return $this->adapter->getItem($key);
        }

        /**
         * Retrieves multiple cache items by their keys.
         *
         * @param array<string> $keys An array of cache keys.
         * @return iterable<string, CacheItemInterface> An array of cache items.
         */
        public function getItems(array $keys = []): iterable
        {
            return $this->adapter->getItems($keys);
        }

        /**
         * Determines whether a cache item exists and is not expired.
         *
         * @param string $key The cache item key.
         * @return bool True if the cache item exists, false otherwise.
         */
        public function hasItem(string $key): bool
        {
            return $this->adapter->hasItem($key);
        }

        /**
         * Clears all cache items from the current storage adapter.
         *
         * @return bool True on success, false on failure.
         */
        public function clear(): bool
        {
            return $this->adapter->clear();
        }

        /**
         * Deletes a cache item by key.
         *
         * @param string $key The cache item key.
         * @return bool True on success, false on failure.
         */
        public function deleteItem(string $key): bool
        {
            return $this->adapter->deleteItem($key);
        }

        /**
         * Deletes multiple cache items by their keys.
         *
         * @param array<string> $keys An array of cache keys.
         * @return bool True on success, false on failure.
         */
        public function deleteItems(array $keys): bool
        {
            return $this->adapter->deleteItems($keys);
        }

        /**
         * Saves a cache item to the current adapter.
         *
         * @param CacheItemInterface $item The cache item to save.
         * @return bool True on success, false on failure.
         */
        public function save(CacheItemInterface $item): bool
        {
            return $this->adapter->save($item);
        }

        /**
         * Saves a cache item for deferred saving.
         *
         * @param CacheItemInterface $item The cache item to defer.
         * @return bool True on success, false on failure.
         */
        public function saveDeferred(CacheItemInterface $item): bool
        {
            return $this->adapter->saveDeferred($item);
        }

        /**
         * Commits all deferred cache items to the storage.
         *
         * @return bool True on success, false on failure.
         */
        public function commit(): bool
        {
            return $this->adapter->commit();
        }

        /**
         * Sets the current cache adapter.
         *
         * @param CacheAdapterInterface $adapter The adapter to use.
         */
        public function setAdapter(CacheAdapterInterface $adapter): void
        {
            $this->adapter = $adapter;
        }

        /**
         * Retrieves the current cache adapter.
         *
         * @return CacheAdapterInterface The current cache adapter.
         */
        public function getAdapter(): CacheAdapterInterface
        {
            return $this->adapter;
        }

        /**
         * Adds a new cache adapter to the manager.
         *
         * @param string $name The name to identify the adapter.
         * @param CacheAdapterInterface $adapter The adapter to add.
         * @throws InvalidArgumentException If the adapter with the same name already exists.
         */
        public function addAdapter(string $name, CacheAdapterInterface $adapter): void
        {
            if (isset($this->adapters[$name])) {
                throw new InvalidArgumentException("Adapter '{$name}' already exists.");
            }
            $this->adapters[$name] = $adapter;
        }

        /**
         * Switches to a specific cache adapter by name.
         *
         * @param string $name The name of the adapter to switch to.
         * @throws InvalidArgumentException If the adapter is not found.
         */
        public function switchAdapter(string $name): void
        {
            if (!isset($this->adapters[$name])) {
                throw new InvalidArgumentException("Adapter '{$name}' not found.");
            }
            $this->adapter = $this->adapters[$name];
        }

        /**
         * Retrieves all available cache adapters.
         *
         * @return array<string, CacheAdapterInterface> An array of all added adapters.
         */
        public function getAdapters(): array
        {
            return $this->adapters;
        }
    }
}