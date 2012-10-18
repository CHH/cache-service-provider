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
            'cache.provider' => new Cache\ArrayCache
        ));

        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['cache']);
    }

    function testMultipleCaches()
    {
        $app = new Application;

        $app->register(new CacheServiceProvider, array(
            'cache.caches' => array(
                "default" => new Cache\ArrayCache,
                "foo" => new Cache\FilesystemCache('/tmp')
            )
        ));

        $this->assertEquals(2, count($app['caches']));
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\FileCache', $app['caches']['foo']);
        $this->assertInstanceOf('\\Doctrine\\Common\\Cache\\ArrayCache', $app['cache']);
    }
}
