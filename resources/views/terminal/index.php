<?php
/**
 * Terminal view — full-screen terminal with a floating/dockable client reference panel.
 *
 * Variables:
 *   $clients          array  — [id, name] for the client selector
 *   $terminalEnabled  bool   — TERMINAL_ENABLED from .env
 *   $terminalWsUrl    string — TERMINAL_WS_URL from .env (WebSocket URL for ttyd)
 */
?>

<!-- ── Terminal pane (fills entire sv-terminal-body) ─────────────────────── -->
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


<!-- ── Floating client reference panel ───────────────────────────────────── -->
<div id="sv-float-panel" class="dock-right">

    <!-- Toolbar / drag handle -->
    <div class="sv-float-toolbar" id="sv-float-toolbar">
        <i class="ti ti-grip-vertical sv-drag-handle" id="sv-drag-handle" title="Drag to float"></i>
        <span class="sv-panel-title" id="sv-panel-title">
            <i class="ti ti-users me-1 text-muted"></i>Client Reference
        </span>
        <!-- Dock buttons -->
        <button class="btn-panel" id="btn-dock-left"   title="Dock left"   onclick="SvPanel.dock('left')">
            <i class="ti ti-layout-sidebar"></i>
        </button>
        <button class="btn-panel active" id="btn-dock-right"  title="Dock right"  onclick="SvPanel.dock('right')">
            <i class="ti ti-layout-sidebar-right"></i>
        </button>
        <button class="btn-panel" id="btn-dock-bottom" title="Dock bottom" onclick="SvPanel.dock('bottom')">
            <i class="ti ti-layout-bottombar"></i>
        </button>
        <!-- Minimize -->
        <button class="btn-panel" id="btn-minimize" title="Minimize" onclick="SvPanel.toggleMinimize()">
            <i class="ti ti-minus" id="minimize-icon"></i>
        </button>
    </div>

    <!-- Panel body -->
    <div class="sv-float-body">

        <!-- Client selector -->
        <div class="sv-float-client-row">
            <select id="sv-client-select" class="form-select form-select-sm">
                <option value="">— Select a client —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tab bar -->
        <div class="sv-float-tabs mt-2">
            <ul class="nav nav-tabs nav-tabs-alt" id="info-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-tab="docs" href="#">
                        <i class="ti ti-file-text me-1"></i>Docs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-tab="ips" href="#">
                        <i class="ti ti-table me-1"></i>IPs
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
        <div class="sv-float-content" id="info-content">

            <!-- Empty state -->
            <div id="tab-empty" class="text-center text-muted py-4">
                <i class="ti ti-arrow-up" style="font-size:1.5rem;"></i>
                <div class="mt-1" style="font-size:0.8125rem;">Select a client to load reference data</div>
            </div>

            <!-- Loading spinner -->
            <div id="tab-loading" class="text-center text-muted py-4 d-none">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="mt-1" style="font-size:0.8125rem;">Loading…</div>
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

        </div><!-- /sv-float-content -->
    </div><!-- /sv-float-body -->
</div><!-- /sv-float-panel -->


