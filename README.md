# Silex Cache Service Provider

This service provider for Silex uses the Cache classes from [Doctrine
Common][] to provide a `cache` service to a Silex application, and
other service providers.

[Doctrine Common]: https://github.com/doctrine/common

## Install

If you haven't got composer:

    % wget http://getcomposer.org/composer.phar

Add `chh/cache-service-provider` to your `composer.json`:

    % php composer.phar require chh/cache-service-provider:*@dev

## Usage

### Configuration

If you only need one application wide cache, then it's sufficient to
only define a default cache, by setting the `default` key in `cache.options`.

The cache definition is an array of options, with `driver` being the
only mandatory option. All other options in the array, are treated as
constructor arguments to the driver class.

The cache named `default` is the cache available through the app's
`cache` service.

```php
<?php

$app = new Silex\Application;

$app->register(new \CHH\Silex\CacheServiceProvider, array(
    'cache.options' => array("default" => array(
        "driver" => "apc"
    ))
));
```

The driver name can be either:

* A fully qualified class name
* A simple identifier like "apc", which then gets translated to
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
$app->register(new \CHH\Silex\CacheServiceProvider, array(
    'cache.options' => array(
        'default' => array('driver' => 'apc'),
        'file' => array(
            'driver' => 'filesystem',
            'directory' => '/tmp/myapp'
        ),
        'global' => array(
            'driver' => function() {
                $redis = new \Doctrine\Common\Cache\RedisCache;
                $redis->setRedis($app['redis']);

                return $redis;
            }
        )
    )
));
```

All caches (including the default) are then available via the `caches`
service:

```php
$app['caches']['file']->save('foo', 'bar');
```

### Usage from within extensions

Extensions should make no assumptions about their environment. Therefore
it's best to use the application's default cache most of the time. But
when you do need a cache with a specific driver, then you can use the
`cache.factory` service. This factory takes an array of cache options,
just like in each key of `cache.options`, and returns a Factory suitable
for Pimple.

```php
<?php

$factory = $app['cache.factory'](array(
    'driver' => 'filesystem',
    'directory' => sys_get_temp_dir() . '/myext'
));

$app['caches']['myext'] = $app['caches']->share($factory);
```

Extensions should when possible prefix their IDs to avoid conflicts
with user specified cache IDs.

To make this easier the Cache Service Provider ships with a `CacheNamespace` class. This
class decorates any `\Doctrine\Common\Cache\Cache`, and prefixes the
IDs on all cache operations.

For caches which have builtin namespacing support via a `setNamespace` method, 
there's also a `namespace` option.

```php
<?php

use CHH\Silex\CacheServiceProvider\CacheNamespace;

class ExampleServiceProvider extends \Silex\ServiceProviderInterface
{
    function register(\Silex\Application $app)
    {
        # Check if Cache Service Provider is registered:
        if (isset($app['caches'])) {
            $app['caches'] = $app->share($app->extend(function($caches) use ($app) {
                # Use a CacheNamespace to safely add keys to the default
                # cache.
                $caches['example'] = $app->share(function() use ($caches) {
                    return new CacheNamespace('example', $caches['default']);
                });
                return $caches;
            });
        }
    }

    function boot(\Silex\Application $app){}
}
```

This library also provides the `cache.namespace` service, which returns a Closure suitable for assigning directly
to a Pimple container. Using this, the above code can be further simplified:

```php
# Check if Cache Service Provider is registered:
if (isset($app['caches'])) {
    $app['caches'] = $app->share($app->extend(function($caches) use ($app) {
        # Use a CacheNamespace to safely add keys to the default
        # cache.
        $caches['example'] = $app->share($app['cache.namespace']('example'));
        return $caches;
    });
}
```

## License

Copyright (c) 2012 Christoph Hochstrasser

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE\

