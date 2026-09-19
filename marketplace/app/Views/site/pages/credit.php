<?php
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Shipping;
use App\Modules\Storefront\Ui;
use App\Support\Pricing;

/** @var array $crumbs */
$fmt = static fn (float $n): string => rtrim(rtrim(number_format($n, 1), '0'), '.');
$buyback = $fmt((float) setting('buyback_pct', 45));
$tradein = $fmt((float) setting('tradein_pct', 50));
$creditBetter = (float) setting('tradein_pct', 50) > (float) setting('buyback_pct', 45);
$memberPct = $fmt((float) setting('member_commission_pct', 10));
$freeOver = Shipping::freeOver();
$minFee = Shipping::minFee();
$example = 20.0;
$exCash = Pricing::buybackOffer($example, 'Good');
$exCredit = Pricing::tradeCredit($example, 'Good');
$prepayOn = Rules::prepayOn();
$pickupFeeText = money(Rules::pickupFee());
$zoneText = implode(', ', array_map(static fn (array $z): string => $z['name'] . ' ' . money($z['fee']), Shipping::zones()));

$faqs = [
    ['What is CyberGaming store credit?', 'Store credit is money in your CyberGaming wallet that you can only spend in our shop. 1 credit is always worth 1 US dollar, and it never expires.'],
    ['How do I earn credit?', 'Sell us your used games: we send you an offer, you accept it, we inspect the games and add the credit to your wallet. Members who sell games to other customers through CyberGaming can also be paid in credit when their item sells.'],
    ['How do I spend credit?', 'At checkout, tick "Pay with my credit". It is taken off your order, and if it does not cover everything you pay the rest in cash on delivery. If your credit covers the whole order, you pay nothing on delivery.'],
    ['Does credit expire?', 'No. Credit never expires and stays in your wallet until you spend it.'],
    ['Can I take cash instead of credit?', "Yes. When you sell us games you can choose cash or credit. Credit is worth more ($tradein% of the shop price against $buyback% in cash), but the choice is yours."],
    ['Is there a fee for using credit?', "No fee for earning or spending credit. Delivery is charged by area ($zoneText" . ($freeOver > 0 ? '; free over ' . money($freeOver) : '') . "), and members who sell to other customers pay a $memberPct% commission. If you sell us games and a courier picks them up, a $pickupFeeText pickup fee is deducted from your payout; bringing them to our hub is free."],
    ['Can I use credit outside the local area?', $prepayOn ? 'Yes. Credit is taken off your order in every area. In remote areas you prepay whatever it does not cover by OMT or Whish (cash on delivery there unlocks after ' . Rules::codAfter() . ' delivered orders); in local areas you pay the rest in cash on delivery.' : 'Yes. Credit is taken off your order in every area and you pay the rest on delivery.'],
    ['Is it safe?', 'We inspect every game we buy and every item we sell, and every credit movement is recorded in your wallet history. Buyers and sellers never see each other: your name, phone number and address stay private and only CyberGaming knows them.'],
    ['Do other members see who I am?', 'No. Every account has an anonymous ID and that is the only name anyone else could ever see. There are no public profiles and no messaging between members: everything goes through CyberGaming.'],
];
if (digital_enabled()) {
    $faqs[] = ['Can I use store credit on gift cards?', 'No. Store credit works on physical items and delivery only. Gift cards and other digital codes are prepaid by OMT or Whish.'];
}
$meta['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
    static fn (array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]],
    $faqs
)];
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'How store credit works', 'lead' => 'Sell your games, get credit, spend it in the shop. 1 credit = $1, and it never expires.']);
?>
<div class="container prose-wrap">
    <article class="prose">
        <div class="credit-facts">
            <div><strong>1 credit = $1</strong><span>Always, at checkout</span></div>
            <div><strong>No expiry</strong><span>It stays in your wallet</span></div>
            <div><strong>Cash is an option</strong><span>You choose when you sell</span></div>
        </div>

        <h2>Earn credit</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('gamepad', 18) ?> <span><strong>Sell us your games.</strong> Get an instant quote, send your request, accept our offer, and hand the games over (bring them to our hub for free, or have a courier pick them up for a <?= e($pickupFeeText) ?> fee that is deducted from your payout). After inspection we add the credit to your wallet.</span></li>
            <li><?= Ui::icon('tag', 18) ?> <span><strong>Sell to other members.</strong> Members can list games for other customers. When one sells, you can be paid in credit or in cash. We take a <?= e($memberPct) ?>% commission (see below).</span></li>
        </ul>
        <p>For a game we list at <?= e(money($example)) ?> in Good condition, you are offered:</p>
        <div class="offer-compare" role="group" aria-label="Example: cash versus credit">
            <div class="offer-box"><span>Cash</span><strong><?= e(money($exCash)) ?></strong><small><?= e($buyback) ?>% of shop price</small></div>
            <div class="offer-box<?= $exCredit > $exCash ? ' is-best' : '' ?>"><span>Store credit</span><strong><?= e(money($exCredit)) ?></strong><small><?= e($tradein) ?>% of shop price<?= $creditBetter ? ': credit is worth more' : '' ?></small></div>
        </div>

        <h2>Spend credit</h2>
        <ol class="steps steps-vertical">
            <li><span class="step-num">1</span><div><h3>Shop as usual</h3><p>Add anything in stock to your cart: games, consoles, controllers, accessories.</p></div></li>
            <li><span class="step-num">2</span><div><h3>Tick &ldquo;Pay with my credit&rdquo;</h3><p>At checkout, your credit is taken off the total (delivery fee included).</p></div></li>
            <li><span class="step-num">3</span><div><h3>Pay any gap</h3><p>If your credit does not cover everything, you pay the rest in cash on delivery in local areas<?= $prepayOn ? ', or first by OMT / Whish in remote areas' : '' ?>. If it does, you pay nothing more.</p></div></li>
        </ol>

        <?php if (digital_enabled()): ?>
        <p><strong>Not for gift cards.</strong> Store credit can't be used on gift cards and other digital codes; it covers physical items and delivery only.</p>
        <?php endif; ?>

        <h2>Fees, explained</h2>
        <h3>Delivery</h3>
        <p>Every delivery has a fee that depends on your area, from <?= e(money($minFee)) ?>. You see the exact amount at checkout before you place the order. Local areas are served by our own courier (cash on delivery); remote areas by a third-party courier, so you prepay there<?= $prepayOn ? '' : ' (currently switched off)' ?>.</p>
        <?= Ui::partial('zone-table') ?>
        <h3>Commission on member sales</h3>
        <p>When a member sells a game to another customer, CyberGaming takes a <strong><?= e($memberPct) ?>% commission</strong>. It is added on top of the amount the seller asked for, so the seller receives exactly what they set and the buyer sees one final price. There is no commission when you sell games to us: our offer is what you get.</p>

        <h2>Safety and privacy</h2>
        <ul class="icon-list">
            <li><?= Ui::icon('shield', 18) ?> <span><strong>We inspect everything</strong> we buy from you and everything we sell.</span></li>
            <li><?= Ui::icon('lock', 18) ?> <span><strong>You stay anonymous.</strong> Members only ever see an anonymous ID: no public profiles, no messaging between members, and every trade goes through us. Your name, phone and address are known only to you and CyberGaming. <a href="<?= e(url('/how-it-works#anonymous')) ?>">How we keep you anonymous</a>.</span></li>
            <li><?= Ui::icon('wallet', 18) ?> <span><strong>Every credit is recorded.</strong> Your wallet shows each amount added or spent, with the order or offer it belongs to.</span></li>
        </ul>
    </article>

    <?= Ui::partial('faq', ['faqs' => $faqs]) ?>

    <section class="cta-block cta-inline">
        <div>
            <h2>Ready to start earning credit?</h2>
            <p>Get a free instant quote in a minute, or browse what you could spend it on.</p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-primary btn-lg" href="<?= e(url('/sell')) ?>">Sell your games</a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(url('/account/register')) ?>">Create an account</a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(url('/shop')) ?>">Shop</a>
        </div>
    </section>
</div>
