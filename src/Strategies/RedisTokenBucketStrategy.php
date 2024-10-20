<?php

namespace RateLimiter\Strategies;

use Redis;

class RedisTokenBucketStrategy implements TokenBucketStrategyInterface
{
    private $redis;
    private $script;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
        $this->loadScript();
    }

    private function loadScript()
    {
        $this->script = $this->redis->script('load', "
            local key = KEYS[1]
            local max_tokens = tonumber(ARGV[1])
            local refill_rate = tonumber(ARGV[2])
            local now = tonumber(ARGV[3])
            local requested = tonumber(ARGV[4])

            local data = redis.call('HMGET', key, 'tokens', 'last_refill')
            local tokens = tonumber(data[1]) or max_tokens
            local last_refill = tonumber(data[2]) or now

            local time_passed = now - last_refill
            local new_tokens = math.min(max_tokens, tokens + time_passed * refill_rate)

            if new_tokens < requested then
                redis.call('HMSET', key, 'tokens', new_tokens, 'last_refill', now)
                return {0, new_tokens}
            end

            new_tokens = new_tokens - requested
            redis.call('HMSET', key, 'tokens', new_tokens, 'last_refill', now)
            return {1, new_tokens}
        ");
    }

    public function attempt(string $key, float $maxTokens, float $refillRate, float $tokensRequested = 1): bool
    {
        $result = $this->redis->evalSha(
            $this->script, 
            [$key, $maxTokens, $refillRate, microtime(true), $tokensRequested],
            1
        );
        return $result[0] == 1;
    }

    public function getAvailableTokens(string $key): float
    {
        $data = $this->redis->hMGet($key, ['tokens', 'last_refill']);
        if (!$data['tokens']) {
            return 0;
        }
        return floatval($data['tokens']);
    }
}
