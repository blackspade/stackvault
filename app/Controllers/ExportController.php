<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\ClientModel;
use App\Services\AuthService;
use App\Services\EncryptionService;

class ExportController extends Controller
{
    private const BACKUP_MAGIC  = "SVBAK1\n";
    private const BACKUP_VER    = 1;

    /** Tables included in the vault backup, in FK-safe import order. */
    private const BACKUP_TABLES = [
        'clients',
        'servers',
        'domains',
        'credentials',
        'applications',
        'db_instances',
        'dns_records',
        'email_accounts',
    ];

    // ─── GET /export → moved to Settings > Export & Import tab ──────────────

    public function index(): void
    {
        $this->redirect('/settings?tab=export');
    }

    // ─── GET /clients/{id}/export ─────────────────────────────────────────────

    public function clientProfile(): void
    {
        $id     = (int) $this->request->param('id', 0);
        $client = $id > 0 ? ClientModel::getById($id) : null;

        if (!$client) {
            $this->notFound();
        }

        $cid = (int) $client['id'];

        $domains      = ClientModel::getDomains($cid);
        $servers      = ClientModel::getServers($cid);
        $applications = ClientModel::getApplications($cid);

        $databases = Database::fetchAll("
            SELECT d.id, d.db_name, d.db_type, d.host, d.port, d.username, d.notes,
                   s.label AS server_label
            FROM `db_instances` d
            LEFT JOIN `servers` s ON s.id = d.server_id
            WHERE d.client_id = ?
            ORDER BY d.db_name ASC
        ", [$cid]);

        $dnsRecords = Database::fetchAll("
            SELECT r.record_type, r.name, r.value, r.ttl, r.priority, d.root_domain
            FROM `dns_records` r
            INNER JOIN `domains` d ON d.id = r.domain_id
            WHERE d.client_id = ?
            ORDER BY d.root_domain ASC, r.record_type ASC, r.name ASC
        ", [$cid]);

        $emailAccounts = Database::fetchAll("
            SELECT e.email_address, e.mail_host, e.smtp_port, e.imap_port,
                   e.username, e.webmail_url, e.notes, d.root_domain
            FROM `email_accounts` e
            LEFT JOIN `domains` d ON d.id = e.domain_id
            WHERE e.client_id = ?
            ORDER BY e.email_address ASC
        ", [$cid]);

        AuthService::log(
            (int) $_SESSION['user_id'],
            'client_profile_exported',
            'client',
            $cid,
            "Client profile printed/exported: {$client['name']}",
            $this->request->ip(),
            $this->request->userAgent()
        );

        $this->view('export/client', [
            'title'         => e($client['name']) . ' — Profile Export',
            'client'        => $client,
            'domains'       => $domains,
            'servers'       => $servers,
            'applications'  => $applications,
            'databases'     => $databases,
            'dnsRecords'    => $dnsRecords,
            'emailAccounts' => $emailAccounts,
            'exportedAt'    => date('Y-m-d H:i'),
            'appName'       => \App\Core\Config::get('APP_NAME', 'StackVault'),
        ], 'print');
    }

    // ─── GET /export/download ─────────────────────────────────────────────────

    public function download(): void
    {
        if (!vault_unlocked()) {
            $this->flashError('The vault must be unlocked to download a backup.');
            $this->redirect('/vault/unlock');
        }

        $vaultKey = $_SESSION['vault_key'];

        // Collect all table data
        $tables = [];
        foreach (self::BACKUP_TABLES as $table) {
            $tables[$table] = Database::fetchAll("SELECT * FROM `{$table}` ORDER BY id ASC");
        }

        $payload = json_encode([
            'version'     => self::BACKUP_VER,
            'exported_at' => date('Y-m-d H:i:s'),
            'tables'      => $tables,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $encrypted = EncryptionService::encrypt($payload, $vaultKey);
        $file      = self::BACKUP_MAGIC . $encrypted;
        $filename  = 'stackvault-backup-' . date('Y-m-d') . '.svbak';

        AuthService::log(
            (int) $_SESSION['user_id'],
            'vault_backup_exported',
            'system', 0,
            'Vault backup downloaded.',
            $this->request->ip(), $this->request->userAgent()
        );

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Content-Length: ' . strlen($file));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        echo $file;
        exit;
    }

    // ─── GET /export/import ───────────────────────────────────────────────────

    public function showImport(): void
    {
        $this->view('export/import', [
            'title'       => 'Import Backup',
            'breadcrumbs' => [
                ['label' => 'Export & Import', 'url' => '/export'],
                ['label' => 'Import Backup'],
            ],
            'errors' => get_flash('error'),
        ]);
    }

    // ─── POST /export/import ──────────────────────────────────────────────────

    public function uploadImport(): void
    {
        $this->validateCsrf();

        if (!vault_unlocked()) {
            $this->flashError('The vault must be unlocked to import a backup.');
            $this->redirect('/vault/unlock');
        }

        $file = $_FILES['backup'] ?? null;

        if (!$file || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            flash('error', 'Please choose a .svbak backup file to upload.');
            $this->redirect('/export/import');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $msgs = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload size limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form size limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            ];
            flash('error', $msgs[$file['error']] ?? 'Upload failed (error code ' . $file['error'] . ').');
            $this->redirect('/export/import');
        }

        $raw = file_get_contents($file['tmp_name']);

        if ($raw === false || !str_starts_with($raw, self::BACKUP_MAGIC)) {
            flash('error', 'Invalid file. This does not appear to be a StackVault backup.');
            $this->redirect('/export/import');
        }

        $encrypted = substr($raw, strlen(self::BACKUP_MAGIC));
        $json      = EncryptionService::decrypt($encrypted, $_SESSION['vault_key']);

        if ($json === false) {
            flash('error', 'Decryption failed. The file may be corrupt or was created with a different vault key.');
            $this->redirect('/export/import');
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['tables']) || !is_array($data['tables'])) {
            flash('error', 'Backup file structure is invalid.');
            $this->redirect('/export/import');
        }

        $_SESSION['svbak_import'] = $data;

        // Build preview counts
        $preview = [];
        foreach (self::BACKUP_TABLES as $table) {
            $incoming = count($data['tables'][$table] ?? []);
            $existing = (int) (Database::fetchOne("SELECT COUNT(*) AS n FROM `{$table}`")['n'] ?? 0);
            $preview[$table] = ['incoming' => $incoming, 'existing' => $existing];
        }

        $this->view('export/import_preview', [
            'title'       => 'Import — Preview',
            'breadcrumbs' => [
                ['label' => 'Export & Import', 'url' => '/export'],
                ['label' => 'Import Backup',   'url' => '/export/import'],
                ['label' => 'Preview'],
            ],
            'exportedAt' => $data['exported_at'] ?? 'unknown',
            'version'    => (int) ($data['version'] ?? 1),
            'preview'    => $preview,
        ]);
    }

    // ─── POST /export/import/confirm ──────────────────────────────────────────

    public function confirmImport(): void
    {
        $this->validateCsrf();

        if (!vault_unlocked()) {
            $this->flashError('The vault must be unlocked to import a backup.');
            $this->redirect('/vault/unlock');
        }

        $data = $_SESSION['svbak_import'] ?? null;
        unset($_SESSION['svbak_import']);

        if (!$data || !isset($data['tables'])) {
            $this->flashError('Import session expired. Please upload the backup again.');
            $this->redirect('/export/import');
        }

        $imported = 0;
        $skipped  = 0;

        Database::beginTransaction();

        try {
            Database::execute('SET FOREIGN_KEY_CHECKS = 0');

            foreach (self::BACKUP_TABLES as $table) {
                $rows = $data['tables'][$table] ?? [];

                foreach ($rows as $row) {
                    if (empty($row) || !is_array($row)) {
                        continue;
                    }

                    $cols         = array_keys($row);
                    $colList      = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
                    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
                    $values       = array_values($row);

                    $affected = Database::execute(
                        "INSERT IGNORE INTO `{$table}` ({$colList}) VALUES ({$placeholders})",
                        $values
                    );

                    if ($affected > 0) {
                        $imported++;
                    } else {
                        $skipped++;
                    }
                }
            }

            Database::execute('SET FOREIGN_KEY_CHECKS = 1');
            Database::commit();

        } catch (\Throwable $e) {
            Database::rollback();
            Database::execute('SET FOREIGN_KEY_CHECKS = 1');
            $this->flashError('Import failed: ' . $e->getMessage());
            $this->redirect('/export/import');
        }

        AuthService::log(
            (int) $_SESSION['user_id'],
            'vault_backup_imported',
            'system', 0,
            "Vault backup imported: {$imported} records inserted, {$skipped} skipped.",
            $this->request->ip(), $this->request->userAgent()
        );

        $this->flashSuccess(
            "Import complete: {$imported} record(s) imported, {$skipped} already existed and were skipped."
        );

        $this->redirect('/export');
    }
}
