<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventSubscriber;

use Danilovl\CacheResponseBundle\EventSubscriber\CacheResponseSubscriber;
use Danilovl\CacheResponseBundle\EventSubscriber\Event\{
    ClearCacheResponseAllEvent,
    ClearCacheResponseKeyEvent
};
use Danilovl\CacheResponseBundle\Service\CacheService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CacheResponseSubscriberTest extends TestCase
{
    private const CLEAR_CACHE_RESPONSE_KEY = 'cache.key.test';

    private CacheItemPoolInterface $cacheItemPool;
    private CacheResponseSubscriber $subscriber;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->cacheItemPool = new ArrayAdapter;

        $cacheItemKey = $this->cacheItemPool->getItem(self::CLEAR_CACHE_RESPONSE_KEY);
        $cacheItemKey->set(true);
        $this->cacheItemPool->save($cacheItemKey);

        $cacheItemKey = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
        $cacheItemKey->set([]);
        $this->cacheItemPool->save($cacheItemKey);

        $cacheService = new CacheService($this->cacheItemPool);
        $this->subscriber = new CacheResponseSubscriber($this->cacheItemPool, $cacheService);

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addListener(ClearCacheResponseKeyEvent::class, [$this->subscriber, 'onClearCacheKey']);
        $this->eventDispatcher->addListener(ClearCacheResponseAllEvent::class, [$this->subscriber, 'onClearCacheAll']);
    }

    public function testClearCacheResponseKeyEvent(): void
    {
        $this->eventDispatcher->dispatch(new ClearCacheResponseKeyEvent(self::CLEAR_CACHE_RESPONSE_KEY));

        $cacheItem = $this->cacheItemPool->getItem(self::CLEAR_CACHE_RESPONSE_KEY);

        $this->assertFalse($cacheItem->isHit());
    }

    public function testClearCacheResponseAllEvent(): void
    {
        $this->eventDispatcher->dispatch(new ClearCacheResponseAllEvent);

        $cacheItem = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);

        $this->assertFalse($cacheItem->isHit());
    }
}
