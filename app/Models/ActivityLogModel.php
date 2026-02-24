<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ActivityLogModel
{
    public const PER_PAGE = 50;

    // ─── Entity type metadata ─────────────────────────────────────────────────

    /** Human-readable label for each entity_type value. */
    public const ENTITY_LABELS = [
        'client'        => 'Client',
        'domain'        => 'Domain',
        'server'        => 'Server',
        'credential'    => 'Credential',
        'database'      => 'Database',
        'application'   => 'Application',
        'dns_record'    => 'DNS Record',
        'email_account' => 'Email Account',
        'bookmark_set'  => 'Bookmark Set',
        'host_machine'  => 'Host File',
        'client_file'   => 'Client File',
        'user'          => 'User',
    ];

    /** Base URL prefix for linking to the entity show page. */
    public const ENTITY_URL_PREFIX = [
        'client'        => '/clients',
        'domain'        => '/domains',
        'server'        => '/servers',
        'credential'    => '/credentials',
        'database'      => '/databases',
        'application'   => '/applications',
        'dns_record'    => '/dns',
        'email_account' => '/email',
        'bookmark_set'  => '/bookmarks',
        'host_machine'  => '/hosts',
        // client_file has no show page — no URL prefix
    ];

    // ─── Action groups ────────────────────────────────────────────────────────

    /** Named action categories for the filter dropdown. */
    public const ACTION_GROUPS = [
        'created'  => 'Created',
        'updated'  => 'Updated',
        'deleted'  => 'Deleted',
        'revealed' => 'Revealed',
        'login'    => 'Login / Auth',
        'vault'    => 'Vault',
        'failed'   => 'Failed / Locked',
    ];

    // ─── Badge styling ────────────────────────────────────────────────────────

    public static function actionBadgeClass(string $action): string
    {
        return match (true) {
            str_contains($action, 'created') || str_contains($action, 'imported') || str_contains($action, 'exported') || str_contains($action, 'uploaded') => 'bg-success-lt text-success',
            str_contains($action, 'downloaded')                                 => 'bg-cyan-lt text-cyan',
            str_contains($action, 'updated')                                => 'bg-blue-lt text-blue',
            str_contains($action, 'deleted')                                => 'bg-danger-lt text-danger',
            str_contains($action, 'revealed')                               => 'bg-yellow-lt text-yellow',
            str_contains($action, 'vault_unlocked')                         => 'bg-purple-lt text-purple',
            str_contains($action, 'vault')                                  => 'bg-purple-lt text-purple',
            str_contains($action, 'login') && !str_contains($action, 'fail')
                                          && !str_contains($action, 'block') => 'bg-teal-lt text-teal',
            str_contains($action, 'logout')                                 => 'bg-teal-lt text-teal',
            str_contains($action, 'failed') || str_contains($action, 'locked')
                                           || str_contains($action, 'blocked') => 'bg-red-lt text-red',
            default                                                         => 'bg-secondary-lt text-muted',
        };
    }

    // ─── Query ────────────────────────────────────────────────────────────────

    public static function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = self::buildWhere($filters);
        $offset = max(0, ($page - 1) * self::PER_PAGE);

        return Database::fetchAll("
            SELECT a.*, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            {$where}
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [self::PER_PAGE, $offset]));
    }

    public static function count(array $filters = []): int
    {
        [$where, $params] = self::buildWhere($filters);
        $row = Database::fetchOne("
            SELECT COUNT(*) AS n
            FROM `activity_logs` a
            {$where}
        ", $params);
        return (int) ($row['n'] ?? 0);
    }

    /** Delete every row from activity_logs. Returns number of rows deleted. */
    public static function clearAll(): int
    {
        return Database::execute("DELETE FROM `activity_logs`");
    }

    /** Distinct entity_type values present in the table (for filter dropdown). */
    public static function getDistinctEntityTypes(): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT entity_type FROM `activity_logs`
             WHERE entity_type IS NOT NULL
             ORDER BY entity_type ASC"
        );
        return array_column($rows, 'entity_type');
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /** Build the WHERE clause and params array from a filter array. */
    private static function buildWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        // Free-text search: description, action, IP
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like    = '%' . $search . '%';
            $where[] = '(a.description LIKE ? OR a.action LIKE ? OR a.ip_address LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like]);
        }

        // Entity type exact match
        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        if ($entityType !== '') {
            $where[]  = 'a.entity_type = ?';
            $params[] = $entityType;
        }

        // Action group (LIKE on action column)
        $actionGroup = trim((string) ($filters['action_group'] ?? ''));
        if ($actionGroup !== '' && array_key_exists($actionGroup, self::ACTION_GROUPS)) {
            $where[]  = 'a.action LIKE ?';
            $params[] = '%' . $actionGroup . '%';
        }

        // Date range
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && strtotime($dateFrom)) {
            $where[]  = 'a.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && strtotime($dateTo)) {
            $where[]  = 'a.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereClause, $params];
    }
}
