<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\Mock;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    #[CacheResponseAttribute(key: 'index', expiresAfter: 60, useQuery: true)]
    public function index(): Response
    {
        return new Response('content');
    }

    #[CacheResponseAttribute(factory: TestCacheKeyFactory::class)]
    public function cacheKeyFactory(): Response
    {
        return new Response('CacheKeyFactory content');
    }

    #[CacheResponseAttribute(factory: TestCacheKeyFactoryException::class)]
    public function cacheKeyFactoryException(): Response
    {
        return new Response('CacheKeyFactory content');
    }

    #[CacheResponseAttribute(key: 'disableOnQuery', disableOnQuery: true)]
    public function disableOnQuery(): Response
    {
        return new Response('DisableOnQuery content');
    }

    #[CacheResponseAttribute(key: 'disableOnRequest', disableOnRequest: true)]
    public function disableOnRequest(): Response
    {
        return new Response('DisableOnRequest content');
    }
}
