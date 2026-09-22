<?php
use App\Modules\Storefront\Guides;
use App\Modules\Storefront\Seo;
use App\Modules\Storefront\Ui;

/** @var array $crumbs @var array $result @var string $tag @var array<string,int> $tags @var array $query */
$h1 = $tag !== '' ? Guides::tagLabel($tag) . ' guides' : 'Gaming guides for Lebanon';
echo Ui::partial('page-head', ['crumbs' => $crumbs, 'h1' => $h1, 'lead' => '']);
if ($tag === '' && $result['page'] === 1) {
    echo Seo::render('quick-answer', ['text' => Seo::quickAnswer('guides')]);
}
?>
<div class="container guide-hub">
    <?php if (count($tags) > 1): ?>
    <nav class="pill-nav" aria-label="Filter guides by topic">
        <a class="pill<?= $tag === '' ? ' is-active' : '' ?>" href="<?= e(url('/guides')) ?>"<?= $tag === '' ? ' aria-current="true"' : '' ?>>All guides</a>
        <?php foreach ($tags as $t => $n): ?>
            <a class="pill<?= $tag === $t ? ' is-active' : '' ?>" href="<?= e(url('/guides?tag=' . rawurlencode($t))) ?>"<?= $tag === $t ? ' aria-current="true"' : '' ?>><?= e(Guides::tagLabel($t)) ?> (<?= $n ?>)</a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if (!$result['items']): ?>
        <p class="empty-note">No guides here yet. Try <a href="<?= e(url('/guides')) ?>">all guides</a>, or <a href="<?= e(url('/how-it-works')) ?>">how CyberGaming works</a>.</p>
    <?php else: ?>
    <ul class="guide-grid">
        <?php foreach ($result['items'] as $g): $when = (string) ($g['published_at'] ?: $g['created_at']); ?>
            <li class="guide-card">
                <p class="guide-tag"><?= e(Guides::tagLabel($g['tag'])) ?></p>
                <h2><a href="<?= e(url('/guides/' . $g['slug'])) ?>"><?= e(Guides::text($g['title'])) ?></a></h2>
                <p class="guide-excerpt"><?= e(Guides::text($g['excerpt'])) ?></p>
                <p class="guide-meta"><time datetime="<?= e(date('Y-m-d', (int) strtotime($when))) ?>"><?= e(date('j M Y', (int) strtotime($when))) ?></time></p>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= Ui::pagination('/guides', $query, $result['page'], $result['pages']) ?>
    <?php endif; ?>
</div>
