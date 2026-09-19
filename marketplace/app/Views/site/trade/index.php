<?php
use App\Modules\Storefront\Quoter;
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Ui;
use App\Support\Pricing;

/** @var bool $saved @var ?array $customer @var array $platforms @var array $factors @var array $titles @var array $wantTitles @var array $rows @var array $wantRows @var ?array $prefill @var array $crumbs @var array $meta */
$fmt = static fn (float $n): string => rtrim(rtrim(number_format($n, 1), '0'), '.');
$tradein = $fmt((float) setting('tradein_pct', 50));
$buyback = $fmt((float) setting('buyback_pct', 45));
$better = (float) setting('tradein_pct', 50) >= (float) setting('buyback_pct', 45);
$resale = 20.0;
$target = 35.0;
$credit = Pricing::tradeCredit($resale, 'Good') * 2;
$balance = $target - $credit;
$factorText = implode(', ', array_map(static fn (string $c, float $f): string => $c . ' ' . $fmt($f) . '%', array_keys($factors), $factors));
$faqs = [
    ['How does the trade-in calculator work?', 'List the games you want to trade (platform, title, condition) and the products you want from our shop. You instantly see what each game is worth in store credit and in cash, the price of each item you want, and your balance: what you pay, or the credit you keep. To send the request you sign in to a free account.'],
    ['How much credit do I get?', "Store credit is $tradein% of the price we list the same game at in our shop, adjusted for condition: $factorText of that amount. " . ($better ? "Taking cash instead pays $buyback%, so store credit is worth more." : "Taking cash instead pays $buyback% (see Sell).")],
    ['What can I use my credit on?', 'Any game, steelbook, console, controller or accessory that is in stock in our shop. Credit lives in your CyberGaming wallet, never expires, and 1 credit is worth $1 at checkout. If it does not cover the whole order, you pay the gap in cash on delivery in local areas, or first by OMT / Whish in remote areas.'],
    ['Do I have to pay the difference?', 'Only if what you want costs more than your credit. The calculator shows "You pay" for that amount, in cash on delivery, OMT or Whish.'],
    ['Is the calculator result final?', 'The result is an estimate based on the condition you select. We inspect your games in person, send you an offer and you accept it before anything is exchanged. Any change is agreed with you first.'],
    ['What makes a strong trade-in?', 'Tick what comes with each game (original box, cover art, manual) and add photos of the disc, the box from outside and the box from inside when you send your request. Complete copies in a clean box are the easiest for us to resell, so they get the best offers. Location data is removed from your photos automatically.'],
    ...Rules::sellFaqs(),
    ['Can I trade in a game that is not in your catalogue?', 'Yes. Games we cannot price automatically are flagged, and our team prices them when they review your request.'],
];
$meta['jsonld'][] = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
    static fn (array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]],
    $faqs
)];
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Trade in your games for store credit', 'lead' => "Get $tradein% of resale value as CyberGaming store credit (or $buyback% in cash) and spend it on any game, steelbook or accessory in the shop. See your balance instantly."]);
?>
<div class="container">
    <?= Ui::partial('flow-nav', ['active' => 'trade']) ?>
    <?php if ($saved): ?>
        <p class="flash flash-success saved-quote" role="status">You have a saved quote. <a href="<?= e(url('/trade/quote')) ?>">Continue with it</a> or start a new list below.</p>
    <?php endif; ?>
</div>

