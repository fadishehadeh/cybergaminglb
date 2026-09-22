<?php
declare(strict_types=1);

/**
 * Starter guides for the public "Guides" section (table `articles`). Idempotent by slug: existing rows are left alone
 * (they may have been edited in the admin panel). Pass --force to overwrite title, text and meta of the starter slugs.
 *   php database/seeds/articles_starter.php [--force]
 * Bodies use {{tokens}} (see App\Support\Tokens) for every fee, percentage and zone, so they never go stale.
 * Internal links are root-relative ("/sell"); they are turned into full URLs when the page is rendered.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__, 2);
define('BASE_PATH', $root);
define('PUBLIC_PATH', $root . '/public');
require $root . '/app/Support/helpers.php';
spl_autoload_register(static function (string $c) use ($root): void {
    $f = $root . '/app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) {
        require $f;
    }
});
new App\Core\Application($root);

$force = in_array('--force', $argv, true);

/** slug, tag, days ago published, title, meta_title, meta_description, excerpt, body */
$articles = [];

// 1 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'how-to-sell-used-ps4-games-in-lebanon', 'selling', 2,
    'How to sell your used PS4 games in Lebanon',
    'How to Sell Used PS4 Games in Lebanon | CyberGaming',
    'A step-by-step guide to selling used PS4 games in Lebanon: get an instant quote, choose cash or store credit, hand your games over and get paid.',
    'You can sell used PS4 games to {{site_name}} for cash at {{buyback_pct}} of our shop price or for store credit at {{tradein_pct}}. Get an instant quote online, send your request, accept our offer, hand the games over for inspection and get paid. Bringing them to our hub is free.',
    <<<'HTML'
<p>Selling a game you have finished should not mean posting in a group and waiting for a stranger. At {{site_name}} you get a price first, and nobody ever sees your name or phone number. This guide walks through the whole process.</p>

<h2>What you get paid</h2>
<p>We start from the price we list the same game for in our shop. Cash pays <strong>{{buyback_pct}}</strong> of that price, and store credit pays <strong>{{tradein_pct}}</strong>. Both move up or down with the condition of your copy, so a clean, complete game earns more than a scratched one. Credit is worth more than cash, is kept in your wallet, never expires and is worth exactly $1 per credit at checkout. Read <a href="/credit">how store credit works</a> if you want the details.</p>

<h2>Step by step</h2>
<ol>
<li><strong>Get an instant quote.</strong> Open the <a href="/sell">sell page</a>, pick the platform, start typing the title and choose the honest condition. The quote shows cash and credit side by side. You do not need an account for this.</li>
<li><strong>Send your request.</strong> Create a free account (or sign in) and send it. Your quote is saved while you sign up.</li>
<li><strong>Accept our offer.</strong> We review your list and post an offer in your account. You choose cash or credit and accept it.</li>
<li><strong>Hand the games over.</strong> Bring them to our hub, which is free, or ask for a pickup.</li>
<li><strong>We inspect and pay you.</strong> If the condition matches what you described, you get exactly the offered amount.</li>
</ol>

<h2>How your games reach us</h2>
<table>
<thead><tr><th>Option</th><th>Cost</th><th>What happens</th></tr></thead>
<tbody>
<tr><td>Bring them to our hub</td><td>Free</td><td>No minimum, available from every area.</td></tr>
<tr><td>Courier pickup in a local area ({{local_zones}})</td><td>{{pickup_fee}}, deducted from your payout</td><td>Our own courier collects the games and checks them on the spot.</td></tr>
<tr><td>Courier shipment from a remote area</td><td>{{pickup_fee}}, deducted from your payout</td><td>A third-party courier brings them to our hub and we inspect on arrival. The shipment must be worth at least {{min_sell}}.</td></tr>
</tbody>
</table>
<p>You never pay the pickup fee separately: the request form shows what you will receive after it is deducted.</p>

<h2>How to get the best offer</h2>
<ul>
<li><strong>Be honest about condition.</strong> We inspect every game in person, so an accurate description avoids a revised offer.</li>
<li><strong>Include everything that came with it:</strong> the original case, the cover art and any manual or inserts. Complete copies are easier for us to resell.</li>
<li><strong>Add clear photos</strong> of the disc, the box from outside and the box from inside. Photos are optional, but they help us price your copy with confidence. We remove location data from every photo automatically.</li>
<li><strong>Clean the disc</strong> gently with a soft, dry cloth before you hand it over.</li>
</ul>

<h2>What if a game is rejected?</h2>
<p>Sometimes a disc is too damaged or the condition is worse than described. Nothing is decided without you. You can take a revised (lower) offer if we can make one, ask us to send the game back and pay the {{return_fee}} return fee in cash to the courier, or let us recycle it for free. We hold the game for {{hold_days}} days for your answer. In local areas our courier checks games on the spot, so a declined item has no return trip and no fee.</p>

<h2>Is my identity safe?</h2>
<p>Yes. Buyers never see who sold a game, and we never share your name, phone number or address with anyone. Read <a href="/guides/how-we-keep-buyers-and-sellers-anonymous">how we keep buyers and sellers anonymous</a>.</p>

<h2>Ready to start?</h2>
<p>Add your games to the <a href="/sell">instant quote form</a>. If you would rather swap a game for another one, try the <a href="/swap">swap board</a> (flat {{swap_fee}} per side), or <a href="/trade">trade in for shop credit</a>.</p>
HTML,
];

