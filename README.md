[![phpunit](https://github.com/danilovl/cache-response-bundle/actions/workflows/phpunit.yml/badge.svg)](https://github.com/danilovl/cache-response-bundle/actions/workflows/phpunit.yml)
[![downloads](https://img.shields.io/packagist/dt/danilovl/cache-response-bundle)](https://packagist.org/packages/danilovl/cache-response-bundle)
[![latest Stable Version](https://img.shields.io/packagist/v/danilovl/cache-response-bundle)](https://packagist.org/packages/danilovl/cache-response-bundle)
[![license](https://img.shields.io/packagist/l/danilovl/cache-response-bundle)](https://packagist.org/packages/danilovl/cache-response-bundle)

# CacheResponseBundle #

## About ##

Symfony bundle provides simple cache response.

Before:

![Alt text](/.github/readme/profiler_before.png?raw=true "Profiler before")

After:

![Alt text](/.github/readme/profiler_after.png?raw=true "Profiler after")

### Requirements

* PHP 8.2.0 or higher
* Symfony 6.3 or higher

### 1. Installation

Install `danilovl/cache-response-bundle` package by Composer:

``` bash
$ composer require danilovl/cache-response-bundle
```

Add the `CacheResponseBundle` to your application's bundles if does not add automatically:

``` php
<?php
// config/bundles.php

return [
    // ...
    Danilovl\CacheResponseBundle\CacheResponseBundle::class => ['all' => true]
];
```

### 2. Usage

You can define custom cache service witch implement `CacheItemPoolInterface`.

```yaml
# config/packages/danilovl_cache_response.yaml

danilovl_cache_response:
  service: You service name
```

#### 2.1 Controller

Add attribute `CacheResponseAttribute` to controller method.

```php
#[CacheResponseAttribute(cacheKey: 'index', expiresAfter: 60, cacheKeyWithQuery: true, cacheKeyWithRequest: true)]
public function index(Request $request): Response
{
    return new Response('content');
}
```

## License

The CacheResponseBundle is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).