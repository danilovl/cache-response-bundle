<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\EventListener\KernelControllerListener;
use Generator;
use Danilovl\CacheResponseBundle\Tests\{
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
    /**
     * @dataProvider dataControllerMethod
     */
    public function testOnKernelControllerExistCache(string $method): void
    {
        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            [new TestController, $method],
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

        $cacheItemKey = $cacheItemPool->getItem($cacheKey);
        $cacheItemKey->set($controllerResponse);
        $cacheItemPool->save($cacheItemKey);

        $container = new Container;
        $container->set(TestCacheKeyFactory::class, $testCacheKeyFactory);

        $subscriber = new KernelControllerListener($cacheItemPool, $container);
        $subscriber->onKernelController($event);

        /** @var Response $response */
        $response = $event->getController()();

        $this->assertEquals($controllerResponse->getContent(), $response->getContent());
    }

    /**
     * @dataProvider dataControllerMethod
     */
    public function testOnKernelControllerNotExistCache(string $method): void
    {
        $event = new ControllerEvent(
            $this->createMock(KernelInterface::class),
            [new TestController, $method],
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
        /** @var null $response */
        $response = $cache->get();

        $this->assertNull($response);
    }

    public function dataControllerMethod(): Generator
    {
        yield ['index'];
        yield ['cacheKeyFactory'];
    }
}