// 2 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'used-vs-new-games-what-to-check-before-you-buy', 'buying', 3,
    'Used vs new games: what to check before you buy',
    'Used vs New Games: What to Check Before You Buy',
    'Used games save money if the disc, case and extras are right. Here is what to check on a used copy, when new is worth it, and how our condition grades work.',
    'A used game can cost much less than a new one, but check the disc surface, the case and cover art, the included extras and any codes before you pay. New is worth it for gifts and collectors. Our condition grades tell you what to expect from each copy.',
    <<<'HTML'
<p>A used game can be the same game for a lot less. The catch is that no two used copies are alike. This guide covers what to look at, when buying new makes more sense, and how we grade the copies we sell.</p>

<h2>Used or new: a quick comparison</h2>
<table>
<thead><tr><th></th><th>Used</th><th>New (sealed)</th></tr></thead>
<tbody>
<tr><td>Price</td><td>Lower, and it depends on title and condition</td><td>Higher</td></tr>
<tr><td>Condition</td><td>Varies: check the disc, case and extras</td><td>Unopened</td></tr>
<tr><td>Codes and extras</td><td>May already be used or missing</td><td>Complete and unredeemed</td></tr>
<tr><td>Best for</td><td>Playing the game for less</td><td>Gifts and collectors</td></tr>
</tbody>
</table>

<h2>What to check on a used game</h2>
<h3>The disc</h3>
<p>Hold it by the edges and tilt it under a light. Light surface marks are normal and rarely affect play. Deep scratches, cracks or a wobbling, warped disc are not. Look at both sides, and check that the label side is intact.</p>

<h3>The case and cover art</h3>
<p>A cracked case, torn cover art or a missing sleeve does not change how the game plays, but it changes what the copy is worth and how it looks on your shelf. Collectors care about the cover art more than players do.</p>

<h3>What came in the box</h3>
<ul>
<li>The manual and any inserts, posters or extras.</li>
<li>Download codes for DLC, bonus items or online passes. On a used game these may already be redeemed, so do not count on them unless the seller confirms.</li>
<li>For special editions, the steelbook, art book or other items listed for that edition. See <a href="/guides/steelbook-editions-explained">steelbook editions explained</a>.</li>
</ul>

<h3>The game itself</h3>
<p>Physical discs often need a day-one update or patch that downloads from the internet, so plan for some storage space and a connection the first time you play. Check that the game is for your console generation, and remember that some multiplayer modes depend on online servers that may change over time.</p>

<h2>How our condition grades work</h2>
<ul>
<li><strong>New:</strong> sealed or unopened, still in the wrapper.</li>
<li><strong>Like New:</strong> opened but looks untouched: flawless disc, crisp case and artwork, all inserts.</li>
<li><strong>Good:</strong> normal signs of play. Light disc marks that do not affect play, and the case may show light wear.</li>
<li><strong>Fair:</strong> heavier wear, such as visible scratches, a scuffed or cracked case or a missing insert, but the disc still plays.</li>
</ul>
<p>Every item is inspected by our team before it is listed, and every used listing shows photos of the exact copy: the disc, the box from outside and the box from inside.</p>

<h2>When buying new is worth it</h2>
<p>Buy new when the game is a gift, when you want an unredeemed code, or when you collect sealed editions. For everything else, a good used copy from a shop that inspects its stock is usually the smarter buy.</p>

<h2>Buying used from us</h2>
<p>You see the condition grade and photos before you order, and we confirm every order on WhatsApp. In local areas ({{local_zones}}) our courier lets you check the game at the door and you can pay cash on delivery. Browse the <a href="/shop">shop</a> or see <a href="/delivery-and-payment">delivery and payment</a> for fees by area.</p>
HTML,
];

// 3 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'how-to-check-a-used-ps4-or-ps5-before-you-buy-it', 'consoles', 4,
    'How to check a used PS4 or PS5 before you buy it',
    'How to Check a Used PS4 or PS5 Before You Buy It',
    'A practical checklist for buying a used PS4 or PS5: physical condition, ports, disc drive, storage, controller, network and account checks.',
    'Before you buy a used PS4 or PS5, check the body and ports, the disc drive, the storage, the controller and the network connection, and make sure the seller has removed their account. This checklist takes about fifteen minutes and can save you from an expensive surprise.',
    <<<'HTML'
<p>A used console is a great way to start gaming for less, and it is also the purchase where a quick check pays off most. Use this list whether you buy from a shop, from a friend or from a stranger.</p>

<h2>1. Look at the console</h2>
<ul>
<li><strong>Body:</strong> look for cracks, deep dents and signs of liquid. Slight scuffs are cosmetic.</li>
<li><strong>Vents:</strong> they should be free of thick dust and blockages. Heavy dust can mean a hot console.</li>
<li><strong>Ports:</strong> check the HDMI, USB, power and network ports for bent pins, loose connectors or burn marks. Wiggle a cable gently: it should not feel loose.</li>
<li><strong>Serial and model labels:</strong> they should be present and match the box, if there is one.</li>
</ul>

<h2>2. Power it on</h2>
<p>Connect it to a TV you know works, with the original power cable if possible. It should start normally, show the setup screen or home screen and stay stable for several minutes. Listen to the fan: a little noise under load is normal, a constant loud whine or rattling is not.</p>

<h2>3. Test the disc drive</h2>
<p>Insert a game disc and see whether it is recognised, loads and ejects cleanly. Try a second disc if you can. Grinding, repeated ejecting or discs that are never recognised point to a tired or dirty drive. Digital-only PS5 models have no disc drive, so skip this step for them and check the model before you buy. A PS5 with a disc drive and a Digital Edition look different from the front.</p>

