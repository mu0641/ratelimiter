<?php

namespace RateLimiter\Strategies;

use Redis;

class RedisLeakyBucketStrategy implements LeakyBucketStrategyInterface
{
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function attempt(string $key, int $capacity, float $leakRate, int $amount): bool
    {
        $script = <<<LUA
        local key = KEYS[1]
        local capacity = tonumber(ARGV[1])
        local leakRate = tonumber(ARGV[2])
        local amount = tonumber(ARGV[3])
        local now = tonumber(ARGV[4])

        local last_update = redis.call('HGET', key, 'last_update') or now
        local current_value = tonumber(redis.call('HGET', key, 'value') or 0)

        local elapsed = now - last_update
        local leaked = math.min(current_value, elapsed * leakRate)
        current_value = math.max(0, current_value - leaked)

        if current_value + amount <= capacity then
            redis.call('HSET', key, 'value', current_value + amount)
            redis.call('HSET', key, 'last_update', now)
            return 1
        else
            return 0
        end
        LUA;

        $result = $this->redis->eval($script, [$key, $capacity, $leakRate, $amount, time()], 1);
        return $result === 1;
    }

    public function getAvailableCapacity(string $key): float
    {
        $current = $this->redis->hGet($key, 'value');
        return $current !== false ? floatval($current) : 0.0;
    }
}

