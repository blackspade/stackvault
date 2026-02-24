<?php
/**
 * Vars: $app[], $addonBadges[], $descHtml, $changeHtml
 */
$manifest  = $app['manifest'];
$title     = $manifest['title'];
$tagline   = $manifest['tagline'] ?? '';
$version   = $manifest['upstreamVersion'] ?? $manifest['version'] ?? '';
$website   = $manifest['website'] ?? '';
$docUrl    = $manifest['documentationUrl'] ?? '';
$forumUrl  = $manifest['forumUrl'] ?? '';
$author    = $manifest['author'] ?? '';
$featured  = $app['featured'] ?? false;
$tags      = $manifest['tags'] ?? [];
$memLimit  = $manifest['memoryLimit'] ?? 0;
$httpPort  = $manifest['httpPort'] ?? 0;

// Return-to for back link and "Create Application" button
$returnTo  = trim((string) ($_GET['return_to'] ?? ''));
$backUrl   = $returnTo ?: url('/app-catalog');

// "Create Application" respects return_to context
if ($returnTo) {
    // Coming from a create/edit form — go back to form with catalog_id
    $createUrl = $returnTo
        . (str_contains($returnTo, '?') ? '&' : '?')
        . 'catalog_id=' . urlencode($app['id']);
} else {
    $createUrl = url('/applications/create?catalog_id=' . urlencode($app['id']));
}

$iconUrl = url('/app-icon/' . rawurlencode($title));
?>

<!-- ── Hero header ────────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start gap-4 flex-wrap">

            <!-- App icon -->
            <img src="<?= $iconUrl ?>" width="88" height="88" class="rounded-2 flex-shrink-0"
                 alt="<?= e($title) ?>"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'88\' height=\'88\' viewBox=\'0 0 88 88\'%3E%3Crect width=\'88\' height=\'88\' rx=\'12\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'55%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' font-size=\'36\' fill=\'%23999\'%3E<?= urlencode(mb_substr($title, 0, 1)) ?>%3C/text%3E%3C/svg%3E'">

            <!-- App meta -->
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h2 class="mb-0"><?= e($title) ?></h2>
                    <?php if ($version): ?>
                    <span class="badge bg-blue-lt text-blue fs-6">v<?= e($version) ?></span>
                    <?php endif; ?>
                    <?php if ($featured): ?>
                    <span class="badge bg-yellow-lt text-yellow">
                        <i class="ti ti-star-filled me-1"></i>Featured
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($tagline): ?>
                <p class="text-muted fs-5 mb-2"><?= e($tagline) ?></p>
                <?php endif; ?>

                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                <div class="mb-2 d-flex flex-wrap gap-1">
                    <?php foreach ($tags as $tag): ?>
                    <a href="<?= url('/app-catalog?tag=' . urlencode($tag)) ?>"
                       class="badge bg-secondary-lt text-secondary text-decoration-none">
                        <?= e($tag) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Author -->
                <?php if ($author): ?>
                <div class="text-muted small mb-2">
                    <i class="ti ti-user me-1"></i>by <?= e($author) ?>
                </div>
                <?php endif; ?>

                <!-- Links -->
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php if ($website): ?>
                    <a href="<?= e($website) ?>" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-world me-1"></i>Website
                    </a>
                    <?php endif; ?>
                    <?php if ($docUrl): ?>
                    <a href="<?= e($docUrl) ?>" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-book me-1"></i>Documentation
                    </a>
                    <?php endif; ?>
                    <?php if ($forumUrl): ?>
                    <a href="<?= e($forumUrl) ?>" target="_blank" rel="noopener"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-message-circle me-1"></i>Forum
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Primary action -->
            <div class="flex-shrink-0 d-flex flex-column gap-2 text-center">
                <a href="<?= $createUrl ?>" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Create Application
                </a>
                <a href="<?= $backUrl ?>" class="btn btn-ghost-secondary btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Back to catalog
                </a>
            </div>

        </div><!-- /.d-flex -->
    </div><!-- /.card-body -->
</div>

