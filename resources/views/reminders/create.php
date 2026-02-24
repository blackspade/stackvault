<?php
/**
 * Vars: $types[], $clients[]
 */

$old = json_decode(get_flash('old')[0] ?? '{}', true) ?: [];
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">New Reminder</h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('/reminders/store') ?>">
            <?= csrf_field() ?>
            <?php \App\Core\View::partial('reminders/form', compact('types', 'clients', 'old')) ?>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-bell-plus me-1"></i>Add Reminder
                </button>
                <a href="<?= url('/reminders') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
