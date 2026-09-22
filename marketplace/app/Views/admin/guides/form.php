<?php
/** @var ?array $guide @var array $tokens token => [description, current value] @var array $tags */
use App\Modules\Admin\Forms;
use App\Modules\Admin\GuideController;

$isEdit    = $guide !== null;
$g         = $guide ?? [];
$pageTitle = $isEdit ? 'Edit guide' : 'New guide';
$nav       = 'guides';
$f = static fn (string $key, mixed $default = '') => Forms::val($key, $g[$key] ?? $default);

$publishedAt = $g['published_at'] ?? null;
$whenValue   = (string) Forms::val('published_at', $publishedAt ? date('Y-m-d\TH:i', strtotime((string) $publishedAt)) : '');
$isPublished = Forms::checked('is_published', !empty($g['is_published']));
$scheduled   = $isEdit && !empty($g['is_published']) && $publishedAt && strtotime((string) $publishedAt) > time();
$live        = $isEdit && !empty($g['is_published']) && !$scheduled;
$statusKey   = $live ? 'published' : ($scheduled ? 'scheduled' : 'draft');
$base        = rtrim(url(''), '/') . '/guides/';
$action      = $isEdit ? '/admin/guides/' . $g['id'] . '/edit' : '/admin/guides/create';
$here        = $isEdit ? '/admin/guides/' . $g['id'] . '/edit' : '/admin/guides';
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <?php if ($isEdit): ?><p class="muted"><?= e($g['title']) ?> &middot; <span class="pill pill-<?= e($statusKey) ?>"><?= e(GuideController::STATUSES[$statusKey]) ?></span></p><?php endif; ?>
    </div>
    <div class="actions">
        <?php if ($isEdit): ?><a class="btn" href="<?= e(url('/guides/' . $g['slug'] . '?preview=1')) ?>" target="_blank" rel="noopener">Preview &nearr;</a><?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('/admin/guides')) ?>">&larr; All guides</a>
    </div>
</div>

