<?php
namespace M6Web\Component\CacheExtra;

use M6Web\Component\CacheExtra\Resetter\CacheResetterInterface;

/**
 * Cache Interface
 */
interface CacheInterface
{
    /**
     * Checks if the cache has a value for a key.
     *
     * @param string $key A unique key
     *
     * @return Boolean Whether the cache has a value for this key
     */
    public function has($key);

    /**
     * Returns the value for a key.
     *
     * @param string $key A unique key
     *
     * @return string|null The value in the cache
     */
    public function get($key);

    /**
     * Returns the value for a key.
     *
     * @param string $key A unique key
     *
     * @return string|null The value in the cache
     */
    public function getConcurrent($key);

    /**
     * Sets a value in the cache.
     *
     * @param string  $key   A unique key
     * @param string  $value The value to cache
     * @param integer $ttl   Time to live in seconds
     */
    public function set($key, $value, $ttl = null);

    /**
     * Sets a value in the cache.
     *
     * @param string  $key   A unique key
     * @param string  $value The value to cache
     * @param integer $ttl   Time to live in seconds
     */
    public function setConcurrent($key, $value, $ttl = null);

    /**
     * Removes a value from the cache.
     *
     * @param string $key A unique key
     */
    public function remove($key);

    /**
     * Return the TTL in second of a cache key or false if key doesn't exist
     * @param string $key The key
     *
     * @return integer|false the ttl or false
     */
    public function ttl($key);

    /**
     * Set a cache resetter service to ignore cache
     *
     * @param CacheResetterInterface $cacheResetterService The cache resetter service
     */
    public function setCacheResetterService(CacheResetterInterface $cacheResetterService);
}
