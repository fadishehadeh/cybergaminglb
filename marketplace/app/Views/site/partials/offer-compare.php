<?php
/** Cash and credit totals side by side. @var array $given */
$cash = (float) $given['total_cash'];
$credit = (float) $given['total_credit'];
$diff = round($credit - $cash, 2);
?>
<div class="offer-compare" role="group" aria-label="Your estimate in cash or store credit">
    <div class="offer-box">
        <span>Cash</span>
        <strong><?= e(money($cash)) ?></strong>
        <small>paid to you after inspection</small>
    </div>
    <div class="offer-box<?= $diff > 0 ? ' is-best' : '' ?>">
        <span>Store credit</span>
        <strong><?= e(money($credit)) ?></strong>
        <small><?= $diff > 0 ? 'credit is worth more: +' . e(money($diff)) . ' vs cash' : 'added to your wallet' ?></small>
    </div>
</div>
