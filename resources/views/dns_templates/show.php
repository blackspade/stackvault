<?php
/**
 * Vars: $template[], $records[]
 */

$id        = (int) $template['id'];
$isBuiltin = (bool) $template['is_builtin'];

// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/dns/apply-template?template_id=' . $id) ?>" class="btn btn-primary">
    <i class="ti ti-wand me-1"></i>Apply Template
</a>
<?php if (!$isBuiltin): ?>
<a href="<?= url('/dns/templates/' . $id . '/edit') ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<button type="button" class="btn btn-outline-danger"
        onclick="document.getElementById('delete-form').classList.toggle('d-none')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php endif; ?>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<?php if (!$isBuiltin): ?>
<!-- ── Delete confirm ─────────────────────────────────────────────────────── -->
<div id="delete-form" class="card border-danger mb-4 d-none">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span class="text-danger fw-medium">
            <i class="ti ti-alert-triangle me-1"></i>Permanently delete this template and all its records?
        </span>
        <form method="post" action="<?= url('/dns/templates/' . $id . '/delete') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Yes, delete</button>
        </form>
        <button type="button" class="btn btn-sm btn-ghost-secondary"
                onclick="document.getElementById('delete-form').classList.add('d-none')">
            Cancel
        </button>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Template info ───────────────────────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <h3 class="card-title mb-0"><?= e($template['name']) ?></h3>
                <?php if ($isBuiltin): ?>
                <span class="badge bg-blue-lt text-blue ms-auto">Built-in</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0">

                    <dt class="col-sm-5 text-muted fw-normal">Records</dt>
                    <dd class="col-sm-7"><?= count($records) ?></dd>

                    <dt class="col-sm-5 text-muted fw-normal">Variables</dt>
                    <dd class="col-sm-7">
                        <?php $vars = \App\Models\DnsTemplateModel::detectVariables($records); ?>
                        <?php if (empty($vars)): ?>
                        <span class="text-muted">None</span>
                        <?php else: ?>
                        <?php foreach ($vars as $v): ?>
                        <code class="me-1">{<?= e($v) ?>}</code>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5 text-muted fw-normal">Created</dt>
                    <dd class="col-sm-7 text-muted small"><?= e($template['created_at']) ?></dd>

                </dl>

                <?php if ($template['description']): ?>
                <hr class="my-3">
                <p class="text-muted mb-0"><?= e($template['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Records table ───────────────────────────────────────────────────── -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h4 class="card-title">Records in this Template</h4></div>
            <?php if (empty($records)): ?>
            <div class="card-body text-center text-muted py-4">
                No records defined yet.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table font-monospace">
                    <thead>
                        <tr>
                            <th style="width:80px">Type</th>
                            <th style="width:140px">Name</th>
                            <th>Value</th>
                            <th style="width:70px" class="text-end">TTL</th>
                            <th style="width:60px" class="text-end">Prio</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $rec):
                        $badge = \App\Models\DnsRecordModel::typeBadgeClass($rec['record_type']);
                    ?>
                    <tr>
                        <td><span class="badge <?= $badge ?>"><?= e($rec['record_type']) ?></span></td>
                        <td><?= e($rec['name']) ?></td>
                        <td class="text-muted small text-truncate" style="max-width:320px" title="<?= e($rec['value']) ?>">
                            <?= e($rec['value']) ?>
                        </td>
                        <td class="text-muted text-end"><?= (int) $rec['ttl'] ?></td>
                        <td class="text-muted text-end"><?= $rec['priority'] !== null ? (int) $rec['priority'] : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.row -->
