<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests;

use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;

class TestCacheKeyFactory implements CacheKeyFactoryInterface
{
    public const string CACHE_KEY = 'TestCacheKeyFactory';

    public function getCacheKey(): string
    {
        return self::CACHE_KEY;
    }
}
