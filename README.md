# Silex Cache Service Provider

This service provider for Silex uses the Doctrine Cache library to provide a `cache` service to a Silex application as well as
to other service providers.

## Install

Install with [composer](https://getcomposer.org):

    % composer require chh/cache-service-provider

## Usage

### Configuration

For an application wide cache use the default cache by setting the `default` key in `cache.options` with the cache definition.

The cache definition is an array of options, with `driver` being the
only mandatory one. All other options in the array, are treated as
constructor arguments to the driver class.

The cache named `default` is the cache available through the container's
`cache` service.

```php
<?php

use Doctrine\Common\Cache\ApcuCache;

$app = new Silex\Application;

$app->register(new \CHH\Silex\CacheServiceProvider, [
    'cache.options' => [
        'default' => [
            'driver' => ApcuCache::class,
        ],
    ],
]);
```

The driver name can be one of the following:

* A fully qualified class name of a class which implements the `Doctrine\Common\Cache\Cache` interface
* An alias like "apc", which then gets translated to
  `\Doctrine\Common\Cache\ApcCache`.
* A Closure, which returns an object implementing
  `\Doctrine\Common\Cache\Cache`.

This cache is then available through the `cache` service, and provides
an instance of `Doctrine\Common\Cache\Cache`:

```php
if ($app['cache']->contains('foo')) {
    echo $app['cache']->fetch('foo'), "<br>";
} else {
    $app['cache']->save('foo', 'bar');
}
```

To configure multiple caches, define them as additional keys in
`cache.options`:

```php
$app->register(new \CHH\Silex\CacheServiceProvider, [
    'cache.options' => [
        'default' => ['driver' => ApcuCache::class],
        'file' => [
            'driver' => 'filesystem',
            'directory' => '/tmp/myapp',
        ],
        'global' => [
            'driver' => function () {
                $redis = new \Doctrine\Common\Cache\RedisCache;
                $redis->setRedis($app['redis']);

                return $redis;
            },
        ],
    ],
]);
```

All caches (including the default) are then available as a key of the `caches` service:

```php
$app['caches']['file']->save('foo', 'bar');
$app['caches']['default']->save('bar', 'baz');
```

### Usage from within extensions

Extensions should make no assumptions about their environment. Therefore
it's best to use the application's default cache most of the time. But
when you do need a cache with a specific driver, then you can use the
`cache.factory` service.

This factory takes an array of cache options,
just like in each key of `cache.options`, and returns a Factory suitable
for Pimple.

```php
<?php

$app['caches']['myext'] = $app['cache.factory']([
    'driver' => 'filesystem',
    'directory' => sys_get_temp_dir() . '/myext',
]);
```

Extensions should prefix their cache keys to avoid conflicts
with user specified cache IDs.

To make this easier the Cache Service Provider ships with a `CacheNamespace` class. This
class decorates any `\Doctrine\Common\Cache\Cache`, and prefixes the
keys on all cache operations.

For caches with builtin namespacing support via a `setNamespace` method, 
there's also a `namespace` configuration option.

```php
<?php

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use CHH\Silex\CacheServiceProvider\CacheNamespace;

class ExampleServiceProvider extends ServiceProviderInterface
{
    function register(Container $app)
    {
        // Check if Cache Service Provider is registered:
        if (isset($app['caches'])) {
            $app['caches'] = $app->extend(function ($caches) use ($app) {
                // Use a CacheNamespace to safely add keys to the default
                // cache.
                $caches['example'] = function () use ($caches) {
                    return new CacheNamespace('example', $caches['default']);
                };

                return $caches;
            });
        }
    }
}
```

This library also provides the `cache.namespace` factory, which returns a Closure suitable for assigning directly
to a Pimple container. Using this, the above code can be further simplified:

```php
// Check if Cache Service Provider is registered:
if (isset($app['caches'])) {
    $app['caches'] = $app->extend(function ($caches) use ($app) {
        // Use a CacheNamespace to safely add keys to the default cache
        $caches['example'] = $app['cache.namespace']('example');
        
        return $caches;
    });
}
```

## License

Copyright (c) 2016 Christoph Hochstrasser

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE\

