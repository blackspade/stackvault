<?php
/**
 * Shared application create / edit form.
 *
 * Vars:
 *   $app         array  — current field values (edit) or old-input / pre-fills (create)
 *   $catalogApp  ?array — catalog entry if one is linked; null otherwise
 *   $clients     array  — ClientModel::getForSelect()
 *   $servers     array  — ServerModel::getForSelect()
 *   $domains     array  — DomainModel::getForSelect()
 *   $action      string — form POST URL
 *   $submitLabel string — button label
 *   $returnUrl   string — "Browse Catalog" return target (for back-link after catalog selection)
 */
$v         = $app ?? [];
$returnUrl = $returnUrl ?? url('/applications/create');
$sel       = fn(string $k, mixed $cmp): string
    => (string) ($v[$k] ?? '') === (string) $cmp ? 'selected' : '';
$val       = fn(string $k, mixed $def = ''): mixed => $v[$k] ?? $def;
?>

<?php if ($catalogApp): ?>
<!-- ── Catalog app banner ────────────────────────────────────────────────── -->
<div class="alert alert-info d-flex align-items-center gap-3 mb-4 catalog-banner" id="catalog-banner">
    <img src="<?= url('/app-icon/' . rawurlencode($catalogApp['manifest']['title'])) ?>"
         width="52" height="52" class="rounded flex-shrink-0"
         alt="<?= e($catalogApp['manifest']['title']) ?>"
         onerror="this.style.display='none'">
    <div class="flex-grow-1 min-w-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <strong class="fs-5"><?= e($catalogApp['manifest']['title']) ?></strong>
            <?php
                $ver = $catalogApp['manifest']['upstreamVersion']
                    ?? $catalogApp['manifest']['version']
                    ?? '';
            ?>
            <?php if ($ver): ?>
            <span class="badge bg-blue-lt text-blue">v<?= e($ver) ?></span>
            <?php endif; ?>
            <?php if ($catalogApp['featured'] ?? false): ?>
            <span class="badge bg-yellow-lt text-yellow"><i class="ti ti-star-filled me-1"></i>Featured</span>
            <?php endif; ?>
        </div>
        <?php if ($catalogApp['manifest']['tagline'] ?? ''): ?>
        <div class="text-muted small mt-1"><?= e($catalogApp['manifest']['tagline']) ?></div>
        <?php endif; ?>
        <?php
            $addonStr = \App\Services\AppCatalogService::getAddonString($catalogApp['manifest']['addons'] ?? []);
        ?>
        <?php if ($addonStr): ?>
        <div class="text-muted small"><i class="ti ti-plug me-1"></i><?= e($addonStr) ?></div>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-column gap-1 flex-shrink-0 text-end">
        <a href="<?= url('/app-catalog/' . rawurlencode($catalogApp['id'])) ?>"
           class="btn btn-sm btn-ghost-secondary" target="_blank" title="View catalog details">
            <i class="ti ti-info-circle me-1"></i>Details
        </a>
        <button type="button" class="btn btn-sm btn-ghost-secondary text-muted"
                onclick="svClearCatalog()" title="Remove catalog link and enter details manually">
            <i class="ti ti-unlink me-1"></i>Clear
        </button>
    </div>
</div>
<?php else: ?>
<!-- ── No catalog selection ─────────────────────────────────────────────── -->
<div class="mb-4 p-3 rounded border border-dashed d-flex align-items-center gap-3" id="no-catalog-hint">
    <i class="ti ti-apps fs-2 text-muted flex-shrink-0"></i>
    <div class="flex-grow-1">
        <div class="fw-medium">Select from App Catalog</div>
        <div class="text-muted small">Browse 374 pre-defined apps to auto-fill details, or fill in manually below.</div>
    </div>
    <a href="<?= url('/app-catalog?return_to=' . urlencode($returnUrl)) ?>"
       class="btn btn-outline-primary flex-shrink-0">
        <i class="ti ti-layout-grid me-1"></i>Browse Catalog
    </a>
