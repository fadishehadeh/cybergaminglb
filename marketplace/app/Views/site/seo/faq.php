<?php
use App\Modules\Storefront\Seo;

/** @var array<int,array{0:string,1:string}> $faqs answers may contain [[/path|label]] links @var string $heading */
?>
<section class="faq" aria-labelledby="faq-h">
    <h2 id="faq-h"><?= e($heading) ?></h2>
    <?php foreach ($faqs as [$q, $a]): ?>
        <details>
            <summary><?= e($q) ?></summary>
            <p><?= Seo::linkify($a) ?></p>
        </details>
    <?php endforeach; ?>
</section>
