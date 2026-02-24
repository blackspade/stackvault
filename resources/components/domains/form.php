<?php
/**
 * Shared domain form component.
 *
 * Expected vars:
 *   $domain      — array of current field values (empty array for create)
 *   $clients     — array from ClientModel::getForSelect()
 *   $action      — form POST action URL
 *   $submitLabel — button text
 */
$v   = fn(string $key): string => e($domain[$key] ?? '');
$sel = fn(int $id): string => ((int)($domain['client_id'] ?? 0)) === $id ? 'selected' : '';
?>

<form action="<?= $action ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- Root Domain (required) -->
        <div class="col-md-8">
            <label for="root_domain" class="form-label required">Root Domain</label>
            <input type="text" id="root_domain" name="root_domain" class="form-control"
                   value="<?= $v('root_domain') ?>"
                   placeholder="e.g. example.com"
                   maxlength="255" required autofocus>
            <div class="form-hint">Enter the root domain only — no http:// or www prefix.</div>
        </div>

        <!-- Client -->
        <div class="col-md-4">
            <label for="client_id" class="form-label">Client</label>
            <select id="client_id" name="client_id" class="form-select">
                <option value="0">— No client —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $sel((int)$c['id']) ?>>
                    <?= e($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Registrar -->
        <div class="col-md-6">
            <label for="registrar" class="form-label">Registrar</label>
            <input type="text" id="registrar" name="registrar" class="form-control"
                   value="<?= $v('registrar') ?>"
                   placeholder="e.g. Namecheap, GoDaddy, Cloudflare"
                   maxlength="100">
        </div>

        <!-- Expiry Date -->
        <div class="col-md-3">
            <label for="expiry_date" class="form-label">Registration Expiry</label>
            <input type="date" id="expiry_date" name="expiry_date" class="form-control"
                   value="<?= $v('expiry_date') ?>">
        </div>

        <!-- SSL Expiry -->
        <div class="col-md-3">
            <label for="ssl_expiry" class="form-label">SSL Certificate Expiry</label>
            <input type="date" id="ssl_expiry" name="ssl_expiry" class="form-control"
                   value="<?= $v('ssl_expiry') ?>">
        </div>

        <!-- Nameservers -->
        <div class="col-12">
            <label for="nameservers" class="form-label">Nameservers</label>
            <textarea id="nameservers" name="nameservers" class="form-control" rows="3"
                      placeholder="ns1.example.com&#10;ns2.example.com"><?= $v('nameservers') ?></textarea>
            <div class="form-hint">One per line, or comma-separated.</div>
        </div>

        <!-- Notes -->
        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="3"
                      placeholder="Any relevant notes…"><?= $v('notes') ?></textarea>
        </div>

        <!-- Active -->
        <div class="col-12">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                    <?= !empty($domain['is_active']) || empty($domain) ? 'checked' : '' ?>>
                <span class="form-check-label">Active domain</span>
            </label>
        </div>

    </div><!-- /row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel ?? 'Save') ?>
        </button>
        <a href="<?= url('/domains') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>
