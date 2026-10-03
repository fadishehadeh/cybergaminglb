<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Support\SafeHtml;
use App\Support\Tokens;

/**
 * /llms.txt (an index of the site in the llms.txt markdown convention) and /llms-full.txt (the same plus full answers,
 * inventory summary, delivery table and guide texts). Built from the database, settings and tokens, so it never goes stale.
 */
final class LlmsController extends Controller
{
    public function short(Request $request): void
    {
        $this->send($this->build(false));
    }

    public function full(Request $request): void
    {
        $this->send($this->build(true));
    }

    private function send(string $body): void
    {
        $last = Cacheable::newest([
            SeoCatalog::lastUpdated(),
            db()->fetchValue('SELECT MAX(a.updated_at) FROM articles a WHERE ' . Guides::published()),
            (int) filemtime(__FILE__),
        ]);
        Cacheable::send($body, 'text/plain; charset=utf-8', $last);
    }

    private function build(bool $full): string
    {
        $name = Seo::storeName();
        $fee = money(SeoCatalog::cheapestFee());
        $inv = SeoCatalog::inventory();
        $items = array_sum(array_column($inv['categories'], 'n'));
        $L = [];
        $link = static fn (string $label, string $path, string $note = ''): string => '- [' . $label . '](' . url($path) . ')' . ($note !== '' ? ': ' . $note : '');
        $range = static fn (array $r): string => $r['lo'] === $r['hi'] ? money($r['lo']) : money($r['lo']) . ' to ' . money($r['hi']);

        $L[] = '# ' . $name;
        $L[] = '';
        $L[] = '> ' . $name . ' (' . url('/') . ') is an online shop in Lebanon selling used and new video games and gaming gear: mainly PlayStation 4 games, consoles, controllers and PC gaming peripherals (keyboards, mice, mousepads). '
            . 'Prices are in US dollars, every item is inspected before delivery, and we deliver across Lebanon from ' . $fee . '.';
        $L[] = '';
        $L[] = 'Key facts: currency USD; language English; country Lebanon; payment by cash on delivery in local areas, or by OMT or Whish in remote areas; '
            . ($items > 0 ? $items . ' items in stock at the time of writing; ' : '') . 'checkout is guest-only, no account or registration needed; every order is confirmed by a person on WhatsApp.';
        $L[] = '';

        // ---- Shop
        $L[] = '## Shop';
        $L[] = $link('All products', '/shop', 'everything in stock, filterable by genre, edition and condition');
        foreach ($inv['categories'] as $c) {
            $L[] = $link($c['name'], '/shop/' . $c['slug'], $c['n'] . ' in stock, ' . $range($c) . ($c['kind'] === 'hardware' ? ' (gaming hardware)' : ''));
        }
        foreach ($inv['platforms'] as $p) {
            $L[] = $link($p['name'], '/platform/' . $p['slug'], $p['n'] . ' items in stock, ' . $range($p));
        }
        $L[] = '';

        $active = Collections::active();
        if ($active) {
            $L[] = '## Collections';
            $L[] = $link('All collections', '/collections');
            foreach ($active as $slug => $d) {
                $L[] = $link($d['h1'], '/collections/' . $slug, $d['n'] . ' items');
            }
            $L[] = '';
        }

        // ---- How it works
        $L[] = '## How it works';
        $L[] = $link('How it works', '/how-it-works', 'browse, add to cart, guest checkout, WhatsApp confirmation, delivery');
        $L[] = $link('Delivery and payment', '/delivery-and-payment', 'fees by area, cash on delivery, OMT, Whish');
        $L[] = '';

        // ---- Delivery areas
        $zones = SeoCatalog::zones();
        if ($zones) {
            $L[] = '## Delivery areas';
            foreach ($zones as $z) {
                $L[] = $link('Delivery to ' . $z['short'], '/delivery-to/' . $z['slug'], money($z['fee']) . ', ' . ($z['mode'] === 'local' ? 'local: own courier, cash on delivery' : 'remote: third-party courier, prepay'));
            }
            $L[] = '';
        }

        // ---- Guides
        $guides = Guides::paginate('', 1, 1000)['items'];
        if ($guides) {
            $L[] = '## Guides';
            $L[] = $link('All guides', '/guides');
            foreach ($guides as $g) {
                $L[] = $link(Guides::text($g['title']), '/guides/' . $g['slug'], self::oneLine(Guides::text($g['excerpt'])));
            }
            $L[] = '';
        }

        // ---- Policies
        $L[] = '## Policies';
        $L[] = $link('Delivery and payment policy', '/delivery-and-payment', 'delivery fees, local and remote rules, payment methods');
        $L[] = $link('About', '/about');
        $L[] = '';

        // ---- Optional: feeds
        $L[] = '## Optional';
        $L[] = $link('Product feed (JSON)', '/feeds/products.json', 'in-stock products with price, condition, availability, platform, brand and specs');
        $L[] = $link('Product feed (Google Merchant Center RSS)', '/feeds/products.xml');
        $L[] = $link('Sitemap', '/sitemap.xml');
        $L[] = $link('robots.txt', '/robots.txt');
        if (!$full) {
            $L[] = $link('Full text version of this file', '/llms-full.txt', 'longer answers, delivery table, inventory summary and guide texts');
        }
        $L[] = '';

        // ---- Contact
        $L[] = '## Contact';
        $L[] = $link('Contact page', '/contact');
        $phone = Seo::realPhone();
        if ($phone !== null) {
            $L[] = '- WhatsApp: ' . $phone;
        }
        $ig = trim((string) setting('instagram_url', ''));
        if ($ig !== '') {
            $L[] = '- Instagram: ' . $ig;
        }
        $email = trim((string) setting('contact_email', ''));
        if ($email !== '') {
            $L[] = '- Email: ' . $email;
        }
        $L[] = '';

        if (!$full) {
            return implode("\n", $L);
        }

        // =========================================================== llms-full.txt extras
        $L[] = '---';
        $L[] = '';
        $L[] = '# Answers to common questions';
        $L[] = '';
        foreach ($this->answers() as [$q, $a]) {
            $L[] = '## ' . $q;
            $L[] = '';
            $L[] = $a;
            $L[] = '';
        }

        $L[] = '# Delivery zones and fees';
        $L[] = '';
        $L[] = '| Area | Fee | Type | Page |';
        $L[] = '| --- | --- | --- | --- |';
        foreach ($zones as $z) {
            $L[] = '| ' . $z['name'] . ' | ' . money($z['fee']) . ' | ' . ($z['mode'] === 'local' ? 'Local (own courier, cash on delivery)' : 'Remote (third-party courier, prepay)') . ' | ' . url('/delivery-to/' . $z['slug']) . ' |';
        }
        $free = Shipping::freeOver();
        if ($free > 0) {
            $L[] = '';
            $L[] = 'Delivery is free on orders of ' . money($free) . ' or more.';
        }
        $L[] = '';

        $L[] = '# Inventory summary';
        $L[] = '';
        $L[] = 'Counts are of items in stock right now.';
        $L[] = '';
        $L[] = '| Category | Items in stock | Price range (USD) | Page |';
        $L[] = '| --- | --- | --- | --- |';
        foreach ($inv['categories'] as $c) {
            $L[] = '| ' . $c['name'] . ' | ' . $c['n'] . ' | ' . $range($c) . ' | ' . url('/shop/' . $c['slug']) . ' |';
        }
        $L[] = '';
        $L[] = '| Platform | Items in stock | Price range (USD) | Page |';
        $L[] = '| --- | --- | --- | --- |';
        foreach ($inv['platforms'] as $p) {
            $L[] = '| ' . $p['name'] . ' | ' . $p['n'] . ' | ' . $range($p) . ' | ' . url('/platform/' . $p['slug']) . ' |';
        }
        $L[] = '';

        if ($guides) {
            $L[] = '# Guides (full text)';
            $L[] = '';
            foreach ($guides as $g) {
                $L[] = '## ' . Guides::text($g['title']);
                $L[] = 'Source: ' . url('/guides/' . $g['slug']);
                $L[] = '';
                $L[] = self::htmlToText(Guides::renderBody((string) $g['body'])['html']);
                $L[] = '';
            }
        }
        return implode("\n", $L);
    }

