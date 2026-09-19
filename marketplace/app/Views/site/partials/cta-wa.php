<?php
use App\Modules\Storefront\Ui;

/** @var string $title @var string $text @var string $message @var string $button */
?>
<section class="cta-block cta-inline">
    <div>
        <h2><?= e($title) ?></h2>
        <p><?= e($text) ?></p>
    </div>
    <div class="cta-actions">
        <a class="btn btn-wa btn-xl" href="<?= e(wa_link($message)) ?>" rel="noopener" target="_blank"><?= Ui::icon('whatsapp', 24) ?> <?= e($button) ?></a>
    </div>
</section>
