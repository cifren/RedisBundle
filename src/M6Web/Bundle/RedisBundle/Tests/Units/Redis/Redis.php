<?php
namespace M6Web\Bundle\RedisBundle\Redis\tests\units;

require_once __DIR__.'/../../../../../../../vendor/autoload.php';

use mageekguy\atoum;
use M6Web\Component\Redis\Cache;
use M6Web\Bundle\RedisBundle\Redis\Redis as BaseRedis;
use M6Web\Component\RedisMock\RedisMockFactory;

/**
* Test compute last modified date
*
*/
class Redis extends atoum\test
{
    static protected $params = array(
        'namespace' => '__tt____',
        'timeout' => 2,
        'compress' => true,
        'server_config' => array(
            'local' => array(
                'ip' => 'localhost',
                'port' => 6379,
            )
        )
    );

    /**
     * get a redis Instance
     *
     * @return BaseRedis
     */
    protected function getRedisInstance()
    {
        $factory     = new RedisMockFactory();
        $myRedisMockClass = $factory->getMock('M6Web\Component\Redis\Cache', false, [
                'orphanizeConstructor' => true,
                'failOnlyAtRuntime' => true
            ]);
        $myRedisMock = new $myRedisMockClass(static::$params, true);
        $redis = new BaseRedis($myRedisMock);

        return $redis;
    }

    /**
     * test remove method
     *
     * @return void
     */
    public function testRemove()
    {
        $redis = $this->getRedisInstance();

        $controller = new \atoum\mock\controller();
        $controller->__construct = function() {}; // overwrite constructor
        $controller->del         = function() {}; // overwrite del method
        $redisObject = new \mock\M6Web\Component\Redis\Cache($controller);
        $redis->setRedis($redisObject);

        $this->if($redis->remove('raoul'))
            ->then
            ->mock($redisObject)
                ->call('del')
                ->once();
    }

    /**
     * test the cache Resetter
     *
     * @return void
     */
    public function testCacheResetter()
    {
        $this->mockGenerator->generate('M6Web\Bundle\RedisBundle\Redis\CacheResetter\CacheResetterInterface', '\myMock');
        $cacheResetter = new \myMock\CacheResetterInterface;
        $cacheResetter->getMockController()->shouldResetCache = function() {

            return false;
        };
        $redis = $this->getRedisInstance();

        $this->if($redis->set('foo', 'bar'))
            ->then
            ->string($redis->get('foo'))
            ->isIdenticalTo('bar');

        $this->if($redis->setCacheResetterService($cacheResetter))
            ->then
            ->string($redis->get('foo'))
            ->isIdenticalTo('bar');

        $cacheResetter->getMockController()->shouldResetCache = function() {

            return true;
        };

        $this->if($redis->setCacheResetterService($cacheResetter))
            ->then
            ->string($redis->getRedis()->get('foo')) // foo key is here
            ->boolean($redis->get('foo'))
            ->isIdenticalTo(false)
            ->variable($redis->getRedis()->get('foo')) // foo key is no more here
            ->isNull();
    }
}