<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class BookmarkFolderModel
{
    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getBySet(int $setId): array
    {
        return Database::fetchAll("
            SELECT f.*,
                (SELECT COUNT(*) FROM `bookmarks` b WHERE b.folder_id = f.id) AS bookmark_count
            FROM `bookmark_folders` f
            WHERE f.set_id = ?
            ORDER BY f.sort_order ASC, f.name ASC
        ", [$setId]);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM `bookmark_folders` WHERE id = ?", [$id]
        );
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(int $setId, string $name, int $sortOrder = 0): int
    {
        return Database::insert(
            "INSERT INTO `bookmark_folders` (set_id, name, sort_order, created_at)
             VALUES (?, ?, ?, NOW())",
            [$setId, $name, $sortOrder]
        );
    }

    public static function update(int $id, string $name): void
    {
        Database::execute(
            "UPDATE `bookmark_folders` SET name = ? WHERE id = ?",
            [$name, $id]
        );
    }

    /**
     * Delete a folder. Bookmarks inside it become unfiled (folder_id = NULL).
     */
    public static function delete(int $id): void
    {
        Database::execute(
            "UPDATE `bookmarks` SET folder_id = NULL WHERE folder_id = ?", [$id]
        );
        Database::execute("DELETE FROM `bookmark_folders` WHERE id = ?", [$id]);
    }

    public static function deleteBySet(int $setId): void
    {
        Database::execute("DELETE FROM `bookmark_folders` WHERE set_id = ?", [$setId]);
    }
}