<script>
(function () {
    'use strict';

    /* ── Panel state ────────────────────────────────────────────────────── */
    var STORE_KEY = 'sv_term_panel';
    var panel     = document.getElementById('sv-float-panel');
    var body      = document.querySelector('.sv-terminal-body');

    var state = {
        dock:       'right',   // 'left' | 'right' | 'bottom' | 'float'
        minimized:  false,
        x:          null,      // used only in float mode
        y:          null,
        w:          360,
        h:          480,
    };

    // Load persisted state
    try {
        var saved = JSON.parse(localStorage.getItem(STORE_KEY) || '{}');
        Object.assign(state, saved);
    } catch (e) {}

    function saveState() {
        try { localStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch (e) {}
    }

    /* ── Apply state to DOM ─────────────────────────────────────────────── */
    var DOCK_CLASSES = ['dock-left', 'dock-right', 'dock-bottom', 'floating'];

    function applyState() {
        // Dock class
        DOCK_CLASSES.forEach(function (c) { panel.classList.remove(c); });
        if (state.dock === 'float') {
            panel.classList.add('floating');
            panel.style.top    = (state.y ?? 60) + 'px';
            panel.style.left   = (state.x ?? (body.offsetWidth - state.w - 20)) + 'px';
            panel.style.width  = state.w + 'px';
            panel.style.height = state.minimized ? '' : state.h + 'px';
            panel.style.right  = '';
            panel.style.bottom = '';
        } else {
            panel.classList.add('dock-' + state.dock);
            panel.style.top = panel.style.left = panel.style.right =
                panel.style.bottom = panel.style.width = panel.style.height = '';
        }

        // Minimized
        panel.classList.toggle('minimized', state.minimized);
        document.getElementById('minimize-icon').className =
            state.minimized ? 'ti ti-chevron-up' : 'ti ti-minus';

        // Active dock button
        ['left','right','bottom'].forEach(function (d) {
            document.getElementById('btn-dock-' + d).classList.toggle('active', state.dock === d);
        });

        notifyTermResize();
    }

    /* ── Public API ─────────────────────────────────────────────────────── */
    window.SvPanel = {
        dock: function (side) {
            if (state.dock === side) {
                // Clicking active dock → switch to floating
                state.dock = 'float';
                state.x = body.offsetWidth - state.w - 20;
                state.y = 60;
            } else {
                state.dock = side;
            }
            state.minimized = false;
            saveState();
            applyState();
        },
        toggleMinimize: function () {
            state.minimized = !state.minimized;
            saveState();
            applyState();
        }
    };

    /* ── Drag (float mode only) ─────────────────────────────────────────── */
    var dragHandle = document.getElementById('sv-drag-handle');
    var dragging   = false;
    var dragStartX, dragStartY, panelStartX, panelStartY;

    dragHandle.addEventListener('mousedown', function (e) {
        e.preventDefault();
        // Undock to float if currently docked
        if (state.dock !== 'float') {
            var rect  = panel.getBoundingClientRect();
            var bRect = body.getBoundingClientRect();
            state.dock = 'float';
            state.x = rect.left - bRect.left;
            state.y = rect.top  - bRect.top;
            state.w = rect.width;
            state.h = rect.height;
            state.minimized = false;
            applyState();
        }
        dragging    = true;
        dragStartX  = e.clientX;
        dragStartY  = e.clientY;
        panelStartX = state.x;
        panelStartY = state.y;
        document.body.style.cursor = 'grabbing';
        document.body.style.userSelect = 'none';
    });

    document.addEventListener('mousemove', function (e) {
        if (!dragging) return;
        var dx = e.clientX - dragStartX;
        var dy = e.clientY - dragStartY;
        var bRect = body.getBoundingClientRect();
        state.x = Math.max(0, Math.min(panelStartX + dx, bRect.width  - state.w));
        state.y = Math.max(0, Math.min(panelStartY + dy, bRect.height - 40));
        panel.style.left = state.x + 'px';
        panel.style.top  = state.y + 'px';
    });

    document.addEventListener('mouseup', function () {
        if (!dragging) return;
        dragging = false;
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        saveState();
        notifyTermResize();
    });

    /* ── Notify xterm to re-fit after panel layout changes ─────────────── */
    function notifyTermResize() {
        // Delay slightly so CSS transitions settle
        setTimeout(function () {
            if (window._svFitAddon) {
                try { window._svFitAddon.fit(); } catch (e) {}
            }
        }, 160);
    }

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
        if (!clientId) { showEmpty(); return; }
        loadClientData(clientId);
    });

    function showEmpty() {
        document.getElementById('tab-empty').classList.remove('d-none');
        document.getElementById('tab-loading').classList.add('d-none');
        ['docs','ips','servers','dns'].forEach(function (t) {
            document.getElementById('tab-' + t).classList.add('d-none');
        });
    }

    function loadClientData(clientId) {
        document.getElementById('tab-empty').classList.add('d-none');
        document.getElementById('tab-loading').classList.remove('d-none');
        ['docs','ips','servers','dns'].forEach(function (t) {
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
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderDocs(content) {
        var el    = document.getElementById('docs-content');
        var empty = document.getElementById('docs-empty');
        if (content && content.trim()) {
            el.innerHTML = content;
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
            if (tbl.name) html += '<div class="fw-semibold mb-1 mt-2" style="font-size:0.8rem;">' + esc(tbl.name) + '</div>';
            html += '<table class="table table-sm table-hover sv-ip-table mb-2">'
                  + '<thead><tr><th>IP</th><th>Label</th><th>Port</th><th>Notes</th></tr></thead><tbody>';
            (tbl.rows || []).forEach(function (row) {
                html += '<tr>'
                    + '<td class="font-monospace">' + esc(row.ip) + '</td>'
                    + '<td>' + esc(row.label) + '</td>'
                    + '<td class="text-muted">' + esc(row.port) + '</td>'
                    + '<td class="text-muted" style="white-space:normal;word-break:break-word;">' + esc(row.notes) + '</td>'
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
                    + '<td class="font-monospace text-muted" style="font-size:0.75rem;word-break:break-all;">' + esc(r.value) + '</td>'
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
    window._svFitAddon = fitAddon;
    term.loadAddon(fitAddon);
    term.open(document.getElementById('xterm-mount'));
    fitAddon.fit();
    term.focus();

    document.getElementById('xterm-mount').addEventListener('click', function () { term.focus(); });

    var ws = new WebSocket(WS_URL, ['tty']);
    ws.binaryType = 'arraybuffer';

    var encoder = new TextEncoder();

    function sendResize() {
        if (ws.readyState === WebSocket.OPEN) {
            ws.send(encoder.encode('1' + JSON.stringify({ columns: term.cols, rows: term.rows })));
        }
    }

    ws.onopen = function () {
        ws.send(JSON.stringify({ AuthToken: '' }));
        fitAddon.fit();
        sendResize();
        var statusEl = document.getElementById('terminal-status');
        statusEl.textContent = 'Connected';
        statusEl.className = 'badge bg-success-lt text-success ms-1';
    };

    ws.onmessage = function (evt) {
        var raw = evt.data;
        if (typeof raw === 'string') {
            try {
                var msg = JSON.parse(raw);
                if (msg.AuthToken !== undefined) ws.send(JSON.stringify({ AuthToken: msg.AuthToken }));
            } catch (e) {}
            return;
        }
        if (raw instanceof ArrayBuffer) {
            var uint8 = new Uint8Array(raw);
            if (uint8[0] === 0x30) term.write(uint8.slice(1));
        }
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
        if (ws.readyState === WebSocket.OPEN) ws.send(encoder.encode('0' + data));
    });

    var resizeObserver = new ResizeObserver(function () { fitAddon.fit(); sendResize(); });
    resizeObserver.observe(document.getElementById('xterm-mount'));

    window.addEventListener('beforeunload', function () { ws.close(); });
<?php endif; ?>

    /* ── Init ───────────────────────────────────────────────────────────── */
    applyState();

}());
</script>
