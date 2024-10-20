<?php

namespace RateLimiter\Strategies;


interface RateLimitStrategyInterface
{
    /**
     * 检查是否允许请求
     *
     * @param string $key 限流的唯一标识符
     * @param array $params 限流参数，不同算法可能需要不同的参数
     * @return bool 是否允许请求
     */
    public function isAllowed(string $key, array $params): bool;

    // /**
    //  * 获取当前限流状态
    //  *
    //  * @param string $key 限流的唯一标识符
    //  * @return array 包含限流状态信息的数组
    //  */
    // public function getStatus(string $key): array;

    // /**
    //  * 重置指定键的限流状态
    //  *
    //  * @param string $key 限流的唯一标识符
    //  * @return bool 重置是否成功
    //  */
    // public function reset(string $key): bool;
}
