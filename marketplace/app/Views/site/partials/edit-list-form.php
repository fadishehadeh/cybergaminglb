<?php
/** "Change my list" button: posts the same rows back to the form. @var string $mode @var array $given @var ?array $wanted */
?>
<form class="edit-list" method="post" action="<?= e(url('/' . $mode . '/edit')) ?>">
    <?= csrf_field() ?>
    <?= \App\Modules\Storefront\Ui::partial('hidden-rows', ['given' => $given, 'wanted' => $wanted ?? null]) ?>
    <button class="btn btn-ghost btn-sm" type="submit">Change my list</button>
</form>
