<?php
use App\Modules\Storefront\Shipping;

/** Delivery fee per zone, read from the admin-editable delivery_zones table. */
$zones = Shipping::zones();
$freeOver = Shipping::freeOver();
?>
<table class="zone-table">
    <caption class="sr-only">Delivery fee by area</caption>
    <thead><tr><th scope="col">Area</th><th scope="col">Delivery fee</th></tr></thead>
    <tbody>
        <?php foreach ($zones as $z): ?>
            <tr><th scope="row"><?= e($z['name']) ?></th><td><?= e(money($z['fee'])) ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php if ($freeOver > 0): ?><p class="fine"><strong>Free delivery</strong> on orders of <?= e(money($freeOver)) ?> or more.</p><?php endif; ?>
