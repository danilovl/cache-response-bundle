<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\EventListener\KernelResponseListener;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Danilovl\CacheResponseBundle\Tests\Mock\{
    TestController,
    TestCacheKeyFactory
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\{
    Request,
    Response
};
use Symfony\Component\HttpKernel\{
    Event\ResponseEvent,
    KernelInterface,
    HttpKernelInterface
};

class KernelResponseListenerTest extends TestCase
{
    #[DataProvider('dataControllerMethod')]
    public function testOnKernelResponseCreateCache(string $method): void
    {
        $controllerResponse = (new TestController)->{$method}();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(
                attributes: ['_controller' => TestController::class . '::' . $method],
            ),
            HttpKernelInterface::MAIN_REQUEST,
            $controllerResponse
        );

        $testCacheKeyFactory = new TestCacheKeyFactory;
        $cacheItemPool = new ArrayAdapter;

        $container = new Container;
        $container->set(TestCacheKeyFactory::class, $testCacheKeyFactory);

        $subscriber = new KernelResponseListener($cacheItemPool, $container);
        $subscriber->onKernelResponse($event);

        if ($method === 'cacheKeyFactory') {
            $cacheKey = $testCacheKeyFactory->getCacheKey();
        } else if ($method === 'index') {
            $key = 'index';
            $cacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($key);
        } else {
            $cacheKey = CacheResponseAttribute::CACHE_KEY_PREFIX . CacheResponseAttribute::hash($method);
        }

        $cache = $cacheItemPool->getItem($cacheKey);
        /** @var Response $response */
        $response = $cache->get();

        $this->assertEquals($controllerResponse->getContent(), $response->getContent());
    }

    public function testOnKernelResponseFactoryException(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        $controllerResponse = (new TestController)->cacheKeyFactoryException();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(
                attributes: ['_controller' => TestController::class . '::cacheKeyFactoryException'],
            ),
            HttpKernelInterface::MAIN_REQUEST,
            $controllerResponse
        );

        $testCacheKeyFactory = new TestCacheKeyFactory;
        $cacheItemPool = new ArrayAdapter;

        $container = new Container;
        $container->set(TestCacheKeyFactory::class, $testCacheKeyFactory);

        $subscriber = new KernelResponseListener($cacheItemPool, $container);
        $subscriber->onKernelResponse($event);
    }

    public static function dataControllerMethod(): Generator
    {
        yield ['index'];
        yield ['cacheKeyFactory'];
    }
}
