<?php
/**
 * Shared client form component.
 *
 * Expected vars:
 *   $client   — array of current field values (empty array for create)
 *   $action   — form POST action URL
 *   $submitLabel — button text
 */
$v = fn(string $key): string => e($client[$key] ?? '');
?>

<form action="<?= $action ?>" method="post" novalidate>
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- Name (required) -->
        <div class="col-12">
            <label for="name" class="form-label required">Client Name</label>
            <input type="text" id="name" name="name" class="form-control"
                   value="<?= $v('name') ?>"
                   placeholder="e.g. Acme Corp"
                   maxlength="255" required autofocus>
        </div>

        <!-- Contact Name -->
        <div class="col-md-6">
            <label for="contact_name" class="form-label">Contact Name</label>
            <input type="text" id="contact_name" name="contact_name" class="form-control"
                   value="<?= $v('contact_name') ?>"
                   placeholder="e.g. Jane Smith"
                   maxlength="255">
        </div>

        <!-- Contact Email -->
        <div class="col-md-6">
            <label for="contact_email" class="form-label">Contact Email</label>
            <input type="email" id="contact_email" name="contact_email" class="form-control"
                   value="<?= $v('contact_email') ?>"
                   placeholder="jane@example.com"
                   maxlength="255">
        </div>

        <!-- Contact Phone -->
        <div class="col-md-6">
            <label for="contact_phone" class="form-label">Contact Phone</label>
            <input type="text" id="contact_phone" name="contact_phone" class="form-control"
                   value="<?= $v('contact_phone') ?>"
                   placeholder="+1 555 000 0000"
                   maxlength="50">
        </div>

        <!-- Website -->
        <div class="col-md-6">
            <label for="website" class="form-label">Website</label>
            <input type="url" id="website" name="website" class="form-control"
                   value="<?= $v('website') ?>"
                   placeholder="https://example.com"
                   maxlength="500">
        </div>

        <!-- Active -->
        <div class="col-12">
            <label class="form-check">
                <input type="checkbox" name="is_active" value="1" class="form-check-input"
                    <?= !empty($client['is_active']) || empty($client) ? 'checked' : '' ?>>
                <span class="form-check-label">Active client</span>
            </label>
        </div>

    </div><!-- /row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel ?? 'Save') ?>
        </button>
        <a href="<?= url('/clients') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>