<h2>4. Check storage and software</h2>
<ul>
<li>Open the storage settings and confirm the capacity matches what you were told. Storage sizes differ between models.</li>
<li>Check that system software updates install.</li>
<li>Make sure the previous owner has signed out and that the console is not tied to their account. A console that is still linked to someone else's account can cause problems later, so ask them to deactivate it or reset it in front of you.</li>
</ul>

<h2>5. Test the controller and network</h2>
<ul>
<li>Pair the controller, then test the sticks, triggers, all buttons and the charging port. Stick drift, where the cursor moves on its own, is a common wear problem.</li>
<li>Connect to Wi-Fi or a network cable and confirm the console goes online.</li>
</ul>

<h2>6. Ask what is included</h2>
<p>Confirm which cables, controllers and box contents come with it. A missing power cable or HDMI cable is easy to replace, but you should know before you pay. See how we describe what is included in <a href="/guides/used-vs-new-games-what-to-check-before-you-buy">used vs new: what to check</a>.</p>

<h2>Buying from a shop instead</h2>
<p>A shop that inspects its stock does this work for you. At {{site_name}} every item is inspected before it is listed, listings show photos of the exact unit and what is in the box, and in local areas ({{local_zones}}) our courier lets you check the item at the door before you pay. In remote areas we inspect, photograph and seal the order at our hub first. Delivery starts at {{local_fee}} in local areas and {{remote_fee}} elsewhere. See what is in the <a href="/shop">shop</a>, and read <a href="/guides/ps4-or-ps5-in-lebanon-which-should-you-buy">PS4 or PS5: which should you buy?</a></p>
HTML,
];

// 4 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'ps4-or-ps5-in-lebanon-which-should-you-buy', 'consoles', 5,
    'PS4 or PS5 in Lebanon: which should you buy?',
    'PS4 or PS5 in Lebanon: Which Should You Buy?',
    'PS4 or PS5? Compare the two consoles on games, backward compatibility, storage, controllers and budget, and find out which suits how you play.',
    'Buy a PS4 if you want a large, affordable library of games and a lower entry cost. Buy a PS5 if you want new-generation games, faster loading and the newer controller. A PS5 also plays most PS4 games, but a PS4 cannot play PS5 games.',
    <<<'HTML'
<p>Both consoles are still worth buying, and the right one depends on your budget and the games you want. This guide compares them without hype so you can decide.</p>

<h2>The short answer</h2>
<ul>
<li><strong>Choose the PS4</strong> if you want the widest, cheapest library of games and you are happy with the previous generation.</li>
<li><strong>Choose the PS5</strong> if you want games made for the new generation, faster loading and the newer controller, and your budget allows it.</li>
</ul>

<h2>Side by side</h2>
<table>
<thead><tr><th></th><th>PS4</th><th>PS5</th></tr></thead>
<tbody>
<tr><td>Game library</td><td>Very large, with many titles available used</td><td>Growing, and it also plays most PS4 games</td></tr>
<tr><td>Plays PS4 games</td><td>Yes</td><td>Yes, most of them</td></tr>
<tr><td>Plays PS5 games</td><td>No</td><td>Yes</td></tr>
<tr><td>Storage</td><td>Hard drive, size depends on the model</td><td>Built-in SSD, so games load faster</td></tr>
<tr><td>Controller</td><td>DualShock 4</td><td>DualSense, with adaptive triggers and haptic feedback</td></tr>
<tr><td>Disc drive</td><td>Yes</td><td>Yes on the standard model, none on the Digital Edition</td></tr>
<tr><td>Cost of entry</td><td>Lower, especially used</td><td>Higher</td></tr>
</tbody>
</table>

<h2>Games matter more than hardware</h2>
<p>Look at the games you actually want to play. If they are PS4 titles, a PS4 does the job, and a PS5 plays most of them too. If you want games made only for the PS5, you need a PS5. Used PS4 discs are where the savings are: many good titles cost far less than a new release. Browse our <a href="/platform/ps4">PS4 games</a> and see how prices depend on title and condition.</p>

<h2>Disc or digital?</h2>
<p>A PS5 Digital Edition cannot play discs, so you can only buy games from the online store, and you cannot buy used games, trade them in or lend them. If you like to buy used, sell games when you finish them or swap them with friends, get a console with a disc drive.</p>

<h2>Storage and updates</h2>
<p>New games take a lot of space and often need updates. Whichever console you choose, plan for enough storage and a decent internet connection for the first day of a new game.</p>

<h2>Buying a used console</h2>
<p>Used consoles save money, but check them carefully. Follow our <a href="/guides/how-to-check-a-used-ps4-or-ps5-before-you-buy-it">used console checklist</a> before you pay. When you buy from {{site_name}}, every item is inspected before it is listed, and delivery starts at {{local_fee}} in local areas and {{remote_fee}} elsewhere.</p>

<h2>Who should buy which?</h2>
<ul>
<li><strong>First console on a budget:</strong> a PS4 with a few used games goes a long way.</li>
<li><strong>Playing with friends:</strong> buy the console your friends play on, so you can join the same online games.</li>
<li><strong>Want the newest games:</strong> a PS5, ideally with a disc drive.</li>
<li><strong>Buying for a child:</strong> a PS4 is often enough, and used games keep the total cost down.</li>
</ul>

<h2>Already have a console?</h2>
<p>Play the games you own, sell the ones you have finished, and put the credit toward the next one. Our <a href="/trade">trade-in calculator</a> shows what your games are worth in store credit ({{tradein_pct}} of the shop price, before condition).</p>
HTML,
];

