<?php
namespace RateLimiter\Strategies;
use PDO;
use PDOException;

class MySQLTokenBucketStrategy implements TokenBucketStrategyInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->createTable();
        $this->createProcedure();
    }

    private function createTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS rate_limits (
            rate_key VARCHAR(255) PRIMARY KEY,
            tokens FLOAT NOT NULL,
            max_tokens FLOAT NOT NULL,
            refill_rate FLOAT NOT NULL,
            last_refill INT NOT NULL
        )";
        $this->pdo->exec($sql);
    }

    private function createProcedure()
    {
        $sql = "
        CREATE PROCEDURE IF NOT EXISTS attempt_token_bucket(
            IN p_key VARCHAR(255),
            IN p_max_tokens FLOAT,
            IN p_refill_rate FLOAT,
            IN p_requested FLOAT,
            OUT p_allowed BOOLEAN,
            OUT p_available_tokens FLOAT
        )
        BEGIN
            DECLARE v_tokens FLOAT;
            DECLARE v_last_refill INT;
            DECLARE v_now INT;
            
            SET v_now = UNIX_TIMESTAMP();
            
            INSERT INTO rate_limits (rate_key, tokens, max_tokens, refill_rate, last_refill)
            VALUES (p_key, p_max_tokens, p_max_tokens, p_refill_rate, v_now)
            ON DUPLICATE KEY UPDATE
                tokens = LEAST(max_tokens, tokens + (v_now - last_refill) * refill_rate),
                last_refill = v_now,
                max_tokens = p_max_tokens,
                refill_rate = p_refill_rate;
            
            SELECT tokens INTO v_tokens FROM rate_limits WHERE rate_key = p_key;
            
            IF v_tokens >= p_requested THEN
                UPDATE rate_limits SET tokens = tokens - p_requested WHERE rate_key = p_key;
                SET p_allowed = TRUE;
                SET p_available_tokens = v_tokens - p_requested;
            ELSE
                SET p_allowed = FALSE;
                SET p_available_tokens = v_tokens;
            END IF;
        END
        ";
        $this->pdo->exec($sql);
    }

    public function attempt(string $key, float $maxTokens, float $refillRate, float $tokensRequested=1): bool
    {
        try {
            $sql = "CALL attempt_token_bucket(:key, :max_tokens, :refill_rate, :tokens_requested, @allowed, @available_tokens)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':key' => $key,
                ':max_tokens' => $maxTokens,
                ':refill_rate' => $refillRate,
                ':tokens_requested' => $tokensRequested
            ]);
            
            $result = $this->pdo->query("SELECT @allowed AS allowed, @available_tokens AS available_tokens")->fetch(PDO::FETCH_ASSOC);
            echo "~~~~~~",$result['available_tokens'],"~~~~~~";
            
            return $result['allowed'] == 1;
        } catch (PDOException $e) {
            echo "SQL错误: " . $e->getMessage() . "\n";
            echo "错误代码: " . $e->getCode() . "\n";
            throw $e;
        }
    }

    public function getAvailableTokens(string $key): float
    {
        // $now = microtime(true);
        
        // $stmt = $this->pdo->prepare("
        //     SELECT 
        //         LEAST(max_tokens, tokens + (:now - last_refill) * refill_rate) AS available_tokens
        //     FROM rate_limits 
        //     WHERE rate_key = :key
        // ");
        // $stmt->execute([':key' => $key, ':now' => $now]);
        // $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // return $result ? floatval($result['available_tokens']) : 0;

        return 0;
    }
}
