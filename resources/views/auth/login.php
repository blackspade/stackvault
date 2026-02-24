<?php
/**
 * Login view
 * Available vars: $title, $timeout, $errors[], $old_username, $whitelisted, $clientIp
 */
?>

<?php if (!($whitelisted ?? true)): ?>

<div class="card card-md shadow-sm">
    <div class="card-body text-center py-5">
        <div class="mb-3">
            <i class="ti ti-shield-lock" style="font-size: 3rem; color: var(--tblr-danger);"></i>
        </div>
        <h2 class="h3 mb-2 text-danger">Access Restricted</h2>
        <p class="text-muted mb-0">
            Your IP address (<code><?= e($clientIp ?? '') ?></code>) is not authorized
            to access this application.
        </p>
    </div>
</div>

<div class="text-center text-muted mt-3" style="font-size: 12px;">
    <?= e($appName) ?> &mdash; Internal DevOps Vault
</div>

<?php return; endif; ?>

<?php if ($timeout ?? false): ?>
<div class="alert alert-warning alert-dismissible mb-3" role="alert">
    <div class="d-flex gap-2">
        <i class="ti ti-clock fs-4"></i>
        <div>Your session expired due to inactivity. Please sign in again.</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

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
        <h2 class="h3 text-center mb-4">Sign in to StackVault</h2>

        <form action="<?= url('/login') ?>" method="post" autocomplete="on" novalidate>
            <?= csrf_field() ?>

            <!-- Username -->
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ti ti-user"></i>
                    </span>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?= e($old_username ?? '') ?>"
                           placeholder="admin"
                           autocomplete="username"
                           autofocus required>
                </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="login_password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-lock"></i></span>
                    <input type="password" id="login_password" name="login_password"
                           class="form-control"
                           placeholder="••••••••••••"
                           autocomplete="current-password"
                           required>
                    <button type="button" class="btn btn-outline-secondary pw-toggle"
                            data-pw-toggle="login_password" tabindex="-1">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-login me-1"></i>Sign In
                </button>
            </div>
        </form>
    </div>
</div>

<div class="text-center text-muted mt-3" style="font-size: 12px;">
    <?= e($appName) ?> &mdash; Internal DevOps Vault
</div>