// 5 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'how-store-credit-works-at-cybergaming', 'credit', 6,
    'How store credit works at CyberGaming',
    'How Store Credit Works at CyberGaming Lebanon',
    'How CyberGaming store credit works: earn it by selling games, spend it at checkout, and why one credit is always worth one US dollar.',
    'Store credit is money in your CyberGaming wallet that you spend in the shop. One credit is always worth $1 and it never expires. You earn it by selling or trading in games, and you can apply it at checkout to reduce or cover an order.',
    <<<'HTML'
<p>Store credit is the simplest way to turn games you have finished into games you want next. This guide explains how it works, from the first sale to the last dollar spent.</p>

<h2>The basics</h2>
<ul>
<li><strong>1 credit = $1.</strong> Always, at checkout.</li>
<li><strong>It never expires.</strong> It stays in your wallet until you spend it.</li>
<li><strong>It lives in your account.</strong> Your wallet shows every amount added or spent, with the order or offer it belongs to.</li>
<li><strong>It is optional.</strong> When you sell games to us, you choose credit or cash.</li>
</ul>

<h2>How you earn credit</h2>
<p><strong>Sell or trade in games.</strong> Get a quote on the <a href="/sell">sell page</a> or in the <a href="/trade">trade-in calculator</a>, send your request, accept our offer and hand the games over. After inspection, the credit is added to your wallet. Credit pays <strong>{{tradein_pct}}</strong> of the shop price, and cash pays <strong>{{buyback_pct}}</strong>, both adjusted for condition. That is why most people choose credit when they plan to shop.</p>
<p><strong>Sell to other members.</strong> Members who list games for other customers can be paid in credit or cash when their item sells. CyberGaming takes a {{member_commission}} commission, which is added on top of the price the seller asked for, so the seller receives exactly what they set and the buyer sees one final price. There is no commission when you sell games to us.</p>

<h2>How you spend credit</h2>
<ol>
<li>Add anything in stock to your cart.</li>
<li>At checkout, tick <strong>Pay with my credit</strong>. Your credit is taken off the total, delivery fee included.</li>
<li>If your credit does not cover everything, you pay the rest: in cash on delivery in local areas ({{local_zones}}), or first by OMT or Whish in remote areas. If it covers everything, you pay nothing more.</li>
</ol>

<h2>A worked example</h2>
<p>You trade in two games for a total of $20 in credit. You choose a game priced at $28 in the shop. Your credit covers $20 and you pay the remaining $8, in cash on delivery or by OMT or Whish depending on your area. If you had chosen a $15 game instead, you would keep the extra $5 in your wallet for next time.</p>

<h2>Fees</h2>
<p>There is no fee for earning or spending credit. Delivery is charged by area, from {{local_fee}} in local areas and from {{remote_fee}} in remote areas. If a courier collects the games you sell to us, a {{pickup_fee}} pickup fee is deducted from your payout, and bringing them to our hub is free.</p>

<h2>Cash or credit: which should you choose?</h2>
<p>Choose credit if you plan to buy from the shop, because it pays more per game. Choose cash if you need the money now. You decide each time you sell.</p>

<h2>Is it safe?</h2>
<p>We inspect every game we buy and every item we sell, and every credit movement is recorded. Buyers and sellers never see each other: your name, phone number and address stay private. See <a href="/guides/how-we-keep-buyers-and-sellers-anonymous">how we keep buyers and sellers anonymous</a>, or read the full <a href="/credit">store credit page</a>.</p>
HTML,
];

// 6 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'delivery-in-lebanon-local-vs-remote-areas-explained', 'delivery', 7,
    'Delivery in Lebanon: local vs remote areas explained',
    'Delivery in Lebanon: Local vs Remote Areas Explained',
    'How CyberGaming delivery works in Lebanon: local areas with our own courier and cash on delivery, remote areas with a third-party courier and prepayment.',
    'We deliver across Lebanon. In local areas our own courier delivers for a flat fee and you can pay cash on delivery. In remote areas a third-party courier delivers, so we inspect and seal your order at our hub and you pay in advance by OMT or Whish.',
    <<<'HTML'
<p>We deliver across Lebanon, but not every address is delivered the same way. This guide explains the two kinds of delivery, why they differ and what to expect in each.</p>

<h2>Local delivery: our own courier</h2>
<p>Local areas are <strong>{{local_zones}}</strong>. Our own courier delivers there for a flat fee that starts at {{local_fee}}. Because the courier works for us:</p>
<ul>
<li>you can look the item over at the door before you pay;</li>
<li>you can pay <strong>cash on delivery</strong>, or use store credit, OMT or Whish;</li>
<li>if you sell games to us, the courier can collect them and check them on the spot.</li>
</ul>

<h2>Remote delivery: a third-party courier</h2>
<p>Remote areas are <strong>{{remote_zones}}</strong>. A third-party courier delivers there, from {{remote_fee}}. That courier cannot inspect anything for you, and cannot handle a cash payment for us. So:</p>
<ul>
<li>we inspect, photograph and seal your order at our hub before it ships;</li>
<li>you <strong>pay in advance</strong> by OMT or Whish (or use store credit), and we ship as soon as your payment is confirmed;</li>
<li>after {{cod_after}} delivered orders, cash on delivery also becomes available in remote areas.</li>
</ul>

