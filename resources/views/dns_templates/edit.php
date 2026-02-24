<?php
/**
 * Vars: $template[], $records[], $errors[]
 */
$id = (int) $template['id'];
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible mb-4">
    <div class="d-flex gap-2">
        <i class="ti ti-alert-circle fs-4 flex-shrink-0"></i>
        <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php \App\Core\View::partial('dns_templates/form', [
    'action'      => url('/dns/templates/' . $id . '/update'),
    'submitLabel' => 'Save Changes',
    'template'    => $template,
    'records'     => $records,
]); ?>
