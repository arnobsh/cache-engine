<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/8/2019
 * Time: 9:01 PM
 */

namespace Cache\Storage\Utility;
use Cache\Storage\CacheInterface;

class PrefixReset extends PrefixKeys
{
    /**
     * @var string
     */
    protected $collection;
    /**
     * @param CacheInterface $cache
     * @param string        $name
     */
    public function __construct(CacheInterface $cache, $name)
    {
        $this->cache = $cache;
        $this->collection = $name;
        parent::__construct($cache, $this->getPrefix());
    }
    /**
     */
    public function flush()
    {
        $index = $this->cache->increment($this->collection);
        $this->setPrefix($this->collection.':'.$index.':');
        return $index !== false;
    }
    /**
     * @return string
     */
    protected function getPrefix()
    {
        $index = $this->cache->get($this->collection);
        if ($index === false) {
            $index = $this->cache->set($this->collection, 1);
        }
        return $this->collection.':'.$index.':';
    }
}