</div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="catalog_id" id="inp-catalog-id"
           value="<?= e($catalogApp['id'] ?? ($val('catalog_id'))) ?>">

    <div class="row g-3">

        <!-- ── App Name ──────────────────────────────────────────────────── -->
        <div class="col-md-6">
            <label class="form-label required">Application Name</label>
            <input type="text" name="app_name" id="inp-app-name" class="form-control"
                   value="<?= e($val('app_name', $catalogApp['manifest']['title'] ?? '')) ?>"
                   placeholder="e.g. BookStack, Ghost, Custom App…"
                   required maxlength="255">
        </div>

        <!-- ── Version ───────────────────────────────────────────────────── -->
        <div class="col-md-3">
            <label class="form-label">Version</label>
            <input type="text" name="version" class="form-control"
                   value="<?= e($val('version', $catalogApp['manifest']['upstreamVersion'] ?? $catalogApp['manifest']['version'] ?? '')) ?>"
                   placeholder="1.0.0">
        </div>

        <!-- ── Stack / Tech ──────────────────────────────────────────────── -->
        <div class="col-md-3">
            <label class="form-label">Stack / Tech</label>
            <?php
                $defaultStack = $catalogApp
                    ? \App\Services\AppCatalogService::getAddonString($catalogApp['manifest']['addons'] ?? [])
                    : '';
            ?>
            <input type="text" name="stack_type" class="form-control"
                   value="<?= e($val('stack_type', $defaultStack)) ?>"
                   placeholder="MySQL, Redis, Docker…" maxlength="100">
        </div>

        <!-- ── Client ────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Client</label>
            <select name="client_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $sel('client_id', $c['id']) ?>>
                    <?= e($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Server ────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Server</label>
            <select name="server_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($servers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $sel('server_id', $s['id']) ?>>
                    <?= e($s['label']) ?><?= $s['ip_address'] ? ' (' . e($s['ip_address']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Domain (searchable) ───────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Domain</label>
            <div class="sv-select">
                <input type="hidden" name="domain_id"
                       value="<?= (int)($v['domain_id'] ?? 0) > 0 ? (int)$v['domain_id'] : '' ?>">
                <input type="text" class="form-control sv-select-input"
                       autocomplete="off" placeholder="— None —">
                <div class="sv-select-dropdown">
                    <div class="sv-select-option" data-id="">— None —</div>
                    <?php foreach ($domains as $d): ?>
                    <div class="sv-select-option" data-id="<?= (int)$d['id'] ?>">
                        <?= e($d['root_domain']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ── Install Path ───────────────────────────────────────────────── -->
        <div class="col-md-6">
            <label class="form-label">Install Path</label>
            <input type="text" name="install_path" class="form-control font-monospace"
                   value="<?= e($val('install_path')) ?>"
                   placeholder="/var/www/app or /opt/apps/bookstack">
        </div>

        <!-- ── Deployment Method ─────────────────────────────────────────── -->
        <div class="col-md-3">
            <label class="form-label">Deployment</label>
            <input type="text" name="deployment_method" class="form-control" list="dl-deploy"
                   value="<?= e($val('deployment_method')) ?>"
                   placeholder="Cloudron, Docker…">
            <datalist id="dl-deploy">
                <?php foreach ($deploymentPresets ?? [] as $preset): ?>
                <option value="<?= e($preset) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <!-- ── Git Repo ───────────────────────────────────────────────────── -->
        <div class="col-md-3">
            <label class="form-label">Git Repo</label>
            <input type="text" name="git_repo" class="form-control"
                   value="<?= e($val('git_repo')) ?>"
                   placeholder="https://github.com/…">
        </div>

        <!-- ── Notes ─────────────────────────────────────────────────────── -->
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Admin URL, setup notes, custom config…"><?= e($val('notes')) ?></textarea>
        </div>

    </div><!-- /.row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel) ?>
        </button>
        <a href="<?= url('/applications') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
function svClearCatalog() {
    document.getElementById('inp-catalog-id').value  = '';
    document.getElementById('inp-app-name').readOnly = false;

    const banner = document.getElementById('catalog-banner');
    if (banner) banner.remove();

    // Show the "browse catalog" hint if not already present
    let hint = document.getElementById('no-catalog-hint');
    if (!hint) {
        hint = document.createElement('div');
        hint.id        = 'no-catalog-hint';
        hint.className = 'mb-4 p-3 rounded border border-dashed d-flex align-items-center gap-3';
        hint.innerHTML =
            '<i class="ti ti-apps fs-2 text-muted flex-shrink-0"></i>' +
            '<div class="flex-grow-1"><div class="fw-medium">Select from App Catalog</div>' +
            '<div class="text-muted small">Browse pre-defined apps or fill in manually.</div></div>' +
            '<a href="<?= url('/app-catalog') ?>" class="btn btn-outline-primary flex-shrink-0">' +
            '<i class="ti ti-layout-grid me-1"></i>Browse Catalog</a>';
        banner?.parentNode?.insertBefore(hint, banner?.nextSibling)
            ?? document.querySelector('form')?.before(hint);
    }
}
</script>
