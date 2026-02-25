<?php
declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\VaultController;
use App\Controllers\ClientController;
use App\Controllers\DomainController;
use App\Controllers\ServerController;
use App\Controllers\CredentialController;
use App\Controllers\ApplicationController;
use App\Controllers\AppCatalogController;
use App\Controllers\DatabaseController;
use App\Controllers\DnsRecordController;
use App\Controllers\EmailAccountController;
use App\Controllers\LogsController;
use App\Controllers\BookmarkController;
use App\Controllers\HostMachineController;
use App\Controllers\FileManagerController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\DnsTemplateController;
use App\Controllers\DnsApplyTemplateController;
use App\Controllers\ExportController;
use App\Controllers\ReminderController;
use App\Controllers\TerminalController;

// ─── Public routes (no auth required) ────────────────────────────────────────

Router::get('/login',       [AuthController::class, 'showLogin']);
Router::post('/login',      [AuthController::class, 'login']);
Router::get('/login/2fa',   [AuthController::class, 'show2fa']);
Router::post('/login/2fa',  [AuthController::class, 'verify2fa']);
Router::get('/logout',      [AuthController::class, 'logout']);

// ─── Root redirect ────────────────────────────────────────────────────────────
Router::get('/', [DashboardController::class, 'redirectHome']);

// ─── Protected routes (require login) ────────────────────────────────────────

