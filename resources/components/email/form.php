<?php
/**
 * Email Account form component
 *
 * Required vars:
 *   $account[]      — field values (empty array for create)
 *   $clients[]      — ClientModel::getForSelect()
 *   $domains[]      — DomainModel::getForSelect()
 *   $action         — form action URL
 *   $submitLabel    — button text
 *   $isEdit         — bool
 *   $return_to      — (optional) URL to return to after save
 *   $presetDomainId — (optional) pre-selected domain_id (create only)
 */

$selectedClient  = (int)    ($account['client_id']    ?? 0);
$selectedDomain  = (int)    ($account['domain_id']    ?? $presetDomainId ?? 0);
$smtpPort        = (int)    ($account['smtp_port']    ?? \App\Models\EmailAccountModel::DEFAULT_SMTP);
$imapPort        = (int)    ($account['imap_port']    ?? \App\Models\EmailAccountModel::DEFAULT_IMAP);
$returnTo        = $return_to ?? '';
$isEdit          = $isEdit ?? false;

$smtpPresets = \App\Models\EmailAccountModel::SMTP_PRESETS;
$imapPresets = \App\Models\EmailAccountModel::IMAP_PRESETS;
?>
<form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <?php if ($returnTo): ?>
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php endif; ?>

    <div class="row g-3">

        <!-- Email address -->
        <div class="col-md-6">
            <label class="form-label required">Email Address</label>
            <input type="email" name="email_address" class="form-control"
                   value="<?= e((string)($account['email_address'] ?? '')) ?>"
                   placeholder="user@example.com" required>
        </div>

        <!-- Mail host -->
        <div class="col-md-6">
            <label class="form-label">Mail Host</label>
            <input type="text" name="mail_host" class="form-control font-monospace"
                   value="<?= e((string)($account['mail_host'] ?? '')) ?>"
                   placeholder="mail.example.com">
            <div class="form-text">Incoming/outgoing mail server hostname.</div>
        </div>

        <!-- SMTP port -->
        <div class="col-md-3">
            <label class="form-label">SMTP Port</label>
            <div class="input-group">
                <input type="number" name="smtp_port" id="inp-smtp-port" class="form-control"
                       value="<?= $smtpPort ?>" min="1" max="65535">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Common ports">
                    <i class="ti ti-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($smtpPresets as $port => $label): ?>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="document.getElementById('inp-smtp-port').value=<?= $port ?>;return false;">
                            <?= e($label) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- IMAP port -->
        <div class="col-md-3">
            <label class="form-label">IMAP Port</label>
            <div class="input-group">
                <input type="number" name="imap_port" id="inp-imap-port" class="form-control"
                       value="<?= $imapPort ?>" min="1" max="65535">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Common ports">
                    <i class="ti ti-chevron-down"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($imapPresets as $port => $label): ?>
                    <li>
                        <a class="dropdown-item" href="#"
                           onclick="document.getElementById('inp-imap-port').value=<?= $port ?>;return false;">
                            <?= e($label) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Username -->
        <div class="col-md-6">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control font-monospace"
                   value="<?= e((string)($account['username'] ?? '')) ?>"
                   placeholder="Often same as email address">
        </div>

        <!-- Password -->
        <div class="col-12">
            <label class="form-label">Password</label>
            <?php if (!vault_unlocked()): ?>
            <div class="input-group">
                <input type="text" class="form-control text-muted" disabled
                       value="Vault locked — unlock to enter a password">
                <a href="<?= url('/vault/unlock') ?>" class="btn btn-outline-warning">
                    <i class="ti ti-lock me-1"></i>Unlock Vault
                </a>
            </div>
            <?php else: ?>
            <div class="input-group">
                <input type="password" name="password" id="inp-password" class="form-control font-monospace"
                       autocomplete="new-password"
                       placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Email account password' ?>">
                <button type="button" class="btn btn-outline-secondary"
                        onclick="svToggleVis('inp-password', this)"
                        title="Show/hide">
                    <i class="ti ti-eye"></i>
                </button>
            </div>
            <?php if ($isEdit): ?>
            <div class="form-text">Leave blank to keep the current password.</div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Webmail URL -->
        <div class="col-12">
            <label class="form-label">Webmail URL <span class="text-muted fw-normal">(optional)</span></label>
            <input type="url" name="webmail_url" class="form-control"
                   value="<?= e((string)($account['webmail_url'] ?? '')) ?>"
                   placeholder="https://webmail.example.com">
        </div>

        <!-- Client + Domain -->
        <div class="col-md-6">
            <label class="form-label">Client <span class="text-muted fw-normal">(optional)</span></label>
            <select name="client_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $selectedClient === (int)$c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Domain <span class="text-muted fw-normal">(optional)</span></label>
            <select name="domain_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($domains as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $selectedDomain === (int)$d['id'] ? 'selected' : '' ?>>
                    <?= e($d['root_domain']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Notes -->
        <div class="col-12">
            <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="IMAP/SMTP config details, provider notes…"><?= e((string)($account['notes'] ?? '')) ?></textarea>
        </div>

    </div><!-- /.row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel) ?>
        </button>
        <?php if ($returnTo): ?>
        <a href="<?= e($returnTo) ?>#tab-email" class="btn btn-outline-secondary">Cancel</a>
        <?php else: ?>
        <a href="<?= url('/email') ?>" class="btn btn-outline-secondary">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<script>
function svToggleVis(inputId, btn) {
    const inp = document.getElementById(inputId);
    if (!inp) return;
    const isHidden = inp.type === 'password';
    inp.type = isHidden ? 'text' : 'password';
    btn.querySelector('i').className = isHidden ? 'ti ti-eye-off' : 'ti ti-eye';
}
</script>
