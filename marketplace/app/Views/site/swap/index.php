<?php
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;

/** @var array $platforms @var ?array $platform @var array $listings @var int $total @var int $page @var int $pages @var array $query @var array $areas @var string $fee @var array $crumbs @var array $meta */
$faqs = [
    ['How does swapping games work?', 'List the game you have and the game you want. When we find a match, both players bring their game to our hub, we inspect both discs and cases, and hand each player the game they wanted.'],
    ['Do other players see my phone number?', 'No. The board shows only the platform, the games, an anonymous swapper number (like #7K2M) and your delivery zone. We never share names, phone numbers or exact areas between players, and we connect you privately through CyberGaming.'],
    ['How much does a swap cost?', "A flat $fee fee per side, charged only when the swap completes. There is no commission on the value of the games."],
    ['Is it safe?', 'Both games go through our hub and are inspected before anything changes hands, so you never meet a stranger or hand over a game before receiving yours.'],
    ['Why is my listing not on the board yet?', 'New listings are reviewed by our team before they are published. Once approved, your listing appears on the board and we contact you when someone is interested.'],
    ['Why can I not put my phone number or Instagram in the listing?', 'Listings are public, and swaps run through our hub to keep everyone safe. Leave your number in the private details field only; we connect you when there is a match.'],
];
$meta['jsonld'][] = Seo::webPage('WebPage', 'Swap games with other players in Lebanon', (string) ($meta['canonical'] ?? url('/swap')), (string) ($meta['description'] ?? ''));
$meta['jsonld'][] = Seo::faqLd($faqs);
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => 'Swap games with other players in Lebanon', 'lead' => "Trade a game you have for one you want, with another gamer. We stay in the middle so nobody shares a phone number. Flat $fee fee per side."]);
echo Seo::quickAnswerHtml('swap');
?>
<div class="container">
    <?= Ui::partial('flow-nav', ['active' => 'swap']) ?>
</div>

<div class="container">
    <section class="section-tight" aria-labelledby="board-h">
        <div class="section-head">
            <h2 id="board-h">Swap board<?= $platform ? ': ' . e($platform['name']) : '' ?></h2>
            <p class="section-sub"><?= $total ?> open <?= $total === 1 ? 'swap' : 'swaps' ?></p>
        </div>

        <ul class="pill-nav" aria-label="Filter by platform">
            <li><a class="pill<?= $platform ? '' : ' is-current' ?>" href="<?= e(url('/swap')) ?>"<?= $platform ? '' : ' aria-current="page"' ?>>All platforms</a></li>
            <?php foreach ($platforms as $pl): ?>
                <li><a class="pill<?= $platform && $platform['slug'] === $pl['slug'] ? ' is-current' : '' ?>" href="<?= e(url('/swap') . '?platform=' . rawurlencode($pl['slug'])) ?>"<?= $platform && $platform['slug'] === $pl['slug'] ? ' aria-current="page"' : '' ?>><?= e(Ui::shortPlatform($pl['slug'], $pl['name'])) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <?php if (!$listings): ?>
            <div class="empty">
                <?= Ui::icon('swap', 44) ?>
                <h3>No open swaps<?= $platform ? ' for ' . e($platform['name']) : '' ?> right now</h3>
                <p>Be the first: list the game you have and the one you want below. Approved listings appear here.</p>
                <p><a class="btn btn-primary" href="#list-swap">List a swap</a></p>
            </div>
        <?php else: ?>
            <ul class="swap-grid">
                <?php foreach ($listings as $s):
                    // the message only quotes the swap code: nothing that could identify the poster
                    $wa = wa_link("Hi CyberGaming, I'm interested in swap {$s['code']}.\nI have: "); ?>
                    <li class="swap-card">
                        <p class="swap-top">
                            <?php if ($s['platform_name']): ?><span class="tag"><?= e(Ui::shortPlatform($s['platform_slug'], $s['platform_name'])) ?></span><?php endif; ?>
                            <span class="swap-code"><?= e($s['code']) ?></span>
                        </p>
                        <p class="swap-line"><span>Offering</span> <strong><?= e($s['offering']) ?></strong></p>
                        <p class="swap-line"><span>Wants</span> <strong><?= e($s['wanting']) ?></strong></p>
                        <p class="swap-by">Swapper <?= e($s['swapper']) ?><?= $s['zone'] !== '' ? ' &middot; ' . e($s['zone']) : '' ?> &middot; <time datetime="<?= e(date('Y-m-d', strtotime((string) $s['created_at']) ?: time())) ?>"><?= e(date('j M Y', strtotime((string) $s['created_at']) ?: time())) ?></time></p>
                        <a class="btn btn-wa btn-sm" href="<?= e($wa) ?>" rel="noopener nofollow" target="_blank"><?= Ui::icon('whatsapp', 18) ?> I'm interested</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?= Ui::pagination('/swap', $query, $page, $pages) ?>
        <?php endif; ?>
    </section>
