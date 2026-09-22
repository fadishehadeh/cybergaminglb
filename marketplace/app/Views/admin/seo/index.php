<?php
/** @var array $values @var string[] $images @var array $health @var string $siteUrl */
use App\Modules\Admin\Forms;
use App\Modules\Admin\SeoController;

$pageTitle = 'SEO & AI search';
$nav = 'seo';
$v = static fn (string $key): string => (string) Forms::val($key, $values[$key] ?? '');
$aiOn = Forms::checked('seo_ai_crawlers_allowed', $values['seo_ai_crawlers_allowed'] === '1');
$curImage = (string) (old('_form') ? old('og_choice', '') : ($values['seo_default_og_image'] ?? ''));
if ($curImage === 'none') {
    $curImage = '';
}
$imageList = $images;
if ($curImage !== '' && !in_array($curImage, $imageList, true) && is_file(PUBLIC_PATH . '/uploads/' . $curImage)) {
    array_unshift($imageList, $curImage);
}
$h = $health;
$row = static function (string $label, int|string $value, bool $bad, string $note = '', string $href = ''): string {
    $cls = $bad ? 'h-warn' : 'h-ok';
    $val = '<strong class="' . $cls . '">' . e((string) $value) . '</strong>';
    if ($href !== '') {
        $val = '<a href="' . e(url($href)) . '">' . $val . '</a>';
    }
    return '<li><span class="h-label">' . e($label) . ($note !== '' ? ' <small class="muted">' . e($note) . '</small>' : '') . '</span><span class="h-val">' . $val . '</span></li>';
};
$rich = 'https://search.google.com/test/rich-results?url=' . rawurlencode($siteUrl);
$speed = 'https://pagespeed.web.dev/analysis?url=' . rawurlencode($siteUrl);
$sitePath = static fn (string $path): string => rtrim(url(''), '/') . $path;
?>
<div class="page-head">
    <div>
        <h1>SEO &amp; AI search</h1>
        <p class="muted">How Google, Bing and AI assistants find and describe your shop. Everything here can be changed at any time.</p>
    </div>
</div>

