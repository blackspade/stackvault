<?php
/**
 * Vars: $machine[], $activity[]
 */

$id          = (int) $machine['id'];
$badgeClass  = \App\Models\HostMachineModel::osBadgeClass($machine['os']);
$osLabel     = \App\Models\HostMachineModel::OS_TYPES[$machine['os']] ?? ucfirst($machine['os']);
$hostsPath   = \App\Models\HostMachineModel::OS_HOSTS_PATH[$machine['os']] ?? '/etc/hosts';
$hasContent  = !empty($machine['hosts_file']);
$lineCount   = $hasContent ? substr_count($machine['hosts_file'], "\n") + 1 : 0;
?>

<?php
ob_start(); ?>
<a href="<?= url("/hosts/{$id}/edit") ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<form method="post" action="<?= url("/hosts/{$id}/delete") ?>" class="d-inline"
      onsubmit="return confirm('Delete host file for &quot;<?= e(addslashes($machine['name'])) ?>&quot;?')">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-danger">
        <i class="ti ti-trash me-1"></i>Delete
    </button>
</form>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<div class="row g-3">

    <!-- ── Left: machine info ────────────────────────────────────────────── -->
    <div class="col-lg-3">

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge <?= $badgeClass ?>"><?= e($osLabel) ?></span>
                </div>

                <?php if ($machine['client_name']): ?>
                <div class="text-muted small mt-2">
                    <i class="ti ti-user me-1"></i>
                    <a href="<?= url('/clients/' . $machine['client_id']) ?>" class="text-reset">
                        <?= e($machine['client_name']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <hr class="my-2">

                <div class="text-muted small mb-1">
                    <span class="font-monospace text-break"><?= e($hostsPath) ?></span>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Lines</span>
                    <strong class="text-body"><?= $hasContent ? number_format($lineCount) : '—' ?></strong>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Size</span>
                    <strong class="text-body">
                        <?= $hasContent ? number_format(strlen($machine['hosts_file'])) . ' B' : '—' ?>
                    </strong>
                </div>

                <hr class="my-2">

                <div class="text-muted small">
                    Created <?= date('d M Y', strtotime($machine['created_at'])) ?>
                </div>
                <?php if ($machine['updated_at'] !== $machine['created_at']): ?>
                <div class="text-muted small">
                    Saved <?= date('d M Y H:i', strtotime($machine['updated_at'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($machine['description']): ?>
                <hr class="my-2">
                <div class="text-muted small"><?= e($machine['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity -->
        <?php if (!empty($activity)): ?>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Activity</h4>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($activity as $a): ?>
                <div class="list-group-item px-3 py-2">
                    <div class="text-muted small" title="<?= e($a['created_at']) ?>">
                        <?= time_ago($a['created_at']) ?>
                        <?php if ($a['username']): ?>
                        — <span class="font-monospace"><?= e($a['username']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="small"><?= e(str_replace('_', ' ', $a['action'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /col-lg-3 -->

    <!-- ── Right: hosts file viewer ──────────────────────────────────────── -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title">
                    Hosts File
                    <?php if (!$hasContent): ?>
                    <span class="text-muted fw-normal ms-2">— no content saved yet</span>
                    <?php endif; ?>
                </div>
                <?php if ($hasContent): ?>
                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary copy-btn"
                            data-copy="hostsFileContent"
                            title="Copy to clipboard">
                        <i class="ti ti-copy me-1"></i>Copy
                    </button>
                    <a href="<?= url("/hosts/{$id}/edit") ?>" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-pencil me-1"></i>Edit
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!$hasContent): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-file-text fs-1 d-block mb-2 opacity-50"></i>
                No hosts file content saved yet.
                <div class="mt-3">
                    <a href="<?= url("/hosts/{$id}/edit") ?>" class="btn btn-primary btn-sm">
                        <i class="ti ti-pencil me-1"></i>Add Content
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="card-body p-0">
                <pre id="hostsFileContent"
                     class="m-0 p-3 font-monospace"
                     style="background:#1e1e2e;color:#cdd6f4;font-size:.8rem;white-space:pre;overflow-x:auto;min-height:200px;border-radius:0 0 var(--tblr-card-border-radius) var(--tblr-card-border-radius)"><?= e($machine['hosts_file']) ?></pre>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between text-muted small">
                <span><?= number_format($lineCount) ?> line<?= $lineCount !== 1 ? 's' : '' ?></span>
                <span class="font-monospace"><?= e($hostsPath) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /col-lg-9 -->

</div><!-- /row -->

<?php if ($hasContent): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.copy-btn[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId  = btn.getAttribute('data-copy');
            const target    = document.getElementById(targetId);
            const text      = target ? target.innerText : '';
            const origHtml  = btn.innerHTML;

            navigator.clipboard.writeText(text).then(function () {
                btn.innerHTML = '<i class="ti ti-check me-1"></i>Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-secondary');
                setTimeout(function () {
                    btn.innerHTML = origHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(function () {
                btn.innerHTML = '<i class="ti ti-x me-1"></i>Failed';
                setTimeout(function () { btn.innerHTML = origHtml; }, 2000);
            });
        });
    });
});
</script>
<?php endif; ?>
