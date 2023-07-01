<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Tests\EventListener;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\EventListener\KernelResponseListener;
use Generator;
use Danilovl\CacheResponseBundle\Tests\{
    TestController,
    TestCacheKeyFactory
};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\{
    Event\ResponseEvent,
    KernelInterface,
    HttpKernelInterface
};
use Symfony\Component\HttpFoundation\Response;

class KernelResponseListenerTest extends TestCase
{
    /**
     * @dataProvider dataControllerMethod
     */
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

        $cacheKey = CacheResponseAttribute::getCacheKeyWithPrefix($method);
        if ($method === 'cacheKeyFactory') {
            $cacheKey = $testCacheKeyFactory->getCacheKey();
        }

        $cache = $cacheItemPool->getItem($cacheKey);
        /** @var Response $response */
        $response = $cache->get();

        $this->assertEquals($controllerResponse->getContent(), $response->getContent());
    }

    public function dataControllerMethod(): Generator
    {
        yield ['index'];
        yield ['cacheKeyFactory'];
    }
}
