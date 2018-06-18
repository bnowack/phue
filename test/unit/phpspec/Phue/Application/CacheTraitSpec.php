<?php

namespace phpspec\Phue\Application;

use PhpSpec\ObjectBehavior;
use Phue\Application\CacheTrait;

class CacheTraitSpec extends ObjectBehavior
{

    public function let()
    {
        $this->beAnInstanceOf(CacheTraitStub::class);
    }

    public function it_calls_a_value_builder()
    {
        $cacheId = 'test';
        $this->getCacheValue($cacheId, function () {
            return 'test';
        })->shouldBe('test');
    }

    public function it_caches_values()
    {
        $cacheId = 'test';
        // fill cache
        $this->getCacheValue($cacheId, function () {
            return 'test1';
        })->shouldBe('test1');

        // expect cached value
        $this->getCacheValue($cacheId, function () {
            return 'test2';
        })->shouldBe('test1');
    }

    public function it_can_be_refreshed()
    {
        $cacheId = 'test';
        // fill cache
        $this->getCacheValue($cacheId, function () {
            return 'test1';
        })->shouldBe('test1');

        // expect fresh value
        $fresh = true;
        $this->getCacheValue($cacheId, function () {
            return 'test2';
        }, $fresh)->shouldBe('test2');
    }
}

class CacheTraitStub {

    use CacheTrait {
        getCacheValue as getTraitCacheValue;
    }

    /**
     * makes protected trait method available to test context
     *
     * @param $cacheId
     * @param $builder
     * @param bool $fresh
     * @return mixed
     */
    public function getCacheValue($cacheId, $builder, $fresh = false)
    {
        return $this->getTraitCacheValue($cacheId, $builder, $fresh);
    }
}
