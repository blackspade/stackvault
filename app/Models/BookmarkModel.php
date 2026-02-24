<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class BookmarkModel
{
    // ─── List ─────────────────────────────────────────────────────────────────

    /** All bookmarks for a set, with folder name for display. */
    public static function getBySet(int $setId): array
    {
        return Database::fetchAll("
            SELECT b.*, f.name AS folder_name
            FROM `bookmarks` b
            LEFT JOIN `bookmark_folders` f ON f.id = b.folder_id
            WHERE b.set_id = ?
            ORDER BY b.folder_id ASC, b.sort_order ASC, b.title ASC
        ", [$setId]);
    }

    /** Bookmarks in a specific folder. */
    public static function getByFolder(int $folderId): array
    {
        return Database::fetchAll("
            SELECT * FROM `bookmarks`
            WHERE folder_id = ?
            ORDER BY sort_order ASC, title ASC
        ", [$folderId]);
    }

    /** Bookmarks with no folder (root-level). */
    public static function getRootBookmarks(int $setId): array
    {
        return Database::fetchAll("
            SELECT * FROM `bookmarks`
            WHERE set_id = ? AND folder_id IS NULL
            ORDER BY sort_order ASC, title ASC
        ", [$setId]);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM `bookmarks` WHERE id = ?", [$id]
        );
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `bookmarks`
                (set_id, folder_id, title, url, favicon, add_date, sort_order, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['set_id'],
                $data['folder_id']   ?? null,
                $data['title'],
                $data['url'],
                $data['favicon']     ?? null,
                $data['add_date']    ?? null,
                $data['sort_order']  ?? 0,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `bookmarks`
             SET folder_id = ?, title = ?, url = ?
             WHERE id = ?",
            [
                $data['folder_id'] ?: null,
                $data['title'],
                $data['url'],
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `bookmarks` WHERE id = ?", [$id]);
    }

    public static function deleteBySet(int $setId): void
    {
        Database::execute("DELETE FROM `bookmarks` WHERE set_id = ?", [$setId]);
    }
}
