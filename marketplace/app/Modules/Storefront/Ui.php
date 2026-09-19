<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/** Small presentation helpers used by the storefront views (kept out of the core helpers on purpose). */
final class Ui
{
    /** Render app/Views/site/partials/{name}.php in an isolated scope and return the HTML. */
    public static function partial(string $name, array $vars = []): string
    {
        $file = base_path('app/Views/site/partials/' . $name . '.php');
        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            require $__file;
            return (string) ob_get_clean();
        })($file, $vars);
    }

    private const ICONS = [
        'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'whatsapp' => '<path d="M20 11.5a8 8 0 0 1-11.9 7L4 20l1.5-4A8 8 0 1 1 20 11.5z"/><path d="M9.2 8.6c.3 2.4 2.4 4.8 5.4 5.8l1.2-1.2-1.8-1-.8.6c-.8-.3-1.6-1.1-2-1.9l.6-.8-1-1.8z"/>',
        'instagram'=> '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8"/>',
        'check'    => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'cross'    => '<path d="m6 6 12 12M18 6 6 18"/>',
        'camera'   => '<path d="M4 8h3l1.5-2h7L17 8h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'truck'    => '<path d="M2 6h11v10H2z"/><path d="M13 9h4l3 3v4h-7"/><circle cx="6.5" cy="17.5" r="1.7"/><circle cx="16.5" cy="17.5" r="1.7"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.8-1-8-4.5-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4z"/>',
        'tag'      => '<path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
        'swap'     => '<path d="M4 8h14l-3-3M20 16H6l3 3"/>',
        'gamepad'  => '<rect x="2" y="7" width="20" height="11" rx="5"/><path d="M7 10v5M4.5 12.5h5"/><circle cx="16" cy="11.5" r=".8"/><circle cx="18" cy="14" r=".8"/>',
        'box'      => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
        'wallet'   => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14h2"/>',
        'pin'      => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'lock'     => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'gift'     => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7M7.5 8a2.5 2.5 0 1 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 1 1 0 5"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    ];

    /** Inline decorative SVG icon (stroke style, inherits currentColor). */
    public static function icon(string $name, int $size = 20): string
    {
        return '<svg class="icon" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . (self::ICONS[$name] ?? '') . '</svg>';
    }

    /**
     * Product cover: the uploaded image, or (digital goods without one) a neutral tile so no cover art is required.
     * $attrs is appended to the <img> tag (loading / fetchpriority ...).
     */
    public static function cover(array $p, string $alt, int $w, int $h, string $attrs = '', string $id = ''): string
    {
        $image = (string) ($p['image'] ?? '');
        if ($image === '' && (int) ($p['is_digital'] ?? 0) === 1) {
            $region = trim((string) ($p['digital_region'] ?? ''));
            return '<span class="cover-tile" role="img" aria-label="' . e($alt) . '"' . ($id !== '' ? ' id="' . e($id) . '"' : '') . '>'
                . self::icon('gift', 40)
                . '<span class="cover-tile-kind">' . e(Digital::kindLabel($p['digital_kind'] ?? null)) . '</span>'
                . ($region !== '' ? '<span class="cover-tile-region">' . e($region) . '</span>' : '')
                . '</span>';
        }
        return '<img' . ($id !== '' ? ' id="' . e($id) . '"' : '') . ' src="' . e(media($image)) . '" alt="' . e($alt) . '" width="' . $w . '" height="' . $h . '" ' . $attrs . '>';
    }

    /** One-line meaning of each used grade (shown on the product page). */
    public const GRADE_NOTES = [
        'Like New' => 'Like New: looks unplayed.',
        'Good'     => 'Good: light wear, plays perfectly.',
        'Fair'     => 'Fair: visible wear, but it plays.',
    ];

    /** Physical item that is not sealed-new (Like New / Good / Fair). Digital goods are never "used". */
    public static function isUsed(array $p): bool
    {
        return (int) ($p['is_digital'] ?? 0) !== 1 && (string) ($p['item_condition'] ?? '') !== 'New';
    }

    /** Short badge text: "New" or "Used · Good"; '' for digital goods (they carry their own badge). */
    public static function conditionBadge(array $p): string
    {
        if ((int) ($p['is_digital'] ?? 0) === 1) {
            return '';
        }
        return self::isUsed($p) ? 'Used · ' . (string) $p['item_condition'] : 'New';
    }

    /**
     * What is included with a used copy: [key, label, included] for every item that is stated (0 or 1).
     * Empty for new items, and for items where nothing is stated (NULL = not stated, so we say nothing).
     * @return array<int,array{0:string,1:string,2:bool}>
     */
    public static function includes(array $p): array
    {
        if (!self::isUsed($p)) {
            return [];
        }
        $out = [];
        foreach ([['box', 'Original box / case', 'includes_box'], ['cover', 'Cover art', 'includes_cover_art'], ['manual', 'Manual / inserts', 'includes_manual']] as [$key, $label, $col]) {
            if (isset($p[$col]) && $p[$col] !== null && $p[$col] !== '') {
                $out[] = [$key, $label, (int) $p[$col] === 1];
            }
        }
        return $out;
    }

    /** True when a used copy is stated to come with box, cover art and manual. */
    public static function isComplete(array $p): bool
    {
        if (!self::isUsed($p)) {
            return false;
        }
        foreach (['includes_box', 'includes_cover_art', 'includes_manual'] as $col) {
            if (!isset($p[$col]) || (int) $p[$col] !== 1) {
                return false;
            }
        }
        return true;
    }

    /** Photo kinds shown to buyers. */
    public const PHOTO_LABELS = ['disc' => 'Disc', 'box_outside' => 'Box outside', 'box_inside' => 'Box inside', 'extra' => 'More'];

    public static function genres(string $csv): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $csv)), static fn (string $g): bool => $g !== ''));
    }

    public static function shortPlatform(?string $slug, ?string $name): string
    {
        return match ($slug) {
            'ps4' => 'PS4',
            'ps5' => 'PS5',
            default => (string) $name,
        };
    }

    /** Visible breadcrumb trail. Each crumb: [label, path|null]. */
    public static function breadcrumbs(array $crumbs): string
    {
        $html = '<nav class="breadcrumb" aria-label="Breadcrumb"><ol>';
        $last = count($crumbs) - 1;
        foreach ($crumbs as $i => [$label, $path]) {
            if ($i === $last || $path === null) {
                $html .= '<li aria-current="page">' . e($label) . '</li>';
            } else {
                $html .= '<li><a href="' . e(url($path)) . '">' . e($label) . '</a></li>';
            }
        }
        return $html . '</ol></nav>';
    }

    /** Display form of the store WhatsApp number, or '' when the setting is still a placeholder. */
    public static function waNumber(): string
    {
        $digits = preg_replace('/\D+/', '', (string) setting('whatsapp_number', ''));
        return strlen((string) $digits) >= 8 ? '+' . $digits : '';
    }

    /** Numbered pagination links (window of pages around the current one). */
    public static function pagination(string $basePath, array $query, int $page, int $pages): string
    {
        if ($pages <= 1) {
            return '';
        }
        unset($query['page']);
        $link = static function (int $p) use ($basePath, $query): string {
            $q = $query + ($p > 1 ? ['page' => $p] : []);
            return url($basePath) . ($q ? '?' . http_build_query($q) : '');
        };
        $show = array_unique(array_filter(
            array_merge([1, $pages], range(max(1, $page - 2), min($pages, $page + 2))),
            static fn (int $p): bool => $p >= 1 && $p <= $pages
        ));
        sort($show);

        $html = '<nav class="pagination" aria-label="Pagination"><ul>';
        $html .= $page > 1
            ? '<li><a class="page-link" rel="prev" href="' . e($link($page - 1)) . '">&larr; Prev</a></li>'
            : '<li><span class="page-link is-disabled">&larr; Prev</span></li>';
        $prev = 0;
        foreach ($show as $p) {
            if ($p - $prev > 1) {
                $html .= '<li><span class="page-gap" aria-hidden="true">&hellip;</span></li>';
            }
            $html .= $p === $page
                ? '<li><span class="page-link is-current" aria-current="page">' . $p . '</span></li>'
                : '<li><a class="page-link" href="' . e($link($p)) . '" aria-label="Page ' . $p . '">' . $p . '</a></li>';
            $prev = $p;
        }
        $html .= $page < $pages
            ? '<li><a class="page-link" rel="next" href="' . e($link($page + 1)) . '">Next &rarr;</a></li>'
            : '<li><span class="page-link is-disabled">Next &rarr;</span></li>';
        return $html . '</ul></nav>';
    }
}
