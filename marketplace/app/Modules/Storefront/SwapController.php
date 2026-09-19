<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * Swap board. Listings are private until an admin publishes them; the public board only ever shows the platform,
 * what is offered/wanted, an anonymous "Swapper #1234" handle (last 4 characters of the swap code) and the delivery
 * ZONE. Names, phone numbers and free-text areas are never selected or rendered here.
 */
final class SwapController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): void
    {
        $platforms = Catalog::platforms();
        $slug = $request->query('platform');
        $slug = is_string($slug) ? $slug : '';
        $platform = $slug !== '' ? Catalog::platformBySlug($slug) : null;
        $page = max(1, (int) $request->query('page', 1));

        $where = "s.is_public = 1 AND s.status = 'listed'";
        $params = [];
        if ($platform) {
            $where .= ' AND s.platform_id = :plat';
            $params['plat'] = (int) $platform['id'];
        }
        $total = (int) db()->fetchValue("SELECT COUNT(*) FROM swap_requests s WHERE $where", $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        if ($page > $pages && $page > 1) {
            Response::abort(404);
        }
        $listings = [];
        if ($page <= $pages) {
            // Deliberately no name and no phone column: only the code, the games, the zone and the date.
            $listings = db()->fetchAll(
                "SELECT s.code, s.offering, s.wanting, s.area, s.created_at,
                        pl.name AS platform_name, pl.slug AS platform_slug
                   FROM swap_requests s LEFT JOIN platforms pl ON pl.id = s.platform_id
                  WHERE $where ORDER BY s.created_at DESC, s.id DESC
                  LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
                $params
            );
            // Legacy rows may hold a free-text area: only ever show a real delivery zone.
            $zones = Shipping::names();
            foreach ($listings as &$l) {
                $l['swapper'] = self::swapperId((string) $l['code']);
                $l['zone'] = in_array((string) $l['area'], $zones, true) ? (string) $l['area'] : '';
                unset($l['area']);
            }
            unset($l);
        }

        $query = $platform ? ['platform' => $platform['slug']] : [];
        $href = static fn (int $p): string => url('/swap') . ($query || $p > 1 ? '?' . http_build_query($query + ($p > 1 ? ['page' => $p] : [])) : '');

        $crumbs = [['Home', '/'], ['Swap games', null]];
        $suffix = ($platform ? ' (' . Ui::shortPlatform($platform['slug'], $platform['name']) . ')' : '') . ($page > 1 ? ' - page ' . $page : '');
        $fee = money(setting('swap_fee', 3));

        $this->render('site/swap/index', [
            'platforms' => $platforms,
            'platform'  => $platform,
            'listings'  => $listings,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'query'     => $query,
            'areas'     => Shipping::names(),
            'fee'       => $fee,
            'nav'       => 'swap',
            'crumbs'    => $crumbs,
            'meta'      => [
                'title'       => 'Swap Games with Other Players in Lebanon' . $suffix . ' | CyberGaming',
                'description' => Seo::clip('Swap games with other gamers in Lebanon without sharing your name or phone number. Browse the swap board or list yours. We inspect both discs, flat ' . $fee . ' fee per side.'),
                'canonical'   => $href($page),
                'prev'        => $page > 1 ? $href($page - 1) : null,
                'next'        => $page < $pages ? $href($page + 1) : null,
                'jsonld'      => [Seo::breadcrumbs($crumbs)],
            ],
        ]);
    }

    public function submit(Request $request): void
    {
        if ((string) $request->input('website', '') !== '') {
            $this->redirect('/');
        }

        $name = Quoter::clean($request->input('name'), 120);
        $phone = Quoter::clean($request->input('phone'), 40);
        $area = Quoter::clean($request->input('area'), 60);
        $slug = Quoter::clean($request->input('platform'), 40);
        $offering = Quoter::clean($request->input('offering'), 400);
        $wanting = Quoter::clean($request->input('wanting'), 400);
        $notes = Quoter::cleanNote($request->input('notes'));
        $input = ['name' => $name, 'phone' => $phone, 'area' => $area, 'platform' => $slug, 'offering' => $offering, 'wanting' => $wanting, 'notes' => $notes];

        // the area is a delivery zone picked from the list (never free text), validated against the admin-managed zones
        $errors = Quoter::validateContact($name, $phone, $area, Shipping::names());
        $platform = Catalog::platformBySlug($slug);
        if ($platform === null) {
            $errors[] = 'Please choose the platform.';
        }
        foreach (['offering' => ['The game you have', $offering], 'wanting' => ['The game you want', $wanting]] as [$label, $value]) {
            if (mb_strlen($value) < 2) {
                $errors[] = "$label is required.";
            } elseif (mb_strlen($value) > 255) {
                $errors[] = "$label is too long (max 255 characters).";
            }
        }
        if (!$errors) {
            foreach ([$offering, $wanting, $notes] as $text) {
                if (self::hasContactDetails($text)) {
                    $errors[] = "Please don't put contact details in the listing (phone numbers, links, emails or @handles). We connect you privately.";
                    break;
                }
            }
        }
        if (!$errors && Quoter::throttled('swap', $request->ip(), $phone, 'swap_requests')) {
            $errors[] = 'You have sent several requests in the last hour. Please wait a little, or message us on WhatsApp if it is urgent.';
        }
        if ($errors) {
            $this->back('/swap#list-swap', implode(' ', array_unique($errors)), $input);
        }

        $code = Quoter::uniqueCode('SW-', 'swap_requests');
        db()->insert(
            "INSERT INTO swap_requests (code, name, phone, area, platform_id, offering, wanting, notes, is_public, status)
             VALUES (:code, :name, :phone, :area, :plat, :offering, :wanting, :notes, 0, 'new')",
            [
                'code' => $code, 'name' => $name, 'phone' => $phone, 'area' => $area, 'plat' => (int) $platform['id'],
                'offering' => $offering, 'wanting' => $wanting, 'notes' => $notes !== '' ? $notes : null,
            ]
        );
        Quoter::recordHit('swap', $request->ip());
        $this->redirect('/swap/thanks/' . $code);
    }

    public function thanks(Request $request, string $code): void
    {
        header('Cache-Control: no-store');
        $code = strtoupper($code);
        if (!preg_match('/^SW-[A-Z0-9]{6}$/', $code)) {
            Response::abort(404);
        }
        $swap = db()->fetch(
            "SELECT s.code, s.offering, s.wanting, pl.name AS platform_name
               FROM swap_requests s LEFT JOIN platforms pl ON pl.id = s.platform_id WHERE s.code = :c",
            ['c' => $code]
        ) ?? Response::abort(404);

        $msg = "Hi CyberGaming, I just listed a swap {$swap['code']}"
            . ($swap['platform_name'] ? " ({$swap['platform_name']})" : '') . ".\nI have: {$swap['offering']}\nI want: {$swap['wanting']}";

        $this->render('site/swap/thanks', [
            'swap'   => $swap,
            'fee'    => money(setting('swap_fee', 3)),
            'waLink' => wa_link($msg),
            'nav'    => 'swap',
            'meta'   => ['title' => 'Swap request ' . $swap['code'] . ' | CyberGaming', 'noindex' => true, 'description' => 'Your swap request has been received.'],
        ]);
    }

    /** Anonymous public handle for a swap: the last 4 characters of its code, e.g. SW-7K2M9Q -> "#2M9Q". */
    public static function swapperId(string $code): string
    {
        return '#' . strtoupper(substr($code, -4));
    }

    /** Listings become public, so refuse anything that would let people bypass the hub. */
    public static function hasContactDetails(string $text): bool
    {
        if ($text === '') {
            return false;
        }
        // a phone-like number: a run of digits / separators holding 7 or more digits (Arabic-Indic digits included)
        if (preg_match_all('/\p{Nd}[\p{Nd}\s().\-]*\p{Nd}/u', $text, $runs)) {
            foreach ($runs[0] as $run) {
                if (preg_match_all('/\p{Nd}/u', $run) >= 7) {
                    return true;
                }
            }
        }
        if (str_contains($text, '@')) {
            return true; // emails and @handles
        }
        if (preg_match('#https?://|www\.|\b[a-z0-9-]+\.(?:com|net|org|lb|io|me|co|app|ly|gl|link|info|biz|tv|gg)\b|\bwa\.me\b#i', $text)) {
            return true;
        }
        return (bool) preg_match('/\b(?:whats\s?app|whatsap|instagram|telegram|snapchat|viber|messenger)\b/i', $text);
    }
}
