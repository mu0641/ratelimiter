<?php

namespace RateLimiter\Strategies;

use PDO;
use PDOException;

class MySQLLeakyBucketStrategy implements LeakyBucketStrategyInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->createTable();
        $this->createProcedure();
    }

    private function createTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS leaky_bucket (
            `key` VARCHAR(255) PRIMARY KEY,
            `value` FLOAT NOT NULL,
            `last_update` TIMESTAMP NOT NULL
        )";
        $this->pdo->exec($sql);
    }

    private function createProcedure()
    {
        $sql = "
        CREATE PROCEDURE IF NOT EXISTS leaky_bucket_attempt(
            IN p_key VARCHAR(255),
            IN p_capacity INT,
            IN p_leak_rate FLOAT,
            IN p_amount INT,
            OUT p_success BOOLEAN
        )
        BEGIN
            DECLARE v_current_value FLOAT;
            DECLARE v_last_update INT;
            DECLARE v_now INT;
            DECLARE v_elapsed INT;
            DECLARE v_leaked FLOAT;
            DECLARE v_new_value FLOAT;

            SET v_now = UNIX_TIMESTAMP();

            -- 尝试获取现有记录
            SELECT value, UNIX_TIMESTAMP(last_update) INTO v_current_value, v_last_update
            FROM leaky_bucket
            WHERE `key` = p_key
            FOR UPDATE;

            IF v_current_value IS NULL THEN
                SET v_current_value = 0;
                SET v_last_update = v_now;
            END IF;

            -- 计算漏出的量
            SET v_elapsed = v_now - v_last_update;
            SET v_leaked = LEAST(v_current_value, v_elapsed * p_leak_rate);
            SET v_current_value = GREATEST(0, v_current_value - v_leaked);

            -- 检查是否可以添加新的量
            IF v_current_value + p_amount <= p_capacity THEN
                SET v_new_value = v_current_value + p_amount;
                
                -- 更新或插入记录
                INSERT INTO leaky_bucket (`key`, value, last_update)
                VALUES (p_key, v_new_value, FROM_UNIXTIME(v_now))
                ON DUPLICATE KEY UPDATE
                    value = v_new_value,
                    last_update = FROM_UNIXTIME(v_now);

                SET p_success = TRUE;
            ELSE
                SET p_success = FALSE;
            END IF;
        END
        ";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // 如果存储过程已存在,MySQL会抛出异常
            // 我们可以忽略这个错误,因为这意味着存储过程已经创建
            if ($e->getCode() !== '42000') {
                throw $e;
            }
        }
    }

    public function attempt(string $key, int $capacity, float $leakRate, int $amount): bool
    {
        $stmt = $this->pdo->prepare("CALL leaky_bucket_attempt(:key, :capacity, :leak_rate, :amount, @success)");
        $stmt->execute([
            'key' => $key,
            'capacity' => $capacity,
            'leak_rate' => $leakRate,
            'amount' => $amount
        ]);

        $result = $this->pdo->query("SELECT @success as success")->fetch(PDO::FETCH_ASSOC);
        return (bool) $result['success'];
    }

    public function getAvailableCapacity(string $key): float
    {
        $stmt = $this->pdo->prepare("SELECT value FROM leaky_bucket WHERE `key` = :key");
        $stmt->execute(['key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? floatval($row['value']) : 0.0;
    }
}
