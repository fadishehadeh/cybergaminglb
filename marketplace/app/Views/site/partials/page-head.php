<?php
use App\Modules\Storefront\Ui;

/** @var array $crumbs @var string $h1 @var string $lead */
?>
<div class="container page-head">
    <?= Ui::breadcrumbs($crumbs) ?>
    <h1><?= e($h1) ?></h1>
    <?php if (!empty($lead)): ?><p class="lead-sm"><?= e($lead) ?></p><?php endif; ?>
</div>
