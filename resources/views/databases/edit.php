<?php /** Vars: $db[], $errors[], $types[], $clients[], $servers[], $apps[] */ ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible mb-4">
    <div class="d-flex gap-2">
        <i class="ti ti-alert-circle fs-4 flex-shrink-0"></i>
        <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title">Edit Database</h3></div>
    <div class="card-body">
        <?php \App\Core\View::partial('databases/form', [
            'db'          => $db,
            'types'       => $types,
            'clients'     => $clients,
            'servers'     => $servers,
            'apps'        => $apps,
            'action'      => url('/databases/' . $db['id'] . '/update'),
            'submitLabel' => 'Save Changes',
            'isEdit'      => true,
        ]); ?>
    </div>
</div>
