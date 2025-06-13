<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\EventListener\KernelControllerListener;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Danilovl\CacheResponseBundle\Tests\Mock\{
    TestController,
    TestCacheKeyFactory
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\{
    KernelInterface,
    HttpKernelInterface
};

class KernelControllerListenerTest extends TestCase
{
    #[DataProvider('dataControllerMethod')]
    public function testExistCache(string $method): void
    {
        /** @var callable $callable */
        $callable = [new TestController, $method];

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            new Request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $controllerResponse = (new TestController)->{$method}();
        $testCacheKeyFactory = new TestCacheKeyFactory;

        $cacheItemPool = new ArrayAdapter;
        $cacheKey = $method;
        if ($method === 'cacheKeyFactory') {
            $cacheKey = $testCacheKeyFactory->getCacheKey();
        }

        $cash = $cacheItemPool->getItem($cacheKey);
        $this->assertFalse($cash->isHit());

        $cash->set($controllerResponse);
        $cacheItemPool->save($cash);

        $container = new Container;
        $container->set(TestCacheKeyFactory::class, $testCacheKeyFactory);

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        /** @var Response $response */
        $response = $event->getController()();

        $this->assertEquals($controllerResponse->getContent(), $response->getContent());
    }

    #[DataProvider('dataControllerMethod')]
    public function testNotExistCache(string $method): void
    {
        /** @var callable $callable */
        $callable = [new TestController, $method];

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            new Request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $testCacheKeyFactory = new TestCacheKeyFactory;

        $cacheItemPool = new ArrayAdapter;
        $cacheKey = $method;
        if ($method === 'cacheKeyFactory') {
            $cacheKey = $testCacheKeyFactory->getCacheKey();
        }

        $container = new Container;
        $container->set(TestCacheKeyFactory::class, $testCacheKeyFactory);

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        $cache = $cacheItemPool->getItem($cacheKey);
        $this->assertFalse($cache->isHit());

        $response = $cache->get();
        $this->assertNull($response);
    }

    public function testEnableFalse(): void
    {
        $method = 'index';

        /** @var callable $callable */
        $callable = [new TestController, $method];

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            new Request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemPool = new ArrayAdapter;
        $container = $this->createMock(Container::class);
        $container
            ->expects($this->never())
            ->method('get');

        $subscriber = new KernelControllerListener($cacheItemPool, $container, false);
        $subscriber->onKernelController($event);
    }

    public function testDisableOnQuery(): void
    {
        /** @var callable $callable */
        $callable = [new TestController, 'disableOnQuery'];

        $request = new Request(query: ['param' => 'value']);

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemPool = new ArrayAdapter;
        $container = new Container;

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        $isCacheIgnore = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_IGNORE);
        $isCacheUsed = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED);

        $this->assertTrue($isCacheIgnore);
        $this->assertFalse($isCacheUsed);
    }

    public function testDisableOnRequest(): void
    {
        /** @var callable $callable */
        $callable = [new TestController, 'disableOnRequest'];

        $request = new Request(request: ['param' => 'value']);

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemPool = new ArrayAdapter;
        $container = new Container;

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        $isCacheIgnore = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_IGNORE);
        $isCacheUsed = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED);

        $this->assertTrue($isCacheIgnore);
        $this->assertFalse($isCacheUsed);
    }

    public function testDisableOnQueryWithoutQuery(): void
    {
        /** @var callable $callable */
        $callable = [new TestController, 'disableOnQuery'];

        $request = new Request;

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemPool = new ArrayAdapter;
        $container = new Container;

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        $isCacheIgnore = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_IGNORE);
        $isCacheUsed = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED);

        $this->assertFalse($isCacheIgnore);
        $this->assertFalse($isCacheUsed);
    }

    public function testDisableOnRequestWithoutRequest(): void
    {
        /** @var callable $callable */
        $callable = [new TestController, 'disableOnRequest'];

        $request = new Request;

        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            $callable,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $cacheItemPool = new ArrayAdapter;
        $container = new Container;

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        $isCacheIgnore = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_IGNORE);
        $isCacheUsed = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED);

        $this->assertFalse($isCacheIgnore);
        $this->assertFalse($isCacheUsed);
    }

    public static function dataControllerMethod(): Generator
    {
        yield ['index'];
        yield ['cacheKeyFactory'];
    }
}