    /** @return array<int,array{0:string,1:string}> long-form answers, numbers from settings */
    private function answers(): array
    {
        $policies = FeedController::policies();
        $local = Rules::nameList(Rules::names('local'));

        return [
            ['How do I buy from ' . Seo::storeName() . '?',
                'Browse the shop, add items to your cart and check out with your name, phone number and delivery area. No account or registration is needed, and no card details are ever taken on the site. '
                . 'A person then confirms your order on WhatsApp, and we deliver or you collect it at our pickup point. Every item is inspected before delivery.'],
            ['What are the delivery zones and fees?', $policies['delivery'] . ' Each area has its own page, linked in the Delivery areas section of this file.'],
            ['How can I pay?', $policies['payment']],
            ['How does inspection and condition grading work?',
                'Every item is inspected by our team before it is listed and again before delivery. Used games are graded: Like New (opened but looks untouched: flawless disc, crisp case and artwork, all inserts), Good (normal signs of play: light disc marks that do not affect play, case may show light wear) and Fair (heavier wear: visible scratches, scuffed or cracked case, a missing insert, but the disc still plays). New items are sealed. '
                . 'Local couriers can let you check the item at the door. Remote orders are photographed and sealed at our hub.'],
            ['Does ' . Seo::storeName() . ' buy, trade or swap games?', 'No. ' . Seo::storeName() . ' is a straightforward shop: we sell our own stock of used and new games and gaming gear. We do not buy games from the public, take trade-ins, or run a swap board.'],
            ['Where does ' . Seo::storeName() . ' operate?', 'The store operates online across Lebanon. Local delivery areas are ' . $local . '; the rest of Lebanon is served by a third-party courier. The exact pickup point is shared on WhatsApp when an order is confirmed.'],
        ];
    }

    private static function pct(string $key, int $default): string
    {
        return rtrim(rtrim(number_format((float) setting($key, $default), 1), '0'), '.') . '%';
    }

    private static function oneLine(string $s): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    /** Cleaned guide HTML to plain markdown-ish text (headings, lists, tables flattened). */
    private static function htmlToText(string $html): string
    {
        $html = SafeHtml::clean($html);
        $html = (string) preg_replace('#<h2[^>]*>(.*?)</h2>#is', "\n\n### $1\n", $html);
        $html = (string) preg_replace('#<h3[^>]*>(.*?)</h3>#is', "\n\n#### $1\n", $html);
        $html = (string) preg_replace('#<li[^>]*>#i', "\n- ", $html);
        $html = (string) preg_replace('#</(p|ul|ol|table|blockquote)>#i', "\n\n", $html);
        $html = (string) preg_replace('#</(tr)>#i', "\n", $html);
        $html = (string) preg_replace('#</(td|th)>#i', ' | ', $html);
        $html = (string) preg_replace('#<br\s*/?>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace("/[ \t]+\n/", "\n", $text);
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
