<?php
use App\Modules\Storefront\Ui;

/**
 * "Send my request" block of the quote pages: the form for signed-in customers, a sign-in prompt for guests.
 * @var string $mode sell|trade  @var array $given @var ?array $wanted @var array $options @var array $errors
 * @var ?array $customer @var string $loginUrl @var string $registerUrl
 */
$isTrade = $mode === 'trade';
$cash = (float) $given['total_cash'];
$credit = (float) $given['total_credit'];
?>
<?php if ($customer === null): ?>
    <h2 id="details-h">Send your request</h2>
    <?= Ui::partial('errors', ['errors' => $errors]) ?>
    <p>Your quote is saved. To send it to us you need a free account: it is where you track your offer and where your credit lives.</p>
    <p><a class="btn btn-primary btn-lg btn-block" href="<?= e(url($registerUrl)) ?>">Create a free account</a></p>
    <p><a class="btn btn-outline btn-block" href="<?= e(url($loginUrl)) ?>">I already have an account</a></p>
    <p class="fine">You come straight back to this quote afterwards. <a href="<?= e(url('/credit')) ?>">How store credit works</a></p>
<?php else:
    $method = $options['method'];
    $collection = $options['collection']; ?>
    <h2 id="details-h">Send your request</h2>
    <?= Ui::partial('errors', ['errors' => $errors]) ?>
    <p class="sending-as">Sending as <strong><?= e($customer['name']) ?></strong><br><small><?= e($customer['phone'] ?? '') ?><?= !empty($customer['area']) ? ' &middot; ' . e($customer['area']) : '' ?></small><br><a class="fine" href="<?= e(url('/account/profile')) ?>">Change my details</a></p>
    <form method="post" action="<?= e(url('/' . $mode . '/submit')) ?>" enctype="multipart/form-data" data-once>
        <?= csrf_field() ?>
        <?= Ui::partial('hidden-rows', ['given' => $given, 'wanted' => $wanted]) ?>

        <fieldset class="choice-set">
            <legend>How would you like to be paid?</legend>
            <label class="choice"><input type="radio" name="preferred_method" value="credit"<?= $method === 'credit' ? ' checked' : '' ?>><span><strong>Store credit</strong> <em><?= e(money($credit)) ?></em><small>Goes into your wallet, never expires<?= $credit > $cash ? ' (worth more)' : '' ?></small></span></label>
            <label class="choice"><input type="radio" name="preferred_method" value="cash"<?= $method === 'cash' ? ' checked' : '' ?>><span><strong>Cash</strong> <em><?= e(money($cash)) ?></em><small>Paid to you once we have inspected the games</small></span></label>
        </fieldset>

        <fieldset class="choice-set">
            <legend>How do we get your games?</legend>
            <label class="choice"><input type="radio" name="collection" value="dropoff"<?= $collection === 'dropoff' ? ' checked' : '' ?>><span><strong>I will drop them off</strong><small>At our pickup point</small></span></label>
            <label class="choice"><input type="radio" name="collection" value="pickup"<?= $collection === 'pickup' ? ' checked' : '' ?>><span><strong>Please collect them</strong><small>We come to your area</small></span></label>
        </fieldset>
        <div class="form-row">
            <label for="o-pickup">Pickup details <span class="opt">(if you chose collection)</span></label>
            <input id="o-pickup" name="pickup_note" type="text" maxlength="255" placeholder="Address hint, best time, floor..." value="<?= e($options['pickup_note']) ?>">
        </div>
        <div class="form-row">
            <label for="o-note">Note <span class="opt">(optional)</span></label>
            <textarea id="o-note" name="note" rows="2" maxlength="500" placeholder="Extras in the box, anything we should know."><?= e($options['note']) ?></textarea>
        </div>
        <div class="form-row photo-upload">
            <label for="o-photos"><?= \App\Modules\Storefront\Ui::icon('camera', 16) ?> Photos <span class="opt">(optional)</span></label>
            <input id="o-photos" name="photos[]" type="file" multiple accept="image/*" data-max="<?= (int) \App\Modules\Storefront\Quoter::MAX_PHOTOS ?>" aria-describedby="o-photos-help">
            <small id="o-photos-help">Photos help us make a better offer &mdash; add up to <?= (int) \App\Modules\Storefront\Quoter::MAX_PHOTOS ?>: the discs and the boxes (outside and inside). Location data is removed automatically.</small>
            <small class="photo-count" data-photo-count hidden></small>
        </div>
        <div class="hp" aria-hidden="true"><label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <button class="btn btn-primary btn-lg btn-block" type="submit">Send my request</button>
        <p class="fine">Nothing is charged. We review your request, send you an offer, and you accept it before anything moves.</p>
    </form>
<?php endif; ?>
