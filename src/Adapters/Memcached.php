<?php
namespace Cache\Storage\Adapters;
use Cache\Storage\Collections\Memcached as Collection;
use Cache\Storage\Exception\InvalidKey;
use Cache\Storage\Exception\OperationFailed;
use Cache\Storage\CacheInterface;
/**
 * Memcached adapter. Basically just a wrapper over \Memcached, but in an
 * exchangeable (KeyValueStore) interface.
 */
class Memcached implements CacheInterface
{
    /**
     * @var \Memcached
     */
    protected $client;
    /**
     * @param \Memcached $client
     */
    public function __construct(\Memcached $client)
    {
        $this->client = $client;
    }

    public function exists($key)
    {
        if($this->client->exists($key))
            return true;
        else return false;
    }
    /**
     */
    public function get($key)
    {
        $values = $this->getMulti(array($key));
        if (!isset($values[$key])) {
            return false;
        }
        return $values[$key];
    }
    /**
     */
    public function getMulti(array $keys)
    {
        $tokens = array();
        if (empty($keys)) {
            return array();
        }
        $keys = array_map(array($this, 'encode'), $keys);

            $return = $this->client->getMulti($keys);
            $this->throwExceptionOnClientCallFailure($return);
        $keys = array_map(array($this, 'decode'), array_keys($return));
        $return = array_combine($keys, $return);
        return $return ?: array();
    }
    /**
     */
    public function set($key, $value, $expire = 0)
    {
        // Memcached seems to not timely purge items the way it should when
        // storing it with an expired timestamp
        if ($expire < 0 || ($expire > 2592000 && $expire < time())) {
            $this->delete($key);
            return true;
        }
        $key = $this->encode($key);
        return $this->client->set($key, $value, $expire);
    }
    /**
     */
    public function setMulti(array $items, $expire = 0)
    {
        if (empty($items)) {
            return array();
        }
        // Memcached seems to not timely purge items the way it should when
        // storing it with an expired timestamp
        if ($expire < 0 || ($expire > 2592000 && $expire < time())) {
            $keys = array_keys($items);
            $this->deleteMulti($keys);
            return array_fill_keys($keys, true);
        }
        if (defined('HHVM_VERSION')) {
            $nums = array_filter(array_keys($items), 'is_numeric');
            if (!empty($nums)) {
                return $this->setMultiNumericItemsForHHVM($items, $nums, $expire);
            }
        }
        $keys = array_map(array($this, 'encode'), array_keys($items));
        $items = array_combine($keys, $items);
        $success = $this->client->setMulti($items, $expire);
        $keys = array_map(array($this, 'decode'), array_keys($items));
        return array_fill_keys($keys, $success);
    }
    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->encode($key);
        return $this->client->delete($key);
    }
    /**
     * {@inheritdoc}
     */
    public function deleteMulti(array $keys)
    {
        if (empty($keys)) {
            return array();
        }
        if (!method_exists($this->client, 'deleteMulti')) {
            $values = $this->getMulti($keys);
            $keys = array_map(array($this, 'encode'), array_keys($values));
            $this->client->setMulti(array_fill_keys($keys, ''), time() - 1);
            $return = array();
            foreach ($keys as $key) {
                $key = $this->decode($key);
                $return[$key] = array_key_exists($key, $values);
            }
            return $return;
        }
        $keys = array_map(array($this, 'encode'), $keys);
        $result = (array) $this->client->deleteMulti($keys);
        $keys = array_map(array($this, 'decode'), array_keys($result));
        $result = array_combine($keys, $result);

        foreach ($result as $key => $status) {
            $result[$key] = $status === true;
        }
        return $result;
    }
    /**
     */
    public function add($key, $value, $expire = 0)
    {
        $key = $this->encode($key);
        return $this->client->add($key, $value, $expire);
    }
    /**
     */
    public function replace($key, $value, $expire = 0)
    {
        $key = $this->encode($key);
        return $this->client->replace($key, $value, $expire);
    }


    /**
     */
    public function touch($key, $expire)
    {
        if ($expire < 0 || ($expire > 2592000 && $expire < time())) {
            return $this->delete($key);
        }
        return $value = $this->get($key);
    }
    /**
     */
    public function flush()
    {
        return $this->client->flush();
    }
    /**
     */
    public function getCollection($name)
    {
        return new Collection($this, $name);
    }

    /**
     * Encode a key for use on the wire inside the memcached protocol.
     *
     * @param string $key
     *
     * @return string
     *
     * @throws InvalidKey
     */
    protected function encode($key)
    {
        $regex = '/[^\x21\x22\x24\x26-\x39\x3b-\x7e]+/';
        $key = preg_replace_callback($regex, function ($match) {
            return rawurlencode($match[0]);
        }, $key);
        if (strlen($key) > 255) {
            throw new InvalidKey(
                "Invalid key: $key. Encoded Memcached keys can not exceed 255 chars."
            );
        }
        return $key;
    }
    /**
     * Decode a key encoded with encode().
     *
     * @param string $key
     *
     * @return string
     */
    protected function decode($key)
    {
        // matches %20, %7F, ... but not %21, %22, ...
        // (=the encoded versions for those encoded in encode)
        $regex = '/%(?!2[1246789]|3[0-9]|3[B-F]|[4-6][0-9A-F]|5[0-9A-E])[0-9A-Z]{2}/i';
        return preg_replace_callback($regex, function ($match) {
            return rawurldecode($match[0]);
        }, $key);
    }
    /**
     * Numerical strings turn into integers when used as array keys, and
     *
     * @param array $items
     * @param array $nums
     * @param int   $expire
     *
     * @return array
     */
    protected function setMultiNumericItemsForHHVM(array $items, array $nums, $expire = 0)
    {
        $success = array();
        $nums = array_intersect_key($items, array_fill_keys($nums, null));
        foreach ($nums as $k => $v) {
            $success[$k] = $this->set((string) $k, $v, $expire);
        }
        $remaining = array_diff_key($items, $nums);
        if ($remaining) {
            $success += $this->setMulti($remaining, $expire);
        }
        return $success;
    }
    /**
     * Will throw an exception if the returned result from a Memcached call
     * indicates a failure in the operation.
     * The exception will contain debug information about the failure.
     *
     * @param mixed $result
     *
     * @throws OperationFailed
     */
    protected function throwExceptionOnClientCallFailure($result)
    {
        if ($result !== false) {
            return;
        }
        throw new OperationFailed(
            $this->client->getResultMessage(),
            $this->client->getResultCode()
        );
    }
}