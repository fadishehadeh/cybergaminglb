<?php
/** Mini navigation shared by the sell / trade / swap pages. @var string $active sell|trade|swap */
$tabs = ['sell' => ['Sell', '/sell'], 'trade' => ['Trade in', '/trade'], 'swap' => ['Swap', '/swap']];
?>
<nav class="flow-nav" aria-label="Sell, trade or swap">
    <ul>
        <?php foreach ($tabs as $key => [$label, $path]): ?>
            <li><a class="flow-tab<?= $active === $key ? ' is-current' : '' ?>" href="<?= e(url($path)) ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
