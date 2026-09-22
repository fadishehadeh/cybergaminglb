<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;
use App\Support\Settings;

/** SEO & AI-search settings (verification codes, default social image, business details, AI crawler switch) and a live SEO health panel. */
final class SeoController extends AdminController
{
    public const KEYS = [
        'seo_ai_crawlers_allowed', 'seo_google_verification', 'seo_bing_verification', 'seo_default_og_image',
        'biz_address', 'biz_hours', 'biz_price_range', 'biz_facebook_url', 'biz_tiktok_url', 'biz_youtube_url', 'instagram_url',
    ];
    public const PRICE_RANGES = ['$' => '$ (cheap)', '$$' => '$$ (moderate)', '$$$' => '$$$ (expensive)', '$$$$' => '$$$$ (very expensive)'];
    private const MAX_OG = 3145728;
    private const DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

    /** field => [label, allowed host suffixes] */
    private const SOCIAL = [
        'biz_facebook_url' => ['Facebook URL', ['facebook.com', 'fb.com']],
        'biz_tiktok_url'   => ['TikTok URL', ['tiktok.com']],
        'biz_youtube_url'  => ['YouTube URL', ['youtube.com', 'youtu.be']],
        'instagram_url'    => ['Instagram URL', ['instagram.com']],
    ];

