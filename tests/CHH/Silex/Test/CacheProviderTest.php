<?php

namespace CHH\Silex\Test;

use CHH\Silex\CacheServiceProvider;
use Silex\Application;
use Doctrine\Common\Cache;
use Doctrine\Common\Cache\ArrayCache;

class CacheProviderTest extends \PHPUnit_Framework_TestCase
{
    function testDefaultCache()
    {
        $app = new Application;

        $app->register(new CacheServiceProvider, array(
            'cache.options' => array("default" => array(
                'driver' => "array"
            ))
        ));

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['cache']);
    }

    function testMultipleCaches()
    {
        $app = new Application;

        $app->register(new CacheServiceProvider, array(
            'cache.options' => array(
                "default" => array('driver' => "array"),
                "foo" => array(
                    'driver' => '\\Doctrine\\Common\\Cache\\FilesystemCache',
                    'directory' => '/tmp'
                )
            )
        ));

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\FilesystemCache', $app['caches']['foo']);
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['cache']);
    }

    function testCacheFactory()
    {
        $app = new Application;

        $app->register(new CacheServiceProvider, array('cache.options' => array(
            'default' => 'array'
        )));

        $app['caches'] = $app->share($app->extend('caches', function($caches) use ($app) {
            $caches['foo'] = $app->share($app['cache.factory'](array(
                'driver' => 'array'
            )));

            $caches['bar'] = $app->share($app['cache.factory'](array(
                'driver' => function() { return new Cache\ArrayCache; }
            )));

            return $caches;
        }));

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['caches']['foo']);
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['caches']['bar']);
    }

    function testNamespaceFactory()
    {
        $app = new Application;
        $app->register(new CacheServiceProvider);

        $app['cache.options'] = array('default' => array(
            'driver' => 'array'
        ));

        $app['caches']['foo'] = $app->share($app['cache.namespace']('foo'));

        $this->assertInstanceOf('\\CHH\\Silex\\CacheServiceProvider\\CacheNamespace', $app['caches']['foo']);
    }

    function testSameNamespaceInDifferentCaches()
    {
        $app = new Application;
        $app->register(new CacheServiceProvider);

        $app['cache.options'] = array('default' => array(
            'driver' => 'array'
        ));

        $bar = new ArrayCache;

        $app['caches']['foo'] = $app->share($app['cache.namespace']('foo'));

        $app['caches']['bar'] = $app->share($app['cache.namespace']('foo', $bar));
        $app['caches']['bar']->save('foo', 'bar');

        $this->assertFalse($app['caches']['foo']->contains('foo'));
        $this->assertEquals('bar', $app['caches']['bar']->fetch('foo'));
    }
}
