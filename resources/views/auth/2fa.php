<?php
/**
 * 2FA verification view — shown after successful username/password login
 * when the account has TOTP enabled.
 *
 * Available vars: $title, $errors[], $username
 */
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible mb-3" role="alert">
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

<div class="card card-md shadow-sm">
    <div class="card-body">

        <div class="text-center mb-4">
            <div class="mb-3">
                <i class="ti ti-device-mobile-code" style="font-size: 2.5rem; color: var(--tblr-primary);"></i>
            </div>
            <h2 class="h3 mb-1">Two-Factor Authentication</h2>
            <?php if ($username !== ''): ?>
            <p class="text-muted mb-0" style="font-size: 13px;">
                Signing in as <strong><?= e($username) ?></strong>
            </p>
            <?php endif; ?>
        </div>

        <p class="text-muted text-center mb-4" style="font-size: 13px;">
            Enter the 6-digit code from your authenticator app.
        </p>

        <form action="<?= url('/login/2fa') ?>" method="post" autocomplete="off" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-check">
                    <input type="checkbox" class="form-check-input"
                           name="remember_device" id="remember_device" value="1">
                    <span class="form-check-label text-muted" style="font-size: 13px;">
                        Remember this device for 15 days
                    </span>
                </label>
            </div>

            <div class="mb-4">
                <label for="totp_code" class="form-label">Verification Code</label>
                <input type="text"
                       id="totp_code"
                       name="totp_code"
                       class="form-control form-control-lg text-center"
                       placeholder="000000"
                       maxlength="6"
                       inputmode="numeric"
                       pattern="[0-9]{6}"
                       autocomplete="one-time-code"
                       autofocus
                       required
                       style="letter-spacing: .35em; font-size: 1.5rem; font-family: monospace;">
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-shield-check me-1"></i>Verify
                </button>
            </div>
        </form>

    </div>
</div>

<div class="text-center mt-3">
    <a href="<?= url('/login') ?>" class="text-muted" style="font-size: 12px;">
        <i class="ti ti-arrow-left me-1"></i>Back to sign in
    </a>
</div>

<div class="text-center text-muted mt-2" style="font-size: 12px;">
    <?= e($appName) ?> &mdash; Internal DevOps Vault
</div>

<script>
// Auto-submit when 6 digits entered, with a brief delay so the user can
// tick "Remember this device" before the form fires.
var _autoSubmitTimer = null;
document.getElementById('totp_code').addEventListener('input', function () {
    var val = this.value.replace(/\D/g, '');
    this.value = val;
    clearTimeout(_autoSubmitTimer);
    if (val.length === 6) {
        var form = this.closest('form');
        _autoSubmitTimer = setTimeout(function () { form.submit(); }, 800);
    }
});
</script>
