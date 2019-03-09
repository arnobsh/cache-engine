<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/8/2019
 * Time: 8:59 PM
 */
namespace Cache\Storage\Collections;
use Cache\Storage\Adapters\Memcached as Adapter;
use Cache\Storage\Utility\PrefixReset;
/**
 * Memcached adapter for a subset of data, accomplished by prefixing keys.
 *
 */
class Memcached extends PrefixReset
{
    /**
     * @param Adapter $cache
     * @param string  $name
     */
    public function __construct(Adapter $cache, $name)
    {
        parent::__construct($cache, $name);
    }
}