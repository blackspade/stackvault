<?php
/**
 * Settings — tabbed view
 *
 * Vars: $activeTab, $currentUser, $appNameValue,
 *       $whitelistEnabled, $whitelistIps, $currentIp,
 *       $totpEnabled, $totpSetupSecret, $totpSetupUri,
 *       $logFilters, $logPage, $logTotal, $logTotalPages, $logs,
 *       $logPerPage, $entityTypes, $actionGroups, $entityLabels, $urlPrefixes
 */
?>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">

            <li class="nav-item" role="presentation">
                <a href="#tab-general"
                   class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-general"
                   role="tab" aria-selected="<?= $activeTab === 'general' ? 'true' : 'false' ?>">
                    <i class="ti ti-settings-2 me-1"></i>General
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a href="#tab-profile"
                   class="nav-link <?= $activeTab === 'profile' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-profile"
                   role="tab" aria-selected="<?= $activeTab === 'profile' ? 'true' : 'false' ?>">
                    <i class="ti ti-user me-1"></i>Profile
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a href="#tab-vault"
                   class="nav-link <?= $activeTab === 'vault' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-vault"
                   role="tab" aria-selected="<?= $activeTab === 'vault' ? 'true' : 'false' ?>">
                    <i class="ti ti-lock me-1"></i>Vault Password
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a href="#tab-whitelist"
                   class="nav-link <?= $activeTab === 'whitelist' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-whitelist"
                   role="tab" aria-selected="<?= $activeTab === 'whitelist' ? 'true' : 'false' ?>">
                    <i class="ti ti-shield me-1"></i>Login Whitelist
                    <?php if ($whitelistEnabled): ?>
                    <span class="badge bg-success ms-1" style="font-size: 10px; padding: 2px 5px;">ON</span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a href="#tab-2fa"
                   class="nav-link <?= $activeTab === '2fa' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-2fa"
                   role="tab" aria-selected="<?= $activeTab === '2fa' ? 'true' : 'false' ?>">
                    <i class="ti ti-device-mobile-code me-1"></i>Two-Factor Auth
                    <?php if ($totpEnabled ?? false): ?>
                    <span class="badge bg-success ms-1" style="font-size: 10px; padding: 2px 5px;">ON</span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a href="#tab-logs"
                   class="nav-link <?= $activeTab === 'logs' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-logs"
                   role="tab" aria-selected="<?= $activeTab === 'logs' ? 'true' : 'false' ?>">
                    <i class="ti ti-activity me-1"></i>Activity Log
                </a>
            </li>

            <li class="nav-item" role="presentation">
                <a href="#tab-export"
                   class="nav-link <?= $activeTab === 'export' ? 'active' : '' ?>"
                   data-bs-toggle="tab" data-bs-target="#tab-export"
                   role="tab" aria-selected="<?= $activeTab === 'export' ? 'true' : 'false' ?>">
                    <i class="ti ti-database-export me-1"></i>Export &amp; Import
                </a>
            </li>

        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            <!-- ── General (Preset Manager) ─────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === 'general' ? 'show active' : '' ?>"
                 id="tab-general" role="tabpanel">

                <h3 class="card-title mb-1">Dropdown Presets</h3>
                <p class="text-muted mb-4" style="font-size:13px;">
                    Manage the suggestion lists and type options shown on Server, Application, and Reminder forms.
                    Built-in defaults cannot be removed.
                </p>

                <?php foreach ($presetGroupLabels as $group => $groupLabel): ?>
                <?php $presets = $presetGroups[$group] ?? []; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title mb-0"><?= e($groupLabel) ?></h4>
                    </div>
                    <div class="card-body pb-2">

                        <!-- Preset list -->
                        <div class="d-flex flex-wrap gap-2 mb-3" id="preset-list-<?= e($group) ?>">
                            <?php foreach ($presets as $preset): ?>
                            <div class="d-flex align-items-center gap-1" data-preset-id="<?= (int)$preset['id'] ?>">
                                <span class="badge bg-secondary-lt text-secondary py-1 px-2"
                                      style="font-size:12px; font-weight:400">
                                    <?= e($preset['value']) ?>
                                    <?php if ($preset['is_default']): ?>
                                    <span class="ms-1 text-muted" style="font-size:10px; opacity:.6">default</span>
                                    <?php endif; ?>
                                </span>
                                <?php if (!$preset['is_default']): ?>
                                <button type="button" class="btn btn-xs btn-ghost-danger p-0"
                                        style="line-height:1; width:18px; height:18px; font-size:11px"
                                        title="Remove"
                                        data-url="<?= url('/settings/presets/' . $preset['id'] . '/delete') ?>"
                                        onclick="svDeletePreset(this)">
                                    <i class="ti ti-x"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($presets)): ?>
                            <span class="text-muted small fst-italic">No presets yet.</span>
                            <?php endif; ?>
                        </div>

                        <!-- Add form -->
                        <form method="post" action="<?= url('/settings/presets/add') ?>"
                              class="d-flex gap-2 align-items-center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="group" value="<?= e($group) ?>">
                            <input type="text" name="value"
                                   class="form-control form-control-sm"
                                   style="max-width:260px"
                                   placeholder="Add new preset…"
                                   maxlength="255"
                                   required>
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-plus me-1"></i>Add
                            </button>
                        </form>

                    </div>
                </div>

                <?php endforeach; ?>

            </div>

            <!-- ── Profile ──────────────────────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === 'profile' ? 'show active' : '' ?>"
                 id="tab-profile" role="tabpanel">

                <h3 class="card-title mb-4">Profile</h3>

                <form method="post" action="<?= url('/settings/profile') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">

                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label required" for="username">Username</label>
                                <input type="text" id="username" name="username"
                                       class="form-control"
                                       value="<?= e($currentUser['username'] ?? '') ?>"
                                       maxlength="32"
                                       autocomplete="username"
                                       required>
                                <div class="form-hint">Letters, numbers, underscores, hyphens, and dots only.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required" for="email">Email</label>
                                <input type="email" id="email" name="email"
                                       class="form-control"
                                       value="<?= e($currentUser['email'] ?? '') ?>"
                                       maxlength="255"
                                       autocomplete="email"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label" for="new_password">New Login Password</label>
                                <input type="password" id="new_password" name="new_password"
                                       class="form-control"
                                       placeholder="Leave blank to keep current"
                                       autocomplete="new-password">
                                <div class="form-hint">Minimum 8 characters. Leave blank to keep current password.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                       class="form-control"
                                       placeholder="Repeat new password"
                                       autocomplete="new-password">
                            </div>
                        </div>

                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Profile
                    </button>
                </form>

            </div>

            <!-- ── Vault Password ────────────────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === 'vault' ? 'show active' : '' ?>"
                 id="tab-vault" role="tabpanel">

                <h3 class="card-title mb-4">Change Vault Password</h3>

                <p class="text-muted">The vault password encrypts and protects access to credentials, database passwords, and API keys stored in StackVault.</p>

                <form method="post" action="<?= url('/settings/vault-password') ?>">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-5">

                            <div class="mb-3">
                                <label class="form-label required" for="current_vault_password">Current Vault Password</label>
                                <input type="password" id="current_vault_password"
                                       name="current_vault_password"
                                       class="form-control"
                                       placeholder="Current vault password"
                                       autocomplete="current-password"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required" for="new_vault_password">New Vault Password</label>
                                <input type="password" id="new_vault_password"
                                       name="new_vault_password"
                                       class="form-control"
                                       placeholder="New vault password (min 8 chars)"
                                       autocomplete="new-password"
                                       required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required" for="confirm_vault_password">Confirm New Password</label>
                                <input type="password" id="confirm_vault_password"
                                       name="confirm_vault_password"
                                       class="form-control"
                                       placeholder="Repeat new vault password"
                                       autocomplete="new-password"
                                       required>
                            </div>

                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-key me-1"></i>Update Vault Password
                    </button>
                </form>

            </div>

            <!-- ── Login Whitelist ───────────────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === 'whitelist' ? 'show active' : '' ?>"
                 id="tab-whitelist" role="tabpanel">

                <!-- Header row with enable/disable toggle -->
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="card-title mb-1">Login Whitelist</h3>
                        <p class="text-muted mb-0" style="font-size: 13px;">
                            When enabled, only IPs in this list can access the login page.
                            Active sessions are not affected.
                        </p>
                    </div>
                    <form method="post" action="<?= url('/settings/whitelist/toggle') ?>" class="ms-3 flex-shrink-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="whitelist_enabled" value="<?= $whitelistEnabled ? '0' : '1' ?>">
                        <?php if ($whitelistEnabled): ?>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="ti ti-shield-off me-1"></i>Disable Whitelist
                        </button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="ti ti-shield-check me-1"></i>Enable Whitelist
                        </button>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Status alert -->
                <?php if ($whitelistEnabled): ?>
                <div class="alert alert-warning mb-4">
                    <div class="d-flex gap-2">
                        <i class="ti ti-shield fs-4"></i>
                        <div>
                            <strong>Whitelist is active.</strong>
                            Only IPs listed below can access the login page.
                            Your current IP is <code><?= e($currentIp) ?></code>.
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary mb-4">
                    <div class="d-flex gap-2">
                        <i class="ti ti-shield-off fs-4"></i>
                        <div>
                            Whitelist is <strong>disabled</strong> — all IPs can access the login page.
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Add IP card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title mb-0">
                            <i class="ti ti-plus me-1"></i>Add IP Address
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?= url('/settings/whitelist/add') ?>"
                              class="row g-2 align-items-end">
                            <?= csrf_field() ?>

                            <div class="col-md-4">
                                <label class="form-label required form-label-sm">IP Address</label>
                                <input type="text" name="ip_address" id="wl-ip"
                                       class="form-control form-control-sm"
                                       placeholder="e.g. 192.168.1.1"
                                       required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label form-label-sm">
                                    Label <span class="text-muted">(optional)</span>
                                </label>
                                <input type="text" name="label"
                                       class="form-control form-control-sm"
                                       placeholder="e.g. Home, Office"
                                       maxlength="255">
                            </div>

                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>Add
                                </button>
                            </div>

                            <div class="col-auto">
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        id="useMyIpBtn"
                                        data-ip="<?= e($currentIp) ?>">
                                    <i class="ti ti-current-location me-1"></i>Use My IP
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Whitelisted IPs table -->
                <?php if (empty($whitelistIps)): ?>
                <div class="empty py-4">
                    <div class="empty-img mb-3">
                        <i class="ti ti-shield" style="font-size: 2.5rem; color: var(--tblr-muted);"></i>
                    </div>
                    <p class="empty-title">No IPs in whitelist</p>
                    <p class="empty-subtitle text-muted">Add at least one IP above before enabling the whitelist.</p>
                </div>

                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-vcenter">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Label</th>
                                <th>Added</th>
                                <th style="width: 1%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($whitelistIps as $wip): ?>
                            <tr>
                                <td>
                                    <code><?= e($wip['ip_address']) ?></code>
                                    <?php if ($wip['ip_address'] === $currentIp): ?>
                                    <span class="badge bg-blue-lt text-blue ms-1">You</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= e($wip['label'] ?? '') ?></td>
                                <td class="text-muted" style="font-size: 12px;">
                                    <?= e(date('Y-m-d H:i', strtotime($wip['created_at']))) ?>
                                </td>
                                <td>
                                    <form method="post"
                                          action="<?= url('/settings/whitelist/' . $wip['id'] . '/delete') ?>"
                                          onsubmit="return confirm('Remove <?= e(addslashes($wip['ip_address'])) ?> from the whitelist?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-ghost-danger"
                                                title="Remove">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            </div><!-- /tab-whitelist -->

            <!-- ── Two-Factor Authentication ────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === '2fa' ? 'show active' : '' ?>"
                 id="tab-2fa" role="tabpanel">

                <h3 class="card-title mb-1">Two-Factor Authentication</h3>
                <p class="text-muted mb-4" style="font-size: 13px;">
                    Add an extra layer of security. After entering your password, you will be
                    asked for a 6-digit code from your authenticator app
                    (Google Authenticator, Authy, Bitwarden, 1Password, etc.).
                </p>

                <?php if ($totpEnabled ?? false): ?>
                <!-- ── 2FA is enabled ─────────────────────────────────────── -->

                <div class="alert alert-success mb-4">
                    <div class="d-flex gap-2 align-items-center">
                        <i class="ti ti-shield-check fs-3"></i>
                        <div>
                            <strong>Two-factor authentication is enabled.</strong><br>
                            <span style="font-size: 13px;">Your account is protected with TOTP.</span>
                        </div>
                    </div>
                </div>

                <form method="post" action="<?= url('/settings/2fa/disable') ?>"
                      onsubmit="return confirm('Disable two-factor authentication? Your account will only be protected by your password.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-shield-off me-1"></i>Disable Two-Factor Auth
                    </button>
                </form>

                <?php elseif (($totpSetupSecret ?? null) !== null): ?>
                <!-- ── Setup in progress ──────────────────────────────────── -->

                <div class="alert alert-info mb-4">
                    <div class="d-flex gap-2">
                        <i class="ti ti-info-circle fs-4"></i>
                        <div>Follow the steps below to link your authenticator app, then enter the 6-digit code to confirm.</div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">

                        <!-- Step 1: manual key -->
                        <div class="mb-4">
                            <h4 class="mb-2" style="font-size: 14px; font-weight: 600;">
                                Step 1 — Add to your authenticator app
                            </h4>
                            <p class="text-muted mb-2" style="font-size: 13px;">
                                Open your authenticator app, choose <em>Add account</em> or
                                <em>Enter setup key</em>, and enter the key below.
                            </p>

                            <label class="form-label form-label-sm text-muted">Secret Key (manual entry)</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" id="totp-secret-display"
                                       class="form-control font-monospace"
                                       value="<?= e(\App\Services\TotpService::formatSecret($totpSetupSecret)) ?>"
                                       readonly
                                       style="letter-spacing: .1em;">
                                <button type="button" class="btn btn-outline-secondary copy-btn"
                                        data-copy="<?= e($totpSetupSecret) ?>"
                                        title="Copy secret">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>

                            <label class="form-label form-label-sm text-muted mt-2">OTP Auth URI (for direct import)</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="totp-uri-display"
                                       class="form-control font-monospace"
                                       value="<?= e($totpSetupUri) ?>"
                                       readonly
                                       style="font-size: 11px;">
                                <button type="button" class="btn btn-outline-secondary copy-btn"
                                        data-copy="<?= e($totpSetupUri) ?>"
                                        title="Copy URI">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: verify -->
                        <div>
                            <h4 class="mb-2" style="font-size: 14px; font-weight: 600;">
                                Step 2 — Verify your code
                            </h4>
                            <p class="text-muted mb-3" style="font-size: 13px;">
                                Enter the 6-digit code shown in your authenticator app to confirm setup.
                            </p>

                            <form method="post" action="<?= url('/settings/2fa/confirm') ?>">
                                <?= csrf_field() ?>
                                <div class="input-group" style="max-width: 240px;">
                                    <input type="text"
                                           name="totp_code"
                                           class="form-control text-center font-monospace"
                                           placeholder="000000"
                                           maxlength="6"
                                           inputmode="numeric"
                                           pattern="[0-9]{6}"
                                           autocomplete="one-time-code"
                                           autofocus
                                           style="letter-spacing: .35em; font-size: 1.2rem;"
                                           required>
                                    <button type="submit" class="btn btn-success">
                                        <i class="ti ti-circle-check me-1"></i>Enable
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3">
                                <form method="post" action="<?= url('/settings/2fa/setup') ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-ghost-secondary">
                                        <i class="ti ti-refresh me-1"></i>Generate new key
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>

                <?php else: ?>
                <!-- ── Not enabled, no setup in progress ─────────────────── -->

                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-secondary-lt border-0 mb-4">
                            <div class="card-body">
                                <div class="d-flex gap-3 align-items-center">
                                    <div>
                                        <i class="ti ti-shield-off" style="font-size: 2rem; color: var(--tblr-muted);"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold mb-1">2FA is not enabled</div>
                                        <div class="text-muted" style="font-size: 13px;">
                                            Enable it to require a time-based one-time code on every login.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form method="post" action="<?= url('/settings/2fa/setup') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-mobile-code me-1"></i>Set Up Two-Factor Auth
                            </button>
                        </form>
                    </div>
                </div>

                <?php endif; ?>

            </div><!-- /tab-2fa -->

            <!-- ── Activity Log ─────────────────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === 'logs' ? 'show active' : '' ?>"
                 id="tab-logs" role="tabpanel">

                <?php
                // Query-string builder — always pins tab=logs, base is /settings
                $lqs = function(array $overrides = []) use ($logFilters, $logPage): string {
                    $merged = array_merge(['tab' => 'logs'], $logFilters, ['page' => $logPage], $overrides);
                    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null && $v !== 0);
                    return '?' . http_build_query($merged);
                };
                $hasLogFilter = array_filter($logFilters, fn($v) => $v !== '');
                ?>

                <!-- Header + Clear All -->
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div>
                        <h3 class="card-title mb-1">Activity Log</h3>
                        <p class="text-muted mb-0" style="font-size: 13px;">
                            All system events: logins, record changes, vault operations, exports.
                        </p>
                    </div>
                    <form method="post" action="<?= url('/settings/logs/clear') ?>"
                          class="ms-3 flex-shrink-0"
                          onsubmit="return confirm('Clear all activity logs? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="ti ti-trash me-1"></i>Clear All Logs
                        </button>
                    </form>
                </div>

                <!-- Filter bar -->
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <form method="get" action="<?= url('/settings') ?>" class="row g-2 align-items-end">
                            <input type="hidden" name="tab" value="logs">

                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control form-control-sm"
                                       placeholder="Search description, action, IP…"
                                       value="<?= e($logFilters['search']) ?>">
                            </div>

                            <div class="col-md-2">
                                <select name="entity_type" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <?php foreach ($entityTypes as $et): ?>
                                    <option value="<?= e($et) ?>"
                                        <?= $logFilters['entity_type'] === $et ? 'selected' : '' ?>>
                                        <?= e($entityLabels[$et] ?? ucfirst(str_replace('_', ' ', $et))) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <select name="action_group" class="form-select form-select-sm">
                                    <option value="">All Actions</option>
                                    <?php foreach ($actionGroups as $key => $label): ?>
                                    <option value="<?= $key ?>"
                                        <?= $logFilters['action_group'] === $key ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                       value="<?= e($logFilters['date_from']) ?>">
                            </div>

                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                       value="<?= e($logFilters['date_to']) ?>">
                            </div>

                            <div class="col-md-1 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter">
                                    <i class="ti ti-search"></i>
                                </button>
                                <?php if ($hasLogFilter): ?>
                                <a href="<?= url('/settings?tab=logs') ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Clear filters">
                                    <i class="ti ti-x"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results -->
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="card-title">
                            <?= number_format($logTotal) ?> event<?= $logTotal !== 1 ? 's' : '' ?>
                            <?php if ($hasLogFilter): ?>
                            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($logTotalPages > 1): ?>
                        <small class="text-muted">Page <?= $logPage ?> of <?= $logTotalPages ?></small>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($logs)): ?>
                    <div class="card-body text-center text-muted py-5">
                        <i class="ti ti-activity fs-1 d-block mb-2 opacity-50"></i>
                        <?= $hasLogFilter ? 'No events match your filters.' : 'No activity logged yet.' ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table table-sm">
                            <thead>
                                <tr>
                                    <th style="width:155px">When</th>
                                    <th style="width:170px">Action</th>
                                    <th style="width:130px">Entity</th>
                                    <th>Description</th>
                                    <th style="width:100px">User</th>
                                    <th style="width:120px">IP</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($logs as $log): ?>
                            <?php
                                $badgeClass  = \App\Models\ActivityLogModel::actionBadgeClass($log['action']);
                                $entityLabel = $entityLabels[$log['entity_type']] ?? ucfirst(str_replace('_', ' ', (string) $log['entity_type']));
                                $entityUrl   = isset($urlPrefixes[$log['entity_type']])
                                               ? url($urlPrefixes[$log['entity_type']] . '/' . $log['entity_id'])
                                               : null;
                            ?>
                            <tr>
                                <td class="text-muted small" style="white-space:nowrap"
                                    title="<?= e($log['created_at']) ?>">
                                    <?= time_ago($log['created_at']) ?>
                                    <div style="font-size:.7rem;opacity:.6"><?= date('d M y H:i', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>" style="font-size:.7rem;white-space:normal;text-align:left">
                                        <?= e(str_replace('_', ' ', $log['action'])) ?>
                                    </span>
                                </td>
                                <td class="small">
                                    <?php if ($log['entity_type']): ?>
                                    <span class="text-muted"><?= e($entityLabel) ?></span>
                                    <?php if ($log['entity_id'] && $entityUrl): ?>
                                    <a href="<?= $entityUrl ?>" class="ms-1 text-reset" title="View <?= e($entityLabel) ?> #<?= (int) $log['entity_id'] ?>">
                                        <i class="ti ti-external-link" style="font-size:.8rem"></i>
                                    </a>
                                    <?php elseif ($log['entity_id']): ?>
                                    <span class="text-muted ms-1">#<?= (int) $log['entity_id'] ?></span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $log['description'] ? e($log['description']) : '<span class="opacity-50">—</span>' ?>
                                </td>
                                <td class="text-muted small font-monospace">
                                    <?= $log['username'] ? e($log['username']) : '<span class="opacity-50">system</span>' ?>
                                </td>
                                <td class="text-muted small font-monospace" style="white-space:nowrap">
                                    <?php if ($log['ip_address']): ?>
                                    <a href="<?= url('/settings') . $lqs(['search' => $log['ip_address'], 'page' => 1]) ?>"
                                       class="text-reset text-decoration-none" title="Filter by this IP">
                                        <?= e($log['ip_address']) ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="opacity-50">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($logTotalPages > 1): ?>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <p class="m-0 text-muted small">
                            Showing <?= number_format(($logPage - 1) * $logPerPage + 1) ?>–<?= number_format(min($logPage * $logPerPage, $logTotal)) ?>
                            of <?= number_format($logTotal) ?>
                        </p>
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item <?= $logPage <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= url('/settings') . $lqs(['page' => $logPage - 1]) ?>">
                                    <i class="ti ti-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $window = 3;
                            $start  = max(1, $logPage - $window);
                            $end    = min($logTotalPages, $logPage + $window);
                            if ($start > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/settings') . $lqs(['page' => 1]) ?>">1</a>
                            </li>
                            <?php if ($start > 2): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                            <?php endif; endif; ?>
                            <?php for ($p = $start; $p <= $end; $p++): ?>
                            <li class="page-item <?= $p === $logPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= url('/settings') . $lqs(['page' => $p]) ?>"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <?php if ($end < $logTotalPages): ?>
                            <?php if ($end < $logTotalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= url('/settings') . $lqs(['page' => $logTotalPages]) ?>"><?= $logTotalPages ?></a>
                            </li>
                            <?php endif; ?>
                            <li class="page-item <?= $logPage >= $logTotalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= url('/settings') . $lqs(['page' => $logPage + 1]) ?>">
                                    <i class="ti ti-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div><!-- /tab-logs -->

            <!-- ── Export & Import ──────────────────────────────────────────── -->
            <div class="tab-pane fade <?= $activeTab === 'export' ? 'show active' : '' ?>"
                 id="tab-export" role="tabpanel">

                <h3 class="card-title mb-4">Export &amp; Import</h3>

                <div class="row g-4">

                    <!-- Vault Backup -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="bg-blue-lt p-2 rounded me-3">
                                        <i class="ti ti-database-export fs-2 text-blue"></i>
                                    </span>
                                    <div>
                                        <h4 class="card-title mb-0">Vault Backup</h4>
                                        <div class="text-muted small">Export all data as encrypted backup</div>
                                    </div>
                                </div>
                                <p class="text-muted">
                                    Download a full encrypted backup of all clients, domains, servers,
                                    credentials, applications, databases, DNS records, and email accounts.
                                    Encrypted with AES-256-GCM using your vault key.
                                </p>
                                <div class="mt-3">
                                    <?php if (vault_unlocked()): ?>
                                    <a href="<?= url('/export/download') ?>" class="btn btn-primary btn-sm">
                                        <i class="ti ti-download me-1"></i>Download Backup (.svbak)
                                    </a>
                                    <?php else: ?>
                                    <a href="<?= url('/vault/unlock') ?>" class="btn btn-warning btn-sm">
                                        <i class="ti ti-lock me-1"></i>Unlock Vault to Export
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer text-muted small">
                                <i class="ti ti-info-circle me-1"></i>
                                File: <code>stackvault-backup-<?= date('Y-m-d') ?>.svbak</code>
                            </div>
                        </div>
                    </div>

                    <!-- Import Backup -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="bg-green-lt p-2 rounded me-3">
                                        <i class="ti ti-database-import fs-2 text-green"></i>
                                    </span>
                                    <div>
                                        <h4 class="card-title mb-0">Import Backup</h4>
                                        <div class="text-muted small">Restore data from a .svbak file</div>
                                    </div>
                                </div>
                                <p class="text-muted">
                                    Upload a <code>.svbak</code> backup to restore data. Existing records
                                    are skipped — import never overwrites. Vault must be unlocked.
                                </p>
                                <div class="mt-3">
                                    <?php if (vault_unlocked()): ?>
                                    <a href="<?= url('/export/import') ?>" class="btn btn-success btn-sm">
                                        <i class="ti ti-upload me-1"></i>Import Backup
                                    </a>
                                    <?php else: ?>
                                    <a href="<?= url('/vault/unlock') ?>" class="btn btn-warning btn-sm">
                                        <i class="ti ti-lock me-1"></i>Unlock Vault to Import
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer text-muted small">
                                <i class="ti ti-shield-check me-1"></i>
                                Uses INSERT IGNORE — duplicates are safely skipped.
                            </div>
                        </div>
                    </div>

                    <!-- Client Profile Export -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="bg-orange-lt p-2 rounded me-3">
                                        <i class="ti ti-file-text fs-2 text-orange"></i>
                                    </span>
                                    <div>
                                        <h4 class="card-title mb-0">Client Profile Export</h4>
                                        <div class="text-muted small">Print-ready profile for any client</div>
                                    </div>
                                </div>
                                <p class="text-muted mb-2">
                                    Generate a print-friendly profile — includes domains, servers,
                                    applications, databases, DNS records, and email accounts.
                                    You can also click <strong>Export Profile</strong> on any client page.
                                </p>
                                <?php
                                $exportClients = \App\Models\ClientModel::getAll();
                                ?>
                                <?php if (!empty($exportClients)): ?>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <?php foreach ($exportClients as $c): ?>
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

            </div><!-- /tab-export -->

        </div><!-- /tab-content -->
    </div><!-- /card-body -->
</div><!-- /card -->

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Copy buttons (data-copy attribute)
    document.querySelectorAll('.copy-btn[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = this.dataset.copy;
            if (!text) return;
            navigator.clipboard.writeText(text).then(function () {
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.className = 'ti ti-check text-success';
                    setTimeout(function () { icon.className = 'ti ti-copy'; }, 1800);
                }
            });
        });
    });

    // "Use My IP" button fills the IP input
    var useMyIpBtn = document.getElementById('useMyIpBtn');
    if (useMyIpBtn) {
        useMyIpBtn.addEventListener('click', function () {
            var ipInput = document.getElementById('wl-ip');
            if (ipInput) {
                ipInput.value = this.dataset.ip;
                ipInput.focus();
            }
        });
    }

    // Update URL query param when tabs switch (no page reload)
    var tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabLinks.forEach(function (link) {
        link.addEventListener('shown.bs.tab', function (e) {
            var target = e.target.getAttribute('data-bs-target') || '';
            var tabId  = target.replace('#tab-', '');
            if (tabId && window.history && window.history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.replaceState(null, '', url.toString());
            }
        });
    });

});

// ── AJAX preset delete ────────────────────────────────────────────────────
function svDeletePreset(btn) {
    var url  = btn.dataset.url;
    var wrap = btn.closest('[data-preset-id]');

    btn.disabled = true;

    var fd = new FormData();
    fd.append('_token', SV_CSRF);

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd,
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            wrap.remove();
            showToast(data.message, 'success');
        } else {
            btn.disabled = false;
            showToast(data.message, 'error');
        }
    })
    .catch(function () {
        btn.disabled = false;
        showToast('Request failed. Please try again.', 'error');
    });
}
</script>
