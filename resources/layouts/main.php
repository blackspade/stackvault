<?php
/**
 * Main application layout — Tabler sidebar + topbar
 *
 * Available vars from View::share(): $currentPath, $user, $appName
 * Available vars from controller:    $title, $content, + any page-specific vars
 */

// Build nav active helper
$isActive = fn(string $prefix): string =>
    str_starts_with($currentPath ?? '/', $prefix) ? ' active' : '';

// Sidebar navigation definition
$navItems = [
    ['icon' => 'ti-layout-dashboard', 'label' => 'Dashboard',        'path' => '/dashboard'],
    ['icon' => 'ti-users',            'label' => 'Clients',          'path' => '/clients'],
    ['icon' => 'ti-server',           'label' => 'Servers',          'path' => '/servers'],
    ['icon' => 'ti-world',            'label' => 'Domains',          'path' => '/domains'],
    ['icon' => 'ti-apps',             'label' => 'Applications',     'path' => '/applications'],
    ['icon' => 'ti-database',         'label' => 'Databases',        'path' => '/databases'],
    ['icon' => 'ti-sitemap',          'label' => 'DNS Records',      'path' => '/dns'],
    ['icon' => 'ti-lock',             'label' => 'Credentials',      'path' => '/credentials'],
    ['icon' => 'ti-mail',             'label' => 'Email Accounts',   'path' => '/email'],
    ['icon' => 'ti-bookmarks',        'label' => 'Bookmarks',        'path' => '/bookmarks'],
    ['icon' => 'ti-file-code',        'label' => 'Host Files',       'path' => '/hosts'],
    ['icon' => 'ti-archive',          'label' => 'File Manager',     'path' => '/files'],
    ['divider' => true],
    ['icon' => 'ti-bell',             'label' => 'Reminders',        'path' => '/reminders'],
];

