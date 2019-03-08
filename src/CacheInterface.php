<?php
namespace Cache\Storage;
/**
 * Interface for cache storage engines.
 *
 */
interface CacheInterface
{
    /**
     * Check an item from the cache.
     *
     * @param string $key
     *
     * @return bool true or false on failure
     */
    public function exists($key);
    /**
     * Retrieves an item from the cache.
     *
     * @param string $key
     *
     * @return mixed|bool Value, or false on failure
     */
    public function get($key);
    /**
     * Retrieves multiple items at once.
     *
     * Return value will be an associative array in [key => value] format. Keys
     * missing in cache will be omitted from the array.
     *
     * @param array   $keys
     *
     * @return mixed[] [key => value]
     */
    public function getMulti(array $keys);
    /**
     * Stores a value, regardless of whether or not the key already exists (in
     * which case it will overwrite the existing value for that key).
     *
     * Return value is a boolean true when the operation succeeds, or false on
     * failure.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire Time when item falls out of the cache:
     *                       0 = permanent (doesn't expires);
     *                       under 2592000 (30 days) = relative time, in seconds from now;
     *                       over 2592000 = absolute time, unix timestamp
     *
     * @return bool
     */
    public function set($key, $value, $expire = 0);
    /**
     * Store multiple values at once.
     *
     * Return value will be an associative array in [key => status] form, where
     * status is a boolean true for success, or false for failure.
     *
     * setMulti is preferred over multiple individual set operations as you'll
     * set them all in 1 request.
     *
     * @param mixed[] $items  [key => value]
     * @param int     $expire Time when item falls out of the cache:
     *                        0 = permanent (doesn't expires);
     *                        under 2592000 (30 days) = relative time, in seconds from now;
     *                        over 2592000 = absolute time, unix timestamp
     *
     * @return bool[]
     */
    public function setMulti(array $items, $expire = 0);
    /**
     * Deletes an item from the cache.
     * Returns true if item existed & was successfully deleted, false otherwise.
     *
     * Return value is a boolean true when the operation succeeds, or false on
     * failure.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key);
    /**
     * Deletes multiple items at once (reduced network traffic compared to
     * individual operations).
     *
     * Return value will be an associative array in [key => status] form, where
     * status is a boolean true for success, or false for failure.
     *
     * @param string[] $keys
     *
     * @return bool[]
     */
    public function deleteMulti(array $keys);
    /**
     * Adds an item under new key.
     *
     * This operation fails (returns false) if the key already exists in cache.
     * If the operation succeeds, true will be returned.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire Time when item falls out of the cache:
     *                       0 = permanent (doesn't expires);
     *                       under 2592000 (30 days) = relative time, in seconds from now;
     *                       over 2592000 = absolute time, unix timestamp
     *
     * @return bool
     */
    public function add($key, $value, $expire = 0);
    /**
     * Replaces an item.
     *
     * This operation fails (returns false) if the key does not yet exist in
     * cache. If the operation succeeds, true will be returned.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $expire Time when item falls out of the cache:
     *                       0 = permanent (doesn't expires);
     *                       under 2592000 (30 days) = relative time, in seconds from now;
     *                       over 2592000 = absolute time, unix timestamp
     *
     * @return bool
     */
    public function replace($key, $value, $expire = 0);

    /**
     * Updates an item's expiration time without altering the stored value.
     *
     * Return value is a boolean true when the operation succeeds, or false on
     * failure.
     *
     * @param string $key
     * @param int    $expire Time when item falls out of the cache:
     *                       0 = permanent (doesn't expires);
     *                       under 2592000 (30 days) = relative time, in seconds from now;
     *                       over 2592000 = absolute time, unix timestamp
     *
     * @return bool
     */
    public function touch($key, $expire);
    /**
     * Clears the entire cache (or the everything for the given collection).
     *
     * Return value is a boolean true when the operation succeeds, or false on
     * failure.
     *
     * @return bool
     */
    public function flush();
    /**
     * Returns an isolated subset (collection) in which to store or fetch data
     * from.
     *
     * @param string $name of the collection
     *
     * @return CacheInterface A new CacheInterface instance representing only a
     *                       subset of data on this server
     */
    public function getCollection($name);
}