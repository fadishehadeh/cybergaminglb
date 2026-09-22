<?php
/** @var array $guides @var \App\Modules\Admin\Pagination $pager @var array $filters @var array $counts */
use App\Modules\Admin\Forms;
use App\Modules\Admin\GuideController;

$pageTitle = 'Guides';
$nav = 'guides';
$here = Forms::here();
$hasFilter = (bool) array_filter($filters, static fn ($v) => $v !== '');
$fmt = static fn (?string $d): string => $d ? date('j M Y, H:i', strtotime($d)) : '-';
?>
<div class="page-head">
    <div>
        <h1>Guides</h1>
        <p class="muted"><?= (int) $pager->total ?> guide<?= $pager->total === 1 ? '' : 's' ?><?= $hasFilter ? ' match your filters' : '' ?>. Guides answer the questions customers (and Google) ask, and appear at /guides.</p>
    </div>
    <div class="actions"><a class="btn btn-primary" href="<?= e(url('/admin/guides/create')) ?>">+ New guide</a></div>
</div>

<div class="tabs">
    <a class="<?= $filters['status'] === '' ? 'active' : '' ?>" href="<?= e(url('/admin/guides')) ?>">All <span class="tab-count"><?= (int) array_sum($counts) ?></span></a>
    <?php foreach (GuideController::STATUSES as $key => $label): ?>
        <a class="<?= $filters['status'] === $key ? 'active' : '' ?>" href="<?= e(url('/admin/guides?status=' . $key)) ?>"><?= e($label) ?> <span class="tab-count"><?= (int) $counts[$key] ?></span></a>
    <?php endforeach; ?>
</div>

<form method="get" action="<?= e(url('/admin/guides')) ?>" class="card filters">
    <div class="field grow-2">
        <label for="q">Search</label>
        <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Title, slug or quick answer">
    </div>
    <div class="field">
        <label for="f-status">Status</label>
        <select id="f-status" name="status"><?= Forms::options(['' => 'Any status'] + GuideController::STATUSES, $filters['status']) ?></select>
    </div>
    <div class="field">
        <label for="f-tag">Topic</label>
        <select id="f-tag" name="tag"><?= Forms::options(['' => 'Any topic'] + GuideController::TAGS, $filters['tag']) ?></select>
    </div>
    <div class="filter-actions">
        <button class="btn btn-primary" type="submit">Filter</button>
        <?php if ($hasFilter): ?><a class="btn btn-ghost" href="<?= e(url('/admin/guides')) ?>">Clear</a><?php endif; ?>
    </div>
</form>

<section class="card">
    <?php if (!$guides): ?>
        <div class="empty">
            <strong>No guides found</strong>
            <p><?= $hasFilter ? 'Try clearing the filters.' : 'Write your first guide: a clear answer to a question customers ask.' ?></p>
            <a class="btn btn-primary" href="<?= e(url('/admin/guides/create')) ?>">+ New guide</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Title</th><th>Topic</th><th>Status</th><th>Published</th><th>Updated</th><th class="num">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($guides as $g):
                    $key = (int) $g['is_live'] === 1 ? 'published' : ((int) $g['is_scheduled'] === 1 ? 'scheduled' : 'draft');
                ?>
                    <tr>
                        <td><a href="<?= e(url('/admin/guides/' . $g['id'] . '/edit')) ?>"><strong><?= e($g['title']) ?></strong></a><br><small class="muted">/guides/<?= e($g['slug']) ?></small></td>
                        <td><?= $g['tag'] ? '<span class="tag">' . e(GuideController::TAGS[$g['tag']] ?? $g['tag']) . '</span>' : '<span class="muted">-</span>' ?></td>
                        <td><span class="pill pill-<?= e($key) ?>"><?= e(GuideController::STATUSES[$key]) ?></span></td>
                        <td class="nowrap"><?= e($g['is_published'] ? $fmt($g['published_at']) : '-') ?></td>
                        <td class="nowrap"><small class="muted"><?= e($fmt($g['updated_at'])) ?></small></td>
                        <td class="num nowrap">
                            <a class="btn btn-sm" href="<?= e(url('/admin/guides/' . $g['id'] . '/edit')) ?>">Edit</a>
                            <a class="btn btn-sm" href="<?= e(url('/guides/' . $g['slug'] . '?preview=1')) ?>" target="_blank" rel="noopener">Preview</a>
                            <?php if ((int) $g['is_published'] === 0): ?>
                                <form method="post" action="<?= e(url('/admin/guides/' . $g['id'] . '/publish')) ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>"><button type="submit" class="btn btn-sm btn-primary">Publish</button></form>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('/admin/guides/' . $g['id'] . '/unpublish')) ?>" class="inline-form" data-confirm="Unpublish this guide? It disappears from the website and goes back to draft."><?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>"><button type="submit" class="btn btn-sm">Unpublish</button></form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/admin/guides/' . $g['id'] . '/delete')) ?>" class="inline-form" data-confirm="<?= e('Delete "' . $g['title'] . '" permanently? This cannot be undone.') ?>"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger">Delete</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= $pager->render() ?>
    <?php endif; ?>
</section>