Router::middleware(['auth'])->group(function () {

    // Dashboard
    Router::get('/dashboard', [DashboardController::class, 'index']);

    // Vault unlock / lock
    Router::get('/vault/unlock',  [VaultController::class, 'showUnlock']);
    Router::post('/vault/unlock', [VaultController::class, 'unlock']);
    Router::post('/vault/lock',   [VaultController::class, 'lock']);

    // ── Clients (Stage 5) ────────────────────────────────────────────────────
    // NOTE: /clients/create registered before /clients/{id}
    Router::get('/clients',                [ClientController::class, 'index']);
    Router::get('/clients/create',         [ClientController::class, 'showCreate']);
    Router::post('/clients/store',         [ClientController::class, 'store']);
    Router::post('/clients/{id}/docs/save', [ClientController::class, 'saveDocs']);
    Router::get('/clients/{id}',           [ClientController::class, 'show']);
    Router::get('/clients/{id}/edit',      [ClientController::class, 'showEdit']);
    Router::post('/clients/{id}/update',   [ClientController::class, 'update']);
    Router::post('/clients/{id}/delete',   [ClientController::class, 'delete']);
    Router::get('/clients/{id}/export',    [ExportController::class, 'clientProfile']);

    // ── Domains (Stage 6) ────────────────────────────────────────────────────
    Router::get('/domains',              [DomainController::class, 'index']);
    Router::get('/domains/create',       [DomainController::class, 'showCreate']);
    Router::post('/domains/store',       [DomainController::class, 'store']);
    Router::get('/domains/{id}',         [DomainController::class, 'show']);
    Router::get('/domains/{id}/edit',    [DomainController::class, 'showEdit']);
    Router::post('/domains/{id}/update', [DomainController::class, 'update']);
    Router::post('/domains/{id}/delete', [DomainController::class, 'delete']);

    // ── Servers (Stage 7) ────────────────────────────────────────────────────
    Router::get('/servers',              [ServerController::class, 'index']);
    Router::get('/servers/create',       [ServerController::class, 'showCreate']);
    Router::post('/servers/store',       [ServerController::class, 'store']);
    Router::get('/servers/{id}',         [ServerController::class, 'show']);
    Router::get('/servers/{id}/edit',    [ServerController::class, 'showEdit']);
    Router::post('/servers/{id}/update', [ServerController::class, 'update']);
    Router::post('/servers/{id}/delete', [ServerController::class, 'delete']);

    // ── Credentials (Stage 8) ────────────────────────────────────────────────
    // NOTE: /credentials/create must be registered before /credentials/{id}
    Router::get('/credentials',                 [CredentialController::class, 'index']);
    Router::get('/credentials/create',          [CredentialController::class, 'showCreate']);
    Router::post('/credentials/store',          [CredentialController::class, 'store']);
    Router::get('/credentials/{id}',            [CredentialController::class, 'show']);
    Router::get('/credentials/{id}/edit',       [CredentialController::class, 'showEdit']);
    Router::post('/credentials/{id}/update',    [CredentialController::class, 'update']);
    Router::post('/credentials/{id}/delete',    [CredentialController::class, 'delete']);
    Router::post('/credentials/{id}/reveal',    [CredentialController::class, 'reveal']);

    // ── Applications (Stage 9) ───────────────────────────────────────────────
    // NOTE: /applications/create must be registered before /applications/{id}
    Router::get('/applications',                 [ApplicationController::class, 'index']);
    Router::get('/applications/create',          [ApplicationController::class, 'showCreate']);
    Router::post('/applications/store',          [ApplicationController::class, 'store']);
    Router::get('/applications/{id}',            [ApplicationController::class, 'show']);
    Router::get('/applications/{id}/edit',       [ApplicationController::class, 'showEdit']);
    Router::post('/applications/{id}/update',    [ApplicationController::class, 'update']);
    Router::post('/applications/{id}/delete',    [ApplicationController::class, 'delete']);

    // ── App Catalog (Stage 9 — accessed from within Applications) ────────────
    // NOTE: /app-catalog/{id} registered after /app-catalog (index)
    Router::get('/app-catalog',                  [AppCatalogController::class, 'index']);
    Router::get('/app-catalog/{id}',             [AppCatalogController::class, 'show']);
    Router::get('/app-icon/{name}',              [AppCatalogController::class, 'icon']);

    // ── Databases (Stage 10) ─────────────────────────────────────────────────
    // NOTE: /databases/create must be registered before /databases/{id}
    Router::get('/databases',                [DatabaseController::class, 'index']);
    Router::get('/databases/create',         [DatabaseController::class, 'showCreate']);
    Router::post('/databases/store',         [DatabaseController::class, 'store']);
    Router::get('/databases/{id}',           [DatabaseController::class, 'show']);
    Router::get('/databases/{id}/edit',      [DatabaseController::class, 'showEdit']);
    Router::post('/databases/{id}/update',   [DatabaseController::class, 'update']);
    Router::post('/databases/{id}/delete',   [DatabaseController::class, 'delete']);
    Router::post('/databases/{id}/reveal',   [DatabaseController::class, 'reveal']);

    // ── DNS Records (Stage 11) + DNS Templates ───────────────────────────────
    // NOTE: all literal sub-paths registered before /dns/{id} wildcard
    Router::get('/dns',              [DnsRecordController::class, 'index']);
    Router::get('/dns/create',       [DnsRecordController::class, 'showCreate']);
    Router::post('/dns/store',       [DnsRecordController::class, 'store']);

    // DNS Templates — must come before /dns/{id} (avoids wildcard collision)
    Router::get('/dns/templates',                  [DnsTemplateController::class, 'index']);
    Router::get('/dns/templates/create',           [DnsTemplateController::class, 'showCreate']);
    Router::post('/dns/templates/store',           [DnsTemplateController::class, 'store']);
    Router::get('/dns/templates/{id}',             [DnsTemplateController::class, 'show']);
    Router::get('/dns/templates/{id}/edit',        [DnsTemplateController::class, 'showEdit']);
    Router::post('/dns/templates/{id}/update',     [DnsTemplateController::class, 'update']);
    Router::post('/dns/templates/{id}/delete',     [DnsTemplateController::class, 'delete']);

    // Apply template flow
    Router::get('/dns/apply-template',             [DnsApplyTemplateController::class, 'showForm']);
    Router::post('/dns/apply-template/preview',    [DnsApplyTemplateController::class, 'preview']);
    Router::post('/dns/apply-template/confirm',    [DnsApplyTemplateController::class, 'confirm']);

    // DNS record CRUD — wildcard routes last
    Router::get('/dns/{id}',         [DnsRecordController::class, 'show']);
    Router::get('/dns/{id}/edit',    [DnsRecordController::class, 'showEdit']);
    Router::post('/dns/{id}/update', [DnsRecordController::class, 'update']);
    Router::post('/dns/{id}/delete', [DnsRecordController::class, 'delete']);

    // ── Email Accounts (Stage 12) ────────────────────────────────────────────
    // NOTE: /email/create must be registered before /email/{id}
    Router::get('/email',               [EmailAccountController::class, 'index']);
    Router::get('/email/create',        [EmailAccountController::class, 'showCreate']);
    Router::post('/email/store',        [EmailAccountController::class, 'store']);
    Router::get('/email/{id}',          [EmailAccountController::class, 'show']);
    Router::get('/email/{id}/edit',     [EmailAccountController::class, 'showEdit']);
    Router::post('/email/{id}/update',  [EmailAccountController::class, 'update']);
    Router::post('/email/{id}/delete',  [EmailAccountController::class, 'delete']);
    Router::post('/email/{id}/reveal',  [EmailAccountController::class, 'reveal']);

    // ── Terminal (Stage 19) ──────────────────────────────────────────────────
    // NOTE: /terminal/client-data registered before /terminal (no wildcard, but explicit order)
    Router::get('/terminal/client-data', [TerminalController::class, 'clientData']);
    Router::get('/terminal',             [TerminalController::class, 'index']);

    // ── Reminders (Stage 18) ─────────────────────────────────────────────────
    // NOTE: /reminders/create registered before /reminders/{id} wildcard
    Router::get('/reminders',                [ReminderController::class, 'index']);
    Router::get('/reminders/create',         [ReminderController::class, 'showCreate']);
    Router::post('/reminders/store',         [ReminderController::class, 'store']);
    Router::get('/reminders/{id}/edit',      [ReminderController::class, 'showEdit']);
    Router::post('/reminders/{id}/update',   [ReminderController::class, 'update']);
    Router::post('/reminders/{id}/done',     [ReminderController::class, 'markDone']);
    Router::post('/reminders/{id}/undone',   [ReminderController::class, 'markUndone']);
    Router::post('/reminders/{id}/delete',   [ReminderController::class, 'delete']);

    // ── Activity Log (Stage 13) — redirects to Settings > Logs tab ──────────
    Router::get('/logs', [LogsController::class, 'index']);

    // ── Bookmarks ────────────────────────────────────────────────────────────
    // NOTE: literal sub-paths registered before {id} wildcard
    Router::get('/bookmarks',                                     [BookmarkController::class, 'index']);
    Router::get('/bookmarks/create',                              [BookmarkController::class, 'showCreate']);
    Router::post('/bookmarks/store',                              [BookmarkController::class, 'store']);
    Router::get('/bookmarks/{id}',                                [BookmarkController::class, 'show']);
    Router::get('/bookmarks/{id}/edit',                           [BookmarkController::class, 'showEdit']);
    Router::post('/bookmarks/{id}/update',                        [BookmarkController::class, 'update']);
    Router::post('/bookmarks/{id}/delete',                        [BookmarkController::class, 'delete']);
    Router::get('/bookmarks/{id}/export',                         [BookmarkController::class, 'export']);
    Router::post('/bookmarks/{id}/import',                        [BookmarkController::class, 'importFile']);
    Router::post('/bookmarks/{id}/folders/store',                 [BookmarkController::class, 'addFolder']);
    Router::post('/bookmarks/{id}/folders/{fid}/delete',          [BookmarkController::class, 'deleteFolder']);
    Router::post('/bookmarks/{id}/bookmarks/store',               [BookmarkController::class, 'addBookmark']);
    Router::post('/bookmarks/{id}/bookmarks/{bid}/delete',        [BookmarkController::class, 'deleteBookmark']);

    // ── Host Files ───────────────────────────────────────────────────────────
    // NOTE: /hosts/create registered before /hosts/{id}
    Router::get('/hosts',              [HostMachineController::class, 'index']);
    Router::get('/hosts/create',       [HostMachineController::class, 'showCreate']);
    Router::post('/hosts/store',       [HostMachineController::class, 'store']);
    Router::get('/hosts/{id}',         [HostMachineController::class, 'show']);
    Router::get('/hosts/{id}/edit',    [HostMachineController::class, 'showEdit']);
    Router::post('/hosts/{id}/update', [HostMachineController::class, 'update']);
    Router::post('/hosts/{id}/delete', [HostMachineController::class, 'delete']);

    // ── Global Search (Stage 14) ─────────────────────────────────────────────
    Router::get('/search', [SearchController::class, 'index']);

    // ── File Manager ─────────────────────────────────────────────────────────
    // NOTE: /files/upload registered before /files/{id} wildcard
    Router::get('/files',                  [FileManagerController::class, 'index']);
    Router::post('/files/upload',          [FileManagerController::class, 'upload']);
    Router::post('/files/{id}/update',     [FileManagerController::class, 'update']);
    Router::get('/files/{id}/download',    [FileManagerController::class, 'download']);
    Router::post('/files/{id}/delete',     [FileManagerController::class, 'delete']);

    // ── Export / Import (Stage 17) ───────────────────────────────────────────
    // NOTE: /export index redirects to Settings > Export tab; sub-routes stay
    Router::get('/export',                      [ExportController::class, 'index']);
    Router::get('/export/download',             [ExportController::class, 'download']);
    Router::get('/export/import',               [ExportController::class, 'showImport']);
    Router::post('/export/import',              [ExportController::class, 'uploadImport']);
    Router::post('/export/import/confirm',      [ExportController::class, 'confirmImport']);

    // ── Settings (Stage 15) ──────────────────────────────────────────────────
    // NOTE: literal sub-paths registered before {id} wildcard
    Router::post('/settings/logs/clear',                    [SettingsController::class, 'clearLogs']);
    Router::post('/settings/presets/add',                   [SettingsController::class, 'addPreset']);
    Router::post('/settings/presets/{id}/delete',           [SettingsController::class, 'deletePreset']);
    Router::get('/settings',                                [SettingsController::class, 'index']);
    Router::post('/settings/profile',                       [SettingsController::class, 'saveProfile']);
    Router::post('/settings/vault-password',                [SettingsController::class, 'saveVaultPassword']);
    Router::post('/settings/whitelist/toggle',              [SettingsController::class, 'toggleWhitelist']);
    Router::post('/settings/whitelist/add',                 [SettingsController::class, 'addWhitelistIp']);
    Router::post('/settings/whitelist/{id}/delete',         [SettingsController::class, 'deleteWhitelistIp']);
    Router::post('/settings/2fa/setup',                     [SettingsController::class, 'setup2fa']);
    Router::post('/settings/2fa/confirm',                   [SettingsController::class, 'confirm2fa']);
    Router::post('/settings/2fa/disable',                   [SettingsController::class, 'disable2fa']);
    Router::post('/settings/users/create',                  [SettingsController::class, 'createUser']);
    Router::post('/settings/users/{id}/delete',             [SettingsController::class, 'deleteUser']);
});
