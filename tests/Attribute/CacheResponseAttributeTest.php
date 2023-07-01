<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\Attribute;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Exception\CacheResponseInvalidArgumentException;
use Danilovl\CacheResponseBundle\Tests\{
    TestController,
    TestCacheKeyFactory
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CacheResponseAttributeTest extends TestCase
{
    public function testCreateSucceed(): void
    {
        new CacheResponseAttribute(cacheKey: 'test');
        new CacheResponseAttribute(cacheKeyFactory: TestCacheKeyFactory::class);

        $this->assertTrue(true);
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

    public function testGetCacheKey(): void
    {
        $cacheResponseAttribute = new CacheResponseAttribute('test.key');

        $this->assertEquals('danilovl.cache_response.test.key', $cacheResponseAttribute->getCacheKey(new Request));
    }

    public function testGetCacheKeyWithPrefix(): void
    {
        $this->assertEquals('danilovl.cache_response.test.key', CacheResponseAttribute::getCacheKeyWithPrefix('test.key'));
    }

    public function testIsCacheKeyContainsPrefixSucceed(): void
    {
        $this->assertTrue(CacheResponseAttribute::isCacheKeyContainsPrefix('danilovl.cache_response.test.key'));
    }

    public function testIsCacheKeyContainsPrefixFailed(): void
    {
        $this->assertFalse(CacheResponseAttribute::isCacheKeyContainsPrefix('test.cache_response.test.key'));
    }
}
