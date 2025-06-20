<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\EventListener;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;
use Danilovl\CacheResponseBundle\Service\CacheService;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelResponseListener implements EventSubscriberInterface
{
    public static int $priority = 0;

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly ContainerInterface $container
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var string|array{?string, ?string}|null $controllerAttribute */
        $controllerAttribute = $event->getRequest()->attributes->get('_controller');

        $controller = null;
        $method = null;

        if (is_array($controllerAttribute)) {
            $controller = $controllerAttribute[0] ?? null;
            $method = $controllerAttribute[1] ?? null;
        } elseif (is_string($controllerAttribute) && str_contains($controllerAttribute, '::')) {
            [$controller, $method] = explode('::', $controllerAttribute);
        }

        if ($controller === null || $method === null) {
            return;
        }

        if (!class_exists($controller)) {
            return;
        }

        $this->resolve($controller, $method, $event);
    }

    private function resolve(string $controller, string $method, ResponseEvent $event): void
    {
        $attributes = (new ReflectionClass($controller))
            ->getMethod($method)
            ->getAttributes(CacheResponseAttribute::class);

        $attributes = $attributes[0] ?? null;
        if ($attributes === null) {
            return;
        }

        /** @var CacheResponseAttribute $attribute */
        $attribute = $attributes->newInstance();
        $this->handleResponse($event, $attribute);
    }

    private function handleResponse(
        ResponseEvent $event,
        CacheResponseAttribute $cacheResponseAttribute
    ): void {
        $request = $event->getRequest();
        $isCacheIgnore = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_IGNORE);
        if ($isCacheIgnore) {
            return;
        }

        $isCacheUsed = $request->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED);
        if ($isCacheUsed) {
            return;
        }

        if ($cacheResponseAttribute->factory !== null) {
            /** @var CacheKeyFactoryInterface $cacheFactory */
            $cacheFactory = $this->container->get($cacheResponseAttribute->factory);
            $cacheKey = $cacheFactory->getCacheKey();
        } else {
            $cacheKey = $cacheResponseAttribute->getCacheKeyForRequest($request);
        }

        $cashItemPool = $this->cacheItemPool;
        if ($cacheResponseAttribute->cacheAdapter !== null) {
            /** @var CacheItemPoolInterface $cashItemPool */
            $cashItemPool = $this->container->get($cacheResponseAttribute->cacheAdapter);
        }

        $cache = $cashItemPool->getItem($cacheKey);
        if ($cache->isHit()) {
            return;
        }

        $cache->set($event->getResponse());

        if ($cacheResponseAttribute->expiresAfter) {
            $cache->expiresAfter($cacheResponseAttribute->expiresAfter);
        }

        if ($cacheResponseAttribute->expiresAt) {
            $cache->expiresAt($cacheResponseAttribute->expiresAt);
        }

        $cashItemPool->save($cache);

        $attributeCacheKeys = $cashItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
        $keys = [];

        if ($attributeCacheKeys->isHit()) {
            /** @var array $keys */
            $keys = $attributeCacheKeys->get();
        }

        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
        }

        $attributeCacheKeys->set($keys);
        $cashItemPool->save($attributeCacheKeys);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [['onKernelResponse', self::$priority]]
        ];
    }
}
