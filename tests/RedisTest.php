<?php
namespace Cache\Storage\Tests;


use Cache\Storage\Adapters\Redis;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RedisTest extends TestCase
{
    private $cache;
    private $collection;

    public static function  setUpBeforeClass()
    {

    }

    public function setUp()
    {
        $redisClient = new \Redis();
        $redisClient->connect('127.0.0.1', 6379);
        $redisClient->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        $redisClient->setOption(\Redis::OPT_PREFIX, 'myNotepadApp:');	// use custom prefix on all keys
        $this->cache = new Redis($redisClient);
        $this->collection = "RedisTestCollection";

    }

    public function testGetAndSet()
    {
        $return = $this->cache->set('test key', 'value');
        $this->assertEquals(true, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }


    public function testGetFail()
    {
        $this->assertEquals(false, $this->cache->get('failed key'));
    }
    public function testGetNonReferential()
    {
        // this is mostly for MemoryStore - other stores probably aren't at risk
        $object = new \stdClass();
        $object->value = 'test';
        $this->cache->set('test key', $object);
        // clone the object because we'll be messing with it ;)
        $comparison = clone $object;
        // changing the object after it's been cached shouldn't affect cache
        $object->value = 'updated-value';
        $fromCache = $this->cache->get('test key');
        $this->assertEquals($comparison, $fromCache);
        // changing the value we got from cache shouldn't change what's in cache
        $fromCache->value = 'updated-value-2';
        $fromCache2 = $this->cache->get('test key');
        $this->assertNotEquals($comparison, $fromCache);
        $this->assertEquals($comparison, $fromCache2);
    }
    public function testGetMulti()
    {
        $items = array(
            'failed key' => 'value',
            'failed key2' => 'value2',
        );
        foreach ($items as $key => $value) {
            $this->cache->set($key, $value);
        }
        $this->assertEquals($items, $this->cache->getMulti(array_keys($items)));
        $this->assertEquals(array('failed key' => 'value','failed key2' => 'value2'),
                            $this->cache->getMulti(array('failed key', 'failed key2')));
    }


    public function testSetExpired()
    {
        $return = $this->cache->set('test key', 'value', time() - 2);
        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        // test if we can add to, but not replace or touch an expired value; it
        // should be treated as if the value doesn't exist)
        $return = $this->cache->replace('test key', 'value');
        $this->assertEquals(false, $return);
        $return = $this->cache->touch('test key', time() + 2);
        $this->assertEquals(false, $return);
        $return = $this->cache->add('test key', 'value');
        $this->assertEquals(true, $return);
    }
    public function testSetMulti()
    {
        $items = array(
            'test key' => 'value',
            'key2' => 'value2',
        );
        $return = $this->cache->setMulti($items);
        $expect = array_fill_keys(array_keys($items), true);
        $this->assertEquals($expect, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
        $this->assertEquals('value2', $this->cache->get('key2'));
    }
    public function testSetMultiIntegerKeys()
    {
        $items = array(
            '0' => 'value',
            '1' => 'value2',
        );
        $return = $this->cache->setMulti($items);
        $expect = array_fill_keys(array_keys($items), true);
        $this->assertEquals($expect, $return);
        $this->assertEquals('value', $this->cache->get('0'));
        $this->assertEquals('value2', $this->cache->get('1'));
    }
    public function testSetMultiExpired()
    {
        $items = array(
            'test key' => 'value',
            'key2' => 'value2',
        );
        $return = $this->cache->setMulti($items, time() - 2);
        $expect = array_fill_keys(array_keys($items), true);
        $this->assertEquals($expect, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        $this->assertEquals(false, $this->cache->get('key2'));
    }

    public function testDelete()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->delete('test key');
        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
        // delete non-existing key
        $return = $this->cache->delete('key2');
        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('key2'));
    }

    public function testAdd()
    {
        $return = $this->cache->add('test key', 'value');
        $this->assertEquals(true, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }
    public function testAddFail()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->add('test key', 'value-2');
        $this->assertEquals(false, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }

    public function testReplace()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->replace('test key', 'value-2');
        $this->assertEquals(true, $return);
        $this->assertEquals('value-2', $this->cache->get('test key'));
    }
    public function testReplaceFail()
    {
        $return = $this->cache->replace('replace key', 'value');
        $this->assertEquals(false, $return);
        $this->assertEquals(false, $this->cache->get('replace key'));
    }
    public function testReplaceExpired()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->replace('test key', 'value', time() - 2);
        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }
    public function testReplaceSameValue()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->replace('test key', 'value');
        $this->assertEquals(true, $return);
    }

    public function testTouch()
    {
        $this->cache->set('test key', 'value');
        // not yet expired
        $return = $this->cache->touch('test key', time() + 2);
        $this->assertEquals(true, $return);
        $this->assertEquals('value', $this->cache->get('test key'));
    }
    public function testTouchExpired()
    {
        $this->cache->set('test key', 'value');
        // expired
        $return = $this->cache->touch('test key', time() - 2);
        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }
    public function testFlush()
    {
        $this->cache->set('test key', 'value');
        $return = $this->cache->flush();
        $this->assertEquals(true, $return);
        $this->assertEquals(false, $this->cache->get('test key'));
    }
    public function testCollectionGetParentKey()
    {
        $collection = $this->cache->getCollection($this->collectionName);
        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->get('key'));
        $this->assertEquals(false, $collection->get('key'));
    }
    public function testCollectionGetCollectionKey()
    {
        $collection = $this->cache->getCollection($this->collectionName);
        $collection->set('key', 'value');
        $this->assertEquals('value', $collection->get('key'));
        $collection->flush();
    }
    public function testCollectionSetSameKey()
    {
        $collection = $this->cache->getCollection($this->collectionName);
        $collection->set('key', 'other-value');
        $this->assertEquals('other-value', $collection->get('key'));
        $collection->flush();
    }
    public function testCollectionFlushParent()
    {
        $collection = $this->cache->getCollection($this->collectionName);
        $this->cache->set('key', 'value');
        $collection->set('key', 'other-value');
        $this->cache->flush();
        $this->assertEquals(false, $this->cache->get('key'));
        $this->assertEquals(false, $collection->get('key'));
        $collection->flush();
    }
    public function testCollectionFlushCollection()
    {
        $collection = $this->cache->getCollection($this->collectionName);
        $collection->set('key', 'other-value');
        $collection->flush();
        $this->assertEquals(false, $collection->get('key'));
    }
}