<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/8/2019
 * Time: 9:01 PM
 */
namespace Cache\Storage\Utility;
use Cache\Storage\CacheInterface;

class PrefixKeys implements CacheInterface
{
    /**
     */
    protected $cache;
    /**
     */
    protected $prefix;
    /**
     * @param CacheInterface $cache
     * @param string        $prefix
     */
    public function __construct(CacheInterface $cache, $prefix)
    {
        $this->cache = $cache;
        $this->setPrefix($prefix);
    }

    public function exists($key)
    {
        $key = $this->prefix($key);
        return $this->cache->exists($key);

    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $key = $this->prefix($key);
        return $this->cache->get($key);
    }
    /**
     * {@inheritdoc}
     */
    public function getMulti(array $keys)
    {
        $keys = array_map(array($this, 'prefix'), $keys);
        $results = $this->cache->getMulti($keys);
        $keys = array_map(array($this, 'unfix'), array_keys($results));
        return array_combine($keys, $results);
    }
    /**
     */
    public function set($key, $value, $expire = 0)
    {
        $key = $this->prefix($key);
        return $this->cache->set($key, func_get_arg(1), $expire);
    }
    /**
     */
    public function setMulti(array $items, $expire = 0)
    {
        $keys = array_map(array($this, 'prefix'), array_keys($items));
        $items = array_combine($keys, $items);
        $results = $this->cache->setMulti($items, $expire);
        $keys = array_map(array($this, 'unfix'), array_keys($results));
        return array_combine($keys, $results);
    }
    /**
     */
    public function delete($key)
    {
        $key = $this->prefix($key);
        return $this->cache->delete($key);
    }
    /**
     */
    public function deleteMulti(array $keys)
    {
        $keys = array_map(array($this, 'prefix'), $keys);
        $results = $this->cache->deleteMulti($keys);
        $keys = array_map(array($this, 'unfix'), array_keys($results));
        return array_combine($keys, $results);
    }
    /**
     */
    public function add($key, $value, $expire = 0)
    {
        $key = $this->prefix($key);
        return $this->cache->add($key, func_get_arg(1), $expire);
    }
    /**
     */
    public function replace($key, $value, $expire = 0)
    {
        $key = $this->prefix($key);
        return $this->cache->replace($key, func_get_arg(1), $expire);
    }


    /**
     */
    public function touch($key, $expire)
    {
        $key = $this->prefix($key);
        return $this->cache->touch($key, $expire);
    }
    /**
     */
    public function flush()
    {
        return $this->cache->flush();
    }
    /**
     */
    public function getCollection($name)
    {
        return $this->cache->getCollection($name);
    }
    /**
     * @param string $prefix
     */
    protected function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }
    /**
     */
    protected function prefix($key)
    {
        return $this->prefix.$key;
    }
    /**
     */
    protected function unfix($key)
    {
        return preg_replace('/^'.preg_quote($this->prefix, '/').'/', '', $key);
    }
}