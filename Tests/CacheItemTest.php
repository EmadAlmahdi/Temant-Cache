<?php declare(strict_types=1);

namespace Tests\Temant\Cache {

    use DateTimeImmutable;
    use PHPUnit\Framework\TestCase;
    use Temant\Cache\CacheItem;
    use DateTime;
    use DateInterval;

    /**
     * Class CacheItemTest
     *
     * Unit tests for the CacheItem class, covering key retrieval, value management, expiration handling, 
     * cache hits, and invalidation. 
     */
    class CacheItemTest extends TestCase
    {
        /**
         * Test that the cache key is properly set and retrieved.
         */
        public function testGetKey(): void
        {
            $item = new CacheItem('test_key');
            $this->assertEquals('test_key', $item->getKey(), 'Cache key should match the expected value.');
        }

        /**
         * Test that the cache value is properly set and retrieved.
         */
        public function testGetValue(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $this->assertEquals('test_value', $item->get(), 'Cache value should match the expected value.');
        }

        /**
         * Test that the cache hit status is properly determined for a hit item.
         */
        public function testIsHit(): void
        {
            $item = new CacheItem('test_key', 'test_value', true);
            $this->assertTrue($item->isHit(), 'Cache item should be a hit.');

            $missedItem = new CacheItem('missed_key', null, false);
            $this->assertFalse($missedItem->isHit(), 'Cache item should not be a hit.');
        }

        /**
         * Test that a cache item without expiration is considered a hit.
         */
        public function testIsHitWithoutExpiration(): void
        {
            $item = new CacheItem('test_key', 'test_value', true);
            $this->assertTrue($item->isHit(), 'Cache item should be a hit if no expiration is set.');
        }

        /**
         * Test that a cache item with a past expiration time is not considered a hit.
         */
        public function testIsHitWithExpiredItem(): void
        {
            $expiredTime = (new DateTime())->modify('-1 hour');
            $item = new CacheItem('test_key', 'test_value', true, $expiredTime);
            $this->assertFalse($item->isHit(), 'Cache item should not be a hit if it is expired.');
        }

        /**
         * Test setting expiration using expiresAt with a future date.
         */
        public function testExpiresAt(): void
        {
            $futureTime = (new DateTime())->modify('+1 hour');
            $item = new CacheItem('test_key', 'test_value');
            $item->expiresAt($futureTime);

            $this->assertEquals($futureTime, $item->getExpirationTime(), 'Expiration should match the set future time.');
        }

        /**
         * Test that a cache item with a future expiration is still considered a hit.
         */
        public function testIsHitWithFutureExpiration(): void
        {
            $futureTime = (new DateTime())->modify('+1 hour');
            $item = new CacheItem('test_key', 'test_value', true, $futureTime);
            $this->assertTrue($item->isHit(), 'Cache item should still be a hit if the expiration is in the future.');
        }

        /**
         * Test setting expiration using expiresAfter with seconds.
         */
        public function testExpiresAfterSeconds(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $item->expiresAfter(3600); // Expires in 1 hour

            $expectedExpiration = (new DateTime())->modify('+1 hour');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be set to 1 hour in the future.');
        }

        /**
         * Test setting expiration using expiresAfter with a DateInterval.
         */
        public function testExpiresAfterInterval(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $interval = new DateInterval('PT1H'); // 1 hour interval
            $item->expiresAfter($interval);

            $expectedExpiration = (new DateTime())->add($interval);
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should match the expected interval.');
        }

        /**
         * Test that a cache item does not expire when expiresAfter(null) is used.
         */
        public function testExpiresAfterNull(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $item->expiresAfter(null); // No expiration

            $this->assertNull($item->getExpirationTime(), 'Expiration should be null when expiresAfter(null) is used.');
        }

        /**
         * Test that a cache value can be set and retrieved using the set method.
         */
        public function testSetAndRetrieveValue(): void
        {
            $item = new CacheItem('test_key');
            $item->set('new_value');
            $this->assertEquals('new_value', $item->get(), 'Cache value should match the set value.');
        }

        /**
         * Test that the getTimeUntilExpiration method returns correct values for valid and expired items.
         */
        public function testGetTimeUntilExpiration(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Test with future expiration
            $item->expiresAfter(3600); // Expires in 1 hour
            $this->assertGreaterThan(0, $item->getTimeUntilExpiration(), 'Time until expiration should be positive for future expiration.');

            // Test with expired item
            $expiredItem = new CacheItem('expired_key', 'test_value', true, (new DateTime())->modify('-1 hour'));
            $this->assertNull($expiredItem->getTimeUntilExpiration(), 'Time until expiration should be null for expired items.');
        }

        /**
         * Test that getTimeUntilExpiration returns null when no expiration is set.
         */
        public function testGetTimeUntilExpirationReturnsNullWithoutExpiration(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Ensure the item has no expiration set.
            $this->assertNull($item->getExpirationTime(), 'Expiration should be null for a new item with no expiration set.');

            // Call getTimeUntilExpiration and check that it returns null.
            $this->assertNull($item->getTimeUntilExpiration(), 'getTimeUntilExpiration should return null if no expiration is set.');
        }


        /**
         * Test that invalidating the cache item sets the expiration to the current time.
         */
        public function testInvalidate(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $item->invalidate();

            $this->assertLessThanOrEqual(new DateTime(), $item->getExpirationTime(), 'Expiration should be set to the current time or earlier when invalidated.');
        }

