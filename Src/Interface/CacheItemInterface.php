<?php declare(strict_types=1);

namespace Temant\Cache\Interface {
    /**
     * Interface CacheItemInterface
     *
     * Extends the PSR-6 CacheItemInterface with additional custom methods related to expiration and invalidation.
     */
    interface CacheItemInterface extends \Psr\Cache\CacheItemInterface
    {
        /**
         * Retrieves the expiration time of the cache item.
         *
         * If the cache item has an expiration time set, this method will return it.
         *
         * @return \DateTimeInterface|null The expiration time or null if no expiration is set.
         */
        public function getExpirationTime(): ?\DateTimeInterface;

        /**
         * Determines how long the cache item will remain valid.
         *
         * Returns the number of seconds until the cache item expires. If no expiration is set,
         * this method returns null.
         *
         * @return int|null Number of seconds until expiration, or null if no expiration is set.
         */
        public function getTimeUntilExpiration(): ?int;

        /**
         * Invalidates the cache item by setting the expiration to the current time.
         *
         * This method immediately marks the cache item as expired, making it a cache miss
         * in subsequent lookups.
         *
         * @return static The current CacheItem instance for method chaining.
         */
        public function invalidate(): static;

        /**
         * Checks whether the cache item has expired.
         *
         * @return bool True if the cache item has expired, false otherwise.
         */
        public function hasExpired(): bool;

        /**
         * Extends the expiration time of the cache item by a given interval.
         *
         * This method allows the expiration time to be extended by a certain number of seconds
         * or by a DateInterval.
         *
         * @param int|\DateInterval $time Time in seconds or DateInterval to extend the expiration.
         * @return static The current CacheItem instance for method chaining.
         */
        public function extendExpiration(int|\DateInterval $time): static;

        /**
         * Checks if the cache item is persistent (has no expiration).
         *
         * @return bool True if the cache item has no expiration, false otherwise.
         */
        public function isPersistent(): bool;
    }
}