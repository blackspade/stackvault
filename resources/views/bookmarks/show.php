<?php
/**
 * Vars: $set[], $folders[], $byFolder[], $root[], $clients[], $activity[]
 */

$id          = (int) $set['id'];
$badgeClass  = \App\Models\BookmarkSetModel::browserBadgeClass($set['browser']);
$browserName = \App\Models\BookmarkSetModel::BROWSERS[$set['browser']] ?? ucfirst($set['browser']);
?>

<?php
// ── Page actions ─────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url("/bookmarks/{$id}/export") ?>" class="btn btn-outline-secondary">
    <i class="ti ti-download me-1"></i>Export
</a>
<a href="<?= url("/bookmarks/{$id}/edit") ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<form method="post" action="<?= url("/bookmarks/{$id}/delete") ?>" class="d-inline"
      onsubmit="return confirm('Delete this bookmark set and all its bookmarks?')">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-danger">
        <i class="ti ti-trash me-1"></i>Delete
    </button>
</form>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<div class="row g-3">

    <!-- ── Left: Set info ────────────────────────────────────────────────── -->
    <div class="col-lg-3">

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge <?= $badgeClass ?> mb-1"><?= e($browserName) ?></span>
                    <?php if ($set['client_name']): ?>
                    <div class="text-muted small mt-1">
                        <i class="ti ti-user me-1"></i>
                        <a href="<?= url('/clients/' . $set['client_id']) ?>" class="text-reset">
                            <?= e($set['client_name']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Folders</span>
                    <strong class="text-body"><?= (int) $set['folder_count'] ?></strong>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Bookmarks</span>
                    <strong class="text-body"><?= (int) $set['bookmark_count'] ?></strong>
                </div>

                <hr class="my-2">

                <div class="text-muted small">
                    Created <?= date('d M Y', strtotime($set['created_at'])) ?>
                </div>
                <?php if ($set['updated_at'] !== $set['created_at']): ?>
                <div class="text-muted small">
                    Updated <?= date('d M Y', strtotime($set['updated_at'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($set['description']): ?>
                <hr class="my-2">
                <div class="text-muted small"><?= e($set['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Import card -->
        <div class="card mb-3">
            <div class="card-header">
                <h4 class="card-title">Import Bookmarks</h4>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    Append bookmarks from a browser HTML export file.
                </p>
                <form method="post" action="<?= url("/bookmarks/{$id}/import") ?>"
                      enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="file" name="import_file" class="form-control form-control-sm mb-2"
                           accept=".html,.htm" required>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="ti ti-upload me-1"></i>Import
                    </button>
                </form>
            </div>
        </div>

        <!-- Activity card -->
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

    <!-- ── Right: Folders + Bookmarks ───────────────────────────────────── -->
    <div class="col-lg-9" id="folders">

        <!-- Add Folder -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="post" action="<?= url("/bookmarks/{$id}/folders/store") ?>"
                      class="row g-2 align-items-center">
                    <?= csrf_field() ?>
                    <div class="col">
                        <input type="text" name="folder_name" class="form-control form-control-sm"
                               placeholder="New folder name…" maxlength="255" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-folder-plus me-1"></i>Add Folder
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Folders -->
        <?php foreach ($folders as $folder): ?>
        <?php
            $fid     = (int) $folder['id'];
            $fbms    = $byFolder[$fid] ?? [];
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-folder text-yellow"></i>
                    <span class="fw-medium"><?= e($folder['name']) ?></span>
                    <span class="badge bg-secondary-lt text-muted"><?= count($fbms) ?></span>
                </div>
                <form method="post"
                      action="<?= url("/bookmarks/{$id}/folders/{$fid}/delete") ?>"
                      class="d-inline"
                      onsubmit="return confirm('Delete folder &quot;<?= e(addslashes($folder['name'])) ?>&quot;? Its bookmarks will become unfiled.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-ghost-danger" title="Delete folder">
                        <i class="ti ti-trash"></i>
                    </button>
                </form>
            </div>

            <?php if (empty($fbms)): ?>
            <div class="card-body text-muted small py-2">
                No bookmarks in this folder.
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($fbms as $bm): ?>
                <div class="list-group-item d-flex align-items-center gap-2 px-3 py-2">
                    <!-- Favicon -->
                    <?php if (!empty($bm['favicon'])): ?>
                    <img src="<?= e($bm['favicon']) ?>" width="16" height="16"
                         class="flex-shrink-0 rounded" alt="" onerror="this.style.display='none'">
                    <?php else: ?>
                    <i class="ti ti-bookmark text-muted flex-shrink-0" style="font-size:.85rem"></i>
                    <?php endif; ?>

                    <!-- Title + URL -->
                    <div class="flex-fill overflow-hidden">
                        <a href="<?= e($bm['url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="text-body fw-medium text-decoration-none text-truncate d-block">
                            <?= e($bm['title']) ?>
                        </a>
                        <div class="text-muted small text-truncate" style="font-size:.7rem">
                            <?= e($bm['url']) ?>
                        </div>
                    </div>

                    <!-- Delete -->
                    <form method="post"
                          action="<?= url("/bookmarks/{$id}/bookmarks/" . (int)$bm['id'] . "/delete") ?>"
                          class="flex-shrink-0"
                          onsubmit="return confirm('Delete this bookmark?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-ghost-danger p-1" title="Delete">
                            <i class="ti ti-x" style="font-size:.8rem"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- Unfiled bookmarks -->
        <?php if (!empty($root)): ?>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2 py-2">
                <i class="ti ti-bookmarks text-muted"></i>
                <span class="fw-medium">Unfiled</span>
                <span class="badge bg-secondary-lt text-muted"><?= count($root) ?></span>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($root as $bm): ?>
                <div class="list-group-item d-flex align-items-center gap-2 px-3 py-2">
                    <?php if (!empty($bm['favicon'])): ?>
                    <img src="<?= e($bm['favicon']) ?>" width="16" height="16"
                         class="flex-shrink-0 rounded" alt="" onerror="this.style.display='none'">
                    <?php else: ?>
                    <i class="ti ti-bookmark text-muted flex-shrink-0" style="font-size:.85rem"></i>
                    <?php endif; ?>

                    <div class="flex-fill overflow-hidden">
                        <a href="<?= e($bm['url']) ?>" target="_blank" rel="noopener noreferrer"
                           class="text-body fw-medium text-decoration-none text-truncate d-block">
                            <?= e($bm['title']) ?>
                        </a>
                        <div class="text-muted small text-truncate" style="font-size:.7rem">
                            <?= e($bm['url']) ?>
                        </div>
                    </div>

                    <form method="post"
                          action="<?= url("/bookmarks/{$id}/bookmarks/" . (int)$bm['id'] . "/delete") ?>"
                          class="flex-shrink-0"
                          onsubmit="return confirm('Delete this bookmark?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-ghost-danger p-1" title="Delete">
                            <i class="ti ti-x" style="font-size:.8rem"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($folders) && empty($root)): ?>
        <div class="card mb-3">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-bookmarks fs-1 d-block mb-2 opacity-50"></i>
                No bookmarks yet. Import a browser file or add bookmarks manually below.
            </div>
        </div>
        <?php endif; ?>

        <!-- Add Bookmark -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Add Bookmark</h4>
            </div>
            <div class="card-body">
                <form method="post" action="<?= url("/bookmarks/{$id}/bookmarks/store") ?>"
                      class="row g-2 align-items-end">
                    <?= csrf_field() ?>

                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Title <span class="text-muted">(optional)</span></label>
                        <input type="text" name="title" class="form-control form-control-sm"
                               placeholder="My Link" maxlength="500">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label form-label-sm required">URL</label>
                        <input type="url" name="url" class="form-control form-control-sm"
                               placeholder="https://example.com" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Folder</label>
                        <select name="folder_id" class="form-select form-select-sm">
                            <option value="">— Unfiled —</option>
                            <?php foreach ($folders as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div><!-- /col-lg-9 -->

</div><!-- /row -->
