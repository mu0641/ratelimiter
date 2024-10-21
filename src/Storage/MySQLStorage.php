<?php

namespace RateLimiter\Storage;

use PDO;

class MySQLStorage implements StorageInterface
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}
