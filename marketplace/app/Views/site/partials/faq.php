<?php
/** @var array<int,array{0:string,1:string}> $faqs */
?>
<section class="faq" aria-labelledby="faq-h">
    <h2 id="faq-h">Frequently asked questions</h2>
    <?php foreach ($faqs as [$q, $a]): ?>
        <details>
            <summary><?= e($q) ?></summary>
            <p><?= e($a) ?></p>
        </details>
    <?php endforeach; ?>
</section>
