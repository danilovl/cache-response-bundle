<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\EventSubscriber;

use Danilovl\CacheResponseBundle\EventSubscriber\Event\{
    ClearCacheResponseAllEvent,
    ClearCacheResponseKeyEvent
};
use Danilovl\CacheResponseBundle\Service\CacheService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class CacheResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CacheItemPoolInterface $cacheItemPool,
        private CacheService $cacheService
    ) {}

    public function onClearCacheKey(ClearCacheResponseKeyEvent $event): void
    {
        $similarCacheKeys = $this->cacheService->findSimilarCacheKeys($event->key);
        $deleteCacheKeys = [$event->key, ...$similarCacheKeys];
        $deleteCacheKeys = array_unique($deleteCacheKeys);

        $this->cacheItemPool->deleteItems($deleteCacheKeys);

        $attributeCacheKeysItem = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
        if (!$attributeCacheKeysItem->isHit()) {
            return;
        }

        /** @var string[] $cacheKeys */
        $cacheKeys = $attributeCacheKeysItem->get() ?? [];
        $newCacheKeys = array_diff($cacheKeys, $deleteCacheKeys);
        $newCacheKeys = array_values($newCacheKeys);

        $attributeCacheKeysItem->set($newCacheKeys);
        $this->cacheItemPool->save($attributeCacheKeysItem);
    }

    public function onClearCacheAll(): void
    {
        $this->cacheItemPool->deleteItems($this->cacheService->getCacheKeys());
        $this->cacheItemPool->deleteItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ClearCacheResponseKeyEvent::class => 'onClearCacheKey',
            ClearCacheResponseAllEvent::class => 'onClearCacheAll'
        ];
    }
}
