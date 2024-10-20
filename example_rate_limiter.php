<?php

require_once __DIR__ . '/vendor/autoload.php';

// 设置时区为北京时间
date_default_timezone_set('Asia/Shanghai');

use RateLimiter\Storage\RedisStorage;
use RateLimiter\Storage\MySQLStorage;
use RateLimiter\RateLimitStrategyFactory;
use RateLimiter\RateLimiter;

// 1. 存储设置示例
function getRedisStorage() {
    // Redis 连接示例
    $redis = new Redis();
    $redis->connect('redis', 6379);
    return new RedisStorage($redis);
}

function getMysqlStorage() {
    // MySQL 连接示例
    $host = '192.168.0.106';
    $db   = 'test';
    $user = 'root';
    $pass = '123456';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
    return new MySQLStorage($pdo);
}

// 2. 创建限流策略和实例
$storage = getRedisStorage(); //
// $storage = getMysqlStorage(); // 或 getMysqlStorage()
$strategyFactory = new RateLimitStrategyFactory();
$strategy = $strategyFactory->create('token_bucket', $storage);

// 3. 创建不同级别的RateLimiter实例
$userRateLimiter = new RateLimiter($strategy);
$globalRateLimiter = new RateLimiter($strategy);

// 4. 测试函数
function runTest($userRateLimiter, $globalRateLimiter, $userId, $userLimit, $userPeriod, $globalLimit, $globalPeriod, $requests = 15) {
    for ($i = 1; $i <= $requests; $i++) {
        $userKey = "user:{$userId}";
        $globalKey = "global";

        $userResult = $userRateLimiter->isAllowed($userKey, [
            'maxTokens' => $userLimit,
            'refillRate' => $userLimit / $userPeriod,
            'tokensRequested' => 1
        ]);
        $globalResult = $globalRateLimiter->isAllowed($globalKey, [
            'maxTokens' => $globalLimit,
            'refillRate' => $globalLimit / $globalPeriod,
            'tokensRequested' => 1
        ]);

        $allowed = $userResult && $globalResult;

        $userTokens = $userRateLimiter->getAvailableTokens($userKey);
        $globalTokens = $globalRateLimiter->getAvailableTokens($globalKey);

        echo date('H:i:s') . " - 请求 {$i} " . ($allowed ? "被允许" : "被拒绝") .
            " (用户令牌: {$userTokens}, 全局令牌: {$globalTokens})\n</br>";

        usleep(500000); // 休眠0.5秒
    }
}

function runLeakyBucketTest($leakyBucketRateLimiter, $userId, $capacity, $leakRate, $requests = 15) {
    for ($i = 1; $i <= $requests; $i++) {
        $key = "user:{$userId}:leaky_bucket";

        $result = $leakyBucketRateLimiter->isAllowed($key, [
            'capacity' => $capacity,
            'leakRate' => $leakRate,
            'amount' => 1
        ]);

        $availableCapacity = $leakyBucketRateLimiter->getAvailableCapacity($key);

        echo date('H:i:s') . " - 漏斗请求 {$i} " . ($result ? "被允许" : "被拒绝") .
            " \n</br>";

        usleep(500000); // 休眠0.5秒
    }
}

// 5. 设置测试参数
$userId = 23567;
$userLimit = 5;
$userPeriod = 60; // 60秒内限制5次请求
$globalLimit = 10;
$globalPeriod = 60; // 60秒内限制10次全局请求
$waitTime = 2; // 等待时间，可以根据需要调整

// 6. 执行令牌桶测试
// echo "令牌桶策略测试\n</br>";
// echo "第一次测试开始\n</br>";
// runTest($userRateLimiter, $globalRateLimiter, $userId, $userLimit, $userPeriod, $globalLimit, $globalPeriod);

// echo "等待{$waitTime}秒...\n</br>";
// sleep($waitTime);

// echo "第二次测试开始\n</br>";
// runTest($userRateLimiter, $globalRateLimiter, $userId, $userLimit, $userPeriod, $globalLimit, $globalPeriod);

// 7. 漏斗策略测试
echo "\n漏斗策略测试\n</br>";
$leakyBucketStrategy = $strategyFactory->create('leaky_bucket', $storage);
$leakyBucketRateLimiter = new RateLimiter($leakyBucketStrategy);

$capacity = 5;
$leakRate = 1; // 每秒泄漏1个请求

echo "第一次测试开始\n</br>";
runLeakyBucketTest($leakyBucketRateLimiter, $userId, $capacity, $leakRate);

echo "等待{$waitTime}秒...\n</br>";
sleep($waitTime);

echo "第二次测试开始\n</br>";
runLeakyBucketTest($leakyBucketRateLimiter, $userId, $capacity, $leakRate);
