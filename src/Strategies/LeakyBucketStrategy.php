<?php

namespace RateLimiter\Strategies;

class LeakyBucketStrategy implements RateLimitStrategyInterface
{
    private LeakyBucketStrategyInterface $concreteStrategy;

    public function __construct(LeakyBucketStrategyInterface $concreteStrategy)
    {
        $this->concreteStrategy = $concreteStrategy;
    }

    public function isAllowed(string $key, array $params): bool
    {
        $capacity = $params['capacity'] ?? 10;
        $leakRate = $params['leakRate'] ?? 1;
        $amount = $params['amount'] ?? 1;

        return $this->concreteStrategy->attempt($key, $capacity, $leakRate, $amount);
    }

    public function getAvailableCapacity(string $key): float
    {
        return $this->concreteStrategy->getAvailableCapacity($key);
    }
}