<div class="container">
    <form class="card-box quote-card" method="post" action="<?= e(url('/trade/quote')) ?>" aria-labelledby="trade-h">
        <?= csrf_field() ?>
        <h2 id="trade-h">Trade-in calculator</h2>

        <h3>1. Games you give us</h3>
        <p class="fine">Add up to <?= Quoter::MAX_GIVE ?> games. Choose the platform, start typing the title and pick the honest condition.</p>
        <?= Ui::partial('game-rows', ['rows' => $rows, 'platforms' => $platforms, 'listId' => 'catalog-titles']) ?>
        <datalist id="catalog-titles"><?php foreach ($titles as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?></datalist>

        <h3 class="trade-h3">2. What you want from our shop</h3>
        <?php if ($prefill): ?><p class="fine">We added <strong><?= e($prefill['title']) ?></strong> for you. Change it or add more items below.</p><?php else: ?>
        <p class="fine">Optional. Pick up to <?= Quoter::MAX_WANT ?> items that are in stock. Leave blank to just see how much credit your games are worth.</p><?php endif; ?>
        <?= Ui::partial('want-rows', ['wantRows' => $wantRows, 'listId' => 'wanted-titles']) ?>
        <datalist id="wanted-titles"><?php foreach ($wantTitles as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?></datalist>

        <button class="btn btn-primary btn-lg btn-block" type="submit">Calculate my trade</button>
        <p class="fine">No commitment and no account needed for the calculator. You sign in only when you send your request.</p>
    </form>
</div>

<div class="container prose-wrap">
    <article class="prose">
        <h2>Why trade in?</h2>
        <p>Trading in pays <strong><?= e($tradein) ?>%</strong> of the shop price as store credit<?= $better ? ', compared with <strong>' . e($buyback) . '%</strong> if you take cash' : '' ?>. Your credit sits in your wallet, works on everything in the shop, never expires, and you only pay the difference. <a href="<?= e(url('/credit')) ?>">How store credit works</a>.</p>

        <h2>A worked example</h2>
        <div class="worked">
            <div><span>You trade in 2 games we list at <?= e(money($resale)) ?> each (Good condition)</span><strong><?= e(money($resale * 2)) ?></strong></div>
            <div><span>Your store credit</span><strong><?= e(money($credit)) ?></strong></div>
            <div><span>You choose a game priced at</span><strong><?= e(money($target)) ?></strong></div>
            <div class="grand"><span><?= $balance > 0 ? 'You pay the difference' : 'You keep the extra credit' ?></span><strong><?= e(money(abs($balance))) ?></strong></div>
        </div>
        <p>If your credit is higher than what you pick, keep the rest for next time.</p>

        <h2>What makes a strong trade-in</h2>
        <p>Photos of the disc, the box outside and the box inside, plus the original box, the cover art and the manual, all help us make you a better offer. Tick what comes with each game in the calculator and add your photos when you send your request. Used games listed on our marketplace need the same three photos, so buyers always see the exact copy.</p>

        <h2>How trading in works</h2>
        <ol class="steps steps-vertical">
            <li><span class="step-num">1</span><div><h3>Use the calculator</h3><p>List what you have and what you want. You see your balance straight away.</p></div></li>
            <li><span class="step-num">2</span><div><h3>Send your request</h3><p>Sign in to your free account (or create one) and send it. We review it and post an offer in your account, and nothing moves until you accept.</p></div></li>
            <li><span class="step-num">3</span><div><h3>Hand over and we inspect</h3><p>Bring your games to our hub for free, or have a courier pick them up (<?= e(money(Rules::pickupFee())) ?> fee, deducted from your credit or cash). We inspect them, and your credit is added to your wallet, ready to spend on the items you want. If an item is not acceptable you choose: a revised offer, send it back (<?= e(money(Rules::returnFee())) ?> in cash on delivery) or a free recycle.</p></div></li>
        </ol>
    </article>

    <?= Ui::partial('faq', ['faqs' => $faqs]) ?>
    <p class="see-also">Just want cash? See <a href="<?= e(url('/sell')) ?>">selling your games</a>. New to the wallet? Read <a href="<?= e(url('/credit')) ?>">how store credit works</a>. Browse what you could spend your credit on in the <a href="<?= e(url('/shop')) ?>">shop</a>, or <a href="<?= e(url('/swap')) ?>">swap</a> with another player.</p>
</div>
