<?php

namespace CHH\Silex\Test;

use CHH\Silex\CacheServiceProvider;
use Silex\Application;
use Doctrine\Common\Cache;

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

        $app->register(new CacheServiceProvider);

        $app['cache.foo'] = $app['cache.factory'](array(
            'driver' => 'array'
        ));

        $app['cache.bar'] = $app['cache.factory'](array(
            'driver' => function() { return new Cache\ArrayCache; }
        ));

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['cache.foo']);
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['cache.bar']);
    }
}
