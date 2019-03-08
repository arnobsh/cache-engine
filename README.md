Cache Storage Engine
======================

## Installation and Usages:

Cache Service actually works as storage of the objects in Key Value pairs. Right now, it is incorporated with
Redis cache service . But we can extend the service with the Mysql Adapter/Database adapter/Any other caching mechanism based on our needs

`composer require storage`

# Test URL

Test Url would be

http://localhost:80/index.php

# Example

``
$client = new \Redis();
$client->connect('127.0.0.1',6379);
$cache = new \Cache\Storage\Redis($client);

// set the authorization cache
$authCollection = $cache->collection('authorization');
$cache->set('token', 'TOKEN');
$authCollection->set('token', 'TOKEN');
``
## Testing

* run `./run.ps1` from windows or `run.sh` from unix 
* run `vendor/bin/phpunit` 