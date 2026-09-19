<?php
use App\Modules\Storefront\Ui;

/** @var array $given @var array $options @var array $errors @var array $crumbs @var ?array $customer @var string $loginUrl @var string $registerUrl */
$any = $given['matched'] > 0;
$unmatched = count($given['lines']) - $given['matched'];
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Your instant quote', 'lead' => $any
    ? 'Here is what your games are worth, in cash or in store credit. Credit is worth more, and you choose when you send your request.'
    : 'We could not price these automatically, but our team can. Send your request and we will come back with an offer.']);
?>
<div class="container cart-layout">
    <div>
        <section class="card-box" aria-labelledby="lines-h">
            <h2 id="lines-h">Your games</h2>
            <?= Ui::partial('quote-given', ['given' => $given, 'mode' => 'sell']) ?>
            <?php if ($any): ?>
                <?= Ui::partial('offer-compare', ['given' => $given]) ?>
            <?php endif; ?>
            <?php if ($unmatched > 0): ?>
                <p class="fine"><?= $unmatched === 1 ? 'One game is' : $unmatched . ' games are' ?> not in our catalogue yet, so we price <?= $unmatched === 1 ? 'it' : 'them' ?> for you after you send the request. <?= $unmatched === 1 ? 'It is' : 'They are' ?> not included in the totals above.</p>
            <?php endif; ?>
            <p class="fine">This is an estimate based on the condition you selected. We inspect your games in person, and you always see and accept our final offer before anything is paid. <a href="<?= e(url('/credit')) ?>">How store credit works</a></p>
            <?= Ui::partial('edit-list-form', ['mode' => 'sell', 'given' => $given]) ?>
        </section>
    </div>

    <aside class="summary" aria-labelledby="details-h">
        <?= Ui::partial('offer-panel', [
            'mode' => 'sell', 'given' => $given, 'wanted' => null, 'options' => $options, 'errors' => $errors,
            'customer' => $customer, 'loginUrl' => $loginUrl, 'registerUrl' => $registerUrl,
        ]) ?>
    </aside>
</div>
