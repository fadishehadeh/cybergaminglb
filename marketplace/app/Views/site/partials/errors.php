<?php /** @var string[] $errors */ ?>
<?php if (!empty($errors)): ?>
    <div class="form-errors" role="alert">
        <strong>Please fix the following:</strong>
        <ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>
