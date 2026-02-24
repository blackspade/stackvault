<?php
/**
 * Vars: $apps[], $allTags[], $total, $search, $filterTag
 */
$returnTo = $_GET['return_to'] ?? '';
?>

<!-- ── Search + filter bar ────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/app-catalog') ?>" class="row g-2 align-items-center">
            <?php if ($returnTo): ?>
            <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
            <?php endif; ?>

            <div class="col-md-5">
                <input type="text" name="q" class="form-control"
                       placeholder="Search apps by name, category, tag…"
                       value="<?= e($search) ?>" autofocus>
            </div>

            <!-- Tag filter — show top 20 most common tags -->
            <div class="col-md-4">
                <select name="tag" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach (array_slice($allTags, 0, 30) as $tag): ?>
                    <option value="<?= e($tag) ?>" <?= $filterTag === $tag ? 'selected' : '' ?>>
                        <?= e(ucfirst($tag)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
                <?php if ($search || $filterTag): ?>
                <a href="<?= url('/app-catalog' . ($returnTo ? '?return_to=' . urlencode($returnTo) : '')) ?>"
                   class="btn btn-outline-secondary" title="Clear filters">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ── Results summary ────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted small">
        Showing <strong><?= count($apps) ?></strong>
        <?= (count($apps) < $total) ? "of <strong>{$total}</strong>" : '' ?>
        apps
        <?php if ($filterTag): ?>
        in category <strong><?= e($filterTag) ?></strong>
        <?php endif; ?>
        <?php if ($search): ?>
        matching <strong>"<?= e($search) ?>"</strong>
        <?php endif; ?>
    </div>
    <?php if ($returnTo): ?>
    <a href="<?= e($returnTo) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Back
    </a>
    <?php endif; ?>
</div>

<!-- ── App grid ────────────────────────────────────────────────────────────── -->
<?php if (empty($apps)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-search-off fs-1 d-block mb-2 opacity-50"></i>
        No apps found matching your search.
    </div>
</div>
<?php else: ?>
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3">
    <?php foreach ($apps as $app): ?>
    <?php
        $manifest  = $app['manifest'];
        $title     = $manifest['title'];
        $tagline   = $manifest['tagline'] ?? '';
        $tags      = array_slice($manifest['tags'] ?? [], 0, 3);
        $ver       = $manifest['upstreamVersion'] ?? $manifest['version'] ?? '';
        $featured  = $app['featured'] ?? false;
        $iconUrl   = url('/app-icon/' . rawurlencode($title));
        $detailUrl = url('/app-catalog/' . rawurlencode($app['id']));

        // Return-to for "Create Application" button on detail page
        $createUrl = url('/applications/create?catalog_id=' . urlencode($app['id']));
        if ($returnTo) {
            $createUrl = $returnTo . (str_contains($returnTo, '?') ? '&' : '?')
                       . 'catalog_id=' . urlencode($app['id']);
        }
    ?>
    <div class="col">
        <a href="<?= $detailUrl ?><?= $returnTo ? '?return_to=' . urlencode($returnTo) : '' ?>"
           class="card h-100 text-decoration-none text-reset app-card"
           style="transition: box-shadow .15s ease, transform .15s ease;">
            <div class="card-body d-flex flex-column align-items-center text-center p-3">
                <img src="<?= $iconUrl ?>" width="56" height="56" class="rounded mb-2 flex-shrink-0"
                     alt="<?= e($title) ?>"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'56\' height=\'56\' viewBox=\'0 0 56 56\'%3E%3Crect width=\'56\' height=\'56\' rx=\'8\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'55%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'20\' fill=\'%23999\'%3E<?= urlencode(mb_substr($title, 0, 1)) ?>%3C/text%3E%3C/svg%3E'">

                <div class="fw-semibold small text-truncate w-100 mb-1" title="<?= e($title) ?>">
                    <?= e($title) ?>
                    <?php if ($featured): ?>
                    <i class="ti ti-star-filled text-yellow" style="font-size:.7em"></i>
                    <?php endif; ?>
                </div>

                <?php if ($tagline): ?>
                <div class="text-muted" style="font-size:.7rem; line-height:1.3;
                     display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
                     overflow:hidden; max-height:2.6em;">
                    <?= e($tagline) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($tags)): ?>
                <div class="mt-auto pt-2 d-flex flex-wrap justify-content-center gap-1">
                    <?php foreach ($tags as $tag): ?>
                    <span class="badge bg-secondary-lt text-secondary" style="font-size:.6rem">
                        <?= e($tag) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.app-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    transform: translateY(-2px);
    text-decoration: none;
}
</style>
