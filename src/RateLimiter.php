<?php

namespace RateLimiter;

use RateLimiter\Strategies\RateLimitStrategyInterface;
use RateLimiter\Strategies\TokenBucketStrategy;

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
}
