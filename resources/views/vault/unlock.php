<?php
/**
 * Vault unlock view
 * Available vars: $title, $errors[]
 */
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible mb-4" role="alert">
    <div class="d-flex gap-2">
        <i class="ti ti-alert-circle fs-4"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">

                <!-- Icon + heading -->
                <div class="text-center mb-4">
                    <span class="avatar avatar-lg mb-3" style="background-color: #1e3a5f;">
                        <i class="ti ti-key fs-2" style="color: #90b4d4;"></i>
                    </span>
                    <h3 class="mb-1">Unlock Vault</h3>
                    <p class="text-muted small mb-0">
                        Enter your vault password to decrypt stored credentials.
                        The vault locks automatically when you sign out.
                    </p>
                </div>

                <form action="<?= url('/vault/unlock') ?>" method="post" autocomplete="off" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="vault_password" class="form-label">Vault Password</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background:#1e3a5f; border-color:#2d5080;">
                                <i class="ti ti-shield-lock" style="color:#90b4d4;"></i>
                            </span>
                            <input type="password" id="vault_password" name="vault_password"
                                   class="form-control"
                                   placeholder="••••••••••••"
                                   autofocus required>
                            <button type="button" class="btn btn-outline-secondary pw-toggle"
                                    data-pw-toggle="vault_password" tabindex="-1">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-lock-open-2 me-1"></i>Unlock Vault
                    </button>
                </form>

            </div>
        </div>

        <div class="text-center mt-3">
            <a href="<?= url('/dashboard') ?>" class="text-muted small">
                <i class="ti ti-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>
</div>
