<?php
namespace RateLimiter\Strategies;

interface TokenBucketStrategyInterface
{
    /**
     * 尝试获取令牌
     *
     * @param string $key 限流的唯一标识符
     * @param float $maxTokens 桶的最大容量
     * @param float $refillRate 每秒补充的令牌数
     * @param float $tokensRequested 请求的令牌数，默认为1
     * @return bool 是否允许请求
     */
    public function attempt(string $key, float $maxTokens, float $refillRate, float $tokensRequested = 1): bool;

    /**
     * 获取当前可用的令牌数
     *
     * @param string $key 限流的唯一标识符
     * @return float 当前可用的令牌数
     */
    public function getAvailableTokens(string $key): float;
}