<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SearchModel
{
    private const LIMIT = 10;

    /**
     * Run global search across all entity types.
     * Returns an ordered array of sections, each with label, icon, and result rows.
     * Sections with no results are excluded.
     *
     * @return array<string, array{label:string, icon:string, results:array}>
     */
    public static function search(string $q): array
    {
        // Ensure lazily-migrated tables exist before querying them
        BookmarkSetModel::migrate();
        HostMachineModel::migrate();
        ClientFileModel::migrate();

        $like = '%' . $q . '%';
        $lim  = self::LIMIT;

        $sections = [
            'clients'        => ['label' => 'Clients',        'icon' => 'ti-users',       'results' => self::clients($like, $lim)],
            'domains'        => ['label' => 'Domains',        'icon' => 'ti-world',       'results' => self::domains($like, $lim)],
            'servers'        => ['label' => 'Servers',        'icon' => 'ti-server',      'results' => self::servers($like, $lim)],
            'credentials'    => ['label' => 'Credentials',    'icon' => 'ti-lock',        'results' => self::credentials($like, $lim)],
            'applications'   => ['label' => 'Applications',   'icon' => 'ti-apps',        'results' => self::applications($like, $lim)],
            'databases'      => ['label' => 'Databases',      'icon' => 'ti-database',    'results' => self::databases($like, $lim)],
            'dns_records'    => ['label' => 'DNS Records',    'icon' => 'ti-sitemap',     'results' => self::dnsRecords($like, $lim)],
            'email_accounts' => ['label' => 'Email Accounts', 'icon' => 'ti-mail',        'results' => self::emailAccounts($like, $lim)],
            'bookmarks'      => ['label' => 'Bookmarks',      'icon' => 'ti-bookmarks',   'results' => self::bookmarks($like, $lim)],
            'host_machines'  => ['label' => 'Host Files',     'icon' => 'ti-file-code',   'results' => self::hostMachines($like, $lim)],
            'client_files'   => ['label' => 'Files',          'icon' => 'ti-archive',     'results' => self::clientFiles($like, $lim)],
        ];

        // Remove sections with no results
        return array_filter($sections, fn($s) => !empty($s['results']));
    }

    // ─── Per-entity queries ───────────────────────────────────────────────────

    private static function clients(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT id, name, contact_email, contact_phone, contact_name
            FROM `clients`
            WHERE name LIKE ? OR contact_email LIKE ? OR contact_phone LIKE ?
               OR contact_name LIKE ? OR website LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['name'],
            'subtitle' => implode(' · ', array_filter([$r['contact_name'], $r['contact_email'], $r['contact_phone']])),
            'url'      => '/clients/' . $r['id'],
        ], $rows);
    }

    private static function domains(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT id, root_domain, registrar
            FROM `domains`
            WHERE root_domain LIKE ? OR registrar LIKE ? OR notes LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['root_domain'],
            'subtitle' => $r['registrar'] ?? '',
            'url'      => '/domains/' . $r['id'],
        ], $rows);
    }

    private static function servers(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT id, label, ip_address, hostname, provider
            FROM `servers`
            WHERE label LIKE ? OR ip_address LIKE ? OR hostname LIKE ? OR provider LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['label'],
            'subtitle' => implode(' · ', array_filter([$r['ip_address'], $r['hostname'], $r['provider']])),
            'url'      => '/servers/' . $r['id'],
        ], $rows);
    }

    private static function credentials(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT c.id, c.label, c.username, c.credential_type,
                   cl.name AS client_name
            FROM `credentials` c
            LEFT JOIN `clients` cl ON cl.id = c.client_id
            WHERE c.label LIKE ? OR c.username LIKE ?
            LIMIT ?
        ", [$like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['label'],
            'subtitle' => implode(' · ', array_filter([$r['username'], $r['client_name']])),
            'url'      => '/credentials/' . $r['id'],
        ], $rows);
    }

    private static function applications(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT a.id, a.app_name, a.version, a.stack_type,
                   c.name AS client_name
            FROM `applications` a
            LEFT JOIN `clients` c ON c.id = a.client_id
            WHERE a.app_name LIKE ? OR a.version LIKE ? OR a.install_path LIKE ? OR a.stack_type LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['app_name'],
            'subtitle' => implode(' · ', array_filter([$r['version'], $r['stack_type'], $r['client_name']])),
            'url'      => '/applications/' . $r['id'],
        ], $rows);
    }

    private static function databases(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT d.id, d.db_name, d.host, d.db_type, d.username,
                   c.name AS client_name
            FROM `db_instances` d
            LEFT JOIN `clients` c ON c.id = d.client_id
            WHERE d.db_name LIKE ? OR d.host LIKE ? OR d.username LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['db_name'],
            'subtitle' => implode(' · ', array_filter([$r['db_type'], $r['host'], $r['client_name']])),
            'url'      => '/databases/' . $r['id'],
        ], $rows);
    }

    private static function dnsRecords(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT dr.id, dr.name, dr.record_type, dr.value,
                   d.root_domain
            FROM `dns_records` dr
            LEFT JOIN `domains` d ON d.id = dr.domain_id
            WHERE dr.name LIKE ? OR dr.value LIKE ? OR d.root_domain LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['name'] . ' (' . $r['record_type'] . ')',
            'subtitle' => implode(' · ', array_filter([
                $r['root_domain'],
                mb_strlen($r['value']) > 60 ? mb_substr($r['value'], 0, 60) . '…' : $r['value'],
            ])),
            'url'      => '/dns/' . $r['id'],
        ], $rows);
    }

    private static function emailAccounts(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT e.id, e.email_address, e.mail_host, e.username,
                   c.name AS client_name
            FROM `email_accounts` e
            LEFT JOIN `clients` c ON c.id = e.client_id
            WHERE e.email_address LIKE ? OR e.mail_host LIKE ? OR e.username LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['email_address'],
            'subtitle' => implode(' · ', array_filter([$r['mail_host'], $r['client_name']])),
            'url'      => '/email/' . $r['id'],
        ], $rows);
    }

    private static function bookmarks(string $like, int $lim): array
    {
        // Search individual bookmarks by title/URL
        $bookmarkRows = Database::fetchAll("
            SELECT b.id, b.title, b.url, b.set_id,
                   bs.name AS set_name
            FROM `bookmarks` b
            LEFT JOIN `bookmark_sets` bs ON bs.id = b.set_id
            WHERE b.title LIKE ? OR b.url LIKE ?
            LIMIT ?
        ", [$like, $like, $lim]);

        // Also search bookmark sets by name/description
        $setRows = Database::fetchAll("
            SELECT id, name, browser, description
            FROM `bookmark_sets`
            WHERE name LIKE ? OR description LIKE ?
            LIMIT ?
        ", [$like, $like, $lim]);

        $results = [];

        foreach ($bookmarkRows as $r) {
            $results[] = [
                'title'    => $r['title'],
                'subtitle' => $r['set_name'] . ' · ' . $r['url'],
                'url'      => '/bookmarks/' . $r['set_id'],
            ];
        }

        foreach ($setRows as $r) {
            $results[] = [
                'title'    => $r['name'] . ' (set)',
                'subtitle' => ($r['browser'] ?? '') . ($r['description'] ? ' · ' . $r['description'] : ''),
                'url'      => '/bookmarks/' . $r['id'],
            ];
        }

        return array_slice($results, 0, $lim);
    }

    private static function hostMachines(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT h.id, h.name, h.os, h.description,
                   c.name AS client_name
            FROM `host_machines` h
            LEFT JOIN `clients` c ON c.id = h.client_id
            WHERE h.name LIKE ? OR h.description LIKE ?
            LIMIT ?
        ", [$like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['name'],
            'subtitle' => implode(' · ', array_filter([
                \App\Models\HostMachineModel::OS_TYPES[$r['os']] ?? $r['os'],
                $r['client_name'],
            ])),
            'url'      => '/hosts/' . $r['id'],
        ], $rows);
    }

    private static function clientFiles(string $like, int $lim): array
    {
        $rows = Database::fetchAll("
            SELECT cf.id, cf.client_id, cf.filename, cf.extension, cf.description,
                   c.name AS client_name
            FROM `client_files` cf
            LEFT JOIN `clients` c ON c.id = cf.client_id
            WHERE cf.filename LIKE ? OR cf.description LIKE ? OR c.name LIKE ?
            LIMIT ?
        ", [$like, $like, $like, $lim]);

        return array_map(fn($r) => [
            'title'    => $r['filename'],
            'subtitle' => implode(' · ', array_filter([$r['client_name'], $r['description']])),
            'url'      => '/files?search=' . urlencode($r['filename']) . '&client_id=' . ($r['client_id'] ?? ''),
        ], $rows);
    }
}
