<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\Attribute;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Exception\CacheResponseInvalidArgumentException;
use Danilovl\CacheResponseBundle\Tests\Mock\{
    TestController,
    TestCacheKeyFactory
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CacheResponseAttributeTest extends TestCase
{
    public function testCreateSucceed(): void
    {
        $this->expectNotToPerformAssertions();

        new CacheResponseAttribute(cacheKey: 'test');
        new CacheResponseAttribute(cacheKeyFactory: TestCacheKeyFactory::class);
    }

    public function testCreateFailedWithoutArguments(): void
    {
        $this->expectException(CacheResponseInvalidArgumentException::class);

        new CacheResponseAttribute(cacheKeyFactory: TestController::class);
    }

    public function testCreateFailedWithBadInterface(): void
    {
        $this->expectException(CacheResponseInvalidArgumentException::class);

        new CacheResponseAttribute(cacheKeyFactory: TestController::class);
    }

    public function testGetCacheKeyNotNull(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey);

        $this->assertEquals(
            CacheResponseAttribute::CACHE_KEY_PREFIX . sha1($cacheKey),
            $cacheResponseAttribute->getCacheKeyNotNull()
        );
    }

    public function testGetCacheKeyNotNullException(): void
    {
        $cacheResponseAttribute = new CacheResponseAttribute(cacheKeyFactory: TestCacheKeyFactory::class);

        $this->expectException(CacheResponseInvalidArgumentException::class);
        $this->expectExceptionMessage('CacheKey can not be null.');

        $cacheResponseAttribute->getCacheKeyNotNull();
    }

    public function testGetCacheKeyForRequest(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey);

        $this->assertEquals(
            CacheResponseAttribute::CACHE_KEY_PREFIX . sha1($cacheKey),
            $cacheResponseAttribute->getCacheKeyForRequest(new Request)
        );
    }

    public function testGetCacheKeyWithPrefix(): void
    {
        $cacheKey = '8414b2ff0a6fafcddc0f42d6d5a5b908d34925c3';

        $this->assertEquals(
            CacheResponseAttribute::CACHE_KEY_PREFIX . $cacheKey,
            CacheResponseAttribute::getCacheKeyWithPrefix($cacheKey)
        );
    }

    public function testIsCacheKeyContainsPrefixSucceed(): void
    {
        $this->assertTrue(CacheResponseAttribute::isCacheKeyContainsPrefix(CacheResponseAttribute::CACHE_KEY_PREFIX . 'test.key'));
    }

    public function testIsCacheKeyContainsPrefixFailed(): void
    {
        $this->assertFalse(CacheResponseAttribute::isCacheKeyContainsPrefix('test.cache_response.test.key'));
    }
}
