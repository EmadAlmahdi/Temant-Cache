<?php declare(strict_types=1);

namespace Temant\Cache {

    use DateTimeInterface;
    use DateTime;
    use DateInterval;
    use Temant\Cache\Interface\CacheItemInterface;

    /**
     * Class CacheItem
     *
     * Represents a single cache item with a unique key, stored value, and optional expiration.
     * This class supports both PSR-6 and custom expiration methods for fine-grained cache control.
     */
    class CacheItem implements CacheItemInterface
    {
        /**
         * CacheItem constructor.
         *
         * Initializes a cache item with the provided key, value, and optional expiration time.
         *
         * @param string $key The unique identifier for the cache item.
         * @param mixed $value The data to store in the cache item.
         * @param bool $hit Whether the cache item is a hit (i.e., found in the cache).
         * @param DateTimeInterface|null $expiration Optional expiration time. The time after which the item will be considered expired.
         */
        public function __construct(
            private string $key,
            private mixed $value = null,
            private bool $hit = false,
            private ?DateTimeInterface $expiration = null
        ) {
        }

        /**
         * Returns the unique key associated with this cache item.
         *
         * @return string The cache key.
         */
        public function getKey(): string
        {
            return $this->key;
        }

        /**
         * Retrieves the value stored in this cache item.
         *
         * @return mixed The stored value or null if no value is present.
         */
        public function get(): mixed
        {
            return $this->value;
        }

        /**
         * Checks whether the cache item is a hit or miss.
         *
         * A cache item is considered a hit if it was found in the cache and has not expired.
         *
         * @return bool True if the item is a hit (exists and is not expired), false otherwise.
         */
        public function isHit(): bool
        {
            return $this->hit && (!$this->expiration || $this->expiration > new DateTime());
        }

        /**
         * Updates the stored value in this cache item.
         *
         * @param mixed $value The new value to store in the cache item.
         * @return static Returns the current cache item for method chaining.
         */
        public function set($value): static
        {
            $this->value = $value;
            $this->hit = true;
            return $this;
        }

        /**
         * Returns the expiration time for this cache item.
         *
         * @return DateTimeInterface|null The expiration time, or null if no expiration is set.
         */
        public function getExpirationTime(): ?DateTimeInterface
        {
            return $this->expiration;
        }

        /**
         * Sets a specific expiration time for this cache item.
         *
         * @param DateTimeInterface|null $expiration The expiration time, or null to remove expiration.
         * @return static Returns the current cache item for method chaining.
         */
        public function expiresAt(?DateTimeInterface $expiration): static
        {
            $this->expiration = $expiration;
            return $this;
        }

        /**
         * Sets an expiration time relative to the current time.
         *
         * This method allows setting the expiration either in seconds (int) or as a DateInterval.
         *
         * @param int|DateInterval|null $time Time in seconds or DateInterval, or null for no expiration.
         * @return static Returns the current cache item for method chaining.
         */
        public function expiresAfter(null|int|DateInterval $time): static
        {
            if ($time === null) {
                $this->expiration = null;
            } elseif (is_int($time)) {
                $this->expiration = (new DateTime())->modify("+$time seconds");
            } elseif ($time instanceof DateInterval) {
                $this->expiration = (new DateTime())->add($time);
            }

            return $this;
        }

        /**
         * Calculates the remaining time until this cache item expires.
         *
         * @return int|null The number of seconds until expiration, or null if no expiration is set.
         */
        public function getTimeUntilExpiration(): ?int
        {
            if ($this->expiration === null) {
                return null;
            }

            $now = new DateTime();
            return ($this->expiration > $now) ? $this->expiration->getTimestamp() - $now->getTimestamp() : null;
        }

        /**
         * Invalidates this cache item by setting its expiration to the current time.
         *
         * @return static Returns the current cache item for method chaining.
         */
        public function invalidate(): static
        {
            $this->expiration = new DateTime();
            return $this;
        }

        /**
         * Checks whether the cache item has expired.
         *
         * @return bool True if the cache item has expired, false otherwise.
         */
        public function hasExpired(): bool
        {
            return $this->expiration !== null && $this->expiration <= new DateTime();
        }

        /**
         * Extends the expiration time of the cache item by a given interval.
         *
         * @param int|DateInterval $time Time in seconds or DateInterval to extend the expiration.
         * @return static Returns the current cache item for method chaining.
         */
        public function extendExpiration(int|DateInterval $time): static
        {
            if ($this->expiration instanceof \DateTimeImmutable) {
                $this->expiration = DateTime::createFromInterface($this->expiration);
            } elseif ($this->expiration === null) {
                $this->expiration = new DateTime();
            }

            // Modify the expiration time
            if (is_int($time)) {
                $this->expiration = $this->expiration->modify("+$time seconds");
            } elseif ($time instanceof DateInterval) {
                $this->expiration->add($time);
            }

            return $this;
        }


        /**
         * Checks if the cache item is persistent (has no expiration).
         *
         * @return bool True if the cache item has no expiration, false otherwise.
         */
        public function isPersistent(): bool
        {
            return $this->expiration === null;
        }
    }
}