<h2>At a glance</h2>
<table>
<thead><tr><th></th><th>Local areas</th><th>Remote areas</th></tr></thead>
<tbody>
<tr><td>Courier</td><td>Our own</td><td>Third party</td></tr>
<tr><td>Fee</td><td>From {{local_fee}}</td><td>From {{remote_fee}}</td></tr>
<tr><td>Inspection</td><td>At the door, before you pay</td><td>At our hub, with photos, before we seal it</td></tr>
<tr><td>Payment</td><td>Cash on delivery, credit, OMT or Whish</td><td>OMT or Whish in advance, or credit</td></tr>
</tbody>
</table>

<h2>How an order flows</h2>
<ol>
<li>Order on the site and choose your area. You see the exact fee before you place the order.</li>
<li>We confirm on WhatsApp: availability, price and delivery time.</li>
<li>We deliver (local) or inspect, seal and ship (remote).</li>
</ol>
<p>You can also collect your order at our pickup point. We share the exact location on WhatsApp when we confirm your order.</p>

<h2>Selling from a remote area</h2>
<p>Ship your games to our hub by courier. A {{pickup_fee}} pickup fee is deducted from your payout, and the shipment must be worth at least {{min_sell}}. Bringing games to our hub yourself has no fee and no minimum. If an item is not acceptable after inspection, you choose a revised offer, having it sent back for {{return_fee}}, or a free recycle. We hold it for {{hold_days}} days for your answer.</p>

<h2>Find your area</h2>
<p>Every town belongs to one of our delivery areas. See <a href="/delivery-and-payment">delivery and payment</a> for the full table with a page for each area, or message us on WhatsApp if you are not sure which area your town falls in.</p>
HTML,
];

// 7 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'steelbook-editions-explained', 'collectors', 8,
    'Steelbook editions explained',
    'Steelbook Editions Explained: What to Know | CyberGaming',
    'What a steelbook game edition is, why collectors want them, what to check on a used one and how to look after it.',
    'A steelbook is a metal case for a game disc, often with special artwork, sold as a collector or retail-exclusive edition. It does not change how the game plays. On a used steelbook, check the metal for dents, scratches and rust, and confirm the disc and extras are included.',
    <<<'HTML'
<p>Walk into a game collector's room and you will see rows of metal cases with striking artwork. Those are steelbooks. This guide explains what they are and what to look for if you want one.</p>

<h2>What is a steelbook?</h2>
<p>A steelbook is a metal case for a game disc, usually with printed artwork and a matte or glossy finish. Some hold just the disc and a sleeve. Others come with extras such as an art card, a code or a soundtrack, depending on the edition. They are usually sold as a collector's edition or a retail exclusive, and copies are often limited.</p>

<h2>Does it change the game?</h2>
<p>No. The disc inside plays like any other copy of that edition. You are paying for the case, the artwork and the rarity, so the price depends on the title, the condition of the metal and what else is included.</p>

<h2>What to check on a used steelbook</h2>
<ul>
<li><strong>The metal:</strong> look for dents on the corners and edges, scratches on the face and any rust or tarnish inside the hinge.</li>
<li><strong>The artwork:</strong> check for fading, peeling or fingerprints that will not wipe off.</li>
<li><strong>The hinge and the disc holder:</strong> the disc should sit firmly, and the case should open and close cleanly.</li>
<li><strong>The disc:</strong> confirm that it is the right game and that the surface is clean. See <a href="/guides/used-vs-new-games-what-to-check-before-you-buy">what to check before you buy a used game</a>.</li>
<li><strong>The extras:</strong> art card, sleeve, inserts and any code that came with the edition. Codes on used copies may already be redeemed.</li>
<li><strong>The edition:</strong> check that the steelbook matches the edition described. Some are made for a specific edition or store.</li>
</ul>

<h2>Looking after a steelbook</h2>
<ul>
<li>Handle it by the edges and wipe it with a dry, soft cloth.</li>
<li>Store it upright on a shelf, away from damp and direct sunlight.</li>
<li>Do not stack heavy items on top of it. Corners bend easily.</li>
<li>If you play the game, put the disc back in its holder straight away.</li>
</ul>

<h2>Should you play a steelbook or keep it sealed?</h2>
<p>That is a personal choice. Collectors who want the best condition keep them sealed. Everyone else opens them and enjoys the game. If you are unsure, buy a copy in a condition you are happy to handle.</p>

<h2>Buying and selling steelbooks with us</h2>
<p>We list collectable steelbook editions with photos of the exact copy. Browse them in the <a href="/shop?edition=steelbook">shop</a> or on the steelbook collection page when there are enough in stock. If you have one to sell, add it to the <a href="/sell">sell page</a>. We quote from the standard edition, and a steelbook or collector's edition can earn more: we will tell you in your offer.</p>
HTML,
];

// 8 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'how-we-keep-buyers-and-sellers-anonymous', 'privacy', 9,
    'How we keep buyers and sellers anonymous',
    'How We Keep Buyers and Sellers Anonymous | CyberGaming',
    'How CyberGaming keeps buyers and sellers anonymous: anonymous IDs, no public profiles, no messaging between members and blocked contact details.',
    'On {{site_name}}, buyers and sellers never see each other. Members only have an anonymous ID, there are no public profiles or messages between members, and contact details are blocked in public listings. Your name, phone number and address are known only to you and to us.',
    <<<'HTML'
<p>Buying and selling games between people usually means sharing your phone number with strangers. We built {{site_name}} so you never have to. This guide explains exactly what is private and how it works.</p>

<h2>The short version</h2>
<p>Buyers deal only with us. Sellers deal only with us. Nobody gets anyone else's name, phone number or address.</p>

