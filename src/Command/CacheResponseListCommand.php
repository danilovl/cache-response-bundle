<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Command;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Service\CacheService;
use DateInterval;
use DateTimeInterface;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'danilovl:cache-response:list', description: 'List of cache response attributes.')]
class CacheResponseListCommand extends Command
{
    public function __construct(
        private readonly CacheService $cacheService,
        private readonly RouterInterface $router
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableRows = [];
        $routes = $this->router->getRouteCollection()->all();

        foreach ($routes as $route) {
            $controller = $route->getDefault('_controller');
            if (!str_contains($controller, '::')) {
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
            $actuallyInCache = $this->cacheService->findSimilarCacheKeys($attribute->cacheKey);

            $tableRows[] = [
                'controller' => $controller,
                'method' => $method,
                'originalCacheKey' => $attribute->originalCacheKey,
                'cacheKey' => $attribute->cacheKey,
                'actuallyInCache' => implode(PHP_EOL, $actuallyInCache),
                'expiresAfter' => $this->getFormattedExpiration($attribute->expiresAfter),
                'expiresAt' => $this->getFormattedExpiration($attribute->expiresAt),
                'cacheKeyWithQuery' => $attribute->cacheKeyWithQuery !== null ? 'yes' : 'no',
                'cacheKeyWithRequest' => $attribute->cacheKeyWithRequest !== null ? 'yes' : 'no'
            ];

            $output->writeln($route->getDefault('_controller'));
        }

        (new Table($output))
            ->setHeaders(['Controller', 'Action', 'Original cache key', 'Cache key', 'Actually in cache', 'Expires after', 'Expires at', 'Cache key', 'Cache key'])
            ->setRows($tableRows)
            ->render();

        return Command::SUCCESS;
    }

    function getFormattedExpiration(int|DateInterval|null $expiration): mixed
    {
        $result = 'no';

        if (is_int($expiration)) {
            $result = $expiration;
        } elseif ($expiration instanceof DateTimeInterface) {
            $result = $expiration->format('Y-m-d H:i:s');
        }

        return $result;
    }
}
