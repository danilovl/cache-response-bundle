<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\Attribute;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Exception\CacheResponseInvalidArgumentException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

        new CacheResponseAttribute(key: 'test');
        new CacheResponseAttribute(factory: TestCacheKeyFactory::class);
    }

    public function testCreateFailedWithoutArguments(): void
    {
        $this->expectException(CacheResponseInvalidArgumentException::class);

        new CacheResponseAttribute(factory: TestController::class);
    }

    public function testCreateFailedWithBadInterface(): void
    {
        $this->expectException(CacheResponseInvalidArgumentException::class);

        new CacheResponseAttribute(factory: TestController::class);
    }

    public function testGetCacheKeyNotNull(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey);

        $this->assertEquals(
            CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey),
            $cacheResponseAttribute->getCacheKeyNotNull()
        );
    }

    public function testGetCacheKeyNotNullException(): void
    {
        $cacheResponseAttribute = new CacheResponseAttribute(factory: TestCacheKeyFactory::class);

        $this->expectException(CacheResponseInvalidArgumentException::class);
        $this->expectExceptionMessage('CacheKey can not be null.');

        $cacheResponseAttribute->getCacheKeyNotNull();
    }

    public function testGetCacheKeyForRequestEmptyRouteException(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useRoute: true);

        $request = new Request;
        $request->attributes->set('_route', '');

        $this->expectException(CacheResponseInvalidArgumentException::class);
        $this->expectExceptionMessage('Route _route can not be empty when useRoute is true.');

        $cacheResponseAttribute->getCacheKeyForRequest($request);
    }

    public function testGetCacheKeyForRequestWithWithoutRouteException(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useRoute: true);

        $this->expectException(CacheResponseInvalidArgumentException::class);
        $this->expectExceptionMessage('Route _route can not be empty when useRoute is true.');

        $cacheResponseAttribute->getCacheKeyForRequest(new Request);
    }

    public function testGetCacheKeyForRequestWithEmptyEnvException(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useEnv: true);

        $request = new Request;
        $request->server->set('APP_ENV', '');

        $this->expectException(CacheResponseInvalidArgumentException::class);
        $this->expectExceptionMessage('APP_ENV can not be empty when useEnv is true.');

        $cacheResponseAttribute->getCacheKeyForRequest($request);
    }

    public function testGetCacheKeyForRequestWithoutEnvException(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useEnv: true);

        $this->expectException(CacheResponseInvalidArgumentException::class);
        $this->expectExceptionMessage('APP_ENV can not be empty when useEnv is true.');

        $cacheResponseAttribute->getCacheKeyForRequest(new Request);
    }

    public function testGetCacheKeyForRequest(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey);

        $this->assertEquals(
            CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey),
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

    public function testGetCacheKeyForRequestWithEnv(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useEnv: true);

        $request = new Request;
        $request->server->set('APP_ENV', 'test');

        $data = ['env' => 'test'];
        $expectedCacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey);
        $expectedCacheKey .= '.' . CacheResponseAttribute::hash(serialize($data));

        $this->assertEquals(
            $expectedCacheKey,
            $cacheResponseAttribute->getCacheKeyForRequest($request)
        );
    }

    public function testGetCacheKeyForRequestWithSession(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useSession: true);

        $sessionId = 'test_session_id';
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('getId')
            ->willReturn($sessionId);

        $request = new Request;
        $request->setSession($session);

        $data = ['session' => $sessionId];
        $expectedCacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey);
        $expectedCacheKey .= '.' . CacheResponseAttribute::hash(serialize($data));

        $this->assertEquals(
            $expectedCacheKey,
            $cacheResponseAttribute->getCacheKeyForRequest($request)
        );
    }

    public function testGetCacheKeyForRequestWithRoute(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useRoute: true);

        $routeName = 'test_route';
        $routeParams = ['id' => 123, 'slug' => 'test-slug'];

        $request = new Request;
        $request->attributes->set('_route', $routeName);
        $request->attributes->set('_route_params', $routeParams);

        $data = [
            'route' => $routeName,
            'routeParams' => $routeParams
        ];
        $expectedCacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey);
        $expectedCacheKey .= '.' . CacheResponseAttribute::hash(serialize($data));

        $this->assertEquals(
            $expectedCacheKey,
            $cacheResponseAttribute->getCacheKeyForRequest($request)
        );
    }

    public function testGetCacheKeyForRequestWithRouteNoParams(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute($cacheKey, useRoute: true);

        $routeName = 'test_route';
        $routeParams = [];

        $request = new Request;
        $request->attributes->set('_route', $routeName);
        $request->attributes->set('_route_params', $routeParams);

        $data = ['route' => $routeName];
        $expectedCacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey);
        $expectedCacheKey .= '.' . CacheResponseAttribute::hash(serialize($data));

        $this->assertEquals(
            $expectedCacheKey,
            $cacheResponseAttribute->getCacheKeyForRequest($request)
        );
    }

    public function testGetCacheKeyForRequestWithAllParams(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute(
            key: $cacheKey,
            useSession: true,
            useRoute: true,
            useQuery: true,
            useRequest: true,
            useEnv: true
        );

        $sessionId = 'test_session_id';
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn($sessionId);

        $request = new Request(
            query: ['query_param' => 'query_value'],
            request: ['request_param' => 'request_value']
        );
        $request->setSession($session);
        $request->attributes->set('_route', 'test_route');
        $request->attributes->set('_route_params', ['id' => 123]);
        $request->server->set('APP_ENV', 'test');

        $data = [
            'session' => $sessionId,
            'route' => 'test_route',
            'routeParams' => ['id' => 123],
            'query' => ['query_param' => 'query_value'],
            'request' => ['request_param' => 'request_value'],
            'env' => 'test'
        ];

        $expectedCacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($cacheKey);
        $expectedCacheKey .= '.' . CacheResponseAttribute::hash(serialize($data));

        $this->assertEquals(
            $expectedCacheKey,
            $cacheResponseAttribute->getCacheKeyForRequest($request)
        );
    }

    public function testDisableOnQueryParameter(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute(
            key: $cacheKey,
            disableOnQuery: true
        );

        $this->assertTrue($cacheResponseAttribute->disableOnQuery);
        $this->assertFalse($cacheResponseAttribute->disableOnRequest);
    }

    public function testDisableOnRequestParameter(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute(
            key: $cacheKey,
            disableOnRequest: true
        );

        $this->assertFalse($cacheResponseAttribute->disableOnQuery);
        $this->assertTrue($cacheResponseAttribute->disableOnRequest);
    }

    public function testBothDisableParameters(): void
    {
        $cacheKey = 'test.key';
        $cacheResponseAttribute = new CacheResponseAttribute(
            key: $cacheKey,
            disableOnQuery: true,
            disableOnRequest: true
        );

        $this->assertTrue($cacheResponseAttribute->disableOnQuery);
        $this->assertTrue($cacheResponseAttribute->disableOnRequest);
    }
}
