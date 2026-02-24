<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DnsRecordModel
{
    // ─── Type definitions ─────────────────────────────────────────────────────

    public const TYPES = [
        'A'     => 'A',
        'AAAA'  => 'AAAA',
        'CNAME' => 'CNAME',
        'MX'    => 'MX',
        'TXT'   => 'TXT',
        'NS'    => 'NS',
        'SRV'   => 'SRV',
        'CAA'   => 'CAA',
        'PTR'   => 'PTR',
        'SOA'   => 'SOA',
    ];

    /** Record types that use the priority field. */
    public const PRIORITY_TYPES = ['MX', 'SRV', 'CAA'];

    /** Hint text shown beneath the value field per record type. */
    public const VALUE_HINTS = [
        'A'     => 'IPv4 address — e.g. 93.184.216.34',
        'AAAA'  => 'IPv6 address — e.g. 2606:2800:220:1:248:1893:25c8:1946',
        'CNAME' => 'Target hostname — e.g. alias.example.com.',
        'MX'    => 'Mail server hostname — e.g. mail.example.com.',
        'TXT'   => 'Quoted string — e.g. "v=spf1 include:_spf.google.com ~all"',
        'NS'    => 'Nameserver hostname — e.g. ns1.example.com.',
        'SRV'   => 'weight port target — e.g. 10 443 sipdir.l.google.com.',
        'CAA'   => 'flags tag value — e.g. 0 issue "letsencrypt.org"',
        'PTR'   => 'Reverse-lookup hostname — e.g. host.example.com.',
        'SOA'   => 'mname rname serial refresh retry expire minimum',
    ];

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'A'     => 'bg-blue-lt text-blue',
            'AAAA'  => 'bg-indigo-lt text-indigo',
            'CNAME' => 'bg-cyan-lt text-cyan',
            'MX'    => 'bg-orange-lt text-orange',
            'TXT'   => 'bg-yellow-lt text-yellow',
            'NS'    => 'bg-teal-lt text-teal',
            'SRV'   => 'bg-purple-lt text-purple',
            'CAA'   => 'bg-red-lt text-red',
            'PTR'   => 'bg-pink-lt text-pink',
            'SOA'   => 'bg-secondary-lt text-muted',
            default => 'bg-secondary-lt text-muted',
        };
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * All DNS records across all domains, with domain name and optional
     * domain/type/client filters.
     */
    public static function getAll(
        string $search   = '',
        string $type     = '',
        int    $domainId = 0
    ): array {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $like    = '%' . $search . '%';
            $where[] = '(r.name LIKE ? OR r.value LIKE ? OR d.root_domain LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like]);
        }

        if ($type !== '') {
            $where[]  = 'r.record_type = ?';
            $params[] = $type;
        }

        if ($domainId > 0) {
            $where[]  = 'r.domain_id = ?';
            $params[] = $domainId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT r.*, d.root_domain
            FROM `dns_records` r
            INNER JOIN `domains` d ON d.id = r.domain_id
            {$whereClause}
            ORDER BY d.root_domain ASC, r.record_type ASC, r.name ASC
        ", $params);
    }

    /** All DNS records for a single domain, ordered by type then name. */
    public static function getByDomain(int $domainId): array
    {
        return Database::fetchAll("
            SELECT * FROM `dns_records`
            WHERE domain_id = ?
            ORDER BY record_type ASC, name ASC
        ", [$domainId]);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT r.*, d.root_domain
            FROM `dns_records` r
            INNER JOIN `domains` d ON d.id = r.domain_id
            WHERE r.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `dns_records`
                (domain_id, record_type, name, value, ttl, priority, notes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['domain_id'],
                $data['record_type'],
                $data['name'],
                $data['value'],
                $data['ttl']      ?: 3600,
                $data['priority'] ?: null,
                $data['notes']    ?: null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `dns_records`
             SET domain_id = ?, record_type = ?, name = ?, value = ?,
                 ttl = ?, priority = ?, notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['domain_id'],
                $data['record_type'],
                $data['name'],
                $data['value'],
                $data['ttl']      ?: 3600,
                $data['priority'] ?: null,
                $data['notes']    ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `dns_records` WHERE id = ?", [$id]);
    }

    /** Delete all DNS records for a domain (e.g. when deleting the domain). */
    public static function deleteByDomain(int $domainId): void
    {
        Database::execute("DELETE FROM `dns_records` WHERE domain_id = ?", [$domainId]);
    }
}
