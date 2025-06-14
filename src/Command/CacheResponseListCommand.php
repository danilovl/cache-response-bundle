<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Command;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Interfaces\CacheKeyFactoryInterface;
use Danilovl\CacheResponseBundle\Service\CacheService;
use DateInterval;
use DateTimeInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'danilovl:cache-response:list', description: 'List of cache response attributes.')]
class CacheResponseListCommand extends Command
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly RouterInterface $router,
        private readonly ContainerInterface $container
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = $this->router->getRouteCollection()->all();

        $table = (new Table($output))
            ->setHeaders([
                'Controller',
                'Action',
                'Cache info',
                'Factory',
                'Adapter',
                'Expires after',
                'Expires at',
                'Use session',
                'Use route',
                'Use query',
                'Use request',
                'Use env',
                'Disable on query',
                'Disable on request'
            ]);

        foreach ($routes as $route) {
            /** @var string|null $controller */
            $controller = $route->getDefault('_controller');
            if ($controller == null || !str_contains($controller, '::')) {
                continue;
            }

            [$controller, $method] = explode('::', $controller);

            if (!class_exists($controller)) {
                continue;
            }

            $attributes = (new ReflectionClass($controller))
                ->getMethod($method)
                ->getAttributes(CacheResponseAttribute::class);

            $attributes = $attributes[0] ?? null;
            if ($attributes === null) {
                continue;
            }

            /** @var CacheResponseAttribute $attribute */
            $attribute = $attributes->newInstance();

            if ($attribute->factory !== null) {
                /** @var CacheKeyFactoryInterface $cacheFactory */
                $cacheFactory = $this->container->get($attribute->factory);
                $cacheKey = $cacheFactory->getCacheKey();
                $originalCacheKey = $cacheKey;
            } else {
                $cacheKey = $attribute->getCacheKeyNotNull();
                $originalCacheKey = $attribute->originalCacheKey;
            }

            $actuallyInCache = $this->cacheService->findSimilarCacheKeys($cacheKey);

            $table->addRow([
                $controller,
                $method,
                'Original cache key:',
                $attribute->factory !== null ? $attribute->factory : 'no',
                $attribute->cacheAdapter !== null ? $attribute->cacheAdapter : 'no',
                $this->getFormattedExpiration($attribute->expiresAfter),
                $this->getFormattedExpiration($attribute->expiresAt),
                $attribute->useSession ? 'yes' : 'no',
                $attribute->useRoute ? 'yes' : 'no',
                $attribute->useQuery ? 'yes' : 'no',
                $attribute->useRequest ? 'yes' : 'no',
                $attribute->useEnv ? 'yes' : 'no',
                $attribute->disableOnQuery ? 'yes' : 'no',
                $attribute->disableOnRequest ? 'yes' : 'no'
            ]);

            $nulls = [null, null, null, null, null, null, null, null, null, null, null];

            $table->addRow([null, null, $originalCacheKey, ...$nulls]);
            $table->addRow([null, null, null, ...$nulls]);
            $table->addRow([null, null, 'Attribute cache key:', ...$nulls]);
            $table->addRow([null, null, $cacheKey, ...$nulls]);
            $table->addRow([null, null, null, ...$nulls]);
            $table->addRow([null, null, 'Actually in cache:', ...$nulls]);
            $table->addRow([null, null, implode(PHP_EOL, $actuallyInCache), ...$nulls]);
            $table->addRow([null, null, null, ...$nulls]);
        }

        $table->render();

        return Command::SUCCESS;
    }

    public function getFormattedExpiration(int|DateInterval|DateTimeInterface|null $expiration): mixed
    {
        $result = 'no';

        if (is_int($expiration)) {
            $result = $expiration;
        } elseif ($expiration !== null) {
            $result = $expiration->format('Y-m-d H:i:s');
        }

        return $result;
    }
}