<h2>What is hidden</h2>
<ul>
<li><strong>Your name, phone number, email and address</strong> are seen only by you and by CyberGaming.</li>
<li><strong>Your identity when you sell.</strong> Buyers never learn who sold the game they receive.</li>
<li><strong>Your exact location.</strong> Swap listings show only a delivery zone, not an address.</li>
</ul>

<h2>What is visible</h2>
<p>Only an anonymous ID. Every account gets a random ID such as <em>CalmBear11</em>. Swap listings show a swapper number and a delivery zone and nothing else.</p>

<h2>How it works in practice</h2>
<h3>No public profiles</h3>
<p>There is no page anyone can open to see who you are or what you have bought or sold.</p>

<h3>No messaging between members</h3>
<p>Members cannot message each other. Every purchase, sale, trade and swap goes through us, so there is nobody who needs to chase you and nobody you have to chase.</p>

<h3>Contact details are blocked in listings</h3>
<p>Listings are public, so phone numbers, emails, links and social handles are rejected before a listing can be published. Put your details only in the private fields on the form. Only our team sees them.</p>

<h3>Money and items go through us</h3>
<p>You never hand a game or cash to a stranger. Items go to our hub or to our courier, we inspect them, and we handle the payment. That is also why we can promise inspection: <a href="/guides/used-vs-new-games-what-to-check-before-you-buy">every item is checked</a> before it changes hands.</p>

<h2>Swaps and anonymity</h2>
<p>On the <a href="/swap">swap board</a>, listings show the platform, the games, a swapper number and a delivery zone. When there is a match, both players bring their game to our hub, we inspect both and hand each player the game they wanted. The fee is a flat {{swap_fee}} per side, charged only when the swap completes.</p>

<h2>Selling through us</h2>
<p>When you <a href="/sell">sell your games</a>, your request is seen by our team only. When members list games for other customers, CyberGaming takes a {{member_commission}} commission and buyers never see who the seller is. Read <a href="/guides/how-store-credit-works-at-cybergaming">how store credit works</a> for what happens to the money.</p>

<h2>What we ask of you</h2>
<ul>
<li>Do not put your phone number or social handle in a listing or note that other people can see.</li>
<li>Keep your account details to yourself.</li>
<li>Tell us on WhatsApp if anything looks wrong. See <a href="/contact">contact</a>.</li>
</ul>

<p>For the full picture of how buying, selling and safety work, read <a href="/how-it-works#anonymous">how CyberGaming works</a>.</p>
HTML,
];

// 9 ---------------------------------------------------------------------------------------------------------------
$articles[] = [
    'how-to-choose-a-gaming-mouse', 'peripherals', 10,
    'How to choose a gaming mouse: DPI, sensor, weight, wired vs wireless',
    'How to Choose a Gaming Mouse: DPI, Sensor and Weight',
    'What actually matters in a gaming mouse: DPI, sensor, shape and grip, weight, buttons and wired versus wireless, explained without the marketing.',
    'Choose a gaming mouse by fit first: shape, size and weight for your hand and grip. Then check the sensor, the switches and whether you want wired or wireless. A very high DPI number is mostly marketing, so look at overall quality instead.',
    <<<'HTML'
<p>A gaming mouse spec sheet is full of big numbers. Most of them matter less than how the mouse feels in your hand. Here is what to look at, in the order that matters.</p>

<h2>1. Shape, size and grip</h2>
<p>This is the biggest factor. Measure or check your hand size, and think about how you hold a mouse:</p>
<ul>
<li><strong>Palm grip:</strong> your whole hand rests on the mouse. Larger, taller shapes suit this.</li>
<li><strong>Claw grip:</strong> the palm touches the back and the fingers arch. Mid-size mice with a raised rear fit well.</li>
<li><strong>Fingertip grip:</strong> only your fingertips touch it. Small, light mice work best.</li>
</ul>
<p>Symmetrical (ambidextrous) mice suit left- and right-handed players. Ergonomic shapes are made for one hand only and can be more comfortable for long sessions.</p>

<h2>2. Sensor and DPI</h2>
<p>DPI (dots per inch) tells you how far the cursor moves for a given hand movement. A higher number is not better by default. Most players run a moderate setting and adjust in the game. What matters more is that the sensor tracks accurately and consistently without jitter, smoothing or spin-outs. Look for an optical sensor from a known maker, and treat a headline DPI figure as a maximum you will rarely use.</p>
<p>Polling rate is how often the mouse reports its position. Higher rates can feel smoother, and 1000 Hz is common on gaming mice.</p>

<h2>3. Weight</h2>
<p>Lighter mice are easier to flick and stop, and heavier ones can feel more stable. Neither is right for everyone. If you are unsure, pick a mid-weight mouse and see how your wrist feels after an hour.</p>

<h2>4. Buttons and switches</h2>
<ul>
<li><strong>Main clicks:</strong> they should feel crisp and consistent, and double clicks should not happen by accident.</li>
<li><strong>Side buttons:</strong> useful in some games. Check that you can reach them without moving your grip.</li>
<li><strong>Scroll wheel:</strong> it should have a clear notch and click without wobble.</li>
</ul>

<h2>5. Wired or wireless</h2>
<table>
<thead><tr><th></th><th>Wired</th><th>Wireless</th></tr></thead>
<tbody>
<tr><td>Response</td><td>Very reliable</td><td>Good on modern models</td></tr>
<tr><td>Weight</td><td>Cable drag</td><td>No cable, but a battery adds weight</td></tr>
<tr><td>Upkeep</td><td>None</td><td>Charging or batteries</td></tr>
<tr><td>Price</td><td>Usually lower</td><td>Usually higher</td></tr>
</tbody>
</table>
<p>A good wired mouse with a flexible cable is a safe, affordable choice. Choose wireless if cable drag bothers you and you are happy to charge it.</p>

<h2>6. Buying a used mouse</h2>
<ul>
<li>Test both main buttons, the wheel and the side buttons.</li>
<li>Move it on a pad: the cursor should track smoothly.</li>
<li>Check the feet (the glides) for wear, and the cable for fraying.</li>
<li>Look for shiny, worn spots on the buttons. They are cosmetic, but they show heavy use.</li>
</ul>
<p>Pair your mouse with the right surface: read <a href="/guides/how-to-choose-a-mousepad">how to choose a mousepad</a>.</p>

<h2>Shop mice with us</h2>
<p>Every peripheral is tested and inspected before it is listed, and the listing shows the brand, model and what is in the box. Browse <a href="/shop/mice">mice</a> and the rest of our <a href="/shop">gaming gear</a>. Delivery starts at {{local_fee}} in local areas ({{local_zones}}) and {{remote_fee}} elsewhere, and any warranty offered is shown on the product page.</p>
HTML,
];

