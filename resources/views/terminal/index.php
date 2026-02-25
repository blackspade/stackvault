<?php
/**
 * Terminal view — split pane: client info panel (left) + xterm.js terminal (right).
 *
 * Variables:
 *   $clients          array  — [id, name] for the client selector
 *   $terminalEnabled  bool   — TERMINAL_ENABLED from .env
 *   $terminalWsUrl    string — TERMINAL_WS_URL from .env (WebSocket URL for ttyd)
 */
?>

<!-- ── Left: info panel ───────────────────────────────────────────────────── -->
<div class="sv-info-panel">

    <!-- Client selector -->
    <div class="sv-info-panel-header">
        <label class="form-label fw-semibold mb-1" style="font-size:0.8125rem;">
            <i class="ti ti-users me-1 text-muted"></i>Client
        </label>
        <select id="sv-client-select" class="form-select form-select-sm">
            <option value="">— Select a client —</option>
            <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Tab bar -->
    <div class="sv-info-panel-tabs mt-2">
        <ul class="nav nav-tabs nav-tabs-alt" id="info-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-tab="docs" href="#">
                    <i class="ti ti-file-text me-1"></i>Docs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="ips" href="#">
                    <i class="ti ti-table me-1"></i>IP Tables
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="servers" href="#">
                    <i class="ti ti-server me-1"></i>Servers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="dns" href="#">
                    <i class="ti ti-sitemap me-1"></i>DNS
                </a>
            </li>
        </ul>
    </div>

    <!-- Tab content -->
    <div class="sv-info-panel-content" id="info-content">

        <!-- Empty state (no client selected) -->
        <div id="tab-empty" class="text-center text-muted py-5">
            <i class="ti ti-arrow-up-left" style="font-size:2rem;"></i>
            <div class="mt-2" style="font-size:0.875rem;">Select a client to load reference data</div>
        </div>

        <!-- Loading spinner -->
        <div id="tab-loading" class="text-center text-muted py-5 d-none">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            <div class="mt-2" style="font-size:0.875rem;">Loading…</div>
        </div>

        <!-- Docs tab -->
        <div id="tab-docs" class="d-none">
            <div id="docs-content" style="font-size:0.8125rem; white-space:pre-wrap; line-height:1.6;"></div>
            <div id="docs-empty" class="text-muted fst-italic d-none" style="font-size:0.8125rem;">
                No documentation saved for this client.
            </div>
        </div>

        <!-- IP Tables tab -->
        <div id="tab-ips" class="d-none">
            <div id="ips-content"></div>
            <div id="ips-empty" class="text-muted fst-italic d-none" style="font-size:0.8125rem;">
                No IP tables saved for this client.
            </div>
        </div>

        <!-- Servers tab -->
        <div id="tab-servers" class="d-none">
            <div id="servers-content"></div>
            <div id="servers-empty" class="text-muted fst-italic d-none" style="font-size:0.8125rem;">
                No servers linked to this client.
            </div>
        </div>

        <!-- DNS tab -->
        <div id="tab-dns" class="d-none">
            <div id="dns-content"></div>
            <div id="dns-empty" class="text-muted fst-italic d-none" style="font-size:0.8125rem;">
                No DNS records found for this client's domains.
            </div>
        </div>

    </div><!-- /sv-info-panel-content -->
</div><!-- /sv-info-panel -->


<!-- ── Right: terminal pane ──────────────────────────────────────────────── -->
<div class="sv-terminal-pane">

    <?php if ($terminalEnabled && $terminalWsUrl !== ''): ?>

    <!-- Toolbar -->
    <div class="sv-terminal-toolbar text-white-50">
        <i class="ti ti-terminal-2" style="font-size:1rem;"></i>
        <span style="color:#e2e8f0; font-weight:500;">bash</span>
        <span id="terminal-status" class="badge bg-secondary-lt text-secondary ms-1">Connecting…</span>
        <span class="ms-auto" style="font-size:0.75rem; opacity:.5;">
            <?= e(parse_url($terminalWsUrl, PHP_URL_HOST) ?: $terminalWsUrl) ?>
        </span>
    </div>

    <!-- xterm.js mount -->
    <div id="xterm-mount"></div>

    <?php else: ?>

    <!-- Not-configured placeholder -->
    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-center px-4"
         style="color:#6b7280;">
        <i class="ti ti-terminal-2" style="font-size:3rem; opacity:.3;"></i>
        <div class="mt-3 fw-semibold" style="font-size:1rem; color:#9ca3af;">Terminal not configured</div>
        <div class="mt-2" style="font-size:0.8125rem; max-width:360px; line-height:1.6;">
            Add the following to your <code style="background:#2d2f34; padding:1px 5px; border-radius:3px;">.env</code>
            and restart the server:
        </div>
        <pre style="margin-top:1rem; background:#2d2f34; color:#e2e8f0; padding:1rem 1.25rem;
                    border-radius:6px; font-size:0.8rem; text-align:left; line-height:1.8;">TERMINAL_ENABLED=true
