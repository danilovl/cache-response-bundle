<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Service;

use Psr\Cache\CacheItemPoolInterface;

class CacheService
{
    public const CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS = 'danilovl.cache_response:attribute_cache_keys';

    public function __construct(private readonly CacheItemPoolInterface $cacheItemPool) {}

    public function getCacheKeys(): array
    {
        $attributeCacheKeys = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
        if (!$attributeCacheKeys->isHit()) {
            return [];
        }

        return $attributeCacheKeys->get() ?? [];
    }

    function findSimilarCacheKeys(string $attributeCacheKey): array
    {
        $cacheKeys = $this->getCacheKeys();

        return array_filter($cacheKeys, static fn(string $cacheKey): bool => str_contains($cacheKey, $attributeCacheKey));
    }
}
