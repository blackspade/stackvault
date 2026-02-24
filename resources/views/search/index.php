<?php
/**
 * Vars: $q, $tooShort, $sections[], $total
 */
?>

<?php if ($q === ''): ?>
<!-- ── No query — prompt ──────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-search fs-1 d-block mb-2 opacity-50"></i>
        <p class="mb-1">Type in the search bar above to search across all records.</p>
        <p class="small mb-0">
            Searches clients, domains, servers, credentials, applications, databases,
            DNS records, email accounts, bookmarks, host files, and uploaded files.
        </p>
    </div>
</div>

<?php elseif ($tooShort): ?>
<!-- ── Query too short ────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-alert-circle fs-1 d-block mb-2 opacity-50"></i>
        Please enter at least 2 characters to search.
    </div>
</div>

<?php elseif (empty($sections)): ?>
<!-- ── No results ─────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-mood-empty fs-1 d-block mb-2 opacity-50"></i>
        No results found for <strong><?= e($q) ?></strong>.
    </div>
</div>

<?php else: ?>
<!-- ── Results ────────────────────────────────────────────────────────────── -->
<div class="mb-3 text-muted small">
    <strong class="text-body"><?= number_format($total) ?></strong>
    result<?= $total !== 1 ? 's' : '' ?> for
    <strong class="text-body"><?= e($q) ?></strong>
    across <?= count($sections) ?> section<?= count($sections) !== 1 ? 's' : '' ?>
</div>

<div class="row g-3">
    <?php foreach ($sections as $key => $section): ?>
    <div class="col-12">
        <div class="card">

            <!-- Section header -->
            <div class="card-header py-2">
                <div class="card-title d-flex align-items-center gap-2">
                    <i class="ti <?= e($section['icon']) ?> text-muted"></i>
                    <?= e($section['label']) ?>
                    <span class="badge bg-secondary-lt text-muted ms-1">
                        <?= count($section['results']) ?>
                        <?= count($section['results']) >= 10 ? '+' : '' ?>
                    </span>
                </div>
            </div>

            <!-- Results list -->
            <div class="list-group list-group-flush">
                <?php foreach ($section['results'] as $result): ?>
                <a href="<?= url($result['url']) ?>"
                   class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-3 py-2">

                    <div class="flex-fill overflow-hidden">
                        <div class="fw-medium text-truncate">
                            <?= e($result['title']) ?>
                        </div>
                        <?php if (!empty($result['subtitle'])): ?>
                        <div class="text-muted small text-truncate">
                            <?= e($result['subtitle']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <i class="ti ti-chevron-right text-muted flex-shrink-0" style="font-size:.8rem"></i>

                </a>
                <?php endforeach; ?>
            </div>

            <?php if (count($section['results']) >= 10): ?>
            <div class="card-footer text-muted small">
                Showing first 10 matches — refine your search to narrow results.
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>
