<?php

namespace RateLimiter\Storage;

use Redis;

class RedisStorage implements StorageInterface
{
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }
}
