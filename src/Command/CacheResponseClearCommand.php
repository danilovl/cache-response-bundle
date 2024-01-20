<?php declare(strict_types=1);

namespace Danilovl\CacheResponseBundle\Command;

use Danilovl\CacheResponseBundle\Attribute\CacheResponseAttribute;
use Danilovl\CacheResponseBundle\Service\CacheService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{
    InputInterface,
    InputOption
};
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'danilovl:cache-response:clear', description: 'Clear cache response.')]
class CacheResponseClearCommand extends Command
{
    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly CacheService $cacheService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', null, InputOption::VALUE_OPTIONAL, 'Delete all cache items.')
            ->addOption('cacheKey', null, InputOption::VALUE_OPTIONAL, 'Delete only by cache key.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $input->getOption('all');
        /** @var string|null $deleteCacheKey */
        $deleteCacheKey = $input->getOption('cacheKey');

        if ($all === null && $deleteCacheKey === null) {
            $output->writeln('You must specify at least one option.');

            return Command::FAILURE;
        }

        if ($all) {
            $this->cacheItemPool->deleteItems($this->cacheService->getCacheKeys());
            $this->cacheItemPool->deleteItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);

            return Command::SUCCESS;
        }

        if ($deleteCacheKey) {
            $deleteCacheKey = CacheResponseAttribute::getCacheKeyWithPrefix($deleteCacheKey);

            $deleteCacheKeys = [$deleteCacheKey];
            $similarCacheKeys = $this->cacheService->findSimilarCacheKeys($deleteCacheKey);

            if (count($similarCacheKeys) === 0) {
                $output->writeln('Cache key not found or actually cache for this key is already empty.');

                return Command::FAILURE;
            }

            $deleteCacheKeys = array_merge($deleteCacheKeys, $similarCacheKeys);
            $this->cacheItemPool->deleteItems($deleteCacheKeys);

            $attributeCacheKeysItem = $this->cacheItemPool->getItem(CacheService::CACHE_KEY_FOR_ATTRIBUTE_CACHE_KEYS);

            /** @var array $cacheKeysItem */
            $cacheKeysItem = $attributeCacheKeysItem->get();
            $newCacheKeys = array_diff($cacheKeysItem, $deleteCacheKeys);

            $attributeCacheKeysItem->set($newCacheKeys);
            $this->cacheItemPool->save($attributeCacheKeysItem);
        }

        return Command::SUCCESS;
    }
}
