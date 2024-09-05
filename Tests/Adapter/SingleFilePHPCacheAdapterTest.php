<?php declare(strict_types=1);

namespace Tests\Temant\Cache\Adapter {

    use org\bovigo\vfs\vfsStream;
    use Temant\Cache\Adapter\SingleFilePHPCacheAdapter;
    use Temant\Cache\Exception\CacheException;

    class SingleFilePHPCacheAdapterTest extends AbstractCacheAdapterTest
    {
        private string $cacheFile;

        protected function setUp(): void
        {
            $this->cacheFile = sys_get_temp_dir() . '/single_file_php_cache_test.php';
            $this->cachePool = new SingleFilePHPCacheAdapter($this->cacheFile);
            // Ensure the file is clean before running tests
            if (file_exists($this->cacheFile)) {
                unlink($this->cacheFile);
            }
        }

        protected function tearDown(): void
        {
            // Ensure the cache file is cleared after each test
            if (file_exists($this->cacheFile)) {
                unlink($this->cacheFile);
            }
        }

        /**
         * Test that the constructor throws CacheException when the cache directory cannot be created.
         */
        public function testConstructorThrowsExceptionOnDirectoryCreationFailure(): void
        {
            // Set up the virtual file system with a read-only root directory
            vfsStream::setup('root', 0444); // Read-only permission (no write access)

            // Expect the CacheException to be thrown due to inability to create directory
            $this->expectException(CacheException::class);
            $this->expectExceptionMessage("Failed to create cache file: vfs://root/cache_test");

            // Try to instantiate FileSystemCacheAdapter with a directory that cannot be created
            new SingleFilePHPCacheAdapter(vfsStream::url('root/cache_test'));
        }

        /**
         * Test `writeFileWithLock` when `fopen` fails.
         */
        public function testWriteFileWithLockFopenFails(): void
        {
            // Set up a virtual file system where the directory doesn't allow file creation
            $root = vfsStream::setup('root', 0000); // No permissions
            $cacheFile = vfsStream::url('root/cache_test');

            // Expect the file creation to fail due to `fopen` returning false
            $reflection = new \ReflectionClass($this->cachePool);
            $method = $reflection->getMethod('writeFileWithLock');
            $method->setAccessible(true);

            // Invoke the method and check that it returns false when `fopen` fails
            $result = $method->invokeArgs($this->cachePool, [$cacheFile, "<?php return [];"]);
            $this->assertFalse($result, 'Expected file write to fail due to fopen failure.');
        }

        /**
         * Test `writeFileWithLock` when `flock` fails.
         */
        public function testWriteFileWithLockFlockFails(): void
        {
            // Create a writable directory in the virtual file system
            vfsStream::setup('root');
            $cacheFile = vfsStream::url('root/cache_test');

            // Open the file and lock it manually to simulate another process holding the lock
            $fileHandle = fopen($cacheFile, 'c');

            if (!$fileHandle) {
                return;
            }
            
            flock($fileHandle, LOCK_EX);

            // Expect the file locking to fail when `flock` is called again in `writeFileWithLock`
            $reflection = new \ReflectionClass($this->cachePool);
            $method = $reflection->getMethod('writeFileWithLock');
            $method->setAccessible(true);

            // Invoke the method and check that it returns false due to `flock` failure
            $result = $method->invokeArgs($this->cachePool, [$cacheFile, "<?php return [];"]);
            $this->assertFalse($result, 'Expected file write to fail due to flock failure.');

            // Release the lock
            flock($fileHandle, LOCK_UN);
            fclose($fileHandle);
        }
    }
}
