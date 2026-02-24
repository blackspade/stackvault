<?php
/**
 * Vars: $reminder[], $types[], $clients[]
 */

$old = json_decode(get_flash('old')[0] ?? '{}', true) ?: [];
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Reminder</h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('/reminders/' . $reminder['id'] . '/update') ?>">
            <?= csrf_field() ?>
            <?php \App\Core\View::partial('reminders/form', compact('types', 'clients', 'old', 'reminder')) ?>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Save Changes
                </button>
                <a href="<?= url('/reminders') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
