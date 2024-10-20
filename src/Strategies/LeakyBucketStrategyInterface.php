<?php

namespace RateLimiter\Strategies;

interface LeakyBucketStrategyInterface
{
    public function attempt(string $key, int $capacity, float $leakRate, int $amount): bool;
    public function getAvailableCapacity(string $key): float;
}

