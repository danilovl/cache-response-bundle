<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\EventListener;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Service\CacheService;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class KernelResponseListener implements EventSubscriberInterface
{
    public function __construct(private CacheItemPoolInterface $cacheItemPool) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $controllerAttribute = $event->getRequest()->attributes->get('_controller');
        if (!str_contains($controllerAttribute, '::')) {
            return;
        }

        [$controller, $method] = explode('::', $event->getRequest()->attributes->get('_controller'));

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
        CacheResponseAttribute $hashidsParamConverterAttribute
    ): void {
        $isCacheUsed = $event->getRequest()->attributes->get(CacheResponseAttribute::REQUEST_ATTRIBUTES_CACHE_USED);
        if ($isCacheUsed) {
            return;
        }

        $cacheKey = $hashidsParamConverterAttribute->getCacheKey($event->getRequest());

        $cache = $this->cacheItemPool->getItem($cacheKey);
        if ($cache->isHit()) {
            return;
        }

        $cache->set($event->getResponse());

        if ($hashidsParamConverterAttribute->expiresAfter) {
            $cache->expiresAfter($hashidsParamConverterAttribute->expiresAfter);
        }

        if ($hashidsParamConverterAttribute->expiresAt) {
            $cache->expiresAt($hashidsParamConverterAttribute->expiresAt);
        }

        $this->cacheItemPool->save($cache);

        $attributeCacheKeys = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);
        $keys = [];

        if ($attributeCacheKeys->isHit()) {
            $keys = $attributeCacheKeys->get();
        }

        $keys[] = $cacheKey;
        $attributeCacheKeys->set($keys);

        $this->cacheItemPool->save($attributeCacheKeys);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }
}
