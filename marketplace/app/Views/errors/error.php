<?php
use App\Modules\Storefront\Ui;

/** @var int $status @var string $title @var string $message */
$meta = ['title' => ($title ?? 'Error') . ' | CyberGaming', 'noindex' => true, 'description' => 'Something went wrong.'];
?>
<div class="container error-page">
    <p class="error-code"><?= (int) ($status ?? 500) ?></p>
    <h1><?= e($title ?? 'Something went wrong') ?></h1>
    <p class="lead-sm"><?= e(($message ?? '') !== '' ? $message : 'Something went wrong on our side. Please try again in a moment.') ?></p>
    <p>
        <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to home</a>
        <a class="btn btn-wa" href="<?= e(wa_link('Hi CyberGaming!')) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 18) ?> WhatsApp us</a>
    </p>
</div>
