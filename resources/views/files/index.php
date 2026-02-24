<?php
/**
 * Vars: $files[], $clients[], $search, $filterClient, $filterExt
 */

$hasFilter = $search !== '' || $filterClient > 0 || $filterExt !== '';

// Modal: track which file's description is being edited
$editId = (int) ($_GET['edit'] ?? 0);
?>

<!-- ── Upload form ─────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="ti ti-upload me-2 text-muted"></i>Upload File</h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('/files/upload') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">

                <div class="col-md-3">
                    <label class="form-label required form-label-sm">Client</label>
                    <select name="client_id" class="form-select form-select-sm" required>
                        <option value="">— Select Client —</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"
                            <?= $filterClient === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label form-label-sm">
                        File
                        <span class="text-danger ms-1"
                              data-bs-toggle="tooltip"
                              data-bs-placement="top"
                              title="Allowed: zip, 7z, rar, tar, gz, tgz, sql — max 512 MB"
                              style="cursor:help">*</span>
                    </label>
                    <input type="file" name="file" class="form-control form-control-sm" required
                           accept=".zip,.7z,.rar,.tar,.gz,.tgz,.sql">
                </div>

                <div class="col-md-3">
                    <label class="form-label form-label-sm">Description <span class="text-muted">(optional)</span></label>
                    <input type="text" name="description" class="form-control form-control-sm"
                           placeholder="e.g. Pre-migration full backup"
                           maxlength="500">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="ti ti-upload me-1"></i>Upload
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/files') ?>" class="row g-2 align-items-end">

            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search filename, description, client…"
                       value="<?= e($search) ?>">
            </div>

            <div class="col-md-3">
                <select name="client_id" class="form-select form-select-sm">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        <?= $filterClient === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="ext" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach (\App\Models\ClientFileModel::ALLOWED_EXTENSIONS as $e): ?>
                    <option value="<?= $e ?>" <?= $filterExt === $e ? 'selected' : '' ?>>.<?= $e ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="ti ti-search"></i>
                </button>
                <?php if ($hasFilter): ?>
                <a href="<?= url('/files') ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- ── File list ───────────────────────────────────────────────────────────── -->
<?php if (empty($files)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-archive fs-1 d-block mb-2 opacity-50"></i>
        <?= $hasFilter ? 'No files match your filters.' : 'No files uploaded yet.' ?>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th style="width:55px">Type</th>
                    <th>Filename</th>
                    <th style="width:160px">Client</th>
                    <th style="width:90px" class="text-end">Size</th>
                    <th style="width:130px">Uploaded</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($files as $file): ?>
            <?php $badgeClass = \App\Models\ClientFileModel::extensionBadgeClass($file['extension']); ?>
            <tr>
                <!-- Type badge -->
                <td>
                    <span class="badge <?= $badgeClass ?>">.<?= e($file['extension']) ?></span>
                </td>

                <!-- Filename + description -->
                <td>
                    <div class="fw-medium text-truncate" style="max-width:320px"
                         title="<?= e($file['filename']) ?>">
                        <?= e($file['filename']) ?>
                    </div>
                    <?php if ($file['description']): ?>
                    <div class="text-muted small text-truncate" style="max-width:320px">
                        <?= e($file['description']) ?>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Client -->
                <td class="text-muted small">
                    <a href="<?= url('/clients/' . $file['client_id']) ?>" class="text-reset">
                        <?= e($file['client_name']) ?>
                    </a>
                </td>

                <!-- Size -->
                <td class="text-muted small text-end font-monospace">
                    <?= \App\Models\ClientFileModel::formatBytes((int) $file['file_size']) ?>
                </td>

                <!-- Date + uploader -->
                <td class="text-muted small">
                    <?= date('d M Y', strtotime($file['created_at'])) ?>
                    <?php if ($file['uploader']): ?>
                    <div style="font-size:.7rem;opacity:.6"><?= e($file['uploader']) ?></div>
                    <?php endif; ?>
                </td>

                <!-- Actions -->
                <td class="text-end">
                    <!-- Edit description toggle -->
                    <button type="button"
                            class="btn btn-sm btn-ghost-secondary"
                            title="Edit description"
                            data-bs-toggle="collapse"
                            data-bs-target="#desc-<?= (int) $file['id'] ?>">
                        <i class="ti ti-pencil"></i>
                    </button>

                    <!-- Download -->
                    <a href="<?= url('/files/' . (int) $file['id'] . '/download') ?>"
                       class="btn btn-sm btn-ghost-secondary" title="Download">
                        <i class="ti ti-download"></i>
                    </a>

                    <!-- Delete -->
                    <form method="post"
                          action="<?= url('/files/' . (int) $file['id'] . '/delete') ?>"
                          class="d-inline"
                          onsubmit="return confirm('Delete &quot;<?= e(addslashes($file['filename'])) ?>&quot;? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-ghost-danger" title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <!-- Inline description edit row -->
            <tr class="collapse" id="desc-<?= (int) $file['id'] ?>">
                <td colspan="6" class="bg-muted-lt py-2 px-3">
                    <form method="post"
                          action="<?= url('/files/' . (int) $file['id'] . '/update') ?>"
                          class="d-flex gap-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="flex-fill">
                            <label class="form-label form-label-sm">Description</label>
                            <input type="text" name="description" class="form-control form-control-sm"
                                   value="<?= e($file['description']) ?>"
                                   placeholder="Optional description…"
                                   maxlength="500">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        <button type="button" class="btn btn-sm btn-ghost-secondary"
                                data-bs-toggle="collapse"
                                data-bs-target="#desc-<?= (int) $file['id'] ?>">Cancel</button>
                    </form>
                </td>
            </tr>

            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer text-muted small">
        <?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?>
        <?php if ($hasFilter): ?>— filtered<?php endif; ?>
        &mdash;
        Total: <?= \App\Models\ClientFileModel::formatBytes(array_sum(array_column($files, 'file_size'))) ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});
</script>
