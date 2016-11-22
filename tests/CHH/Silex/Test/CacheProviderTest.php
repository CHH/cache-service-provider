<?php

namespace CHH\Silex\Test;

use CHH\Silex\CacheServiceProvider;
use CHH\Silex\CacheServiceProvider\CacheNamespace;
use Silex\Application;
use Doctrine\Common\Cache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\FilesystemCache;

class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    function setUp()
    {
        $this->app = new Application;
        $this->app->register(new CacheServiceProvider);
    }

    function testDefaultCache()
    {
        $this->app['cache.options'] = array(
            "default" => array(
                'driver' => "array"
            )
        );

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $this->app['cache']);
    }

    function testMultipleCaches()
    {
        $this->app['cache.options'] = array(
            "default" => array('driver' => "array"),
            "foo" => array(
                'driver' => FilesystemCache::class,
                'directory' => '/tmp'
            )
        );

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\FilesystemCache', $this->app['caches']['foo']);
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $this->app['cache']);
    }

    function testCacheFactory()
    {
        $app = $this->app;

        $this->app['cache.options'] = [
            'default' => 'array',
        ];

        $this->app['caches'] = $this->app->extend('caches', function($caches) use ($app) {
            $caches['foo'] = $app['cache.factory'](array(
                'driver' => 'array'
            ));

            $caches['bar'] = $app['cache.factory'](array(
                'driver' => function() { return new Cache\ArrayCache; }
            ));

            return $caches;
        });

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $this->app['caches']['foo']);
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $this->app['caches']['bar']);
    }

    function testNamespaceFactory()
    {
        $app = $this->app;

        $app['cache.options'] = array('default' => array(
            'driver' => 'array'
        ));

        $app['caches']['foo'] = $app['cache.namespace']('foo');

        $this->assertInstanceOf(CacheNamespace::class, $app['caches']['foo']);
    }

    function testSameNamespaceInDifferentCaches()
    {
        $app = $this->app;

        $app['cache.options'] = array('default' => array(
            'driver' => 'array'
        ));

        $bar = new ArrayCache;

        $app['caches']['foo'] = $app['cache.namespace']('foo');

        $app['caches']['bar'] = $app['cache.namespace']('foo', $bar);
        $app['caches']['bar']->save('foo', 'bar');

        $this->assertFalse($app['caches']['foo']->contains('foo'));
        $this->assertEquals('bar', $app['caches']['bar']->fetch('foo'));
    }

    function testDefaultCacheServiceIsTheSameInstanceInCacheCollection()
    {
        $app = $this->app;

        $app['cache.options'] = [
            'default' => [
                'driver' => 'array',
            ],
        ];

        $this->assertEquals($app['caches']['default'], $app['cache']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testErrorWhenNoDefaultCacheIsDefineD()
    {
        $app = $this->app;

        $app['cache.options'] = [
            'foo' => ['driver' => 'array'],
        ];

        $app['cache'];
    }
}
