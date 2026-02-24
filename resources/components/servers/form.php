<?php
/**
 * Shared server form component.
 *
 * Expected vars:
 *   $server      — array of current field values (empty array for create)
 *   $clients     — array from ClientModel::getForSelect()
 *   $action      — form POST action URL
 *   $submitLabel — button text
 */
$v   = fn(string $key): string => e($server[$key] ?? '');
$sel = fn(int $id): string => ((int)($server['client_id'] ?? 0)) === $id ? 'selected' : '';
$selStatus = fn(string $s): string => ($server['monitoring_status'] ?? 'unknown') === $s ? 'selected' : '';
$sshPort = (int)($server['ssh_port'] ?? 22) ?: 22;
?>

<form action="<?= $action ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- Label (required) -->
        <div class="col-md-8">
            <label for="label" class="form-label required">Server Label</label>
            <input type="text" id="label" name="label" class="form-control"
                   value="<?= $v('label') ?>"
                   placeholder="e.g. Production Web Server"
                   maxlength="255" required autofocus>
            <div class="form-hint">A descriptive name to identify this server.</div>
        </div>

        <!-- Client -->
        <div class="col-md-4">
            <label for="client_id" class="form-label">Client</label>
            <select id="client_id" name="client_id" class="form-select">
                <option value="0">— No client —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $sel((int)$c['id']) ?>>
                    <?= e($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- IP Address -->
        <div class="col-md-5">
            <label for="ip_address" class="form-label">IP Address</label>
            <input type="text" id="ip_address" name="ip_address" class="form-control font-monospace"
                   value="<?= $v('ip_address') ?>"
                   placeholder="e.g. 192.168.1.1 or 2001:db8::1"
                   maxlength="45">
        </div>

        <!-- Hostname -->
        <div class="col-md-5">
            <label for="hostname" class="form-label">Hostname</label>
            <input type="text" id="hostname" name="hostname" class="form-control"
                   value="<?= $v('hostname') ?>"
                   placeholder="e.g. web1.example.com"
                   maxlength="255">
        </div>

        <!-- SSH Port -->
        <div class="col-md-2">
            <label for="ssh_port" class="form-label">SSH Port</label>
            <input type="number" id="ssh_port" name="ssh_port" class="form-control"
                   value="<?= $sshPort ?>"
                   min="1" max="65535">
        </div>

        <!-- Provider -->
        <div class="col-md-4">
            <label for="provider" class="form-label">Provider</label>
            <input type="text" id="provider" name="provider" class="form-control"
                   value="<?= $v('provider') ?>"
                   placeholder="e.g. Hetzner, DigitalOcean"
                   maxlength="100"
                   list="provider-list">
            <datalist id="provider-list">
                <?php foreach ($providerPresets ?? [] as $preset): ?>
                <option value="<?= e($preset) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <!-- OS Version -->
        <div class="col-md-4">
            <label for="os_version" class="form-label">OS Version</label>
            <input type="text" id="os_version" name="os_version" class="form-control"
                   value="<?= $v('os_version') ?>"
                   placeholder="e.g. Ubuntu 24.04 LTS"
                   maxlength="100"
                   list="os-list">
            <datalist id="os-list">
                <?php foreach ($osPresets ?? [] as $preset): ?>
                <option value="<?= e($preset) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <!-- Monitoring Status -->
        <div class="col-md-4">
            <label for="monitoring_status" class="form-label">Monitoring Status</label>
            <select id="monitoring_status" name="monitoring_status" class="form-select">
                <option value="unknown"  <?= $selStatus('unknown')  ?>>Unknown</option>
                <option value="online"   <?= $selStatus('online')   ?>>Online</option>
                <option value="offline"  <?= $selStatus('offline')  ?>>Offline</option>
                <option value="degraded" <?= $selStatus('degraded') ?>>Degraded</option>
            </select>
        </div>

        <!-- Installed Stacks -->
        <div class="col-md-6">
            <label for="installed_stacks" class="form-label">Installed Stacks</label>
            <textarea id="installed_stacks" name="installed_stacks" class="form-control" rows="3"
                      placeholder="e.g. Docker, Nginx, PHP 8.3, MySQL 8&#10;Cloudron&#10;Node 20"><?= $v('installed_stacks') ?></textarea>
            <div class="form-hint">One per line, or free-form.</div>
        </div>

        <!-- Firewall Notes -->
        <div class="col-md-6">
            <label for="firewall_notes" class="form-label">Firewall Notes</label>
            <textarea id="firewall_notes" name="firewall_notes" class="form-control" rows="3"
                      placeholder="Open ports, UFW rules, security groups…"><?= $v('firewall_notes') ?></textarea>
        </div>

        <!-- Notes -->
        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"
                      placeholder="Any additional notes…"><?= $v('notes') ?></textarea>
        </div>

    </div><!-- /row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel ?? 'Save') ?>
        </button>
        <a href="<?= url('/servers') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>
