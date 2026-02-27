<?php
/**
 * Vars: $license[], $clients[], $allPlans[], $sources[], $intervals[]
 */

$old = json_decode(get_flash('old')[0] ?? '{}', true) ?: [];
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="ti ti-brand-windows me-2 text-blue"></i>Edit M365 License
        </h3>
    </div>
    <div class="card-body">
        <form method="post" action="<?= url('/m365/' . $license['id'] . '/update') ?>">
            <?= csrf_field() ?>
            <?php \App\Core\View::partial('m365/form', compact('license', 'clients', 'allPlans', 'sources', 'intervals', 'old')) ?>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Save Changes
                </button>
                <a href="<?= url('/m365/' . $license['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
