<?php
use App\Modules\Storefront\Quoter;
use App\Modules\Storefront\Rules;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;
use App\Support\Pricing;

/** @var array $platforms @var array $factors @var array $titles @var array $rows @var array $crumbs @var array $meta @var bool $saved @var ?array $customer */
$fmt = static fn (float $n): string => rtrim(rtrim(number_format($n, 1), '0'), '.');
$buyback = $fmt((float) setting('buyback_pct', 45));
$tradein = $fmt((float) setting('tradein_pct', 50));
$creditBetter = (float) setting('tradein_pct', 50) > (float) setting('buyback_pct', 45);
$example = 20.0;
$exCash = Pricing::buybackOffer($example, 'Good');
$exCredit = Pricing::tradeCredit($example, 'Good');
$blurb = [
    'New'      => 'Sealed or unopened, still in the wrapper.',
    'Like New' => 'Opened but looks untouched: flawless disc, crisp case and artwork, all inserts.',
    'Good'     => 'Normal signs of play: light disc marks that do not affect play, case may show light wear.',
    'Fair'     => 'Heavier wear: visible scratches, scuffed or cracked case, missing insert, but the disc still plays.',
];
$factorText = implode(', ', array_map(static fn (string $c, float $f): string => $c . ' ' . $fmt($f) . '%', array_keys($factors), $factors));
$faqs = [
    ['How does selling work?', 'Add your games (platform, title and condition) and press Get my quote. You instantly see what they are worth in cash and in store credit. Create a free account, send your request, and we reply with an offer. You accept it, hand the games over (bring them to our hub, or have a courier pick them up), we inspect them, and you are paid in cash or credit.'],
    ['How much will I get for my games?', "Cash is $buyback% of the price we list the same game at in our shop, and store credit is $tradein%. Both go up or down with the condition of your copy: $factorText (each as a share of the standard amount)."],
    ['Cash or store credit: which is better?', $creditBetter
        ? "Store credit is worth more: $tradein% of the shop price against $buyback% in cash. Credit goes into your CyberGaming wallet, never expires, and 1 credit is always worth 1 US dollar at checkout."
        : "Cash pays $buyback% of the shop price and store credit pays $tradein%. Credit goes into your CyberGaming wallet, never expires, and 1 credit is always worth 1 US dollar at checkout."],
    ['Do I need an account?', 'You can get a quote without one. To send your request you need a free account, because that is where you track your offer and where your credit is kept. Your quote is saved while you sign up.'],
    ['What games do you buy?', 'We buy used PS4, PS5, Nintendo Switch, Xbox One, Xbox Series and PC games in working condition, including steelbooks and special editions. For consoles, controllers and accessories, message us on WhatsApp and we will tell you if we can take them.'],
    ['Is the online quote final?', 'The quote is an estimate based on the condition you select. We inspect every game in person, and if the condition matches what you described, you get exactly the offered amount. If it is worse than described, we tell you and agree a new price before any money or credit changes hands.'],
    ['What makes a strong offer?', 'Tick everything that comes with each game (original box, cover art, manual) and add clear photos of the disc, the box from outside and the box from inside. Complete copies with all three, in a clean box, are the easiest for us to resell, so they earn the best offers. Photos are optional and we still inspect every game in person.'],
    ['What photos should I add, and is my location safe?', 'Add up to 6 photos when you send your request: the disc, the box from outside and the box from inside are the most useful. Your phone may store where a photo was taken, so we re-save every photo and remove that location data automatically.'],

    ...Rules::sellFaqs(),
    ['Do other customers see my name or number?', 'Never. Your details are used only by CyberGaming to contact you about your request, and buyers and sellers never see each other.'],
];
// the numbered steps shown below; the same list feeds the HowTo structured data
$steps = [
    ['Get your instant quote', 'Enter your games above. Matching titles get a price in cash and in credit immediately, and anything we do not recognise is priced by our team.'],
    ['Send your request', 'Create a free account (or sign in) and send it. It takes a minute, and your quote is waiting for you afterwards.'],
    ['Get our offer and accept', 'We review your list and post an offer in your account. Pick cash or credit and accept it.'],
    ['Hand over and we inspect', 'Bring your games to our hub (free), or have them picked up: our own courier checks them on the spot in local areas, and elsewhere a courier ships them to our hub. We check discs, cases and codes.'],
    ['Get paid in cash or credit', 'If the condition matches your description, you get the offered amount: cash, or credit added to your wallet, minus the courier pickup fee if you chose a pickup.'],
];
$meta['title'] = 'Sell Your Used Games in Lebanon: Instant Quote | CyberGaming';
$meta['jsonld'][] = Seo::webPage('WebPage', 'Sell your used games in Lebanon', (string) ($meta['canonical'] ?? url('/sell')), (string) ($meta['description'] ?? ''));
$meta['jsonld'][] = Seo::howTo('How to sell your used games to CyberGaming', 'Get an instant quote for your games, send your request, accept our offer, hand the games over for inspection and get paid in cash or store credit.', $steps);
$meta['jsonld'][] = Seo::faqLd($faqs);
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Sell your used games in Lebanon', 'lead' => 'Get an instant quote for your PS4, PS5, Switch and Xbox games in cash or in store credit' . ($creditBetter ? ' (credit is worth more)' : '') . '. Send your request, get our offer, hand them over and get paid.']);
echo Seo::quickAnswerHtml('sell');
?>
<div class="container">
    <?= Ui::partial('flow-nav', ['active' => 'sell']) ?>
    <?php if ($saved): ?>
        <p class="flash flash-success saved-quote" role="status">You have a saved quote. <a href="<?= e(url('/sell/quote')) ?>">Continue with it</a> or start a new list below.</p>
    <?php endif; ?>