<form method="post" action="<?= e(url($action)) ?>" class="guide-form" data-guide-form data-slug-base="<?= e($base) ?>" data-slug-auto="<?= $isEdit ? '0' : '1' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="_form" value="1">

    <div class="cols-form">
        <div class="stack">
            <section class="card">
                <div class="card-head"><h2>Content</h2></div>
                <div class="card-body form-grid one">
                    <div class="field">
                        <label for="title">Title * <small class="muted counter" data-count-for="title" data-max="200"></small></label>
                        <input type="text" id="title" name="title" value="<?= e($f('title')) ?>" maxlength="200" required>
                    </div>
                    <div class="field">
                        <label for="slug">Web address (slug)</label>
                        <div class="slug-row"><span class="slug-base"><?= e($base) ?></span><input type="text" id="slug" name="slug" value="<?= e($f('slug')) ?>" maxlength="160" placeholder="made from the title" data-slug-input></div>
                        <small class="hint">Lowercase letters, numbers and dashes. It is made from the title; change it only before you share the link. It must be unique.</small>
                    </div>
                    <div class="field">
                        <label for="excerpt">Quick answer (excerpt) <small class="muted counter" data-count-for="excerpt" data-max="400"></small></label>
                        <textarea id="excerpt" name="excerpt" rows="3" maxlength="400"><?= e($f('excerpt')) ?></textarea>
                        <small class="hint">One or two sentences that answer the question directly. It is shown at the top of the guide and used by Google and AI assistants as the summary.</small>
                    </div>
                    <div class="field">
                        <label for="body">Body *</label>
                        <textarea id="body" name="body" rows="22" class="body-area" required data-body-field spellcheck="true"><?= e($f('body')) ?></textarea>
                        <small class="hint">HTML, or plain text (a blank line starts a new paragraph). Unsafe code is removed when you save. Click a token below to insert it where the cursor is.</small>
                    </div>
                </div>
            </section>

            <section class="card" id="tokens">
                <div class="card-head"><h2>Tokens</h2><small class="muted">Numbers that never go out of date</small></div>
                <div class="card-body">
                    <p class="hint">Type or click a token and the guide shows the current value from your Settings, so a price change updates every guide. Values below are what each token says <strong>right now</strong>.</p>
                    <div class="table-wrap">
                        <table class="data token-table">
                            <thead><tr><th>Token</th><th>What it is</th><th>Now</th></tr></thead>
                            <tbody>
                            <?php foreach ($tokens as $token => [$desc, $value]): ?>
                                <tr>
                                    <td><button type="button" class="tok" data-insert-token="{{<?= e($token) ?>}}" title="Insert into the body">{{<?= e($token) ?>}}</button></td>
                                    <td><?= e($desc) ?></td>
                                    <td><strong><?= e($value) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="card" id="cheatsheet">
                <div class="card-head"><h2>Allowed HTML</h2></div>
                <div class="card-body">
                    <p class="hint">Only these tags are kept. Everything else (scripts, styles, iframes, forms, event handlers, inline styles) is stripped when you save, and unknown tags lose their tag but keep their text.</p>
                    <ul class="cheat">
                        <li><code>&lt;p&gt;</code> paragraph, <code>&lt;br&gt;</code> line break, <code>&lt;hr&gt;</code> divider</li>
                        <li><code>&lt;h2&gt;</code> <code>&lt;h3&gt;</code> <code>&lt;h4&gt;</code> headings (the page title is the H1, so start at h2)</li>
                        <li><code>&lt;ul&gt;&lt;li&gt;</code> bullets, <code>&lt;ol&gt;&lt;li&gt;</code> numbered list</li>
                        <li><code>&lt;strong&gt;</code> <code>&lt;em&gt;</code> <code>&lt;u&gt;</code> <code>&lt;small&gt;</code> <code>&lt;sup&gt;</code> <code>&lt;sub&gt;</code> <code>&lt;code&gt;</code> <code>&lt;pre&gt;</code> <code>&lt;blockquote&gt;</code></li>
                        <li><code>&lt;a href="https://..." title="..."&gt;</code> links: https://, a /page on this site, mailto: or tel:. External links get rel="noopener nofollow".</li>
                        <li><code>&lt;img src="/..." alt="describe it" width="" height=""&gt;</code> images (always add alt text)</li>
                        <li><code>&lt;table&gt;&lt;thead&gt;&lt;tbody&gt;&lt;tr&gt;&lt;th&gt;&lt;td&gt;</code> tables (th/td may use colspan and rowspan)</li>
                    </ul>
                </div>
            </section>
        </div>

        <div class="stack">
            <section class="card">
                <div class="card-head"><h2>Publishing</h2></div>
                <div class="card-body form-grid one">
                    <div class="field">
                        <label class="check check-lg"><input type="checkbox" name="is_published" value="1" <?= $isPublished ? 'checked' : '' ?> data-publish-toggle> Published</label>
                        <small class="hint">Off = draft: only you can see it (Preview). On = live on the website at the date below.</small>
                    </div>
                    <div class="field">
                        <label for="published_at">Publish date and time</label>
                        <input type="datetime-local" id="published_at" name="published_at" value="<?= e($whenValue) ?>">
                        <small class="hint">Leave empty to publish right now. A date in the future schedules the guide: it goes live by itself.</small>
                    </div>
                    <div class="field">
                        <label for="tag">Topic</label>
                        <select id="tag" name="tag">
                            <option value="">No topic</option>
                            <?= Forms::options($tags, $f('tag')) ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?= e($f('author', GuideController::DEFAULT_AUTHOR)) ?>" maxlength="80">
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head"><h2>Search result</h2></div>
                <div class="card-body form-grid one">
                    <div class="field">
                        <label for="meta_title">SEO title <small class="muted counter" data-count-for="meta_title" data-max="160"></small></label>
                        <input type="text" id="meta_title" name="meta_title" value="<?= e($f('meta_title')) ?>" maxlength="160" placeholder="Defaults to the title">
                        <small class="hint">Google shows about the first 60 characters.</small>
                    </div>
                    <div class="field">
                        <label for="meta_description">SEO description <small class="muted counter" data-count-for="meta_description" data-max="320"></small></label>
                        <textarea id="meta_description" name="meta_description" rows="3" maxlength="320" placeholder="Defaults to the quick answer"><?= e($f('meta_description')) ?></textarea>
                        <small class="hint">About 140-160 characters is what fits.</small>
                    </div>
                    <div class="serp" data-serp aria-label="Preview of the Google result">
                        <div class="serp-url" data-serp-url></div>
                        <div class="serp-title" data-serp-title></div>
                        <div class="serp-desc" data-serp-desc></div>
                    </div>
                    <small class="hint">A rough preview of how the guide can look in Google.</small>
                </div>
            </section>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary btn-lg"><?= $isEdit ? 'Save changes' : 'Create guide' ?></button>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/admin/guides')) ?>">Cancel</a>
    </div>
</form>

<?php if ($isEdit): ?>
    <section class="card danger-zone">
        <div class="card-head"><h2>Quick actions</h2></div>
        <div class="card-body actions">
            <a class="btn" href="<?= e(url('/guides/' . $g['slug'] . '?preview=1')) ?>" target="_blank" rel="noopener">Preview &nearr;</a>
            <?php if (empty($g['is_published'])): ?>
                <form method="post" action="<?= e(url('/admin/guides/' . $g['id'] . '/publish')) ?>" class="inline-form">
                    <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>">
                    <button type="submit" class="btn btn-primary">Publish now</button>
                </form>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/guides/' . $g['id'] . '/unpublish')) ?>" class="inline-form" data-confirm="Unpublish this guide? It disappears from the website and goes back to draft.">
                    <?= csrf_field() ?><input type="hidden" name="return" value="<?= e($here) ?>">
                    <button type="submit" class="btn">Unpublish</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/admin/guides/' . $g['id'] . '/delete')) ?>" class="inline-form" data-confirm="<?= e('Delete "' . $g['title'] . '" permanently? This cannot be undone.') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Delete guide</button>
            </form>
        </div>
    </section>
<?php endif; ?>
