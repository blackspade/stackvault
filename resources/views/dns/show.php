<?php
/**
 * Vars: $record[], $types[]
 */
$id         = (int) $record['id'];
$typeLabel  = $record['record_type'];
$badgeClass = \App\Models\DnsRecordModel::typeBadgeClass($record['record_type']);
$showPrio   = in_array($record['record_type'], \App\Models\DnsRecordModel::PRIORITY_TYPES, true);
$valueHint  = \App\Models\DnsRecordModel::VALUE_HINTS[$record['record_type']] ?? '';
?>

<?php
// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/dns/' . $id . '/edit') ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<button type="button" class="btn btn-outline-danger"
        onclick="document.getElementById('delete-form').classList.toggle('d-none')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Delete confirm ─────────────────────────────────────────────────────── -->
<div id="delete-form" class="card border-danger mb-4 d-none">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span class="text-danger fw-medium">
            <i class="ti ti-alert-triangle me-1"></i>Permanently delete this DNS record?
        </span>
        <form method="post" action="<?= url('/dns/' . $id . '/delete') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Yes, delete</button>
        </form>
        <button type="button" class="btn btn-sm btn-ghost-secondary"
                onclick="document.getElementById('delete-form').classList.add('d-none')">
            Cancel
        </button>
    </div>
</div>

<div class="row g-4">

    <!-- ── Record details ──────────────────────────────────────────────────── -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge <?= $badgeClass ?> fs-6"><?= e($typeLabel) ?></span>
                <h3 class="card-title mb-0 font-monospace"><?= e($record['name']) ?></h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">

                    <dt class="col-sm-4 text-muted fw-normal">Domain</dt>
                    <dd class="col-sm-8">
                        <a href="<?= url('/domains/' . $record['domain_id']) ?>">
                            <?= e($record['root_domain']) ?>
                        </a>
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">TTL</dt>
                    <dd class="col-sm-8 font-monospace"><?= (int) $record['ttl'] ?>s</dd>

                    <?php if ($showPrio): ?>
                    <dt class="col-sm-4 text-muted fw-normal">Priority</dt>
                    <dd class="col-sm-8 font-monospace">
                        <?= $record['priority'] !== null ? (int) $record['priority'] : '—' ?>
                    </dd>
                    <?php endif; ?>

                    <dt class="col-sm-4 text-muted fw-normal">Added</dt>
                    <dd class="col-sm-8 text-muted small"><?= e($record['created_at']) ?></dd>

                    <dt class="col-sm-4 text-muted fw-normal">Updated</dt>
                    <dd class="col-sm-8 text-muted small"><?= e($record['updated_at']) ?></dd>

                </dl>
            </div>
        </div>

        <?php if ($record['notes']): ?>
        <div class="card mt-3">
            <div class="card-header"><h4 class="card-title">Notes</h4></div>
            <div class="card-body">
                <p class="text-muted mb-0" style="white-space:pre-wrap"><?= e($record['notes']) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Value ───────────────────────────────────────────────────────────── -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><i class="ti ti-code me-1"></i>Record Value</h4>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <textarea id="field-value" class="form-control font-monospace"
                              rows="3" readonly
                              style="resize:none"><?= e($record['value']) ?></textarea>
                    <button type="button" class="btn btn-outline-secondary align-self-start"
                            onclick="svCopyText(document.getElementById('field-value').value, this)"
                            title="Copy value">
                        <i class="ti ti-copy me-1"></i>Copy
                    </button>
                </div>
                <?php if ($valueHint): ?>
                <div class="form-text"><?= e($valueHint) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Zone file snippet -->
        <div class="card mt-3">
            <div class="card-header">
                <h4 class="card-title"><i class="ti ti-terminal me-1"></i>Zone File Snippet</h4>
            </div>
            <div class="card-body">
                <?php
                $name  = $record['name'] === '@' ? '@' : e($record['name']);
                $prio  = $showPrio && $record['priority'] !== null
                            ? (int) $record['priority'] . ' '
                            : '';
                $snippet = sprintf(
                    '%s %s IN %s %s%s',
                    $name,
                    (int) $record['ttl'],
                    e($record['record_type']),
                    $prio,
                    e($record['value'])
                );
                ?>
                <div class="input-group">
                    <input type="text" id="field-snippet" class="form-control font-monospace small"
                           value="<?= $snippet ?>" readonly>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="svCopyText(document.getElementById('field-snippet').value, this)"
                            title="Copy">
                        <i class="ti ti-copy me-1"></i>Copy
                    </button>
                </div>
                <div class="form-text">Standard BIND-style zone file format.</div>
            </div>
        </div>
    </div>

</div><!-- /.row -->

<script>
function svCopyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>Copied!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary');
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