</div>

<div class="container cart-layout">
    <section class="card-box quote-card" aria-labelledby="quote-h">
        <h2 id="quote-h">Get your instant quote</h2>
        <p class="fine">Add up to <?= Quoter::MAX_GIVE ?> games. Choose the platform, start typing the title (suggestions come from our catalogue) and pick the honest condition.</p>
        <form method="post" action="<?= e(url('/sell/quote')) ?>">
            <?= csrf_field() ?>
            <?= Ui::partial('game-rows', ['rows' => $rows, 'platforms' => $platforms, 'listId' => 'catalog-titles']) ?>
            <datalist id="catalog-titles"><?php foreach ($titles as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?></datalist>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Get my quote</button>
            <p class="fine">No commitment and no account needed for the quote. You sign in only when you send your request.</p>
        </form>
    </section>

    <aside class="summary" aria-label="What we pay at a glance">
        <h2>Cash or credit: you choose</h2>
        <dl>
            <div><dt>Cash</dt><dd><?= e($buyback) ?>% of shop price</dd></div>
            <div><dt>Store credit</dt><dd><?= e($tradein) ?>% of shop price</dd></div>
            <?php foreach ($factors as $cond => $f): ?>
                <div><dt><?= e($cond) ?></dt><dd><?= e($fmt($f)) ?>% of that</dd></div>
            <?php endforeach; ?>
        </dl>
        <p class="fine">Store credit lands in your wallet, never expires and is spent at checkout, 1 credit = $1. <a href="<?= e(url('/credit')) ?>">How store credit works</a>.</p>
    </aside>
</div>

<div class="container prose-wrap">
    <article class="prose">
        <h2>How selling to CyberGaming works</h2>
        <ol class="steps steps-vertical">
            <?php foreach ($steps as $i => [$stepName, $stepText]): ?>
            <li><span class="step-num"><?= $i + 1 ?></span><div><h3><?= e($stepName) ?></h3><p><?= e($stepText) ?></p></div></li>
            <?php endforeach; ?>
        </ol>

        <h2>Cash versus store credit</h2>
        <p>For a game we list at <?= e(money($example)) ?> in Good condition:</p>
        <div class="offer-compare" role="group" aria-label="Example: cash versus credit">
            <div class="offer-box"><span>Cash</span><strong><?= e(money($exCash)) ?></strong><small>paid after inspection</small></div>
            <div class="offer-box<?= $exCredit > $exCash ? ' is-best' : '' ?>"><span>Store credit</span><strong><?= e(money($exCredit)) ?></strong><small><?= $exCredit > $exCash ? 'credit is worth more' : 'added to your wallet' ?></small></div>
        </div>
        <p>Credit is kept in your CyberGaming wallet. It never expires, 1 credit is worth $1 when you shop, and any gap is paid in cash on delivery. Read <a href="<?= e(url('/credit')) ?>">how store credit works</a>.</p>

        <h2>How your games reach us: local or remote</h2>
        <div class="worked cond-list">
            <div><span><strong>Bring them to our hub</strong><small>Free, in every area. No minimum.</small></span><strong>Free</strong></div>
            <div><span><strong>Local pickup</strong> &mdash; <?= e(Rules::nameList(Rules::names('local'))) ?><small>Our own courier collects and checks the games on the spot. If the courier declines an item, there is no return trip and no fee.</small></span><strong>&minus;<?= e(money(Rules::pickupFee())) ?></strong></div>
            <div><span><strong>Courier shipment from anywhere else</strong><small>A third-party courier brings them to our hub and we inspect on arrival.<?= Rules::minSell() > 0 ? ' Shipments must be worth at least ' . e(money(Rules::minSell())) . '.' : '' ?></small></span><strong>&minus;<?= e(money(Rules::pickupFee())) ?></strong></div>
        </div>
        <p>The pickup fee is <strong>deducted from your payout</strong>: you never pay it separately, and the request form shows what you will receive. If a shipped item is not acceptable, you choose: a revised offer, get it sent back for <?= e(money(Rules::returnFee())) ?> (paid in cash to the courier on delivery), or let us recycle it for free. We hold it <?= (int) Rules::holdDays() ?> days for your answer, then recycle it.</p>

        <h2>What we buy</h2>
        <ul>
            <li>Used and new games for PS4, PS5, Nintendo Switch, Xbox One, Xbox Series X|S and PC.</li>
            <li>Steelbooks and special editions in good condition.</li>
            <li>Discs must play and the original case should be included.</li>
        </ul>

        <h2>Conditions explained</h2>
        <p>Be honest about the condition: it sets your price, and we always inspect in person. Here is how each condition affects the amounts, for a game we list at <?= e(money($example)) ?>.</p>
        <div class="worked cond-list">
            <?php foreach ($factors as $cond => $f): ?>
                <div>
                    <span><strong><?= e($cond) ?></strong> &mdash; <?= e($fmt($f)) ?>% of the standard amount<small><?= e($blurb[$cond] ?? '') ?></small></span>
                    <strong>Cash <?= e(money(Pricing::buybackOffer($example, $cond))) ?> &middot; Credit <?= e(money(Pricing::tradeCredit($example, $cond))) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>

        <h2>What makes a strong offer</h2>
        <p>The more we can see and the more that comes with the game, the better your offer:</p>
        <ul>
            <li><strong>Photos of the disc, the box outside and the box inside.</strong> Clear photos let us price your copy with confidence.</li>
            <li><strong>The original box or case,</strong> in good shape.</li>
            <li><strong>The cover art</strong> (the sleeve or inlay) still in place.</li>
            <li><strong>The manual and any inserts,</strong> such as codes and posters.</li>
        </ul>
        <p>Tick what comes with each game when you enter it, and add your photos when you send your request. The same rule applies to our marketplace: every used game listed for sale on CyberGaming needs photos of the disc, the box outside and the box inside, so buyers see the exact copy they get.</p>

        <h2>What affects your offer</h2>
        <ul>
            <li><strong>Shop price:</strong> we start from what we list the same title for on this site, so popular current games earn more.</li>
            <li><strong>Condition:</strong> clean discs and complete cases earn a higher share (see above).</li>
            <li><strong>What is included:</strong> box, cover art and manual make a copy easier to resell.</li>
            <li><strong>Edition:</strong> we quote from the standard edition; a steelbook or collector's edition can earn more, and we will tell you in your offer.</li>
        </ul>
    </article>

    <?= Seo::faqHtml($faqs) ?>

    <section class="card-box seller-cta">
        <h2>Own a shop or sell in volume?</h2>
        <p>Sell on CyberGaming and list your store's products for buyers across Lebanon. We handle customers and delivery, you keep your prices.</p>
        <a class="btn btn-outline" href="<?= e(url('/seller/apply')) ?>">Sell on CyberGaming: list your store's products</a>
    </section>

    <p class="see-also">Want a specific game and have games to give? Use the <a href="<?= e(url('/trade')) ?>">trade-in calculator</a>. Curious about the wallet? Read <a href="<?= e(url('/credit')) ?>">how store credit works</a>. Or try a <a href="<?= e(url('/swap')) ?>">swap</a> with another player.</p>
</div>