<div class="cols-form seo-cols">
    <form method="post" action="<?= e(url('/admin/seo')) ?>" enctype="multipart/form-data" class="seo-form">
        <?= csrf_field() ?>
        <input type="hidden" name="_form" value="1">

        <section class="card" id="ai">
            <div class="card-head"><h2>AI search</h2></div>
            <div class="card-body">
                <input type="hidden" name="seo_ai_crawlers_allowed" value="0">
                <label class="check check-lg"><input type="checkbox" name="seo_ai_crawlers_allowed" value="1" <?= $aiOn ? 'checked' : '' ?>> Let AI assistants read and cite the website</label>
                <p class="hint"><strong>ON:</strong> ChatGPT, Claude, Perplexity and Google's AI can read and cite your site.
                    <strong>OFF:</strong> blocks their training crawlers but keeps AI search engines allowed.</p>
            </div>
        </section>

        <section class="card" id="verification">
            <div class="card-head"><h2>Search engine verification</h2></div>
            <div class="card-body form-grid">
                <div class="field span-2">
                    <label for="seo_google_verification">Google Search Console code</label>
                    <input type="text" id="seo_google_verification" name="seo_google_verification" value="<?= e($v('seo_google_verification')) ?>" maxlength="400" autocomplete="off" placeholder="Paste the code or the whole &lt;meta&gt; tag">
                    <small class="hint">In Search Console choose "HTML tag" and paste it here. 20 to 100 letters, numbers, dashes or underscores. If you paste the whole tag, only the code is kept.</small>
                </div>
                <div class="field span-2">
                    <label for="seo_bing_verification">Bing Webmaster code</label>
                    <input type="text" id="seo_bing_verification" name="seo_bing_verification" value="<?= e($v('seo_bing_verification')) ?>" maxlength="400" autocomplete="off" placeholder="Paste the code or the whole &lt;meta&gt; tag">
                </div>
            </div>
        </section>

        <section class="card" id="og">
            <div class="card-head"><h2>Default social image</h2></div>
            <div class="card-body">
                <p class="hint">Shown when a page without its own picture is shared on WhatsApp, Facebook or X. Best size 1200 x 630 px. JPG, PNG or WebP, up to 3 MB.</p>
                <div class="og-grid">
                    <label class="og-opt"><input type="radio" name="og_choice" value="none" <?= $curImage === '' ? 'checked' : '' ?>> <span class="og-none">No default image</span></label>
                    <?php foreach ($imageList as $img): ?>
                        <label class="og-opt"><input type="radio" name="og_choice" value="<?= e($img) ?>" <?= $curImage === $img ? 'checked' : '' ?>>
                            <img src="<?= e(media($img)) ?>" alt="Saved image <?= e(basename($img)) ?>" loading="lazy"></label>
                    <?php endforeach; ?>
                </div>
                <div class="field">
                    <label for="og_image">Upload a new image</label>
                    <input type="file" id="og_image" name="og_image" accept="image/jpeg,image/png,image/webp" data-max-bytes="3145728" data-og-upload>
                    <small class="hint" data-og-note>A new upload replaces the selection above.</small>
                </div>
            </div>
        </section>

        <section class="card" id="business">
            <div class="card-head"><h2>Business details</h2></div>
            <div class="card-body form-grid">
                <p class="hint span-2">Used in Google's structured data (your business card in search results). Leave a field empty to leave it out.</p>
                <div class="field span-2">
                    <label for="biz_address">Address <small class="muted counter" data-count-for="biz_address" data-max="200"></small></label>
                    <input type="text" id="biz_address" name="biz_address" value="<?= e($v('biz_address')) ?>" maxlength="200" placeholder="Street, area, city">
                </div>
                <div class="field">
                    <label for="biz_hours">Opening hours</label>
                    <input type="text" id="biz_hours" name="biz_hours" value="<?= e($v('biz_hours')) ?>" maxlength="200" placeholder="Mo-Sa 10:00-20:00; Su closed">
                    <small class="hint">Days Mo Tu We Th Fr Sa Su, 24-hour times, separate groups with ";". Examples: <code>Mo-Sa 10:00-20:00; Su closed</code> or <code>Mo-Fr 09:00-18:00; Sa 10:00-14:00</code>.</small>
                </div>
                <div class="field">
                    <label for="biz_price_range">Price range</label>
                    <select id="biz_price_range" name="biz_price_range"><?= Forms::options(SeoController::PRICE_RANGES, $v('biz_price_range') !== '' ? $v('biz_price_range') : '$$') ?></select>
                </div>
                <div class="field">
                    <label for="biz_facebook_url">Facebook page</label>
                    <input type="url" id="biz_facebook_url" name="biz_facebook_url" value="<?= e($v('biz_facebook_url')) ?>" maxlength="255" placeholder="https://www.facebook.com/...">
                </div>
                <div class="field">
                    <label for="instagram_url">Instagram</label>
                    <input type="url" id="instagram_url" name="instagram_url" value="<?= e($v('instagram_url')) ?>" maxlength="255" placeholder="https://www.instagram.com/...">
                </div>
                <div class="field">
                    <label for="biz_tiktok_url">TikTok</label>
                    <input type="url" id="biz_tiktok_url" name="biz_tiktok_url" value="<?= e($v('biz_tiktok_url')) ?>" maxlength="255" placeholder="https://www.tiktok.com/@...">
                </div>
                <div class="field">
                    <label for="biz_youtube_url">YouTube channel</label>
                    <input type="url" id="biz_youtube_url" name="biz_youtube_url" value="<?= e($v('biz_youtube_url')) ?>" maxlength="255" placeholder="https://www.youtube.com/@...">
                </div>
            </div>
        </section>

        <div class="form-actions"><button type="submit" class="btn btn-primary btn-lg">Save SEO settings</button></div>
    </form>

    <div class="stack">
        <section class="card" id="health">
            <div class="card-head"><h2>SEO health</h2><small class="muted">Live from your data</small></div>
            <div class="card-body">
                <h3 class="sub-title">Products (<?= (int) $h['products_active'] ?> live)</h3>
                <ul class="health">
                    <?= $row('Without a description', $h['no_description'], $h['no_description'] > 0, '', '/admin/products?seo=nodesc') ?>
                    <?= $row('With a short description', $h['short_description'], $h['short_description'] > 0, 'under 60 characters', '/admin/products?seo=shortdesc') ?>
                    <?= $row('Without a main image', $h['no_image'], $h['no_image'] > 0, '', '/admin/products?seo=noimage') ?>
                </ul>
                <h3 class="sub-title">Categories (<?= (int) $h['categories_active'] ?> enabled)</h3>
                <ul class="health">
                    <?= $row('Without SEO title', $h['cat_no_title'], $h['cat_no_title'] > 0, '', '/admin/catalog') ?>
                    <?= $row('Without SEO description', $h['cat_no_desc'], $h['cat_no_desc'] > 0, '', '/admin/catalog') ?>
                    <?= $row('Without intro text', $h['cat_no_intro'], $h['cat_no_intro'] > 0, '', '/admin/catalog') ?>
                </ul>
                <?php if ($h['cat_names']): ?><p class="hint">Needs attention: <?= e(implode(', ', $h['cat_names'])) ?>.</p><?php endif; ?>
                <h3 class="sub-title">Guides and delivery</h3>
                <ul class="health">
                    <?= $row('Guides published', $h['articles_published'], $h['articles_published'] === 0, '', '/admin/guides?status=published') ?>
                    <?= $row('Guides scheduled', $h['articles_scheduled'], false, '', '/admin/guides?status=scheduled') ?>
                    <?= $row('Guides in draft', $h['articles_draft'], false, '', '/admin/guides?status=draft') ?>
                    <?= $row('Active delivery zones', $h['zones_active'], $h['zones_active'] === 0, '', '/admin/settings#zones') ?>
                </ul>
                <?php if ($h['wa_placeholder']): ?>
                    <div class="alert alert-warn health-alert"><strong>WhatsApp is still the placeholder.</strong> Structured data omits contact info until you set a real number. <a href="<?= e(url('/admin/settings#whatsapp')) ?>">Set it in Settings</a>.</div>
                <?php else: ?>
                    <p class="hint text-good">WhatsApp number is set, so contact info is included in structured data.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card" id="checks">
            <div class="card-head"><h2>Check your site</h2></div>
            <div class="card-body">
                <ul class="check-links">
                    <li><a href="<?= e($sitePath('/robots.txt')) ?>" target="_blank" rel="noopener">robots.txt</a> <small class="muted">who may crawl</small></li>
                    <li><a href="<?= e($sitePath('/sitemap.xml')) ?>" target="_blank" rel="noopener">sitemap.xml</a> <small class="muted">every page</small></li>
                    <li><a href="<?= e($sitePath('/llms.txt')) ?>" target="_blank" rel="noopener">llms.txt</a> <small class="muted">summary for AI</small></li>
                    <li><a href="<?= e($sitePath('/feeds/products.xml')) ?>" target="_blank" rel="noopener">feeds/products.xml</a> <small class="muted">product feed</small></li>
                    <li><a href="<?= e($sitePath('/feeds/products.json')) ?>" target="_blank" rel="noopener">feeds/products.json</a> <small class="muted">product feed</small></li>
                    <li><a href="<?= e($rich) ?>" target="_blank" rel="noopener">Google Rich Results Test</a> <small class="muted">structured data</small></li>
                    <li><a href="<?= e($speed) ?>" target="_blank" rel="noopener">PageSpeed Insights</a> <small class="muted">speed</small></li>
                </ul>
                <p class="hint">The last two open Google's tools with <?= e($siteUrl) ?> filled in. They only work once the site is live on the internet.</p>
            </div>
        </section>
    </div>
</div>
