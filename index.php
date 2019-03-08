<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/8/2019
 * Time: 12:05 AM
 */

echo "This is a Test Cache Library\n";

/**
 * @author Arnob Saha
 * @created 03.04.19
 */
require 'vendor/autoload.php';

$client = new \Redis();
$client->connect('127.0.0.1',6379);
$cache = new \Cache\Storage\Adapters\Redis($client);

// set the authorization collection cache
$authCollection = $cache->getCollection('authorization');
$cache->set('token', 'LOCALTOKEN');
$authCollection->set('token', 'COLLECTIONTOKEN');

echo "Local Token is: ".$cache->get('token')."\n";
echo "Authorization Token is: ".$authCollection->get('token')."\n";