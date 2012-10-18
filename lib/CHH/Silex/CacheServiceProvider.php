<?php

namespace CHH\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Cache\Cache;

class CacheServiceProvider implements ServiceProviderInterface
{
    function register(Application $app)
    {
        $app['cache'] = $app->share(function($app) {
            if (isset($app['cache.caches'])) {
                if (isset($app['cache.caches']['default'])) {
                    $default = $app['cache.caches']['default'];
                } else {
                    $default = current($app['caches']);
                }
            } else {
                $default = $app['cache.provider'];
            }

            return $default;
        });

        $app['caches'] = $app->share(function($app) {
            $caches = $app['cache.caches'];

            foreach ($caches as $cache => $provider) {
                if (!$provider instanceof Cache) {
                    throw new \UnexpectedValueException(sprintf(
                        'Provider for cache "%s" does not implement \Doctrine\Common\Cache\Cache',
                        $cache
                    ));
                }
            }

            return $caches;
        });
    }

    function boot(Application $app)
    {
    }
}