        /**
         * Test extending expiration using extendExpiration with seconds.
         */
        public function testExtendExpirationWithSeconds(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Set the initial expiration to 1 hour from now
            $item->expiresAfter(3600);

            // Extend expiration by another 30 minutes (1800 seconds)
            $item->extendExpiration(1800);

            // Expected expiration should be 1 hour 30 minutes from now
            $expectedExpiration = (new DateTime())->modify('+1 hour 30 minutes');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be extended by 30 minutes.');
        }

        /**
         * Test extending expiration using extendExpiration with DateInterval.
         */
        public function testExtendExpirationWithInterval(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Set the initial expiration to 1 hour from now
            $item->expiresAfter(3600);

            // Extend expiration by another 15 minutes using DateInterval
            $interval = new DateInterval('PT15M');
            $item->extendExpiration($interval);

            // Expected expiration should be 1 hour 15 minutes from now
            $expectedExpiration = (new DateTime())->modify('+1 hour 15 minutes');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be extended by 15 minutes.');
        }

        /**
         * Test extending expiration when the item uses DateTime.
         */
        public function testExtendExpirationWithDateTime(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Set initial expiration to 1 hour from now
            $item->expiresAfter(3600);

            // Extend expiration by 30 minutes (1800 seconds)
            $item->extendExpiration(1800);

            // Expected expiration should be 1 hour 30 minutes from now
            $expectedExpiration = (new DateTime())->modify('+1 hour 30 minutes');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be extended by 30 minutes.');
        }

        /**
         * Test extending expiration when the item uses DateTimeImmutable.
         */
        public function testExtendExpirationWithDateTimeImmutable(): void
        {
            // Set the initial expiration to 1 hour from now using DateTimeImmutable
            $expiration = new DateTimeImmutable('+1 hour');
            $item = new CacheItem('test_key', 'test_value', true, $expiration);

            // Extend expiration by 30 minutes (1800 seconds)
            $item->extendExpiration(1800);

            // Expected expiration should be 1 hour 30 minutes from now
            $expectedExpiration = (new DateTime())->modify('+1 hour 30 minutes');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be extended by 30 minutes when using DateTimeImmutable.');
        }

        /**
         * Test extending expiration when no expiration is initially set (should default to current time).
         */
        public function testExtendExpirationWithNoInitialExpiration(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Extend expiration by 1 hour (3600 seconds)
            $item->extendExpiration(3600);

            // Expected expiration should be 1 hour from now
            $expectedExpiration = (new DateTime())->modify('+1 hour');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be set to 1 hour from now when no initial expiration was set.');
        }

        /**
         * Test extending expiration using a DateInterval.
         */
        public function testExtendExpirationWithDateInterval(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Set initial expiration to 1 hour from now
            $item->expiresAfter(3600);

            // Extend expiration by another 15 minutes using DateInterval
            $interval = new DateInterval('PT15M');
            $item->extendExpiration($interval);

            // Expected expiration should be 1 hour 15 minutes from now
            $expectedExpiration = (new DateTime())->modify('+1 hour 15 minutes');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be extended by 15 minutes using DateInterval.');
        }

        /**
         * Test extending expiration multiple times.
         */
        public function testExtendExpirationMultipleTimes(): void
        {
            $item = new CacheItem('test_key', 'test_value');

            // Set initial expiration to 1 hour from now
            $item->expiresAfter(3600);

            // Extend expiration by 15 minutes
            $item->extendExpiration(900); // 15 minutes in seconds

            // Extend expiration again by another 15 minutes
            $item->extendExpiration(900);

            // Expected expiration should be 1 hour 30 minutes from now
            $expectedExpiration = (new DateTime())->modify('+1 hour 30 minutes');
            $this->assertEqualsWithDelta($expectedExpiration->getTimestamp(), $item->getExpirationTime()?->getTimestamp(), 2, 'Expiration should be extended by 30 minutes in total when extended multiple times.');
        }

        /**
         * Test that hasExpired returns true for an expired item.
         */
        public function testHasExpiredWithExpiredItem(): void
        {
            $expiredTime = (new DateTime())->modify('-1 hour');
            $item = new CacheItem('test_key', 'test_value', true, $expiredTime);

            $this->assertTrue($item->hasExpired(), 'hasExpired should return true for an expired cache item.');
        }

        /**
         * Test that hasExpired returns false for a non-expired item.
         */
        public function testHasExpiredWithNonExpiredItem(): void
        {
            $futureTime = (new DateTime())->modify('+1 hour');
            $item = new CacheItem('test_key', 'test_value', true, $futureTime);

            $this->assertFalse($item->hasExpired(), 'hasExpired should return false for a non-expired cache item.');
        }

        /**
         * Test that isPersistent returns true for an item with no expiration.
         */
        public function testIsPersistentWithNoExpiration(): void
        {
            $item = new CacheItem('test_key', 'test_value');
            $this->assertTrue($item->isPersistent(), 'isPersistent should return true for an item with no expiration.');
        }

        /**
         * Test that isPersistent returns false for an item with an expiration.
         */
        public function testIsPersistentWithExpiration(): void
        {
            $futureTime = (new DateTime())->modify('+1 hour');
            $item = new CacheItem('test_key', 'test_value', true, $futureTime);
            $this->assertFalse($item->isPersistent(), 'isPersistent should return false for an item with an expiration.');
        }
    }
}