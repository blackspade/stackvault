<?php
/**
 * Vars: $templates[]
 */

// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/dns/apply-template') ?>" class="btn btn-primary d-none d-sm-inline-flex">
    <i class="ti ti-wand me-1"></i>Add Records from Template
</a>
<a href="<?= url('/dns/templates/create') ?>" class="btn btn-outline-secondary d-none d-sm-inline-flex">
    <i class="ti ti-plus me-1"></i>New Template
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<?php
$builtins = array_filter($templates, fn($t) => $t['is_builtin']);
$customs  = array_filter($templates, fn($t) => !$t['is_builtin']);
?>

<?php if (empty($templates)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-template fs-1 d-block mb-2 opacity-50"></i>
        No templates yet.
        <a href="<?= url('/dns/templates/create') ?>">Create your first template.</a>
    </div>
</div>
<?php else: ?>

<!-- ── Built-in templates ─────────────────────────────────────────────────── -->
<?php if (!empty($builtins)): ?>
<h4 class="mb-3 text-muted fw-normal">Built-in Templates</h4>
<div class="row g-3 mb-4">
    <?php foreach ($builtins as $tpl): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                        <h4 class="card-title mb-1"><?= e($tpl['name']) ?></h4>
                        <span class="badge bg-blue-lt text-blue">Built-in</span>
                    </div>
                    <span class="badge bg-secondary-lt text-secondary ms-2">
                        <?= (int) $tpl['record_count'] ?> record<?= (int) $tpl['record_count'] !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <?php if ($tpl['description']): ?>
                <p class="text-muted small mb-3"><?= e($tpl['description']) ?></p>
                <?php else: ?>
                <p class="text-muted small mb-3">&nbsp;</p>
                <?php endif; ?>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="<?= url('/dns/apply-template?template_id=' . $tpl['id']) ?>"
                   class="btn btn-sm btn-primary flex-fill">
                    <i class="ti ti-wand me-1"></i>Apply
                </a>
                <a href="<?= url('/dns/templates/' . $tpl['id']) ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-eye me-1"></i>View
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Custom templates ───────────────────────────────────────────────────── -->
<?php if (!empty($customs)): ?>
<h4 class="mb-3 text-muted fw-normal">Custom Templates</h4>
<div class="row g-3">
    <?php foreach ($customs as $tpl): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <h4 class="card-title mb-0"><?= e($tpl['name']) ?></h4>
                    <span class="badge bg-secondary-lt text-secondary ms-2">
                        <?= (int) $tpl['record_count'] ?> record<?= (int) $tpl['record_count'] !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <?php if ($tpl['description']): ?>
                <p class="text-muted small mb-3"><?= e($tpl['description']) ?></p>
                <?php else: ?>
                <p class="text-muted small mb-3 text-italic">No description.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer d-flex gap-2">
                <a href="<?= url('/dns/apply-template?template_id=' . $tpl['id']) ?>"
                   class="btn btn-sm btn-primary flex-fill">
                    <i class="ti ti-wand me-1"></i>Apply
                </a>
                <a href="<?= url('/dns/templates/' . $tpl['id']) ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-eye"></i>
                </a>
                <a href="<?= url('/dns/templates/' . $tpl['id'] . '/edit') ?>"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-pencil"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php elseif (!empty($builtins)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-4">
        <i class="ti ti-plus fs-2 d-block mb-1 opacity-50"></i>
        No custom templates yet.
        <a href="<?= url('/dns/templates/create') ?>">Create one.</a>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>
