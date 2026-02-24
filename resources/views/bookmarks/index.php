<?php
/**
 * Vars: $sets[], $clients[], $search, $filterClient
 */

$hasFilter = $search !== '' || $filterClient > 0;
?>

<?php
// ── Page actions ─────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/bookmarks/create') ?>" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>New Bookmark Set
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/bookmarks') ?>" class="row g-2 align-items-end">

            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name, description, client…"
                       value="<?= e($search) ?>">
            </div>

            <div class="col-md-4">
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

            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter">
                    <i class="ti ti-search"></i>
                </button>
                <?php if ($hasFilter): ?>
                <a href="<?= url('/bookmarks') ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- ── Sets list ───────────────────────────────────────────────────────────── -->
<?php if (empty($sets)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-bookmark fs-1 d-block mb-2 opacity-50"></i>
        <?= $hasFilter ? 'No bookmark sets match your filters.' : 'No bookmark sets yet.' ?>
        <?php if (!$hasFilter): ?>
        <div class="mt-3">
            <a href="<?= url('/bookmarks/create') ?>" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>Create First Set
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th style="width:130px">Browser</th>
                    <th style="width:160px">Client</th>
                    <th style="width:80px" class="text-center">Folders</th>
                    <th style="width:90px" class="text-center">Bookmarks</th>
                    <th style="width:130px">Created</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sets as $s): ?>
            <?php $badgeClass = \App\Models\BookmarkSetModel::browserBadgeClass($s['browser']); ?>
            <tr>
                <td>
                    <a href="<?= url('/bookmarks/' . $s['id']) ?>" class="fw-medium">
                        <?= e($s['name']) ?>
                    </a>
                    <?php if ($s['description']): ?>
                    <div class="text-muted small text-truncate" style="max-width:280px">
                        <?= e($s['description']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $badgeClass ?>">
                        <?= e(\App\Models\BookmarkSetModel::BROWSERS[$s['browser']] ?? ucfirst($s['browser'])) ?>
                    </span>
                </td>
                <td class="text-muted small">
                    <?php if ($s['client_name']): ?>
                    <a href="<?= url('/clients/' . $s['client_id']) ?>" class="text-reset">
                        <?= e($s['client_name']) ?>
                    </a>
                    <?php else: ?>
                    <span class="opacity-50">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center text-muted">
                    <?= (int) $s['folder_count'] ?>
                </td>
                <td class="text-center text-muted">
                    <?= (int) $s['bookmark_count'] ?>
                </td>
                <td class="text-muted small">
                    <?= date('d M Y', strtotime($s['created_at'])) ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('/bookmarks/' . $s['id']) ?>"
                       class="btn btn-sm btn-ghost-secondary" title="View">
                        <i class="ti ti-eye"></i>
                    </a>
                    <a href="<?= url('/bookmarks/' . $s['id'] . '/edit') ?>"
                       class="btn btn-sm btn-ghost-secondary" title="Edit">
                        <i class="ti ti-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
