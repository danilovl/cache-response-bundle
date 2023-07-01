<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Attribute;

use Attribute;
use Danilovl\CacheResponseBundle\Exception\CacheResponseInvalidArgumentException;
use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;
use DateInterval;
use Symfony\Component\HttpFoundation\Request;

#[Attribute]
class CacheResponseAttribute
{
    public const CACHE_KEY_PREFIX = 'danilovl.cache_response.';
    public const REQUEST_ATTRIBUTES_CACHE_USED = 'danilovl.cache_response_used';

    public readonly ?string $originalCacheKey;
    public readonly ?string $cacheKey;

    public function __construct(
        ?string $cacheKey = null,
        public readonly ?string $cacheKeyFactory = null,
        public readonly int|DateInterval|null $expiresAfter = null,
        public readonly int|DateInterval|null $expiresAt = null,
        public readonly bool $cacheKeyWithQuery = false,
        public readonly bool $cacheKeyWithRequest = false
    ) {
        if ($cacheKey === null && $cacheKeyFactory === null) {
            throw new CacheResponseInvalidArgumentException('CacheKey or CacheKeyFactory is required.');
        }

        if ($cacheKey !== null) {
            $this->originalCacheKey = $cacheKey;
            $this->cacheKey = self::CACHE_KEY_PREFIX . $cacheKey;
        } else {
            $this->originalCacheKey = null;
            $this->cacheKey = null;
        }

        if ($cacheKeyFactory !== null) {
            $interfaces = class_implements($cacheKeyFactory);
            if ($interfaces !== false && !in_array(CacheKeyFactoryInterface::class, $interfaces)) {
                throw new CacheResponseInvalidArgumentException('Class CacheKeyFactory is not implemented CacheKeyFactoryInterface.');
            }
        }
    }

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

        if ($this->cacheKeyWithRequest) {
            $requestAll = $request->request->all();
            if (count($requestAll) > 0) {
                $cacheKey .= '.' . sha1(serialize($requestAll));
            }
        }

        return $cacheKey;
    }

    public static function getCacheKeyWithPrefix(string $cacheKey): string
    {
        if (self::isCacheKeyContainsPrefix($cacheKey)) {
            return $cacheKey;
        }

        return self::CACHE_KEY_PREFIX . $cacheKey;
    }

    public static function isCacheKeyContainsPrefix(string $cacheKey): bool
    {
        return str_contains($cacheKey, self::CACHE_KEY_PREFIX);
    }
}