TERMINAL_WS_URL=ws://localhost:7681</pre>
        <div class="mt-3" style="font-size:0.75rem; color:#6b7280;">
            See <strong>README.md → Terminal Setup</strong> for ttyd installation and WebSocket proxy instructions.
        </div>
    </div>

    <?php endif; ?>

</div><!-- /sv-terminal-pane -->


<script>
(function () {
    'use strict';

    /* ── Tab switching ──────────────────────────────────────────────────── */
    var currentTab = 'docs';

    document.querySelectorAll('#info-tabs .nav-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            switchTab(this.dataset.tab);
        });
    });

    function switchTab(name) {
        currentTab = name;
        document.querySelectorAll('#info-tabs .nav-link').forEach(function (l) {
            l.classList.toggle('active', l.dataset.tab === name);
        });
        ['docs', 'ips', 'servers', 'dns'].forEach(function (t) {
            document.getElementById('tab-' + t).classList.toggle('d-none', t !== name);
        });
    }

    /* ── Client selector → fetch data ──────────────────────────────────── */
    var clientSelect = document.getElementById('sv-client-select');

    clientSelect.addEventListener('change', function () {
        var clientId = parseInt(this.value, 10);
        if (!clientId) {
            showEmpty();
            return;
        }
        loadClientData(clientId);
    });

    function showEmpty() {
        setTabsVisible(false);
        document.getElementById('tab-empty').classList.remove('d-none');
        document.getElementById('tab-loading').classList.add('d-none');
    }

    function setTabsVisible(visible) {
        document.getElementById('tab-empty').classList.toggle('d-none', visible);
        ['docs', 'ips', 'servers', 'dns'].forEach(function (t) {
            document.getElementById('tab-' + t).classList.toggle('d-none', t !== currentTab || !visible);
        });
    }

    function loadClientData(clientId) {
        document.getElementById('tab-empty').classList.add('d-none');
        document.getElementById('tab-loading').classList.remove('d-none');
        ['docs', 'ips', 'servers', 'dns'].forEach(function (t) {
            document.getElementById('tab-' + t).classList.add('d-none');
        });

        fetch(<?= json_encode(url('/terminal/client-data')) ?> + '?client_id=' + clientId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            document.getElementById('tab-loading').classList.add('d-none');
            renderDocs(data.docs);
            renderIpTables(data.ip_tables);
            renderServers(data.servers);
            renderDns(data.dns);
            switchTab(currentTab);
        })
        .catch(function () {
            document.getElementById('tab-loading').classList.add('d-none');
            showToast('Failed to load client data.', 'error');
            showEmpty();
        });
    }

    /* ── Render helpers ─────────────────────────────────────────────────── */

    function esc(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function renderDocs(content) {
        var el    = document.getElementById('docs-content');
        var empty = document.getElementById('docs-empty');
        if (content && content.trim()) {
            el.innerHTML = esc(content);
            el.classList.remove('d-none');
            empty.classList.add('d-none');
        } else {
            el.classList.add('d-none');
            empty.classList.remove('d-none');
        }
    }

    function renderIpTables(tables) {
        var wrap  = document.getElementById('ips-content');
        var empty = document.getElementById('ips-empty');

        if (!tables || !tables.length) {
            wrap.innerHTML = '';
            wrap.classList.add('d-none');
            empty.classList.remove('d-none');
            return;
        }

        var html = '';
        tables.forEach(function (tbl) {
            if (tbl.name) {
                html += '<div class="fw-semibold mb-1 mt-2" style="font-size:0.8rem;">' + esc(tbl.name) + '</div>';
            }
            html += '<table class="table table-sm table-hover sv-ip-table mb-2">'
                  + '<thead><tr><th>IP</th><th>Label</th><th>Port</th><th>Notes</th></tr></thead><tbody>';

            (tbl.rows || []).forEach(function (row) {
                html += '<tr>'
                    + '<td class="font-monospace">' + esc(row.ip)    + '</td>'
                    + '<td>' + esc(row.label) + '</td>'
                    + '<td class="text-muted">' + esc(row.port) + '</td>'
                    + '<td class="text-muted">' + esc(row.notes) + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>';
        });

        wrap.innerHTML = html;
        wrap.classList.remove('d-none');
        empty.classList.add('d-none');
    }

    function renderServers(servers) {
        var wrap  = document.getElementById('servers-content');
        var empty = document.getElementById('servers-empty');

        if (!servers || !servers.length) {
            wrap.innerHTML = '';
            wrap.classList.add('d-none');
            empty.classList.remove('d-none');
            return;
        }

        var html = '<table class="table table-sm table-hover sv-ip-table">'
                 + '<thead><tr><th>Label</th><th>IP</th><th>Hostname</th><th>OS</th></tr></thead><tbody>';

        servers.forEach(function (s) {
            html += '<tr>'
                + '<td class="fw-medium">' + esc(s.label) + '</td>'
                + '<td class="font-monospace">' + esc(s.ip_address) + '</td>'
                + '<td class="text-muted">' + esc(s.hostname) + '</td>'
                + '<td class="text-muted" style="font-size:0.75rem;">' + esc(s.os_version) + '</td>'
                + '</tr>';
        });

        html += '</tbody></table>';
        wrap.innerHTML = html;
        wrap.classList.remove('d-none');
        empty.classList.add('d-none');
    }

    function renderDns(records) {
        var wrap  = document.getElementById('dns-content');
        var empty = document.getElementById('dns-empty');

        if (!records || !records.length) {
            wrap.innerHTML = '';
            wrap.classList.add('d-none');
            empty.classList.remove('d-none');
            return;
        }

        // Group by root_domain
        var grouped = {};
        records.forEach(function (r) {
            var d = r.root_domain || 'Unknown';
            if (!grouped[d]) grouped[d] = [];
            grouped[d].push(r);
        });

        var html = '';
        Object.keys(grouped).sort().forEach(function (domain) {
            html += '<div class="fw-semibold mb-1 mt-2" style="font-size:0.8rem;">' + esc(domain) + '</div>'
                  + '<table class="table table-sm table-hover sv-ip-table mb-2">'
                  + '<thead><tr><th>Type</th><th>Name</th><th>Value</th><th>TTL</th></tr></thead><tbody>';

            grouped[domain].forEach(function (r) {
                var cls = 'dns-' + (['A','AAAA','CNAME','MX','TXT','NS'].includes(r.record_type) ? r.record_type : 'other');
                html += '<tr>'
                    + '<td><span class="badge ' + cls + '" style="font-size:0.7rem;">' + esc(r.record_type) + '</span></td>'
                    + '<td class="font-monospace" style="font-size:0.75rem;">' + esc(r.name) + '</td>'
                    + '<td class="font-monospace text-muted" style="font-size:0.75rem; word-break:break-all;">' + esc(r.value) + '</td>'
                    + '<td class="text-muted" style="font-size:0.75rem;">' + esc(r.ttl) + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>';
        });

        wrap.innerHTML = html;
        wrap.classList.remove('d-none');
        empty.classList.add('d-none');
    }

<?php if ($terminalEnabled && $terminalWsUrl !== ''): ?>
    /* ── xterm.js + ttyd WebSocket ──────────────────────────────────────── */
    var WS_URL = <?= json_encode($terminalWsUrl) ?>;

    var term = new Terminal({
        cursorBlink:  true,
        fontSize:     14,
        fontFamily:   '"JetBrains Mono", "Cascadia Code", "Fira Code", Menlo, Consolas, "Courier New", monospace',
        scrollback:   5000,
        theme: {
            background:          '#1a1b1e',
            foreground:          '#c9d1d9',
            cursor:              '#e06c75',
            selectionBackground: 'rgba(255,255,255,0.15)',
            black:   '#000000', brightBlack:   '#5c6370',
            red:     '#e06c75', brightRed:     '#e06c75',
            green:   '#98c379', brightGreen:   '#98c379',
            yellow:  '#e5c07b', brightYellow:  '#e5c07b',
            blue:    '#61afef', brightBlue:    '#61afef',
            magenta: '#c678dd', brightMagenta: '#c678dd',
            cyan:    '#56b6c2', brightCyan:    '#56b6c2',
            white:   '#abb2bf', brightWhite:   '#ffffff',
        }
    });

    var fitAddon = new FitAddon.FitAddon();
    term.loadAddon(fitAddon);
    term.open(document.getElementById('xterm-mount'));
    fitAddon.fit();

    var ws = new WebSocket(WS_URL);
    ws.binaryType = 'arraybuffer';

    function sendResize() {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send('1' + JSON.stringify({ columns: term.cols, rows: term.rows }));
        }
    }

    ws.onopen = function () {
        var statusEl = document.getElementById('terminal-status');
        statusEl.textContent = 'Connected';
        statusEl.className = 'badge bg-success-lt text-success ms-1';
        fitAddon.fit();
        sendResize();
    };

    ws.onmessage = function (evt) {
        if (!(evt.data instanceof ArrayBuffer)) return;
        var uint8 = new Uint8Array(evt.data);
        var type  = String.fromCharCode(uint8[0]);
        if (type === '1') {
            term.write(uint8.slice(1));
        }
        // type '2' = ping, '3' = set_window_title, '4' = set_preferences — not needed
    };

    ws.onerror = function () {
        term.write('\r\n\x1b[31m[WebSocket error — check TERMINAL_WS_URL in .env]\x1b[0m\r\n');
    };

    ws.onclose = function () {
        var statusEl = document.getElementById('terminal-status');
        statusEl.textContent = 'Disconnected';
        statusEl.className = 'badge bg-danger-lt text-danger ms-1';
        term.write('\r\n\x1b[33m[Session ended — reload to reconnect]\x1b[0m\r\n');
    };

    term.onData(function (data) {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send('0' + data);
        }
    });

    // Resize terminal when its container changes size
    var resizeObserver = new ResizeObserver(function () {
        fitAddon.fit();
        sendResize();
    });
    resizeObserver.observe(document.getElementById('xterm-mount'));

    window.addEventListener('beforeunload', function () { ws.close(); });
<?php endif; ?>

}());
</script>
