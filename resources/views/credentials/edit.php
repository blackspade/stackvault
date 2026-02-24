<?php /** Vars: $credential[], $errors[], $types[], $clients[], $servers[], $domains[] */ ?>

<?php if (!vault_unlocked()): ?>
<div class="alert alert-warning mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="ti ti-lock fs-4 flex-shrink-0"></i>
        <div>
            <strong>Vault is locked.</strong>
            <a href="<?= url('/vault/unlock') ?>" class="alert-link">Unlock the vault</a>
            before saving changes — the password fields require the vault key.
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible mb-4">
    <div class="d-flex gap-2">
        <i class="ti ti-alert-circle fs-4 flex-shrink-0"></i>
        <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title">Edit Credential</h3></div>
    <div class="card-body">
        <?php \App\Core\View::partial('credentials/form', [
            'credential'  => $credential,
            'types'       => $types,
            'clients'     => $clients,
            'servers'     => $servers,
            'domains'     => $domains,
            'action'      => url('/credentials/' . $credential['id'] . '/update'),
            'submitLabel' => 'Save Changes',
            'isEdit'      => true,
        ]); ?>
    </div>
</div>