// 10 --------------------------------------------------------------------------------------------------------------
$articles[] = [
    'mechanical-vs-membrane-keyboards-for-gaming', 'peripherals', 11,
    'Mechanical vs membrane keyboards: which is right for gaming?',
    'Mechanical vs Membrane Keyboards for Gaming',
    'Mechanical or membrane? Compare feel, speed, noise, durability and price, plus layouts, switch types and what to check on a used keyboard.',
    'Mechanical keyboards give crisp, consistent key presses, last long and let you choose your switch type, but they cost more and can be noisy. Membrane keyboards are quieter and cheaper but feel softer. For most gamers a mechanical board is the better long-term buy.',
    <<<'HTML'
<p>Keyboards look alike from above, but they feel very different underneath. The choice between mechanical and membrane decides how every keystroke feels. Here is how to pick.</p>

<h2>Mechanical vs membrane at a glance</h2>
<table>
<thead><tr><th></th><th>Mechanical</th><th>Membrane</th></tr></thead>
<tbody>
<tr><td>How it works</td><td>A separate switch under each key</td><td>A rubber dome sheet under all the keys</td></tr>
<tr><td>Feel</td><td>Crisp and consistent, varies by switch</td><td>Softer and more cushioned</td></tr>
<tr><td>Noise</td><td>Louder, depending on the switch</td><td>Quieter</td></tr>
<tr><td>Lifespan</td><td>Long. Makers rate their switches highly</td><td>Shorter. Domes wear</td></tr>
<tr><td>Repairs</td><td>Switches and keycaps can often be replaced</td><td>Usually the whole board</td></tr>
<tr><td>Price</td><td>Higher</td><td>Lower</td></tr>
</tbody>
</table>

<h2>Switch types</h2>
<ul>
<li><strong>Linear:</strong> smooth, with no bump. Popular for fast, repeated presses in games.</li>
<li><strong>Tactile:</strong> a small bump when the key registers. Good if you also type a lot.</li>
<li><strong>Clicky:</strong> a bump and an audible click. Satisfying, but loud in a shared room.</li>
</ul>
<p>If you can, try a few before you buy. Switch colours vary by brand, so read the description rather than trusting the colour alone.</p>

<h2>Features that help in games</h2>
<ul>
<li><strong>Anti-ghosting or n-key rollover:</strong> the keyboard registers several keys pressed together.</li>
<li><strong>Layout size:</strong> full-size boards have a number pad. Tenkeyless (TKL) boards drop it to free mouse space. Compact 60 percent boards drop more, including the arrow keys.</li>
<li><strong>Keycaps:</strong> thicker PBT keycaps resist shine and wear better than thin ABS ones.</li>
<li><strong>Wired or wireless:</strong> wired is simplest. Wireless boards are convenient, but they need charging.</li>
<li><strong>Backlight and software:</strong> nice to have, not essential.</li>
</ul>

<h2>Which should you buy?</h2>
<p>If you game and type a lot, and can afford it, a mechanical keyboard is the better long-term choice. If you share a room, need a quiet keyboard or have a tight budget, a good membrane board is fine. The best keyboard is the one whose layout and key feel suit you.</p>

<h2>Noise and shared rooms</h2>
<p>Sound is the thing people forget. Clicky switches are loud enough to carry through a call or a sleeping household. If you play late, or share a room, choose linear or quiet tactile switches, or a membrane board. You can also add rubber rings under the keycaps on some mechanical boards to soften the bottom-out sound.</p>

<h2>Checking a used keyboard</h2>
<ul>
<li>Press every key, including the function and media keys. Nothing should stick, chatter or fail to register.</li>
<li>Check the keycaps for shine, cracks and missing legends.</li>
<li>Test the cable, the USB connector and any wireless dongle.</li>
<li>Type for a minute on a text field to spot double presses.</li>
</ul>

<h2>Buying from us</h2>
<p>Keyboards are tested and inspected before they are listed, with the brand, model, specs and what is in the box shown on the product page. Browse <a href="/shop/keyboards">keyboards</a> and pair one with the right mouse: read <a href="/guides/how-to-choose-a-gaming-mouse">how to choose a gaming mouse</a>. Delivery starts at {{local_fee}} in local areas and {{remote_fee}} in remote areas. See <a href="/delivery-and-payment">delivery and payment</a>.</p>
HTML,
];

