<?php
/**
 * Shared reminder form fields
 *
 * Vars: $types[], $clients[], $old[] (optional), $reminder[] (optional — edit mode)
 */

$old = $old ?? [];
$r   = $reminder ?? [];

$val = fn(string $k, mixed $default = '') => $old[$k] ?? $r[$k] ?? $default;
?>

<div class="row g-3">

    <div class="col-md-8">
        <label class="form-label required" for="title">Title</label>
        <input type="text" id="title" name="title"
               class="form-control"
               value="<?= e($val('title')) ?>"
               maxlength="255"
               placeholder="e.g. Renew domain example.com"
               required>
    </div>

    <div class="col-md-4">
        <label class="form-label required" for="type">Type</label>
        <select id="type" name="type" class="form-select">
            <?php foreach ($types as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $val('type', 'custom') === $key ? 'selected' : '' ?>>
                <?= e($label) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label required" for="due_date">Due Date</label>
        <input type="date" id="due_date" name="due_date"
               class="form-control"
               value="<?= e($val('due_date')) ?>"
               required>
    </div>

    <div class="col-md-4">
        <label class="form-label" for="client_id">Client <span class="text-muted">(optional)</span></label>
        <select id="client_id" name="client_id" class="form-select">
            <option value="">— No client —</option>
            <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>"
                <?= (int) $val('client_id', 0) === (int) $c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label" for="notes">Notes <span class="text-muted">(optional)</span></label>
        <textarea id="notes" name="notes"
                  class="form-control"
                  rows="3"
                  placeholder="Any extra context, renewal steps, account details…"><?= e($val('notes')) ?></textarea>
    </div>

</div>
