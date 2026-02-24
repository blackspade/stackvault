<?php
/**
 * Vars: $old[], $errors[], $clients[]
 */

$v = fn(string $key, mixed $default = '') => $old[$key] ?? $default;
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
        <li><?= e($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post" action="<?= url('/hosts/store') ?>">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- ── Left: machine details ───────────────────────────────────────── -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Machine Details</h3>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label required">Machine Name</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($v('name')) ?>"
                               placeholder="e.g. MacBook Pro Work"
                               maxlength="255" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Operating System</label>
                        <select name="os" class="form-select">
                            <?php foreach (\App\Models\HostMachineModel::OS_TYPES as $key => $label): ?>
                            <option value="<?= $key ?>"
                                <?= ($v('os', 'mac') === $key) ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client <span class="text-muted">(optional)</span></label>
                        <select name="client_id" class="form-select">
                            <option value="">— No Client —</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= ((int) $v('client_id', 0) === (int) $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="e.g. Work laptop, home office setup…"><?= e($v('description')) ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Right: hosts file content ──────────────────────────────────── -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Hosts File Content</h3>
                    <span class="text-muted small font-monospace" id="hostsPathHint"></span>
                </div>
                <div class="card-body p-0">
                    <textarea name="hosts_file" id="hostsFileEditor"
                              class="form-control font-monospace border-0 rounded-0"
                              rows="22"
                              placeholder="# Paste your hosts file content here&#10;127.0.0.1    localhost&#10;::1          localhost"
                              style="resize:vertical;min-height:300px"><?= e($v('hosts_file')) ?></textarea>
                </div>
                <div class="card-footer text-muted small">
                    Paste the contents of your hosts file. Lines starting with <code>#</code> are comments.
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Save Host File
                </button>
                <a href="<?= url('/hosts') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>

    </div>

</form>

<script>
(function () {
    const osPaths = <?= json_encode(\App\Models\HostMachineModel::OS_HOSTS_PATH) ?>;
    const osSelect   = document.querySelector('select[name="os"]');
    const pathHint   = document.getElementById('hostsPathHint');

    function updateHint() {
        const os = osSelect ? osSelect.value : 'other';
        pathHint.textContent = osPaths[os] || '/etc/hosts';
    }

    if (osSelect) {
        osSelect.addEventListener('change', updateHint);
        updateHint();
    }
})();
</script>
