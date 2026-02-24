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

<div class="row">
    <div class="col-lg-8">

        <form method="post" action="<?= url('/bookmarks/store') ?>"
              enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- ── Details ──────────────────────────────────────────────────── -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Bookmark Set Details</h3>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($v('name')) ?>"
                               placeholder="e.g. Work Bookmarks — Chrome 2026"
                               maxlength="255" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Browser</label>
                        <select name="browser" class="form-select">
                            <?php foreach (\App\Models\BookmarkSetModel::BROWSERS as $key => $label): ?>
                            <option value="<?= $key ?>"
                                <?= ($v('browser', 'chrome') === $key) ? 'selected' : '' ?>>
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
                                  placeholder="Notes about this bookmark set…"><?= e($v('description')) ?></textarea>
                    </div>

                </div>
            </div>

            <!-- ── Import file (optional) ───────────────────────────────────── -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Import Bookmarks <span class="text-muted fw-normal">(optional)</span></h3>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        Export your bookmarks from your browser as an HTML file and upload it here.
                        Supports Chrome, Edge, Firefox, and Safari export formats.
                    </p>
                    <input type="file" name="import_file" class="form-control"
                           accept=".html,.htm">
                    <div class="form-hint mt-1">
                        In Chrome/Edge: Bookmarks → Manage Bookmarks → ⋮ → Export bookmarks
                    </div>
                </div>
            </div>

            <!-- ── Actions ──────────────────────────────────────────────────── -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-bookmark-plus me-1"></i>Create Set
                </button>
                <a href="<?= url('/bookmarks') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>

    </div>
</div>
