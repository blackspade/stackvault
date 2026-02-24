<?php
/**
 * Vars: $set[], $errors[], $clients[]
 */
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
        <li><?= e($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">

        <form method="post" action="<?= url('/bookmarks/' . (int) $set['id'] . '/update') ?>">
            <?= csrf_field() ?>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Edit Bookmark Set</h3>
                </div>
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($set['name']) ?>"
                               maxlength="255" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Browser</label>
                        <select name="browser" class="form-select">
                            <?php foreach (\App\Models\BookmarkSetModel::BROWSERS as $key => $label): ?>
                            <option value="<?= $key ?>"
                                <?= ($set['browser'] === $key) ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client <span class="text-muted">(optional)</span></label>
                        <select name="client_id" class="form-select">
                            <option value="">— No Client —</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= ((int) $set['client_id'] === (int) $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <textarea name="description" class="form-control" rows="3"><?= e($set['description']) ?></textarea>
                    </div>

                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>Save Changes
                </button>
                <a href="<?= url('/bookmarks/' . (int) $set['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>

    </div>
</div>
