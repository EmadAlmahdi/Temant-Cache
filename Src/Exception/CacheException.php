<?php declare(strict_types=1);

namespace Temant\Cache\Exception {

    class CacheException extends \Exception implements \Psr\Cache\CacheException, \Throwable
    {
    }
}