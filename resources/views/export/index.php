<?php
/**
 * Export & Import hub
 */
?>

<div class="row g-4">

    <!-- ── Vault Backup ────────────────────────────────────────────────────── -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="bg-blue-lt p-2 rounded me-3">
                        <i class="ti ti-database-export fs-2 text-blue"></i>
                    </span>
                    <div>
                        <h3 class="card-title mb-0">Vault Backup</h3>
                        <div class="text-muted small">Export all data as encrypted backup</div>
                    </div>
                </div>
                <p class="text-muted">
                    Download a full encrypted backup of all clients, domains, servers, credentials,
                    applications, databases, DNS records, and email accounts.
                    The backup is encrypted with your vault key using AES-256-GCM and can only
                    be decrypted when the vault is unlocked.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <?php if (vault_unlocked()): ?>
                    <a href="<?= url('/export/download') ?>" class="btn btn-primary">
                        <i class="ti ti-download me-1"></i>Download Backup (.svbak)
                    </a>
                    <?php else: ?>
                    <a href="<?= url('/vault/unlock') ?>" class="btn btn-warning">
                        <i class="ti ti-lock me-1"></i>Unlock Vault to Export
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-muted small">
                <i class="ti ti-info-circle me-1"></i>
                File name: <code>stackvault-backup-<?= date('Y-m-d') ?>.svbak</code>
            </div>
        </div>
    </div>

    <!-- ── Import Backup ───────────────────────────────────────────────────── -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="bg-green-lt p-2 rounded me-3">
                        <i class="ti ti-database-import fs-2 text-green"></i>
                    </span>
                    <div>
                        <h3 class="card-title mb-0">Import Backup</h3>
                        <div class="text-muted small">Restore data from a .svbak file</div>
                    </div>
                </div>
                <p class="text-muted">
                    Upload a <code>.svbak</code> backup file to restore data. Existing records
                    (matched by ID) will be skipped — the import never overwrites data already in
                    the database. The vault must be unlocked.
                </p>
                <div class="d-flex gap-2 mt-3">
                    <?php if (vault_unlocked()): ?>
                    <a href="<?= url('/export/import') ?>" class="btn btn-success">
                        <i class="ti ti-upload me-1"></i>Import Backup
                    </a>
                    <?php else: ?>
                    <a href="<?= url('/vault/unlock') ?>" class="btn btn-warning">
                        <i class="ti ti-lock me-1"></i>Unlock Vault to Import
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-muted small">
                <i class="ti ti-shield-check me-1"></i>
                Import uses INSERT IGNORE — duplicate records are safely skipped.
            </div>
        </div>
    </div>

    <!-- ── Client Profile Export ───────────────────────────────────────────── -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <span class="bg-orange-lt p-2 rounded me-3">
                        <i class="ti ti-file-text fs-2 text-orange"></i>
                    </span>
                    <div>
                        <h3 class="card-title mb-0">Client Profile Export</h3>
                        <div class="text-muted small">Print-ready client profile for any client</div>
                    </div>
                </div>
                <p class="text-muted mb-2">
                    Generate a print-friendly profile for a specific client — includes their domains,
                    servers, applications, databases, DNS records, and email accounts.
                    Open a client and click <strong>Export Profile</strong>, or select one below.
                </p>
                <?php
                $clients = \App\Models\ClientModel::getAll();
                ?>
                <?php if (!empty($clients)): ?>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php foreach ($clients as $c): ?>
                    <a href="<?= url('/clients/' . $c['id'] . '/export') ?>"
                       target="_blank"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-printer me-1"></i><?= e($c['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted small fst-italic mb-0">No clients yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /.row -->
