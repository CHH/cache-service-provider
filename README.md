# Silex Cache Service Provider

## Install

If you haven't got composer:

    % wget http://getcomposer.org/composer.phar

Add `chh/cache-service-provider` to your `composer.json`:

    % php composer.phar require chh/cache-service-provider:*@dev

## Usage

This service provider for Silex uses the Cache classes from [Doctrine
Common][] to provide a `cache` service to a Silex application, and
other service providers.

### Configuration

If you only need one application wide cache, then it's sufficient to
only define a default cache.

```php
<?php

use Doctrine\Common\Cache;

$app = new Silex\Application;

$app->register(new \CHH\Silex\CacheServiceProvider, array(
    'cache.options' => array(
        'provider' => new Cache\ApcCache
    )
));
```

This cache is then available through the `cache` service, and provides
an instance of `Doctrine\Common\Common\Cache`.

```php
if ($app['cache']->exists('foo')) {
    echo $app['cache']->fetch('foo'), "<br>";
} else {
    $app['cache']->store('foo', 'bar');
}
```

To configure multiple caches, define the `caches.options` (mind the
plural) key:

```php
$app->register(new \CHH\Silex\CacheServiceProvider, array(
    'caches.options' => array(
        'default' => new Cache\ApcCache,
        'file' => new Cache\FilesystemCache(sys_get_temp_dir() . '/myapp')
    )
))
```

These caches are then available via the `caches` service:

```php
$app['caches']['file']->store('foo', 'bar');
```

The cache named 'default' is also available as the app's `cache`
service.

## License

Copyright (c) 2012 Christoph Hochstrasser

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE\

