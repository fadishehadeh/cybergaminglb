<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Shipping;

/** Delivery fee and type (local / remote) per zone, read from the admin-editable delivery_zones table. */
$zones = Shipping::zones();
$freeOver = Shipping::freeOver();
?>
<table class="zone-table">
    <caption class="sr-only">Delivery fee and delivery type by area</caption>
    <thead><tr><th scope="col">Area</th><th scope="col">Delivery fee</th><th scope="col">Type</th></tr></thead>
    <tbody>
        <?php foreach ($zones as $z): ?>
            <tr>
                <th scope="row"><?= e($z['name']) ?></th>
                <td><?= e(money($z['fee'])) ?></td>
                <td class="zone-type"><span class="<?= $z['mode'] === 'local' ? 'zone-local' : 'zone-remote' ?>"><?= $z['mode'] === 'local' ? 'Local' : 'Remote' ?></span><br><small><?= e(substr(Rules::typeLabel($z['mode']), strpos(Rules::typeLabel($z['mode']), ':') + 2)) ?></small></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php if ($freeOver > 0): ?><p class="fine"><strong>Free delivery</strong> on orders of <?= e(money($freeOver)) ?> or more.</p><?php endif; ?>
