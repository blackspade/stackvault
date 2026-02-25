<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ClientDocModel
{
    public static function ensureSchema(): void
    {
        Database::execute("
            CREATE TABLE IF NOT EXISTS `client_docs` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `client_id`  INT UNSIGNED NOT NULL UNIQUE,
                `content`    LONGTEXT NOT NULL DEFAULT '',
                `ip_tables`  JSON NOT NULL,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public static function getByClient(int $clientId): array|null
    {
        return Database::fetchOne(
            "SELECT * FROM `client_docs` WHERE `client_id` = ?",
            [$clientId]
        ) ?: null;
    }

    public static function save(int $clientId, string $content, string $ipTablesJson): void
    {
        Database::execute("
            INSERT INTO `client_docs` (`client_id`, `content`, `ip_tables`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                `content`    = VALUES(`content`),
                `ip_tables`  = VALUES(`ip_tables`),
                `updated_at` = CURRENT_TIMESTAMP
        ", [$clientId, $content, $ipTablesJson]);
    }
}