</div>

<div class="container cart-layout swap-layout">
    <section class="card-box" id="list-swap" aria-labelledby="list-h">
        <h2 id="list-h">List a swap</h2>
        <p class="fine">Your listing is reviewed before it appears on the board. Only the platform, the games, an anonymous swapper number and your delivery zone are shown &mdash; never your name, phone or exact area. Please do not put phone numbers, links or @handles in the listing.</p>
        <form method="post" action="<?= e(url('/swap/submit')) ?>" data-once>
            <?= csrf_field() ?>
            <div class="form-row">
                <label for="sw-platform">Platform <abbr title="required">*</abbr></label>
                <select id="sw-platform" name="platform" required>
                    <option value="">Choose platform…</option>
                    <?php foreach ($platforms as $pl): ?><option value="<?= e($pl['slug']) ?>"<?= old('platform') === $pl['slug'] ? ' selected' : '' ?>><?= e($pl['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label for="sw-offering">I have <abbr title="required">*</abbr></label>
                <input id="sw-offering" name="offering" type="text" required maxlength="255" placeholder="e.g. God of War (2018), good condition" value="<?= e(old('offering')) ?>">
            </div>
            <div class="form-row">
                <label for="sw-wanting">I want <abbr title="required">*</abbr></label>
                <input id="sw-wanting" name="wanting" type="text" required maxlength="255" placeholder="e.g. Horizon Zero Dawn or Spider-Man" value="<?= e(old('wanting')) ?>">
                <small>Public. Tell us one game or a few you would accept.</small>
            </div>
            <div class="form-row">
                <label for="sw-notes">Private notes <span class="opt">(optional)</span></label>
                <textarea id="sw-notes" name="notes" rows="3" maxlength="500" placeholder="Edition, extras, when you are free to drop it off"><?= e(old('notes')) ?></textarea>
                <small>Only our team sees this.</small>
            </div>
            <h3 class="trade-h3">Your private details</h3>
            <?= Ui::partial('details-fields', ['details' => ['name' => old('name'), 'phone' => old('phone'), 'area' => old('area')], 'areas' => $areas, 'p' => 'sw', 'withNote' => false, 'areaLabel' => 'Your zone', 'areaHelp' => 'Shown on the board as your zone (for example "Beirut"). Your exact area stays private.']) ?>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Submit my swap</button>
            <p class="fine">Flat <?= e($fee) ?> fee per side, charged only when the swap completes.</p>
        </form>
    </section>

    <aside class="summary" aria-label="How swapping works">
        <h2>How swapping works</h2>
        <ol class="mini-steps">
            <li><strong>List it.</strong> Post the game you have and the one you want. We review and publish it.</li>
            <li><strong>Get matched.</strong> When you tap "I'm interested" on someone's listing, or someone taps yours, we set up the swap on WhatsApp.</li>
            <li><strong>Both games come to our hub.</strong> Your identity stays private the whole time.</li>
            <li><strong>We inspect and exchange.</strong> We check both discs and cases, then hand each player the game they wanted.</li>
        </ol>
        <p class="fine"><strong>Fee:</strong> <?= e($fee) ?> per side, charged when the swap completes. No commission on the value of the games.</p>
        <p class="fine"><strong>Safety:</strong> nobody hands a game to a stranger. Everything goes through us.</p>
    </aside>
</div>

<div class="container prose-wrap">
    <?= Seo::faqHtml($faqs) ?>
    <p class="see-also">Would you rather sell? See <a href="<?= e(url('/sell')) ?>">selling for cash</a> or <a href="<?= e(url('/trade')) ?>">trading in for credit</a>.</p>
</div>
