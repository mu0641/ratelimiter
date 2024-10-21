<?php

namespace RateLimiter;

use RateLimiter\Strategies\RateLimitStrategyInterface;
use RateLimiter\Strategies\TokenBucketStrategy;
use RateLimiter\Strategies\LeakyBucketStrategy;

class RateLimiter
{
    private $strategy;

    public function __construct(RateLimitStrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    public function isAllowed($key, array $params): bool
    {
        return $this->strategy->isAllowed($key, $params);
    }

    public function getAvailableTokens($key): float
    {
        if ($this->strategy instanceof TokenBucketStrategy) {
            return $this->strategy->getAvailableTokens($key);
        }
        throw new \RuntimeException("Current strategy does not support getAvailableTokens");
    }


    public function  getAvailableCapacity($key): float
    {
        if ($this->strategy instanceof LeakyBucketStrategy) {
            return $this->strategy->getAvailableCapacity($key);
        }
        throw new \RuntimeException("Current strategy does not support getAvailableCapacity");
    }
}
