<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/6/2019
 * Time: 12:51 PM
 */

namespace Cache\Storage\Adapters;
use Cache\Storage\Collections\Redis as Collection;
use Cache\Storage\Exception\InvalidCollection;
use Cache\Storage\CacheInterface;
/**
 * Redis adapter. Basically just a wrapper over \Redis, but in an exchangeable
 * (CacheInterface) interface.
 *

 */
class Redis implements CacheInterface
{
    /**
     * @var \Redis
     */
    protected $client;
    /**
     * @var string|null
     */
    protected $version;
    /**
     * @param \Redis $client
     */
    public function __construct(\Redis $client)
    {
        $this->client = $client;
        // set a serializer if none is set already
        if ($this->client->getOption(\Redis::OPT_SERIALIZER) == \Redis::SERIALIZER_NONE) {
            $this->client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        }
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
        $this->client->multi();
        $this->client->get($key);
        $this->client->exists($key);
        /** @var array $return */
        $return = $this->client->exec();
        if ($return === false) {
            return false;
        }
        $value = $return[0];
        $exists = $return[1];
        // no value = quit early, don't generate a useless token
        if (!$exists) {
            return false;
        }
        return $value;
    }
    /**
     */
    public function getMulti(array $keys)
    {
        if (empty($keys)) {
            return array();
        }
        $this->client->multi();
        $this->client->mget($keys);
        foreach ($keys as $key) {
            $this->client->exists($key);
        }
        /** @var array $return */
        $return = $this->client->exec();
        if ($return === false) {
            return array();
        }
        $values = array_shift($return);
        $exists = $return;
        if ($values === false) {
            $values = array_fill_keys($keys, false);
        }
        $values = array_combine($keys, $values);
        $exists = array_combine($keys, $exists);
        foreach ($values as $key => $value) {
            // filter out non-existing values
            if ($exists[$key] === false) {
                unset($values[$key]);
                continue;
            }
        }
        return $values;
    }
    /**
     */
    public function set($key, $value, $expire = 0)
    {
        $ttl = $this->ttl($expire);

        if ($ttl < 0) {
            $this->delete($key);
            return true;
        }

        return $this->client->set($key, $value, $ttl);
    }
    /**
     */
    public function setMulti(array $items, $expire = 0)
    {
        if (empty($items)) {
            return array();
        }
        $ttl = $this->ttl($expire);

        if ($ttl < 0) {
            $this->deleteMulti(array_keys($items));
            return array_fill_keys(array_keys($items), true);
        }
        if ($ttl === null) {
            $success = $this->client->mset($items);
            return array_fill_keys(array_keys($items), $success);
        }
        $this->client->multi();
        $this->client->mset($items);
        // Redis has no convenient multi-expire method
        foreach ($items as $key => $value) {
            $this->client->expire($key, $ttl);
        }
        /* @var bool[] $return */
        $result = (array) $this->client->exec();
        $return = array();
        $keys = array_keys($items);
        $success = array_shift($result);
        foreach ($result as $i => $value) {
            $key = $keys[$i];
            $return[$key] = $success && $value;
        }
        return $return;
    }
    /**
     */
    public function delete($key)
    {
        return (bool) $this->client->del($key);
    }
    /**
     */
    public function deleteMulti(array $keys)
    {
        if (empty($keys)) {
            return array();
        }

        $items = $this->getMulti($keys);
        $this->client->del($keys);
        $return = array();
        foreach ($keys as $key) {
            $return[$key] = array_key_exists($key, $items);
        }
        return $return;
    }
    /**
     */
    public function add($key, $value, $expire = 0)
    {
        $ttl = $this->ttl($expire);

        if ($ttl < 0) {
            return true;
        }
        if ($ttl === null) {
            return $this->client->setnx($key, $value);
        }

        $this->client->multi();
        $this->client->setnx($key, $value);
        $this->client->expire($key, $ttl);
        /** @var bool[] $return */
        $return = (array) $this->client->exec();
        return !in_array(false, $return);
    }
    /**
     */
    public function replace($key, $value, $expire = 0)
    {
        $ttl = $this->ttl($expire);

        if ($ttl < 0) {
            return $this->delete($key);
        }

        if ($this->version === null || $this->supportsOptionsArray()) {
            $options = array('xx');
            if ($ttl > 0) {
                $options['ex'] = $ttl;
            }
            // either we support options array or we haven't yet checked, in
            // which case I'll assume a recent server is running
            $result = $this->client->set($key, $value, $options);
            if ($result !== false) {
                return $result;
            }
            if ($this->supportsOptionsArray()) {
                // failed execution, but not because our Redis version is too old
                return false;
            }
        }
        // workaround for old Redis versions
        $this->client->watch($key);
        $exists = $this->client->exists('key');
        if (!$exists) {
             if (method_exists($this->client, 'unwatch')) {
                $this->client->unwatch();
            } else {
                // this should also kill the watch...
                $this->client->multi()->discard();
            }
            return false;
        }
        // since we're watching the key, this will fail should it change in the
        // meantime
        $this->client->multi();
        $this->client->set($key, $value, $ttl);
        /** @var bool[] $return */
        $return = (array) $this->client->exec();
        return !in_array(false, $return);
    }

    /**
     */
    public function touch($key, $expire)
    {
        $ttl = $this->ttl($expire);
        if ($ttl < 0) {
            // Redis can't set expired, so just remove in that case ;)
            return (bool) $this->client->del($key);
        }
        return $this->client->expire($key, $ttl);
    }
    /**
     */
    public function flush()
    {
        return $this->client->flushAll();
    }
    /**
     */
    public function getCollection($name)
    {
        // operate on a different database
        $client = new \Redis();
        if ($this->client->getPersistentID() !== null) {
            $client->pconnect(
                $this->client->getHost(),
                $this->client->getPort(),
                $this->client->getTimeout()
            );
        } else {
            $client->connect(
                $this->client->getHost(),
                $this->client->getPort(),
                $this->client->getTimeout()
            );
        }
        $auth = $this->client->getAuth();
        if ($auth !== null) {
            $client->auth($auth);
        }
        $readTimeout = $this->client->getReadTimeout();
        if ($readTimeout) {
            $client->setOption(\Redis::OPT_READ_TIMEOUT, $this->client->getReadTimeout());
        }
        return new Collection($client, $name);
    }
    /**
     * Redis expects true TTL, not expiration timestamp.
     *
     * @param int $expire
     *
     * @return int|null TTL in seconds, or `null` for no expiration
     */
    protected function ttl($expire)
    {
        if ($expire === 0) {
            return null;
        }
        // relative time in seconds, <30 days
        if ($expire > 30 * 24 * 60 * 60) {
            return $expire - time();
        }
        return $expire;
    }

    /**
     * Returns the version of the Redis server we're connecting to.
     *
     * @return string
     */
    protected function getVersion()
    {
        if ($this->version === null) {
            $info = $this->client->info();
            $this->version = $info['redis_version'];
        }
        return $this->version;
    }
    /**
     * Version-based check to test if passing an options array to set() is
     * supported.
     *
     * @return bool
     */
    protected function supportsOptionsArray()
    {
        return version_compare($this->getVersion(), '2.6.12') >= 0;
    }
}