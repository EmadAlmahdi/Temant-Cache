<?php declare(strict_types=1);
use Temant\Cache\Adapter\FileSystemCacheAdapter;
use Temant\Cache\Adapter\MemcachedCacheAdapter;
use Temant\Cache\Adapter\RedisCacheAdapter;
use Temant\Cache\CacheManager;

require __DIR__ . "/vendor/autoload.php";

// Initialize Redis-based cache pool
$memcachedCacheAdapter = new MemcachedCacheAdapter('127.0.0.1', 11211);

// Initialize the file-based cache pool
$fileCachePool = new FileSystemCacheAdapter(__DIR__ . '/cache');

// Initialize Redis-based cache pool
$redisCacheAdapter = new RedisCacheAdapter('127.0.0.1', 6379);

// Create the CacheManager with a default adapter (file cache)
$cacheManager = new CacheManager($fileCachePool);

// Add the Redis adapter
$cacheManager->addAdapter('redis', $redisCacheAdapter);

// Save an item using the default file cache (already registered as 'default')
$item = $cacheManager->getItem('my_key');
$item->set('my_value');
$cacheManager->save($item);

// Switch to the Redis adapter
$cacheManager->switchAdapter('redis');

// Save an item to Redis
$redisItem = $cacheManager->getItem('redis_key');
$redisItem->set('redis_value');
$cacheManager->save($redisItem);

// Retrieve from Redis
$retrievedRedisItem = $cacheManager->getItem('redis_key');
dump(sprintf("Adapter=%s, Value=%s", $cacheManager->getAdapter()::class, $retrievedRedisItem->get()));

// Switch back to the default file cache
$cacheManager->switchAdapter('default');

// Retrieve the file cache item
$retrievedFileItem = $cacheManager->getItem('my_key');
dump(sprintf("Adapter=%s, Value=%s", $cacheManager->getAdapter()::class, $retrievedFileItem->get()));

$cacheManager->deleteItem('my_key');

rmdir(__DIR__ . "/cache");