<?php
/**
 * Vars: $app[], $catalogApp, $credentials[], $activity[]
 */
$id = (int) $app['id'];

// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/applications/' . $id . '/edit') ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<button type="button" class="btn btn-outline-danger"
        onclick="document.getElementById('delete-form').classList.toggle('d-none')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Delete confirm ─────────────────────────────────────────────────────── -->
<div id="delete-form" class="card border-danger mb-4 d-none">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span class="text-danger fw-medium">
            <i class="ti ti-alert-triangle me-1"></i>Permanently delete this application?
        </span>
        <form method="post" action="<?= url('/applications/' . $id . '/delete') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Yes, delete</button>
        </form>
        <button type="button" class="btn btn-sm btn-ghost-secondary"
                onclick="document.getElementById('delete-form').classList.add('d-none')">
            Cancel
        </button>
    </div>
</div>

<!-- ── Tabs ───────────────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a href="#tab-overview" class="nav-link active" data-bs-toggle="tab" role="tab">
            <i class="ti ti-app-window me-1"></i>Overview
        </a>
    </li>
    <li class="nav-item">
        <a href="#tab-credentials" class="nav-link" data-bs-toggle="tab" role="tab" id="tab-cred-link">
            <i class="ti ti-key me-1"></i>Credentials
            <?php if (!empty($credentials)): ?>
            <span class="badge bg-secondary ms-1"><?= count($credentials) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item">
        <a href="#tab-activity" class="nav-link" data-bs-toggle="tab" role="tab">
            <i class="ti ti-history me-1"></i>Activity
        </a>
    </li>
</ul>

<div class="tab-content">

