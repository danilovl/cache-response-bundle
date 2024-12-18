<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\Mock;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    #[CacheResponseAttribute(cacheKey: 'index', expiresAfter: 60, cacheKeyWithQuery: true)]
    public function index(): Response
    {
        return new Response('content');
    }

    #[CacheResponseAttribute(cacheKeyFactory: TestCacheKeyFactory::class)]
    public function cacheKeyFactory(): Response
    {
        return new Response('CacheKeyFactory content');
    }

    #[CacheResponseAttribute(cacheKeyFactory: TestCacheKeyFactoryException::class)]
    public function cacheKeyFactoryException(): Response
    {
        return new Response('CacheKeyFactory content');
    }
}
