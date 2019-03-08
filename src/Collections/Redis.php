<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/6/2019
 * Time: 1:23 PM
 */

namespace Cache\Storage\Collections;
use Cache\Storage\Adapters\Redis as Adapter;
/**
 * Redis adapter for a subset of data, in a different database.
 *
 * */
class Redis extends Adapter
{
    /**
     * @param \Redis $client
     * @param int    $database
     */
    public function __construct($client, $database)
    {
        parent::__construct($client);
        $this->setOption(\Redis::OPT_PREFIX,$database);
        $count = $this->client->dbSize();
        $this->client->select($count+1);
    }

    public function setOption( $name, $value ) {
        $this->client->setOption($name, $value);
    }
    /**
     */
    public function flush()
    {
        return $this->client->flushDB();
    }
}