<?php

namespace RateLimiter;

use RateLimiter\Strategies\RateLimitStrategyInterface;
use RateLimiter\Strategies\TokenBucketStrategy;
use RateLimiter\Strategies\RedisTokenBucketStrategy;
use RateLimiter\Strategies\MySQLTokenBucketStrategy;
use RateLimiter\Strategies\MySQLLeakyBucketStrategy;
use RateLimiter\Strategies\LeakyBucketStrategy;
use RateLimiter\Strategies\RedisLeakyBucketStrategy;
use RateLimiter\Storage\StorageInterface;
use RateLimiter\Storage\RedisStorage;
use RateLimiter\Storage\MySQLStorage;


class RateLimitStrategyFactory
{
    public static function create(string $strategyType, StorageInterface $storage): RateLimitStrategyInterface
    {
        switch ($strategyType) {
            case 'token_bucket':
                if ($storage instanceof RedisStorage) {
                    $concreteStrategy = new RedisTokenBucketStrategy($storage->getRedis());
                } elseif ($storage instanceof MySQLStorage) {
                    $concreteStrategy = new MySQLTokenBucketStrategy($storage->getPDO());
                } else {
                    throw new \InvalidArgumentException("Unsupported storage for token bucket strategy");
                }
                return new TokenBucketStrategy($concreteStrategy);
            case 'leaky_bucket':
                if ($storage instanceof RedisStorage) {
                    $concreteStrategy = new RedisLeakyBucketStrategy($storage->getRedis());
                } elseif ($storage instanceof MySQLStorage) {
                    $concreteStrategy = new MySQLLeakyBucketStrategy($storage->getPDO());
                } else {
                    throw new \InvalidArgumentException("不支持的存储类型用于漏桶策略");
                }
                return new LeakyBucketStrategy($concreteStrategy);
            case 'fixed_window':
                // return new FixedWindowCounterStrategy($storage);
            default:
                throw new \InvalidArgumentException("Unknown strategy type: $strategyType");
        }
    }
}
