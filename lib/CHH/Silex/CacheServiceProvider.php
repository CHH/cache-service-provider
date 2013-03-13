<?php

namespace CHH\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Cache\Cache;
use CHH\Silex\CacheServiceProvider\CacheNamespace;

class CacheServiceProvider implements ServiceProviderInterface
{
    function register(Application $app)
    {
        $app['cache.factory'] = $app->protect(function($options) {
            return function() use ($options) {
                if (is_callable($options['driver'])) {
                    $cache = $options['driver']();

                    if (!$cache instanceof Cache) {
                        throw new \UnexpectedValueException(sprintf(
                            '"%s" does not implement \\Doctrine\\Common\\Cache\\Cache', get_class($cache)
                        ));
                    }

                    return $cache;
                }

                # If the driver name appears to be a fully qualified class name, then use
                # it verbatim as driver class. Otherwise look the driver up in Doctrine's
                # builtin cache providers.
                if (substr($options['driver'], 0, 1) === '\\') {
                    $driverClass = $options['driver'];
                } else {
                    $driverClass = "\\Doctrine\\Common\\Cache\\"
                        . str_replace(' ', '', ucwords(str_replace('_', ' ', $options['driver']))) . "Cache";

                    if (!class_exists($driverClass)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Driver "%s" (%s) not found.', $options['driver'], $driverClass
                        ));
                    }
                }

                $class = new \ReflectionClass($driverClass);
                $constructor = $class->getConstructor();

                $newInstanceArguments = array();

                if (null !== $constructor) {
                    foreach ($constructor->getParameters() as $parameter) {
                        if (isset($options[$parameter->getName()])) {
                            $value = $options[$parameter->getName()];
                        } else {
                            $value = $parameter->getDefaultValue();
                        }

                        $newInstanceArguments[] = $value;
                    }
                }

                // Workaround for PHP 5.3.3 bug #52854 <https://bugs.php.net/bug.php?id=52854>
                if (count($newInstanceArguments) > 0) {
                    $cache = $class->newInstanceArgs($newInstanceArguments);
                } else {
                    $cache = $class->newInstanceArgs();
                }

                if (!$cache instanceof Cache) {
                    throw new \UnexpectedValueException(sprintf(
                        '"%s" does not implement \\Doctrine\\Common\\Cache\\Cache', $driverClass
                    ));
                }

                if (isset($options['namespace']) and is_callable(array($cache, "setNamespace"))) {
                    $cache->setNamespace($options['namespace']);
                }

                return $cache;
            };
        });

        $app['cache.namespace'] = $app->protect(function($name, Cache $cache = null) use ($app) {
            return function() use ($app, $name, $cache) {
                if (null === $cache) {
                    $cache = $app['cache'];
                }

                return new CacheNamespace($name, $cache);
            };
        });

        $app['cache'] = $app->share(function($app) {
            $factory = $app['cache.factory']($app['cache.options']['default']);
            return $factory();
        });

        $app['caches'] = $app->share(function($app) {
            $caches = new \Pimple;

            foreach ($app['cache.options'] as $cache => $options) {
                $caches[$cache] = $app->share($app['cache.factory']($options));
            }

            return $caches;
        });
    }

    function boot(Application $app)
    {
    }
}
