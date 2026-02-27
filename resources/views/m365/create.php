<?php
/**
 * Vars: $clients[], $allPlans[], $sources[], $intervals[]
 */

$old = json_decode(get_flash('old')[0] ?? '{}', true) ?: [];
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-brand-windows me-2 text-blue"></i>New M365 License
        </h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('/m365/store') ?>">
            <?= csrf_field() ?>
            <?php \App\Core\View::partial('m365/form', compact('clients', 'allPlans', 'sources', 'intervals', 'old')) ?>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Add License
                </button>
                <a href="<?= url('/m365') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
