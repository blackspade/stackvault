<?php
/**
 * Vars: $machine[], $activity[]
 */

$id          = (int) $machine['id'];
$badgeClass  = \App\Models\HostMachineModel::osBadgeClass($machine['os']);
$osLabel     = \App\Models\HostMachineModel::OS_TYPES[$machine['os']] ?? ucfirst($machine['os']);
$hostsPath   = \App\Models\HostMachineModel::OS_HOSTS_PATH[$machine['os']] ?? '/etc/hosts';
$hasContent  = !empty($machine['hosts_file']);
$lineCount   = $hasContent ? substr_count($machine['hosts_file'], "\n") + 1 : 0;
?>

<?php
ob_start(); ?>
<a href="<?= url("/hosts/{$id}/edit") ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<form method="post" action="<?= url("/hosts/{$id}/delete") ?>" class="d-inline"
      onsubmit="return confirm('Delete host file for &quot;<?= e(addslashes($machine['name'])) ?>&quot;?')">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-danger">
        <i class="ti ti-trash me-1"></i>Delete
    </button>
</form>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<div class="row g-3">

    <!-- ── Left: machine info ────────────────────────────────────────────── -->
    <div class="col-lg-3">

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-2">
                    <span class="badge <?= $badgeClass ?>"><?= e($osLabel) ?></span>
                </div>

                <?php if ($machine['client_name']): ?>
                <div class="text-muted small mt-2">
                    <i class="ti ti-user me-1"></i>
                    <a href="<?= url('/clients/' . $machine['client_id']) ?>" class="text-reset">
                        <?= e($machine['client_name']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <hr class="my-2">

                <div class="text-muted small mb-1">
                    <span class="font-monospace text-break"><?= e($hostsPath) ?></span>
                </div>

                <hr class="my-2">

                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Lines</span>
                    <strong class="text-body"><?= $hasContent ? number_format($lineCount) : '—' ?></strong>
                </div>
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Size</span>
                    <strong class="text-body">
                        <?= $hasContent ? number_format(strlen($machine['hosts_file'])) . ' B' : '—' ?>
                    </strong>
                </div>

                <hr class="my-2">

                <div class="text-muted small">
                    Created <?= date('d M Y', strtotime($machine['created_at'])) ?>
                </div>
                <?php if ($machine['updated_at'] !== $machine['created_at']): ?>
                <div class="text-muted small">
                    Saved <?= date('d M Y H:i', strtotime($machine['updated_at'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($machine['description']): ?>
                <hr class="my-2">
                <div class="text-muted small"><?= e($machine['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activity -->
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

    <!-- ── Right: hosts file viewer ──────────────────────────────────────── -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="card-title">
                    Hosts File
                    <?php if (!$hasContent): ?>
                    <span class="text-muted fw-normal ms-2">— no content saved yet</span>
                    <?php endif; ?>
                </div>
                <?php if ($hasContent): ?>
                <div class="d-flex gap-2">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary copy-btn"
                            data-copy="hostsFileContent"
                            title="Copy to clipboard">
                        <i class="ti ti-copy me-1"></i>Copy
                    </button>
                    <a href="<?= url("/hosts/{$id}/edit") ?>" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-pencil me-1"></i>Edit
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($hasContent): ?>
            <script>
            // Embed raw text safely as a JS string for the viewer
            window.__hostsRawText = <?= json_encode($machine['hosts_file'] ?? '') ?>;
            </script>
            <?php endif; ?>

            <?php if (!$hasContent): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-file-text fs-1 d-block mb-2 opacity-50"></i>
                No hosts file content saved yet.
                <div class="mt-3">
                    <a href="<?= url("/hosts/{$id}/edit") ?>" class="btn btn-primary btn-sm">
                        <i class="ti ti-pencil me-1"></i>Add Content
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Search bar -->
            <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2" style="background:#f8f9fa">
                <div class="input-group input-group-sm" style="max-width:380px">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="ti ti-search text-muted" style="font-size:.85rem"></i>
                    </span>
                    <input id="hostsSearch"
                           type="text"
                           class="form-control border-start-0 ps-0"
                           placeholder="Search hosts file…"
                           autocomplete="off"
                           spellcheck="false">
                    <button id="hostsClear" class="btn btn-outline-secondary d-none" type="button" title="Clear search">
                        <i class="ti ti-x" style="font-size:.8rem"></i>
                    </button>
                </div>
                <button id="hostsPrev" class="btn btn-sm btn-outline-secondary d-none" type="button" title="Previous match">
                    <i class="ti ti-chevron-up"></i>
                </button>
                <button id="hostsNext" class="btn btn-sm btn-outline-secondary d-none" type="button" title="Next match">
                    <i class="ti ti-chevron-down"></i>
                </button>
                <span id="hostsMatchCount" class="text-muted small d-none"></span>
            </div>
            <div class="card-body p-0">
                <pre id="hostsFileContent"
                     class="m-0 p-3 font-monospace"
                     style="background:#1e1e2e;color:#cdd6f4;font-size:.8rem;white-space:pre;overflow-x:auto;overflow-y:auto;height:calc(100vh - 260px);min-height:300px;border-radius:0 0 var(--tblr-card-border-radius) var(--tblr-card-border-radius)"></pre>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between text-muted small">
                <span><?= number_format($lineCount) ?> line<?= $lineCount !== 1 ? 's' : '' ?></span>
                <span class="font-monospace"><?= e($hostsPath) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div><!-- /col-lg-9 -->

</div><!-- /row -->

<?php if ($hasContent): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Viewer init ────────────────────────────────────────────────────────
    const pre       = document.getElementById('hostsFileContent');
    const rawText   = window.__hostsRawText || '';

    // Escape HTML entities for safe innerHTML rendering
    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Render plain escaped content on load
    pre.innerHTML = escapeHtml(rawText);

    // ── Copy button ────────────────────────────────────────────────────────
    document.querySelectorAll('.copy-btn[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const origHtml = btn.innerHTML;
            navigator.clipboard.writeText(rawText).then(function () {
                btn.innerHTML = '<i class="ti ti-check me-1"></i>Copied!';
                btn.classList.add('btn-success');
                btn.classList.remove('btn-outline-secondary');
                setTimeout(function () {
                    btn.innerHTML = origHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(function () {
                btn.innerHTML = '<i class="ti ti-x me-1"></i>Failed';
                setTimeout(function () { btn.innerHTML = origHtml; }, 2000);
            });
        });
    });

    // ── Search ─────────────────────────────────────────────────────────────
    const searchInput  = document.getElementById('hostsSearch');
    const clearBtn     = document.getElementById('hostsClear');
    const prevBtn      = document.getElementById('hostsPrev');
    const nextBtn      = document.getElementById('hostsNext');
    const matchCounter = document.getElementById('hostsMatchCount');

    let marks        = [];
    let currentMatch = 0;
    let debounceTimer;

    function applySearch(query) {
        if (!query) {
            pre.innerHTML = escapeHtml(rawText);
            marks = [];
            updateUI(false);
            return;
        }

        // Split raw text by query with a capturing group — odd indices are matches
        const escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex        = new RegExp('(' + escapedQuery + ')', 'gi');
        const parts        = rawText.split(regex);

        let html     = '';
        let matchIdx = 0;
        parts.forEach(function (part, i) {
            if (i % 2 === 1) {
                // Odd index = captured match
                html += '<mark id="hm' + matchIdx + '" style="background:#f9e04b;color:#1e1e2e;border-radius:2px">'
                      + escapeHtml(part) + '</mark>';
                matchIdx++;
            } else {
                html += escapeHtml(part);
            }
        });

        pre.innerHTML = html;
        marks         = pre.querySelectorAll('mark');
        currentMatch  = 0;

        updateUI(marks.length > 0);
        if (marks.length) scrollToMatch(0);
    }

    function scrollToMatch(idx) {
        if (!marks.length) return;
        currentMatch = (idx + marks.length) % marks.length;
        marks.forEach(function (m) { m.style.outline = ''; });
        const active = marks[currentMatch];
        active.style.outline = '2px solid #f9e04b';
        active.scrollIntoView({ block: 'center', behavior: 'smooth' });
        matchCounter.textContent = (currentMatch + 1) + ' / ' + marks.length;
    }

    function updateUI(hasMatches) {
        const hasQuery = searchInput.value.length > 0;
        clearBtn.classList.toggle('d-none', !hasQuery);
        prevBtn.classList.toggle('d-none', !hasMatches);
        nextBtn.classList.toggle('d-none', !hasMatches);
        matchCounter.classList.toggle('d-none', !hasMatches);
        if (!hasMatches && hasQuery) {
            matchCounter.textContent = 'No matches';
            matchCounter.classList.remove('d-none');
        }
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            applySearch(searchInput.value.trim());
        }, 150);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (!marks.length) return;
        if (e.key === 'Enter') {
            e.preventDefault();
            scrollToMatch(e.shiftKey ? currentMatch - 1 : currentMatch + 1);
        }
    });

    prevBtn.addEventListener('click', function () { scrollToMatch(currentMatch - 1); });
    nextBtn.addEventListener('click', function () { scrollToMatch(currentMatch + 1); });

    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        applySearch('');
        searchInput.focus();
    });

});
</script>
<?php endif; ?>
