<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Service;

use Psr\Cache\CacheItemPoolInterface;

class CacheService
{
    public const string CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS = 'danilovl.cache_response.attribute_cache_keys';

    public function __construct(private readonly CacheItemPoolInterface $cacheItemPool) {}

    /**
     * @return string[]
     */
    public function getCacheKeys(): array
    {
        $attributeCacheKeys = $this->cacheItemPool->getItem(self::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
        if (!$attributeCacheKeys->isHit()) {
            return [];
        }

        /** @var string[] $result */
        $result = $attributeCacheKeys->get() ?? [];

        return $result;
    }

    /**
     * @return string[]
     */
    public function findSimilarCacheKeys(string $attributeCacheKey): array
    {
        $cacheKeys = $this->getCacheKeys();

        return array_filter($cacheKeys, static fn (string $cacheKey): bool => str_contains($cacheKey, $attributeCacheKey));
    }

    public function isCacheKeyExistInCache(string $cacheKey): bool
    {
        $cacheKeys = $this->getCacheKeys();

        return in_array($cacheKey, $cacheKeys, true);
    }
}