// 11 --------------------------------------------------------------------------------------------------------------
$articles[] = [
    'how-to-choose-a-mousepad', 'peripherals', 12,
    'How to choose a mousepad: speed vs control, size and surface',
    'How to Choose a Mousepad: Speed, Control and Size',
    'How to choose a gaming mousepad: speed versus control surfaces, the right size for your sensitivity, thickness, base and how to keep it clean.',
    'Choose a mousepad by surface first: speed pads let the mouse glide, control pads give more resistance and stopping power. Then choose a size that fits your arm movement, and a rubber base that does not slide. Larger desk mats suit low sensitivity and give you room for a keyboard.',
    <<<'HTML'
<p>A mousepad is the cheapest upgrade for your aim and the one people skip. The surface under your mouse changes how it glides and stops. Here is how to pick one.</p>

<h2>1. Speed or control?</h2>
<ul>
<li><strong>Speed surfaces</strong> are smooth and let the mouse glide with little friction. They suit fast flicks and quick reactions, but can make small, precise stops harder.</li>
<li><strong>Control surfaces</strong> have more texture and friction, so the mouse stops when you stop. They suit precise aiming and steady tracking, but they need slightly more effort to move.</li>
<li><strong>Balanced surfaces</strong> sit between the two and are a safe first choice.</li>
</ul>
<p>Neither is better. Choose the one that matches how you play, and try to keep the same surface once you get used to it.</p>

<h2>2. Cloth, hard or glass</h2>
<table>
<thead><tr><th>Surface</th><th>Feel</th><th>Notes</th></tr></thead>
<tbody>
<tr><td>Cloth</td><td>Soft, varies from fast to slow</td><td>Most common. Comfortable, and wears slowly with use.</td></tr>
<tr><td>Hard plastic or aluminium</td><td>Very fast, very smooth</td><td>Easy to wipe clean. Can be noisy.</td></tr>
<tr><td>Glass</td><td>Very fast and consistent</td><td>Wears mouse feet faster, and costs more.</td></tr>
</tbody>
</table>

<h2>3. Size</h2>
<p>Size depends on your mouse sensitivity and how far you move your arm.</p>
<ul>
<li><strong>Small:</strong> for high sensitivity and small wrist movements.</li>
<li><strong>Medium and large:</strong> for most players.</li>
<li><strong>Extra-large or desk mat:</strong> for low sensitivity and big arm sweeps. It also holds your keyboard and gives a clean desk look.</li>
</ul>
<p>Measure your desk before you buy and leave a little space around it.</p>

<h2>4. Thickness and base</h2>
<ul>
<li>A thin pad feels firmer and gives less cushion. A thicker pad is more comfortable for your wrist.</li>
<li>A <strong>rubber base</strong> stops the pad from sliding. Check that it lies flat and the corners do not curl.</li>
<li><strong>Stitched edges</strong> stop the fabric fraying, and they make a cloth pad last longer.</li>
</ul>

<h2>5. Match it to your mouse</h2>
<p>A pad and a mouse work as a pair. A light mouse on a fast pad can feel slippery, and a heavy mouse on a slow pad can feel sticky. If you are still choosing a mouse, read <a href="/guides/how-to-choose-a-gaming-mouse">how to choose a gaming mouse</a> first.</p>

<h2>6. Cleaning and care</h2>
<ul>
<li>Brush off dust often.</li>
<li>Wash a cloth pad by hand with lukewarm water and mild soap, rinse well and let it dry flat.</li>
<li>Wipe hard pads with a damp cloth.</li>
<li>Replace the pad if the surface has worn shiny or thin in the middle.</li>
</ul>

<h2>Buying a used mousepad</h2>
<p>Look at the surface in daylight. It should be flat and even, with no stains or worn patches, and the base should still grip the desk. Cloth pads are easy to clean, so a lightly used one can be a good bargain.</p>

<h2>Shop mousepads with us</h2>
<p>Browse <a href="/shop/mousepads">mousepads</a> and other <a href="/shop">gaming gear</a>. Every item is inspected before it is listed, and delivery starts at {{local_fee}} in local areas ({{local_zones}}) and {{remote_fee}} elsewhere. Questions about a specific pad? Message us on <a href="/contact">WhatsApp</a>.</p>
HTML,
];

// ---------------------------------------------------------------------------------------------------------------
$insert = 0;
$update = 0;
$skipped = 0;
foreach ($articles as [$slug, $tag, $daysAgo, $title, $metaTitle, $metaDescription, $excerpt, $body]) {
    $exists = db()->fetchValue('SELECT id FROM articles WHERE slug = ?', [$slug]);
    if ($exists && !$force) {
        $skipped++;
        continue;
    }
    // published_at spread over the last days; computed by the database so it matches the visibility check (published_at <= NOW())
    if ($exists) {
        db()->execute(
            'UPDATE articles SET title = ?, excerpt = ?, body = ?, meta_title = ?, meta_description = ?, tag = ? WHERE id = ?',
            [$title, $excerpt, trim($body), $metaTitle, $metaDescription, $tag, $exists]
        );
        $update++;
        continue;
    }
    db()->execute(
        'INSERT INTO articles (slug, title, excerpt, body, meta_title, meta_description, tag, author, is_published, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW() - INTERVAL ' . (int) $daysAgo . ' DAY)',
        [$slug, $title, $excerpt, trim($body), $metaTitle, $metaDescription, $tag, 'CyberGaming team']
    );
    $insert++;
}
echo "Articles: $insert inserted, $update updated, $skipped already present.\n";
