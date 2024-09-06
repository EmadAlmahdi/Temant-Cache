
# Temant Cache System

![Build Status](https://github.com/EmadAlmahdi/Temant-Cache/actions/workflows/ci.yml/badge.svg) 
![Coverage Status](https://codecov.io/gh/EmadAlmahdi/Temant-Cache/branch/main/graph/badge.svg)

A flexible caching system that supports multiple adapters, including Redis, Memcached, and File-based caching. This library is PSR-6 compatible, making it easy to integrate with existing PHP applications.

## Features

- **Redis Cache Adapter**: High-performance caching using Redis.
- **Memcached Cache Adapter**: Lightweight and distributed caching with Memcached.
- **Single-File PHP Cache Adapter**: A simple file-based cache stored in a single PHP file.
- **Flexible Cache Manager**: Seamlessly switch between cache adapters.
- **PSR-6 Compliant**: Compatible with any PSR-6-based applications.

## Installation

1. Install via Composer:
    ```bash
    composer require temant/cache-system
    ```

2. Ensure you have Redis and Memcached installed if using their respective adapters:
    - For Redis: `sudo apt install redis-server`
    - For Memcached: `sudo apt install memcached`

## Usage

### Basic Setup

```php
use Temant\Cache\CacheManager;
use Temant\Cache\Adapter\RedisCacheAdapter;

// Example using Redis adapter
$redisAdapter = new RedisCacheAdapter('127.0.0.1', 6379);
$cacheManager = new CacheManager($redisAdapter);

// Save an item in the cache
$cacheItem = $cacheManager->getItem('my_key');
$cacheItem->set('some_value');
$cacheManager->save($cacheItem);

// Retrieve the item from the cache
$cachedItem = $cacheManager->getItem('my_key');
if ($cachedItem->isHit()) {
    echo $cachedItem->get(); // Outputs 'some_value'
}

// Switch to another adapter dynamically
$memcachedAdapter = new MemcachedCacheAdapter('127.0.0.1', 11211);
$cacheManager->setAdapter($memcachedAdapter);
```

### Running Tests

You can run the test suite with PHPUnit:

```bash
composer test
```

Static analysis with PHPStan:

```bash
composer phpstan
```

## Contributing

Feel free to submit issues or pull requests. For major changes, please open an issue to discuss what you would like to change.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.