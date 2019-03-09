<?php
/**
 * Created by PhpStorm.
 * User: arnob.s
 * Date: 3/8/2019
 * Time: 12:05 AM
 */
require 'vendor/autoload.php';

$simple_framework = Base::instance();

/**
 * Routes for simple fat free framework application
 */

// Home
# http://localhost:80/
$simple_framework->route('GET /',
    function(){
        echo "Welcome to the Cache storage engine";
    });

//Test Redis Connection
# http://localhost:80/redis
$simple_framework->route('GET /redis',
    function(){
        $client = new \Redis();
        $client->connect('127.0.0.1',6379);
        $cache = new \Cache\Storage\Adapters\Redis($client);

        // set the authorization collection cache
        $authCollection = $cache->getCollection('authorization');
        $cache->set('token', 'LOCALTOKEN');
        $authCollection->set('token', 'COLLECTIONTOKEN');
        echo "Local Token is: ".$cache->get('token')."\n";
        echo "Authorization Token is: ".$authCollection->get('token')."\n";
    }
);

//Test Memcached Connection
# http://localhost:80/memcached
$simple_framework->route('GET /memcached',
    function(){
        $client = new \Memcached();
        $client->addServer('127.0.0.1',11211);
        $cache = new \Cache\Storage\Adapters\Memcached($client);

        // set the authorization collection cache
        $authCollection = $cache->getCollection('authorization');
        $cache->set('token', 'LOCALTOKEN');
        $authCollection->set('token', 'COLLECTIONTOKEN');
        echo "Local Token is: ".$cache->get('token')."\n";
        echo "Authorization Token is: ".$authCollection->get('token')."\n";
    }
);
// Gets All Available Locales
# http://localhost:80
//$f3->route('GET /locale',
//    function() use ($localeController) {
//        return $localeController->showAllAvailable();
//    }
//);



/**
 * Run F3 Application
 */
$simple_framework->run();
