<?php
/**
 * Vars: $templates[], $domains[], $presetDomain (int), $presetTpl (int)
 *
 * $templates — all templates with 'records' key embedded (for JS variable detection)
 */

// Build a JS-friendly map: templateId → [list of variable names needed (excluding 'domain')]
$tplVarsMap = [];
foreach ($templates as $tpl) {
    $vars = \App\Models\DnsTemplateModel::detectVariables($tpl['records'] ?? []);
    $tplVarsMap[(int) $tpl['id']] = $vars;
}
?>

<form method="post" action="<?= url('/dns/apply-template/preview') ?>" id="apply-form">
    <?= csrf_field() ?>

    <div class="row g-4">

        <!-- ── Left: domain + template ──────────────────────────────────────── -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Select Domain &amp; Template</h4></div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label required">Domain</label>
                        <select name="domain_id" id="inp-domain" class="form-select" required>
                            <option value="">— Select domain —</option>
                            <?php foreach ($domains as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $presetDomain === (int) $d['id'] ? 'selected' : '' ?>>
                                <?= e($d['root_domain']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label required">Template</label>
                        <select name="template_id" id="inp-template" class="form-select" required
                                onchange="svTemplateChanged(this.value)">
                            <option value="">— Select template —</option>

                            <?php
                            $builtins = array_filter($templates, fn($t) => $t['is_builtin']);
                            $customs  = array_filter($templates, fn($t) => !$t['is_builtin']);
                            ?>

                            <?php if (!empty($builtins)): ?>
                            <optgroup label="Built-in">
                                <?php foreach ($builtins as $tpl): ?>
                                <option value="<?= $tpl['id'] ?>" <?= $presetTpl === (int) $tpl['id'] ? 'selected' : '' ?>>
                                    <?= e($tpl['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($customs)): ?>
                            <optgroup label="Custom">
                                <?php foreach ($customs as $tpl): ?>
                                <option value="<?= $tpl['id'] ?>" <?= $presetTpl === (int) $tpl['id'] ? 'selected' : '' ?>>
                                    <?= e($tpl['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── Right: variables ──────────────────────────────────────────────── -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Template Variables</h4></div>
                <div class="card-body">

                    <p class="text-muted small mb-3">
                        Fill in any variables required by the selected template.
                        <code>{domain}</code> is automatically filled from the domain you selected.
                    </p>

                    <!-- {ip} -->
                    <div class="mb-3 var-field" id="var-ip" style="display:none">
                        <label class="form-label"><code>{ip}</code> — IPv4 Address</label>
                        <input type="text" name="var_ip" class="form-control font-monospace"
                               placeholder="e.g. 93.184.216.34">
                    </div>

                    <!-- {mail_server} -->
                    <div class="mb-3 var-field" id="var-mail_server" style="display:none">
                        <label class="form-label"><code>{mail_server}</code> — Mail Server Hostname</label>
                        <input type="text" name="var_mail_server" class="form-control font-monospace"
                               placeholder="e.g. mail.example.com">
                    </div>

                    <!-- {spf} -->
                    <div class="mb-0 var-field" id="var-spf" style="display:none">
                        <label class="form-label"><code>{spf}</code> — SPF Include / Mechanism</label>
                        <input type="text" name="var_spf" class="form-control font-monospace"
                               placeholder="e.g. include:_spf.provider.com">
                    </div>

                    <div id="var-none" class="text-muted small fst-italic">
                        Select a template to see required variables.
                    </div>

                </div>
            </div>
        </div>

    </div><!-- /.row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-eye me-1"></i>Preview Records
        </button>
        <a href="<?= url('/dns/templates') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
const SV_TPL_VARS = <?= json_encode($tplVarsMap) ?>;
const ALL_VARS    = ['ip', 'mail_server', 'spf'];

function svTemplateChanged(id) {
    const needed = SV_TPL_VARS[id] || [];
    const noneEl = document.getElementById('var-none');
    let anyShown = false;

    ALL_VARS.forEach(v => {
        const el = document.getElementById('var-' + v);
        if (!el) return;
        const show = needed.includes(v);
        el.style.display = show ? '' : 'none';
        if (show) anyShown = true;
    });

    noneEl.style.display = anyShown ? 'none' : '';
}

// Trigger on load if preset
(function () {
    const sel = document.getElementById('inp-template');
    if (sel && sel.value) svTemplateChanged(sel.value);
})();
</script>
