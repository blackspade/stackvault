<?php
/**
 * Vars: $accounts[], $clients[], $domains[], $search, $filterClient, $filterDomain
 */

ob_start(); ?>
<a href="<?= url('/email/create') ?>" class="btn btn-primary d-none d-sm-inline-flex">
    <i class="ti ti-plus me-1"></i>Add Email Account
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/email') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search address, host, username, client…"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="client_id" class="form-select">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClient === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="domain_id" class="form-select">
                    <option value="">All Domains</option>
                    <?php foreach ($domains as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDomain === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= e($d['root_domain']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
                <?php if ($search || $filterClient || $filterDomain): ?>
                <a href="<?= url('/email') ?>" class="btn btn-outline-secondary" title="Clear">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ── Table ──────────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?= count($accounts) ?> account<?= count($accounts) !== 1 ? 's' : '' ?>
            <?php if ($search || $filterClient || $filterDomain): ?>
            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($accounts)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-mail-off fs-1 d-block mb-2 opacity-50"></i>
        <?php if ($search || $filterClient || $filterDomain): ?>
            No email accounts match your filters.
        <?php else: ?>
            No email accounts yet. <a href="<?= url('/email/create') ?>">Add your first account.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Email Address</th>
                    <th>Mail Host</th>
                    <th style="width:70px" class="text-center">SMTP</th>
                    <th style="width:70px" class="text-center">IMAP</th>
                    <th>Domain</th>
                    <th>Client</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $acc): ?>
            <tr>
                <td>
                    <a href="<?= url('/email/' . $acc['id']) ?>"
                       class="fw-medium text-reset text-decoration-none">
                        <?= e($acc['email_address']) ?>
                    </a>
                    <?php if ($acc['password_encrypted']): ?>
                    <i class="ti ti-lock text-muted ms-1" title="Password stored"></i>
                    <?php endif; ?>
                    <?php if ($acc['webmail_url']): ?>
                    <a href="<?= e($acc['webmail_url']) ?>" target="_blank" rel="noopener"
                       class="text-muted ms-1" title="Open webmail">
                        <i class="ti ti-external-link"></i>
                    </a>
                    <?php endif; ?>
                </td>
                <td class="text-muted small font-monospace">
                    <?= $acc['mail_host'] ? e($acc['mail_host']) : '—' ?>
                </td>
                <td class="text-center text-muted small"><?= (int)$acc['smtp_port'] ?></td>
                <td class="text-center text-muted small"><?= (int)$acc['imap_port'] ?></td>
                <td class="text-muted small">
                    <?php if ($acc['root_domain']): ?>
                    <a href="<?= url('/domains/' . $acc['domain_id']) ?>" class="text-reset">
                        <?= e($acc['root_domain']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-muted small">
                    <?php if ($acc['client_name']): ?>
                    <a href="<?= url('/clients/' . $acc['client_id']) ?>" class="text-reset">
                        <?= e($acc['client_name']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= url('/email/' . $acc['id']) ?>"
                           class="btn btn-ghost-secondary" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="<?= url('/email/' . $acc['id'] . '/edit') ?>"
                           class="btn btn-ghost-secondary" title="Edit">
                            <i class="ti ti-pencil"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