$userName  = $user['username'] ?? 'Admin';
$userEmail = $user['email']    ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($title ?? '') ?><?= isset($title) ? ' — ' : '' ?><?= e($appName) ?></title>

    <link rel="icon" type="image/x-icon"    href="<?= asset('icons/favicon.ico') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('icons/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('icons/favicon-16x16.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180"    href="<?= asset('icons/apple-touch-icon.png') ?>">

    <link rel="stylesheet" href="<?= asset('tabler/css/tabler.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('tabler/css/tabler-icons.min.css') ?>">

    <style>
        :root { --tblr-font-sans-serif: 'Inter', system-ui, -apple-system, sans-serif; }

        /* Sidebar brand */
        .navbar-brand-text { font-weight: 700; font-size: 1.15rem; letter-spacing: -.3px; }
        .navbar-brand-text span { color: #74c0fc; }

        /* Flash alerts */
        .flash-container { position: fixed; top: 16px; right: 16px; z-index: 9999; min-width: 320px; max-width: 420px; }

        /* Copy-to-clipboard badge */
        .copy-btn { cursor: pointer; }
        .copy-btn:active { opacity: .7; }

        /* Searchable select widget (.sv-select) */
        .sv-select { position: relative; }
        .sv-select-dropdown {
            position: absolute; top: calc(100% + 2px); left: 0; right: 0; z-index: 1050;
            background: var(--tblr-bg-surface, #fff);
            border: 1px solid var(--tblr-border-color, #e6e7e9);
            border-radius: var(--tblr-border-radius, .25rem);
            box-shadow: 0 4px 16px rgba(0,0,0,.1);
            max-height: 240px; overflow-y: auto; display: none;
        }
        .sv-select-dropdown.is-open { display: block; }
        .sv-select-option {
            padding: 7px 12px; cursor: pointer; font-size: .875rem;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sv-select-option:hover,
        .sv-select-option.is-focused { background: var(--tblr-active-bg, rgba(32,107,196,.08)); }
        .sv-select-option.is-selected { font-weight: 600; }
        .sv-select-no-results {
            padding: 8px 12px; font-size: .875rem;
            color: var(--tblr-muted, #667382); font-style: italic;
        }
    </style>
</head>

<body class="antialiased">
<div class="page">

    <!-- ══════════════════════════════════════════════════════════════════════
         SIDEBAR
         ══════════════════════════════════════════════════════════════════ -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">

            <!-- Mobile toggler -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sidebar-nav"
                    aria-controls="sidebar-nav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Brand -->
            <a href="<?= url('/dashboard') ?>" class="navbar-brand navbar-brand-autodark">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                     stroke-linejoin="round" class="me-2">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span class="navbar-brand-text">Stack<span>Vault</span></span>
            </a>

            <!-- Collapsed menu (mobile) -->
            <div class="collapse navbar-collapse" id="sidebar-nav">

                <!-- Navigation -->
                <ul class="navbar-nav pt-lg-3">
                    <?php foreach ($navItems as $item): ?>

                    <?php if (!empty($item['divider'])): ?>
                    <li class="nav-item mt-2">
                        <div class="nav-item-divider"></div>
                    </li>
                    <?php continue; endif; ?>

                    <li class="nav-item">
                        <a href="<?= url($item['path']) ?>"
                           class="nav-link<?= $isActive($item['path']) ?>">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti <?= e($item['icon']) ?>"></i>
                            </span>
                            <span class="nav-link-title"><?= e($item['label']) ?></span>
                        </a>
                    </li>

                    <?php endforeach; ?>
                </ul>

                <!-- Bottom: vault status -->
                <div class="mt-auto pt-3 border-top border-dark-subtle">
                    <div class="px-3 py-2">
                        <?php if (vault_unlocked()): ?>
                        <form method="post" action="<?= url('/vault/lock') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                    title="Click to lock the vault">
                                <span class="badge bg-success-lt text-success" style="cursor:pointer;">
                                    <i class="ti ti-lock-open-2 me-1"></i>Vault Unlocked
                                </span>
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="<?= url('/vault/unlock') ?>" class="text-decoration-none"
                           title="Click to unlock the vault">
                            <span class="badge bg-warning-lt text-warning" style="cursor:pointer;">
                                <i class="ti ti-lock me-1"></i>Vault Locked
                            </span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /collapse -->
        </div>
    </aside>

    <!-- ══════════════════════════════════════════════════════════════════════
         PAGE WRAPPER
         ══════════════════════════════════════════════════════════════════ -->
    <div class="page-wrapper">

        <!-- Top navbar -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">

                <!-- Mobile brand -->
                <a href="<?= url('/dashboard') ?>" class="navbar-brand d-lg-none">
                    <?= e($appName) ?>
                </a>

                <!-- Global Search (Stage 14) -->
                <form method="get" action="<?= url('/search') ?>" class="d-none d-md-flex me-auto">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <span class="input-group-text">
                            <i class="ti ti-search"></i>
                        </span>
                        <input id="global-search" type="search" name="q"
                               class="form-control"
                               placeholder="Search clients, IPs, domains…"
                               value="<?= e($_GET['q'] ?? '') ?>"
                               autocomplete="off">
                    </div>
                </form>

                <!-- Right side: notifications + user -->
                <div class="navbar-nav ms-auto">

                    <!-- User menu -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar avatar-sm"
                                  style="background-image: none; background-color: var(--tblr-primary);">
                                <?= strtoupper(substr($userName, 0, 2)) ?>
                            </span>
                            <div class="d-none d-md-block ms-2 lh-1">
                                <div class="fw-bold" style="font-size: 13px;"><?= e($userName) ?></div>
                                <div class="mt-1 text-muted" style="font-size: 11px;"><?= e($userEmail) ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="<?= url('/settings') ?>" class="dropdown-item">
                                <i class="ti ti-settings dropdown-item-icon"></i>Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <?php if (vault_unlocked()): ?>
                            <form method="post" action="<?= url('/vault/lock') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-warning">
                                    <i class="ti ti-lock dropdown-item-icon"></i>Lock Vault
                                </button>
                            </form>
                            <?php else: ?>
                            <a href="<?= url('/vault/unlock') ?>" class="dropdown-item text-primary">
                                <i class="ti ti-key dropdown-item-icon"></i>Unlock Vault
                            </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?= url('/logout') ?>" class="dropdown-item text-danger">
                                <i class="ti ti-logout dropdown-item-icon"></i>Sign out
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </header>

        <!-- Page header (title bar) -->
        <?php if (!empty($title)): ?>
        <div class="page-header d-print-none">
            <div class="container-xl">
                <div class="row g-2 align-items-center">
                    <div class="col">
                        <?php if (!empty($breadcrumbs)): ?>
                        <ol class="breadcrumb" aria-label="breadcrumbs">
                            <li class="breadcrumb-item">
                                <a href="<?= url('/dashboard') ?>">Home</a>
                            </li>
                            <?php foreach ($breadcrumbs as $bc): ?>
                            <li class="breadcrumb-item <?= empty($bc['url']) ? 'active' : '' ?>">
                                <?php if (!empty($bc['url'])): ?>
                                    <a href="<?= url($bc['url']) ?>"><?= e($bc['label']) ?></a>
                                <?php else: ?>
                                    <?= e($bc['label']) ?>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php endif; ?>
                        <h2 class="page-title"><?= e($title) ?></h2>
                    </div>

                    <?php if (!empty($pageActions)): ?>
                    <div class="col-auto ms-auto d-print-none">
                        <?= $pageActions ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Page body ───────────────────────────────────────────────────── -->
        <div class="page-body">
            <div class="container-xl">

                <!-- Flash messages -->
                <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $type => $cls): ?>
                <?php foreach (get_flash($type) as $msg): ?>
                <div class="alert alert-<?= $cls ?> alert-dismissible mb-3" role="alert">
                    <div class="d-flex gap-2">
                        <?php if ($type === 'success'): ?><i class="ti ti-circle-check fs-4"></i>
                        <?php elseif ($type === 'error'):  ?><i class="ti ti-alert-circle fs-4"></i>
                        <?php elseif ($type === 'warning'):?><i class="ti ti-alert-triangle fs-4"></i>
                        <?php else: ?>                       <i class="ti ti-info-circle fs-4"></i>
                        <?php endif; ?>
                        <div><?= e($msg) ?></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <?= $content ?>

            </div>
        </div>

        <!-- Footer -->
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center">
                    <div class="col text-muted" style="font-size: 12px;">
                        <?= e($appName) ?> &mdash; DevOps Client &amp; Infrastructure Manager
                        &mdash; &copy; <?= date('Y') ?>
                    </div>
                </div>
            </div>
        </footer>

    </div><!-- /page-wrapper -->
</div><!-- /page -->

<script src="<?= asset('tabler/js/tabler.min.js') ?>"></script>
<script>
// Press "/" to focus global search (skip if already in a text field)
document.addEventListener('keydown', function (e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        var s = document.getElementById('global-search');
        if (s) { s.focus(); s.select(); }
    }
});

// ── Searchable select widget (.sv-select) ─────────────────────────────────
(function () {
    function svInitSelect(wrap) {
        var hiddenEl = wrap.querySelector('input[type="hidden"]');
        var inputEl  = wrap.querySelector('.sv-select-input');
        var dropEl   = wrap.querySelector('.sv-select-dropdown');
        var allOpts  = Array.from(dropEl.querySelectorAll('.sv-select-option'));

        function labelFor(id) {
            var opt = allOpts.find(function (o) { return o.dataset.id === String(id ?? ''); });
            return opt ? opt.textContent.trim() : '';
        }

        function applyFilter(q) {
            var lq = q.toLowerCase().trim();
            var any = false;
            allOpts.forEach(function (o) {
                var show = !lq || o.textContent.toLowerCase().includes(lq);
                o.style.display = show ? '' : 'none';
                if (show) any = true;
            });
            var noRes = dropEl.querySelector('.sv-select-no-results');
            if (!any) {
                if (!noRes) {
                    noRes = document.createElement('div');
                    noRes.className   = 'sv-select-no-results';
                    noRes.textContent = 'No results';
                    dropEl.appendChild(noRes);
                }
                noRes.style.display = '';
            } else if (noRes) {
                noRes.style.display = 'none';
            }
        }

        function getFocused() {
            return dropEl.querySelector('.sv-select-option.is-focused');
        }

        function moveFocus(dir) {
            var visible = allOpts.filter(function (o) { return o.style.display !== 'none'; });
            if (!visible.length) return;
            var cur = getFocused();
            var idx = cur ? visible.indexOf(cur) : -1;
            allOpts.forEach(function (o) { o.classList.remove('is-focused'); });
            var next = dir > 0
                ? (idx < visible.length - 1 ? idx + 1 : 0)
                : (idx > 0 ? idx - 1 : visible.length - 1);
            visible[next].classList.add('is-focused');
            visible[next].scrollIntoView({ block: 'nearest' });
        }

        function pick(id, label) {
            hiddenEl.value = id;
            inputEl.value  = label;
            allOpts.forEach(function (o) {
                o.classList.toggle('is-selected', o.dataset.id === String(id ?? ''));
                o.classList.remove('is-focused');
            });
            dropEl.classList.remove('is-open');
        }

        function closeDropdown() {
            dropEl.classList.remove('is-open');
            inputEl.value = labelFor(hiddenEl.value);
            allOpts.forEach(function (o) { o.classList.remove('is-focused'); });
        }

        // Init — show current selection label and mark selected option
        inputEl.value = labelFor(hiddenEl.value);
        allOpts.forEach(function (o) {
            o.classList.toggle('is-selected', o.dataset.id === String(hiddenEl.value ?? ''));
        });

        inputEl.addEventListener('focus', function () {
            this.value = '';
            applyFilter('');
            dropEl.classList.add('is-open');
        });

        inputEl.addEventListener('input', function () {
            dropEl.classList.add('is-open');
            applyFilter(this.value);
            allOpts.forEach(function (o) { o.classList.remove('is-focused'); });
        });

        inputEl.addEventListener('keydown', function (e) {
            switch (e.key) {
                case 'ArrowDown': e.preventDefault(); moveFocus(1);  break;
                case 'ArrowUp':   e.preventDefault(); moveFocus(-1); break;
                case 'Enter':
                    e.preventDefault();
                    var f = getFocused();
                    if (f) pick(f.dataset.id, f.textContent.trim());
                    break;
                case 'Escape':
                    e.preventDefault();
                    closeDropdown();
                    this.blur();
                    break;
            }
        });

        inputEl.addEventListener('blur', function () {
            setTimeout(closeDropdown, 160);
        });

        allOpts.forEach(function (opt) {
            opt.addEventListener('mousedown', function (e) {
                e.preventDefault(); // prevent blur before mousedown fires
                pick(this.dataset.id, this.textContent.trim());
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sv-select').forEach(svInitSelect);
    });
}());
</script>
</body>
</html>