<!-- ── Overview tab ──────────────────────────────────────────────────────── -->
<div class="tab-pane active show" id="tab-overview" role="tabpanel">
    <div class="row g-4">

        <!-- Left col: app details -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-3">
                    <img src="<?= url('/app-icon/' . rawurlencode($app['app_name'])) ?>"
                         width="44" height="44" class="rounded flex-shrink-0"
                         alt="<?= e($app['app_name']) ?>"
                         onerror="this.style.display='none'">
                    <div>
                        <h3 class="card-title mb-0"><?= e($app['app_name']) ?></h3>
                        <?php if ($app['version']): ?>
                        <span class="badge bg-blue-lt text-blue">v<?= e($app['version']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">

                        <?php if ($app['stack_type']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Stack</dt>
                        <dd class="col-sm-8"><?= e($app['stack_type']) ?></dd>
                        <?php endif; ?>

                        <?php if ($app['client_name']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Client</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/clients/' . $app['client_id']) ?>">
                                <?= e($app['client_name']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($app['server_label']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Server</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/servers/' . $app['server_id']) ?>">
                                <?= e($app['server_label']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($app['domain_name']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Domain</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/domains/' . $app['domain_id']) ?>">
                                <?= e($app['domain_name']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($app['install_path']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Path</dt>
                        <dd class="col-sm-8 font-monospace small"><?= e($app['install_path']) ?></dd>
                        <?php endif; ?>

                        <?php if ($app['deployment_method']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Deploy</dt>
                        <dd class="col-sm-8"><?= e($app['deployment_method']) ?></dd>
                        <?php endif; ?>

                        <?php if ($app['git_repo']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Git</dt>
                        <dd class="col-sm-8 text-truncate">
                            <a href="<?= e($app['git_repo']) ?>" target="_blank" rel="noopener"
                               class="font-monospace small"><?= e($app['git_repo']) ?></a>
                        </dd>
                        <?php endif; ?>

                        <dt class="col-sm-4 text-muted fw-normal">Added</dt>
                        <dd class="col-sm-8 text-muted small"><?= e($app['created_at']) ?></dd>

                    </dl>
                </div>
            </div>

            <?php if ($app['notes']): ?>
            <div class="card mt-3">
                <div class="card-header"><h4 class="card-title">Notes</h4></div>
                <div class="card-body">
                    <p class="text-muted mb-0" style="white-space:pre-wrap"><?= e($app['notes']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /left col -->

        <!-- Right col: catalog info -->
        <div class="col-lg-7">
            <?php if ($catalogApp): ?>
            <?php $manifest = $catalogApp['manifest']; ?>
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="ti ti-apps text-muted"></i>
                    <h4 class="card-title mb-0">Catalog Info</h4>
                    <a href="<?= url('/app-catalog/' . rawurlencode($catalogApp['id'])) ?>"
                       class="ms-auto btn btn-sm btn-ghost-secondary">
                        <i class="ti ti-external-link me-1"></i>Full Details
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($manifest['tagline'] ?? ''): ?>
                    <p class="text-muted"><?= e($manifest['tagline']) ?></p>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (!empty($manifest['tags'])): ?>
                    <div class="mb-3">
                        <?php foreach ($manifest['tags'] as $tag): ?>
                        <a href="<?= url('/app-catalog?tag=' . urlencode($tag)) ?>"
                           class="badge bg-secondary-lt text-secondary text-decoration-none me-1">
                            <?= e($tag) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Addons -->
                    <?php
                        $badges = \App\Services\AppCatalogService::getAddonBadges($manifest['addons'] ?? []);
                    ?>
                    <?php if (!empty($badges)): ?>
                    <div class="mb-3">
                        <div class="text-muted small mb-1">Integrations</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($badges as $badge): ?>
                            <span class="badge bg-blue-lt text-blue">
                                <i class="ti <?= e($badge['icon']) ?> me-1"></i><?= e($badge['label']) ?>
                                <?= $badge['optional'] ? '<span class="opacity-60">(opt)</span>' : '' ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Links -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($manifest['website'] ?? ''): ?>
                        <a href="<?= e($manifest['website']) ?>" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-world me-1"></i>Website
                        </a>
                        <?php endif; ?>
                        <?php if ($manifest['documentationUrl'] ?? ''): ?>
                        <a href="<?= e($manifest['documentationUrl']) ?>" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-book me-1"></i>Docs
                        </a>
                        <?php endif; ?>
                        <?php if ($manifest['forumUrl'] ?? ''): ?>
                        <a href="<?= e($manifest['forumUrl']) ?>" target="_blank" rel="noopener"
                           class="btn btn-sm btn-outline-secondary">
                            <i class="ti ti-message-circle me-1"></i>Forum
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-dashed">
                <div class="card-body text-center text-muted py-4">
                    <i class="ti ti-apps fs-2 mb-2 d-block opacity-50"></i>
                    <p class="mb-2">Not linked to a catalog entry.</p>
                    <a href="<?= url('/app-catalog?return_to=' . urlencode('/applications/' . $id . '/edit')) ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-layout-grid me-1"></i>Browse Catalog
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /right col -->

    </div><!-- /.row -->
</div><!-- /#tab-overview -->

<!-- ── Credentials tab ───────────────────────────────────────────────────── -->
<div class="tab-pane" id="tab-credentials" role="tabpanel">
    <?php if (empty($credentials)): ?>
    <div class="text-center text-muted py-5">
        <i class="ti ti-key-off fs-1 d-block mb-2 opacity-50"></i>
        No credentials linked to this application.
        <br>
        <a href="<?= url('/credentials/create') ?>" class="btn btn-sm btn-outline-secondary mt-2">
            <i class="ti ti-plus me-1"></i>Add Credential
        </a>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Username</th>
                        <th>Added</th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($credentials as $cred): ?>
                <?php $badgeClass = \App\Models\CredentialModel::typeBadgeClass($cred['credential_type']); ?>
                <tr>
                    <td>
                        <a href="<?= url('/credentials/' . $cred['id']) ?>">
                            <?= e($cred['label']) ?>
                        </a>
                    </td>
                    <td><span class="badge <?= $badgeClass ?>"><?= e($cred['credential_type']) ?></span></td>
                    <td class="text-muted small"><?= $cred['username'] ? e($cred['username']) : '—' ?></td>
                    <td class="text-muted small"><?= time_ago($cred['created_at']) ?></td>
                    <td>
                        <a href="<?= url('/credentials/' . $cred['id']) ?>"
                           class="btn btn-sm btn-ghost-secondary">
                            <i class="ti ti-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Activity tab ──────────────────────────────────────────────────────── -->
<div class="tab-pane" id="tab-activity" role="tabpanel">
    <?php if (empty($activity)): ?>
    <div class="text-center text-muted py-5">
        <i class="ti ti-history fs-1 d-block mb-2 opacity-50"></i>No activity recorded yet.
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>IP</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($activity as $log): ?>
                <?php
                    $icon = match (true) {
                        str_contains($log['action'], 'created') => 'ti-plus text-success',
                        str_contains($log['action'], 'updated') => 'ti-pencil text-blue',
                        str_contains($log['action'], 'deleted') => 'ti-trash text-danger',
                        default                                 => 'ti-activity text-muted',
                    };
                ?>
                <tr>
                    <td>
                        <span class="d-flex align-items-center gap-1">
                            <i class="ti <?= $icon ?>"></i>
                            <span class="text-muted small"><?= e($log['action']) ?></span>
                        </span>
                    </td>
                    <td class="text-muted"><?= e($log['description']) ?></td>
                    <td class="text-muted small"><?= e($log['username'] ?? '—') ?></td>
                    <td class="text-muted small font-monospace"><?= e($log['ip_address'] ?? '—') ?></td>
                    <td class="text-muted small"><?= time_ago($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.tab-content -->

<script>
// Auto-activate tab from URL hash (e.g. #tab-credentials)
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;
    if (hash) {
        const tabLink = document.querySelector('[href="' + hash + '"][data-bs-toggle="tab"]');
        if (tabLink) tabLink.click();
    }
});
</script>