    public function index(Request $request): void
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = (string) (Settings::all()[$key] ?? '');
        }
        if (!array_key_exists('seo_ai_crawlers_allowed', Settings::all())) {
            $values['seo_ai_crawlers_allowed'] = '1';
        }
        $this->view('seo/index', [
            'values' => $values,
            'images' => $this->existingImages(),
            'health' => $this->health(),
            'siteUrl' => rtrim(url(''), '/') . '/',
        ]);
    }

    public function update(Request $request): void
    {
        $errors = [];
        $in     = [];
        $notes  = [];

        $in['seo_ai_crawlers_allowed'] = $request->input('seo_ai_crawlers_allowed') === '1' ? '1' : '0';

        foreach (['seo_google_verification' => 'Google Search Console code', 'seo_bing_verification' => 'Bing Webmaster code'] as $key => $label) {
            $code = self::verificationCode((string) $request->input($key, ''));
            if ($code === null) {
                $errors[] = $label . ' must be 20 to 100 letters, numbers, dashes or underscores. You can paste the whole <meta> tag: the code is picked out of it.';
                $code = '';
            }
            $in[$key] = $code;
        }

        // Default social image: a new upload wins, else a picked existing image, else "none".
        $current = (string) setting('seo_default_og_image', '');
        $upload  = Uploads::check($request->file('og_image'), $errors, self::MAX_OG, 'The social image');
        $ogPath  = null;
        if ($upload !== null) {
            if ($upload['width'] < 200 || $upload['height'] < 200) {
                $errors[] = 'The social image is too small (at least 200 x 200 px, ideally 1200 x 630).';
            } elseif ($upload['width'] < 1200 || $upload['height'] < 630) {
                $notes[] = 'The social image is smaller than the recommended 1200 x 630 px, so it may look soft when shared.';
            }
        }
        $choice = $this->str($request, 'og_choice');
        if ($upload === null) {
            if ($choice === '' || $choice === 'none') {
                $ogPath = '';
            } elseif (preg_match('#^seo/[A-Za-z0-9_.-]+$#', $choice) && is_file(PUBLIC_PATH . '/uploads/' . $choice)) {
                $ogPath = $choice;
            } else {
                $errors[] = 'That social image no longer exists. Pick another or upload a new one.';
                $ogPath = $current !== '' ? $current : '';
            }
        }

        $address = $this->str($request, 'biz_address');
        if (mb_strlen($address) > 200) {
            $errors[] = 'Business address is too long (max 200 characters).';
        }
        $in['biz_address'] = $address;

        $hoursErr = null;
        $hours = self::normaliseHours($this->str($request, 'biz_hours'), $hoursErr);
        if ($hoursErr !== null) {
            $errors[] = $hoursErr;
        }
        $in['biz_hours'] = $hours;

        $range = $this->str($request, 'biz_price_range');
        if (!isset(self::PRICE_RANGES[$range])) {
            $errors[] = 'Price range must be one of $, $$, $$$ or $$$$.';
            $range = '$$';
        }
        $in['biz_price_range'] = $range;

        foreach (self::SOCIAL as $key => [$label, $hosts]) {
            $url = $this->str($request, $key);
            if ($url !== '' && !self::validSocialUrl($url, $hosts)) {
                $errors[] = $label . ' must be a full https:// link on ' . implode(' or ', $hosts) . ' (or leave it empty).';
            }
            $in[$key] = $url;
        }

        if ($errors) {
            $this->invalid('/admin/seo', $errors, $request);
        }

        if ($upload !== null) {
            $stored = Uploads::store($upload, 'seo');
            if ($stored === null) {
                $this->invalid('/admin/seo', ['The social image could not be saved. Check that public/uploads is writable.'], $request);
            }
            $ogPath = $stored;
        }
        $in['seo_default_og_image'] = (string) $ogPath;

        foreach ($in as $key => $value) {
            Settings::set($key, (string) $value);
        }
        $this->ok('SEO & AI settings saved.' . ($notes ? ' ' . implode(' ', $notes) : ''));
        $this->redirect('/admin/seo');
    }

    // ------------------------------------------------------------------------------------------------

    /** Accepts the bare code or a whole <meta ... content="CODE"> tag. '' = empty (allowed), null = invalid. */
    public static function verificationCode(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (str_contains($raw, '<') || preg_match('/content\s*=/i', $raw)) {
            if (!preg_match('/content\s*=\s*(["\'])(.*?)\1/is', $raw, $m)) {
                return null;
            }
            $raw = trim($m[2]);
        }
        return preg_match('/^[A-Za-z0-9_-]{20,100}$/', $raw) ? $raw : null;
    }

    /**
     * "Mo-Sa 10:00-20:00; Su closed" (schema.org openingHours style). Days Mo Tu We Th Fr Sa Su, single days, ranges (Mo-Fr) or lists (Mo,We,Fr),
     * then HH:MM-HH:MM (24h, closing after opening) or "closed". Blank is allowed. Returns the normalised text.
     */
    public static function normaliseHours(string $raw, ?string &$error): string
    {
        $error = null;
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (mb_strlen($raw) > 200) {
            $error = 'Opening hours are too long (max 200 characters).';
            return '';
        }
        $day   = '(?:Mo|Tu|We|Th|Fr|Sa|Su)';
        $entries = [];
        foreach (preg_split('/\s*[;\n]+\s*/', $raw) ?: [] as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            $bad = 'Opening hours: "' . $entry . '" is not valid. Use the form "Mo-Sa 10:00-20:00; Su closed" (days Mo Tu We Th Fr Sa Su, 24-hour times).';
            if (!preg_match('/^(' . $day . '(?:-' . $day . ')?(?:,' . $day . '(?:-' . $day . ')?)*)\s+(closed|(\d{2}:\d{2})-(\d{2}:\d{2}))$/i', $entry, $m)) {
                $error = $bad;
                return '';
            }
            foreach (explode(',', $m[1]) as $part) {
                if (str_contains($part, '-')) {
                    [$a, $b] = explode('-', $part);
                    if (array_search(ucfirst(strtolower($a)), self::DAYS, true) > array_search(ucfirst(strtolower($b)), self::DAYS, true)) {
                        $error = 'Opening hours: the day range "' . $part . '" runs backwards. Write it as two entries, e.g. "Sa 10:00-14:00; Mo 10:00-14:00".';
                        return '';
                    }
                }
            }
            $days = implode(',', array_map(static fn (string $d): string => ucfirst(strtolower($d)), explode(',', $m[1])));
            if (strtolower($m[2]) === 'closed') {
                $entries[] = $days . ' closed';
                continue;
            }
            $from = $m[3];
            $to   = $m[4];
            $valid = static fn (string $t, bool $end): bool => (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $t) || ($end && $t === '24:00');
            if (!$valid($from, false) || !$valid($to, true) || strcmp($to, $from) <= 0) {
                $error = 'Opening hours: "' . $entry . '" has invalid times (use 24-hour HH:MM, closing after opening).';
                return '';
            }
            $entries[] = $days . ' ' . $from . '-' . $to;
        }
        return implode('; ', $entries);
    }

    public static function validSocialUrl(string $url, array $hosts): bool
    {
        if (strlen($url) > 255 || !preg_match('#^https://[^\s<>"\']+$#i', $url)) {
            return false;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '' || parse_url($url, PHP_URL_USER) !== null) {
            return false;
        }
        foreach ($hosts as $h) {
            if ($host === $h || str_ends_with($host, '.' . $h)) {
                return true;
            }
        }
        return false;
    }

    /** @return string[] paths (relative to uploads/) of images already in uploads/seo/, newest first */
    private function existingImages(): array
    {
        $files = glob(PUBLIC_PATH . '/uploads/seo/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
        return array_map(static fn (string $f): string => 'seo/' . basename($f), array_slice($files, 0, 24));
    }

    /** Read-only numbers computed live from the database. */
    private function health(): array
    {
        $count = static fn (string $sql): int => (int) db()->fetchValue($sql);
        $catLack = db()->fetchAll(
            "SELECT name, (seo_title IS NULL OR TRIM(seo_title) = '') AS no_title, (seo_description IS NULL OR TRIM(seo_description) = '') AS no_desc,
                    (intro_text IS NULL OR TRIM(intro_text) = '') AS no_intro
               FROM categories WHERE is_active = 1 ORDER BY sort_order, name"
        );
        $wa = preg_replace('/\D+/', '', (string) setting('whatsapp_number', '961')) ?? '';
        return [
            'products_active'  => $count("SELECT COUNT(*) FROM products WHERE status = 'active'"),
            'no_description'   => $count("SELECT COUNT(*) FROM products WHERE status = 'active' AND (description IS NULL OR TRIM(description) = '')"),
            'short_description' => $count("SELECT COUNT(*) FROM products WHERE status = 'active' AND description IS NOT NULL AND TRIM(description) <> '' AND CHAR_LENGTH(TRIM(description)) < 60"),
            'no_image'         => $count("SELECT COUNT(*) FROM products WHERE status = 'active' AND (image IS NULL OR image = '')"),
            'categories_active' => count($catLack),
            'cat_no_title'     => array_sum(array_column($catLack, 'no_title')),
            'cat_no_desc'      => array_sum(array_column($catLack, 'no_desc')),
            'cat_no_intro'     => array_sum(array_column($catLack, 'no_intro')),
            'cat_names'        => array_column(array_filter($catLack, static fn (array $c): bool => $c['no_title'] || $c['no_desc'] || $c['no_intro']), 'name'),
            'articles_published' => $count('SELECT COUNT(*) FROM articles WHERE is_published = 1 AND (published_at IS NULL OR published_at <= NOW())'),
            'articles_scheduled' => $count('SELECT COUNT(*) FROM articles WHERE is_published = 1 AND published_at > NOW()'),
            'articles_draft'   => $count('SELECT COUNT(*) FROM articles WHERE is_published = 0'),
            'zones_active'     => $count('SELECT COUNT(*) FROM delivery_zones WHERE is_active = 1'),
            'wa_placeholder'   => $wa === '' || $wa === '961',
        ];
    }
}
