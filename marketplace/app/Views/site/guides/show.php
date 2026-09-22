<?php
use App\Modules\Storefront\Guides;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;

/** @var array $a @var string $title @var string $excerpt @var array{html:string,toc:array} $body @var array $crumbs @var array $related @var array $shopLinks @var bool $preview */
$published = (string) ($a['published_at'] ?: $a['created_at']);
$updated = (string) ($a['updated_at'] ?: $published);
$fmt = static fn (string $d): string => date('j F Y', (int) strtotime($d));
$iso = static fn (string $d): string => date('Y-m-d', (int) strtotime($d));
$toc = $body['toc'];
?>
<div class="container page-head guide-head">
    <?= Ui::breadcrumbs($crumbs) ?>
    <h1><?= e($title) ?></h1>
    <p class="guide-byline">
        By <span><?= e($a['author']) ?></span>
        <span aria-hidden="true">&middot;</span> <time datetime="<?= e($iso($published)) ?>">Published <?= e($fmt($published)) ?></time>
        <?php if ($iso($updated) !== $iso($published)): ?><span aria-hidden="true">&middot;</span> <time datetime="<?= e($iso($updated)) ?>">Updated <?= e($fmt($updated)) ?></time><?php endif; ?>
        <?php if (!empty($a['tag'])): ?><span aria-hidden="true">&middot;</span> <a href="<?= e(url('/guides?tag=' . rawurlencode((string) $a['tag']))) ?>"><?= e(Guides::tagLabel($a['tag'])) ?></a><?php endif; ?>
    </p>
</div>
<?php if ($preview): ?>
<div class="container"><p class="flash flash-error" role="status">Preview: this guide is not published yet and is hidden from visitors and search engines.</p></div>
<?php endif; ?>
<?php if ($excerpt !== ''): ?><?= Seo::render('quick-answer', ['text' => $excerpt]) ?><?php endif; ?>

<div class="container guide-layout">
    <article class="prose guide-body">
        <?php if (count($toc) >= 3): ?>
        <nav class="toc" aria-labelledby="toc-h">
            <p class="toc-title" id="toc-h">In this guide</p>
            <ol>
                <?php foreach ($toc as $t): ?>
                    <li class="toc-l<?= (int) $t['level'] ?>"><a href="#<?= e($t['id']) ?>"><?= e($t['text']) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>
        <?= $body['html'] ?>
    </article>

    <aside class="guide-side" aria-label="Related shop links">
        <h2>Related in the shop</h2>
        <ul>
            <?php foreach ($shopLinks as [$label, $path]): ?>
                <li><a href="<?= e(url($path)) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <p class="guide-side-note">Questions? <a href="<?= e(url('/contact')) ?>">Ask us on WhatsApp</a>.</p>
    </aside>
</div>

<?php if ($related): ?>
<section class="container guide-related" aria-labelledby="related-h">
    <h2 id="related-h">Related guides</h2>
    <ul class="guide-grid">
        <?php foreach ($related as $g): $when = (string) ($g['published_at'] ?: $g['created_at']); ?>
            <li class="guide-card">
                <p class="guide-tag"><?= e(Guides::tagLabel($g['tag'])) ?></p>
                <h3><a href="<?= e(url('/guides/' . $g['slug'])) ?>"><?= e(Guides::text($g['title'])) ?></a></h3>
                <p class="guide-excerpt"><?= e(Guides::text($g['excerpt'])) ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><a class="link-more" href="<?= e(url('/guides')) ?>">All guides &rarr;</a></p>
</section>
<?php endif; ?>