<!-- ── Details grid ────────────────────────────────────────────────────────── -->
<div class="row g-4 mb-4">

    <!-- Integrations / Addons -->
    <?php if (!empty($addonBadges)): ?>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title"><i class="ti ti-plug me-1"></i>Integrations</h4>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($addonBadges as $badge): ?>
                    <span class="badge bg-blue-lt text-blue py-2 px-3 fs-6">
                        <i class="ti <?= e($badge['icon']) ?> me-1"></i>
                        <?= e($badge['label']) ?>
                        <?php if ($badge['optional']): ?>
                        <span class="opacity-60 small">(optional)</span>
                        <?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Technical specs -->
    <div class="col-md-<?= !empty($addonBadges) ? '6' : '12' ?>">
        <div class="card h-100">
            <div class="card-header">
                <h4 class="card-title"><i class="ti ti-info-circle me-1"></i>Specs</h4>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <?php if ($memLimit): ?>
                    <dt class="col-sm-5 text-muted fw-normal">Min Memory</dt>
                    <dd class="col-sm-7"><?= \App\Services\AppCatalogService::formatBytes($memLimit) ?></dd>
                    <?php endif; ?>

                    <?php if ($httpPort): ?>
                    <dt class="col-sm-5 text-muted fw-normal">HTTP Port</dt>
                    <dd class="col-sm-7 font-monospace"><?= (int) $httpPort ?></dd>
                    <?php endif; ?>

                    <?php if ($manifest['minBoxVersion'] ?? ''): ?>
                    <dt class="col-sm-5 text-muted fw-normal">Min Cloudron</dt>
                    <dd class="col-sm-7"><?= e($manifest['minBoxVersion']) ?></dd>
                    <?php endif; ?>

                    <?php if ($manifest['optionalSso'] ?? false): ?>
                    <dt class="col-sm-5 text-muted fw-normal">SSO</dt>
                    <dd class="col-sm-7"><span class="badge bg-success-lt text-success">Optional</span></dd>
                    <?php endif; ?>

                    <?php if ($manifest['dockerImage'] ?? ''): ?>
                    <dt class="col-sm-5 text-muted fw-normal">Docker Image</dt>
                    <dd class="col-sm-7 font-monospace small text-truncate"
                        title="<?= e($manifest['dockerImage']) ?>">
                        <?= e(basename($manifest['dockerImage'])) ?>
                    </dd>
                    <?php endif; ?>

                    <?php if ($app['publishedAt'] ?? ''): ?>
                    <dt class="col-sm-5 text-muted fw-normal">Published</dt>
                    <dd class="col-sm-7 text-muted small"><?= e(substr($app['publishedAt'], 0, 10)) ?></dd>
                    <?php endif; ?>

                    <?php if ($app['releaseState'] ?? ''): ?>
                    <dt class="col-sm-5 text-muted fw-normal">Release</dt>
                    <dd class="col-sm-7">
                        <span class="badge <?= $app['releaseState'] === 'stable'
                            ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' ?>">
                            <?= e(ucfirst($app['releaseState'])) ?>
                        </span>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

</div><!-- /.row -->

<!-- ── About ──────────────────────────────────────────────────────────────── -->
<?php if ($descHtml): ?>
<div class="card mb-4">
    <div class="card-header">
        <h4 class="card-title"><i class="ti ti-file-description me-1"></i>About</h4>
    </div>
    <div class="card-body catalog-prose">
        <?= $descHtml ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Changelog (collapsible) ────────────────────────────────────────────── -->
<?php if ($changeHtml): ?>
<div class="card mb-4">
    <div class="card-header" role="button"
         data-bs-toggle="collapse" data-bs-target="#changelog-body"
         aria-expanded="false" style="cursor:pointer">
        <h4 class="card-title mb-0">
            <i class="ti ti-git-merge me-1"></i>Changelog
            <i class="ti ti-chevron-down ms-auto float-end mt-1" id="change-icon"></i>
        </h4>
    </div>
    <div class="collapse" id="changelog-body">
        <div class="card-body catalog-prose">
            <?= $changeHtml ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Post-install message ────────────────────────────────────────────────── -->
<?php if ($manifest['postInstallMessage'] ?? ''): ?>
<div class="card mb-4 border-info">
    <div class="card-header bg-info-lt">
        <h4 class="card-title text-info">
            <i class="ti ti-info-circle me-1"></i>Post-Install Notes
        </h4>
    </div>
    <div class="card-body catalog-prose">
        <?= \App\Services\AppCatalogService::markdownToHtml($manifest['postInstallMessage']) ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Footer action ──────────────────────────────────────────────────────── -->
<div class="card bg-blue-lt border-0">
    <div class="card-body d-flex align-items-center gap-3">
        <img src="<?= $iconUrl ?>" width="40" height="40" class="rounded flex-shrink-0" alt=""
             onerror="this.style.display='none'">
        <div class="flex-grow-1">
            <strong><?= e($title) ?></strong>
            <?php if ($tagline): ?>
            <div class="text-muted small"><?= e($tagline) ?></div>
            <?php endif; ?>
        </div>
        <a href="<?= $createUrl ?>" class="btn btn-primary flex-shrink-0">
            <i class="ti ti-plus me-1"></i>Create Application
        </a>
    </div>
</div>

<style>
.catalog-prose { line-height: 1.7; color: var(--tblr-body-color); }
.catalog-prose h4, .catalog-prose h5, .catalog-prose h6 { color: var(--tblr-heading-color); }
.catalog-prose ul { padding-left: 1.25rem; }
.catalog-prose code { font-size: .85em; }
.catalog-prose a { color: var(--tblr-primary); }
</style>

<script>
// Rotate chevron when changelog is toggled
document.querySelector('[data-bs-target="#changelog-body"]')
    ?.addEventListener('click', function () {
        const icon = document.getElementById('change-icon');
        if (!icon) return;
        const expanded = this.getAttribute('aria-expanded') === 'true';
        icon.style.transform = expanded ? 'rotate(0deg)' : 'rotate(-180deg)';
        icon.style.transition = 'transform .2s ease';
    });
</script>
