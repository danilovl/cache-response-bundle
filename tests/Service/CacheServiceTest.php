<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\Service;

use Danilovl\CacheResponseBundle\Service\CacheService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\{
    CacheItemInterface,
    CacheItemPoolInterface
};

class CacheServiceTest extends TestCase
{
    private MockObject $cacheItemPool;

    private CacheService $cacheService;

    protected function setUp(): void
    {
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheService = new CacheService($this->cacheItemPool);
    }

    public function testGetCacheKeysWhenCacheIsEmpty(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS)
            ->willReturn($cacheItem);

        $result = $this->cacheService->getCacheKeys();

        $this->assertEmpty($result);
    }

    public function testGetCacheKeysWhenCacheHasKeys(): void
    {
        $cacheKeys = ['key1', 'key2', 'key3'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($cacheKeys);

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS)
            ->willReturn($cacheItem);

        $result = $this->cacheService->getCacheKeys();

        $this->assertSame($cacheKeys, $result);
    }

    public function testFindSimilarCacheKeys(): void
    {
        $cacheKeys = ['user_123', 'user_456', 'product_789'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($cacheKeys);

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS)
            ->willReturn($cacheItem);

        $result = $this->cacheService->findSimilarCacheKeys('user_');

        $this->assertCount(2, $result);
        $this->assertSame(['user_123', 'user_456'], $result);
    }

    public function testIsCacheKeyExistInCacheWhenExists(): void
    {
        $cacheKeys = ['key1', 'key2', 'key3'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($cacheKeys);

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS)
            ->willReturn($cacheItem);

        $result = $this->cacheService->isCacheKeyExistInCache('key2');

        $this->assertTrue($result);
    }

    public function testIsCacheKeyExistInCacheWhenNotExists(): void
    {
        $cacheKeys = ['key1', 'key2', 'key3'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($cacheKeys);

        $this->cacheItemPool
            ->expects($this->once())
            ->method('getItem')
            ->with(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS)
            ->willReturn($cacheItem);

        $result = $this->cacheService->isCacheKeyExistInCache('key4');

        $this->assertFalse($result);
    }
}
