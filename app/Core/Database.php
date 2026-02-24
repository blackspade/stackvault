<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host   = Config::get('DB_HOST',     'localhost');
            $port   = Config::get('DB_PORT',     3306);
            $dbname = Config::get('DB_DATABASE', '');
            $user   = Config::get('DB_USERNAME', '');
            $pass   = Config::get('DB_PASSWORD', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ]);
        }

        return self::$instance;
    }

    /** Run a prepared query and return the statement. */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row or null. */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    /** Fetch all rows. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Execute an INSERT and return the last insert ID. */
    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int) self::getInstance()->lastInsertId();
    }

    /** Execute an UPDATE/DELETE and return affected rows. */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /** Begin a transaction. */
    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    /** Commit a transaction. */
    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    /** Roll back a transaction. */
    public static function rollback(): void
    {
        self::getInstance()->rollBack();
    }
}
