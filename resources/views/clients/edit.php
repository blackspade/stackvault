<?php
/**
 * Edit client view
 * Vars: $client[], $errors[]
 */
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible mb-4">
    <div class="d-flex gap-2">
        <i class="ti ti-alert-circle fs-4 flex-shrink-0"></i>
        <div>
            <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Edit Details</h3>
    </div>
    <div class="card-body">
        <?php \App\Core\View::partial('clients/form', [
            'client'      => $client,
            'action'      => url('/clients/' . $client['id'] . '/update'),
            'submitLabel' => 'Save Changes',
        ]); ?>
    </div>
</div>
