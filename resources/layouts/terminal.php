<?php
/**
 * Terminal layout — full-viewport-height split view.
 * Same sidebar/topbar as main.php; no footer, no container-xl, no page-header.
 *
 * Available vars: $currentPath, $user, $appName, $content
 */

$isActive = fn(string $prefix): string =>
    str_starts_with($currentPath ?? '/', $prefix) ? ' active' : '';

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
    ['icon' => 'ti-terminal-2',       'label' => 'Terminal',         'path' => '/terminal'],
    ['icon' => 'ti-bell',             'label' => 'Reminders',        'path' => '/reminders'],
    ['icon' => 'ti-settings',         'label' => 'Settings',         'path' => '/settings'],
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
    <link rel="stylesheet" href="<?= asset('xterm/xterm.css') ?>">

    <style>
        :root { --tblr-font-sans-serif: 'Inter', system-ui, -apple-system, sans-serif; }

        .navbar-brand-text { font-weight: 700; font-size: 1.15rem; letter-spacing: -.3px; }
        .navbar-brand-text span { color: #74c0fc; }

        /* Force page-wrapper to fill viewport and prevent overflow */
        .sv-page-wrapper {
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Terminal body fills remaining space after topbar */
        .sv-terminal-body {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            display: flex;
        }

        /* Left info panel */
        .sv-info-panel {
            width: 380px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--tblr-border-color);
            overflow: hidden;
        }

        .sv-info-panel-header {
            padding: 0.75rem 1rem 0;
            flex-shrink: 0;
        }

        .sv-info-panel-tabs {
            flex-shrink: 0;
            padding: 0 1rem;
        }

        .sv-info-panel-content {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem 1rem;
        }

        /* Right terminal pane */
        .sv-terminal-pane {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background: #1a1b1e;
        }

        .sv-terminal-toolbar {
            background: #16181c;
            border-bottom: 1px solid #2d2f34;
            padding: 0.4rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            font-size: 0.8125rem;
        }

        #xterm-mount {
            flex: 1;
            min-height: 0;
            padding: 6px 8px;
            overflow: hidden;
        }

        /* xterm overrides */
        .xterm { height: 100%; }
        .xterm-viewport { border-radius: 0; }

        /* Info panel table */
        .sv-ip-table { font-size: 0.8125rem; }
        .sv-ip-table th { font-weight: 600; white-space: nowrap; }
        .sv-ip-table td { vertical-align: middle; }

        /* DNS badge colours (mirrored from DnsRecordModel) */
        .dns-A     { background: #dbe7fd; color: #1862ab; }
        .dns-AAAA  { background: #e0e8ff; color: #3730a3; }
        .dns-CNAME { background: #d3f9f9; color: #0c7a7a; }
        .dns-MX    { background: #ffe8cc; color: #c05621; }
        .dns-TXT   { background: #fff3bf; color: #854d0e; }
        .dns-NS    { background: #c3fae8; color: #0c6e4e; }
        .dns-other { background: #f1f3f5; color: #495057; }

        /* Flash toasts */
        .flash-container { position: fixed; top: 16px; right: 16px; z-index: 9999; min-width: 320px; max-width: 420px; }
    </style>
</head>

<body class="antialiased">
<div class="page">

    <!-- ── Sidebar ─────────────────────────────────────────────────────── -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sidebar-nav"
                    aria-controls="sidebar-nav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a href="<?= url('/dashboard') ?>" class="navbar-brand navbar-brand-autodark">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"
                     stroke-linejoin="round" class="me-2">
                    <rect x="3" y="11" width="18" height="11" rx="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <span class="navbar-brand-text">Stack<span>Vault</span></span>
            </a>
            <div class="collapse navbar-collapse" id="sidebar-nav">
                <ul class="navbar-nav pt-lg-3">
                    <?php foreach ($navItems as $item): ?>
                    <?php if (!empty($item['divider'])): ?>
                    <li class="nav-item mt-2"><div class="nav-item-divider"></div></li>
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
                <div class="mt-auto pt-3 border-top border-dark-subtle">
                    <div class="px-3 py-2">
                        <?php if (vault_unlocked()): ?>
                        <form method="post" action="<?= url('/vault/lock') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Click to lock the vault">
                                <span class="badge bg-success-lt text-success" style="cursor:pointer;">
                                    <i class="ti ti-lock-open-2 me-1"></i>Vault Unlocked
                                </span>
                            </button>
                        </form>
                        <?php else: ?>
                        <a href="<?= url('/vault/unlock') ?>" class="text-decoration-none" title="Click to unlock the vault">
                            <span class="badge bg-warning-lt text-warning" style="cursor:pointer;">
                                <i class="ti ti-lock me-1"></i>Vault Locked
                            </span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ── Page wrapper ─────────────────────────────────────────────────── -->
    <div class="page-wrapper sv-page-wrapper">

        <!-- Topbar -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <a href="<?= url('/dashboard') ?>" class="navbar-brand d-lg-none"><?= e($appName) ?></a>
                <form method="get" action="<?= url('/search') ?>" class="d-none d-md-flex me-auto">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input id="global-search" type="search" name="q" class="form-control"
                               placeholder="Search clients, IPs, domains…"
                               value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
                    </div>
                </form>
                <div class="navbar-nav ms-auto">
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

        <!-- Terminal body (fills remaining height) -->
        <div class="sv-terminal-body">
            <?= $content ?>
        </div>

    </div><!-- /page-wrapper -->
</div><!-- /page -->

<script src="<?= asset('tabler/js/tabler.min.js') ?>"></script>
<script src="<?= asset('xterm/xterm.js') ?>"></script>
<script src="<?= asset('xterm/addon-fit.js') ?>"></script>

<div id="sv-toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999"></div>
<script>
var SV_CSRF = '<?= csrf_token() ?>';
function showToast(msg, type) {
    var colors = { success: 'bg-success', error: 'bg-danger', warning: 'bg-warning', info: 'bg-azure' };
    var icons  = { success: 'ti-circle-check', error: 'ti-alert-circle', warning: 'ti-alert-triangle', info: 'ti-info-circle' };
    var el = document.createElement('div');
    el.className = 'toast align-items-center text-white border-0 ' + (colors[type] || 'bg-secondary');
    el.setAttribute('role', 'alert');
    el.innerHTML = '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2">'
        + '<i class="ti ' + (icons[type] || 'ti-bell') + '"></i><span>' + msg + '</span></div>'
        + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    document.getElementById('sv-toast-container').appendChild(el);
    var t = new bootstrap.Toast(el, { delay: 4000 });
    t.show();
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
}
document.addEventListener('keydown', function (e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        var s = document.getElementById('global-search');
        if (s) { s.focus(); s.select(); }
    }
});
</script>
</body>
</html>
