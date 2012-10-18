<?php

namespace CHH\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Cache\Cache;

class CacheServiceProvider implements ServiceProviderInterface
{
    function register(Application $app)
    {
        $app['cache.config_initializer'] = $app->protect(function() use ($app) {
            static $initialized = false;

            if ($initialized) return;

            $initialized = true;

            if (!isset($app['caches.options'])) {
                $app['caches.options'] = array(
                    'default' => isset($app['cache.options']) ? $app['cache.options'] : array()
                );
            }

            $tmp = $app['caches.options'];

            array_walk($tmp, function($options, $cache) {
                if (!$options['provider'] instanceof Cache) {
                    throw new \UnexpectedValueException(sprintf(
                        'Provider for cache "%s" does not implement \Doctrine\Common\Cache\Cache',
                        $cache
                    ));
                }
            });
        });

        $app['cache'] = $app->share(function($app) {
            $app['cache.config_initializer']();

            return $app['caches.options']['default']['provider'];
        });

        $app['caches'] = $app->share(function($app) {
            $app['cache.config_initializer']();

            return array_map(function($it) { return $it['provider']; }, $app['caches.options']);
        });
    }

    function boot(Application $app)
    {
    }
}
