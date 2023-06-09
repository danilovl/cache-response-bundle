<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Attribute;

use Attribute;
use DateInterval;
use Symfony\Component\HttpFoundation\Request;

#[Attribute]
class CacheResponseAttribute
{
    public const REQUEST_ATTRIBUTES_CACHE_USED = 'danilovl.cache_response_used';

    public function __construct(
        public readonly string $cacheKey,
        public readonly int|DateInterval|null $expiresAfter = null,
        public readonly int|DateInterval|null $expiresAt = null,
        public readonly bool $cacheKeyWithQuery = false,
        public readonly bool $cacheKeyWithRequest = false
    ) {}

    public function getCacheKey(Request $request): string
    {
        if (!$this->cacheKeyWithQuery && !$this->cacheKeyWithRequest) {
            return $this->cacheKey;
        }

        $cacheKey = $this->cacheKey;
        if ($this->cacheKeyWithQuery) {
            $queryAll = $request->query->all();
            if (count($queryAll) > 0) {
                $cacheKey .= '.' . sha1(serialize($queryAll));
            }
        }

        if ($this->cacheKeyWithQuery) {
            $requestAll = $request->request->all();
            if (count($requestAll) > 0) {
                $cacheKey .= '.' . sha1(serialize($requestAll));
            }
        }

        return $cacheKey;
    }
}
