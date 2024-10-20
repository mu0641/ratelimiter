<?php

namespace RateLimiter\Storage;

use PDO;

class MySQLStorage implements StorageInterface
{
    private $pdo;

    public function __construct()
    {

        // 添加MySQL连接代码
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
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
