<?php

namespace RateLimiter\Strategies;

class TokenBucketStrategy implements RateLimitStrategyInterface
{
    private TokenBucketStrategyInterface $concreteStrategy;

    public function __construct(TokenBucketStrategyInterface $concreteStrategy)
    {
        $this->concreteStrategy = $concreteStrategy;
    }

    public function isAllowed(string $key, array $params): bool
    {
        $maxTokens = $params['maxTokens'] ?? 10;
        $refillRate = $params['refillRate'] ?? 1;
        $tokensRequested = $params['tokensRequested'] ?? 1;

        return $this->concreteStrategy->attempt($key, $maxTokens, $refillRate, $tokensRequested);
    }

    public function getAvailableTokens(string $key): float
    {
        return $this->concreteStrategy->getAvailableTokens($key);
    }
}
