<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Support\Delivery;

/** Builders for schema.org JSON-LD, meta text, answer-first "quick answer" blocks and FAQ markup. */
final class Seo
{
    public const BRAND = 'CyberGaming';
    public const TITLE_MAX = 65;

    // ------------------------------------------------------------------ identity

    public static function storeName(): string
    {
        return (string) setting('site_name', 'CyberGaming Lebanon');
    }

    public static function storeId(): string
    {
        return url('/') . '#store';
    }

    /** Logo URL without the cache-busting query string (structured data and social previews should not carry it). */
    public static function logoUrl(): string
    {
        return url("assets/img/logo.jpg");
    }

    public static function websiteId(): string
    {
        return url('/') . '#website';
    }

    /** Default social/preview image: the seo_default_og_image setting (absolute URL, or a file under uploads/), else the logo. */
    public static function defaultImage(): string
    {
        $v = trim((string) setting('seo_default_og_image', ''));
        if ($v === '') {
            return self::logoUrl();
        }
        if (preg_match('#^https?://#i', $v)) {
            return $v;
        }
        $rel = ltrim($v, '/');
        if (is_file(PUBLIC_PATH . '/uploads/' . $rel)) {
            return media($rel);
        }
        if (is_file(PUBLIC_PATH . '/' . $rel)) {
            return url($rel);
        }
        return self::logoUrl();
    }

    /** The WhatsApp number as +digits, or null while it is still the "961" placeholder. */
    public static function realPhone(): ?string
    {
        $digits = (string) preg_replace('/\D+/', '', (string) setting('whatsapp_number', ''));
        return strlen($digits) > 6 ? '+' . $digits : null;
    }

    /** The store as a schema.org OnlineStore (a LocalBusiness subtype): only facts that are set are output. */
    public static function organization(): array
    {
        $name = self::storeName();
        $zones = SeoCatalog::zones();
        $area = [];
        foreach ($zones as $z) {
            $area[] = ['@type' => 'AdministrativeArea', 'name' => $z['short']];
        }
        $area[] = ['@type' => 'Country', 'name' => 'Lebanon'];

        $ld = [
            '@context'    => 'https://schema.org',
            '@type'       => 'OnlineStore',
            '@id'         => self::storeId(),
            'name'        => $name,
            'url'         => url('/'),
            'logo'        => ['@type' => 'ImageObject', 'url' => self::logoUrl()],
            'image'       => self::defaultImage(),
            'description' => 'Online marketplace in Lebanon to buy, sell, trade and swap used and new video games and gaming gear. Every item is inspected before delivery, and buyers and sellers stay anonymous.',
            'areaServed'  => $area,
            'currenciesAccepted' => 'USD',
            'paymentAccepted'    => 'Cash on delivery, store credit, OMT, Whish',
            'knowsLanguage'      => 'en',
        ];
        $price = trim((string) setting('biz_price_range', ''));
        if ($price !== '') {
            $ld['priceRange'] = $price;
        }
        $same = array_values(array_filter(array_map(static fn (string $k): string => trim((string) setting($k, '')), ['instagram_url', 'biz_facebook_url', 'biz_tiktok_url', 'biz_youtube_url'])));
        if ($same) {
            $ld['sameAs'] = $same;
        }
        $address = trim((string) setting('biz_address', ''));
        if ($address !== '') {
            $ld['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressCountry' => 'LB'];
        }
        $hours = trim((string) setting('biz_hours', ''));
        if ($hours !== '') {
            $spec = self::openingHours($hours);
            if ($spec) {
                $ld['openingHoursSpecification'] = $spec;
            }
            $ld['openingHours'] = $hours;
        }
        $email = trim((string) setting('contact_email', ''));
        if ($email !== '') {
            $ld['email'] = $email;
        }
        $phone = self::realPhone();
        if ($phone !== null) {
            $ld['telephone'] = $phone;
            $ld['contactPoint'] = ['@type' => 'ContactPoint', 'contactType' => 'customer support', 'telephone' => $phone, 'availableLanguage' => ['English'], 'areaServed' => 'LB'];
        }
        return $ld;
    }

    public static function website(): array
    {
        return [
            '@context'  => 'https://schema.org',
            '@type'     => 'WebSite',
            '@id'       => self::websiteId(),
            'name'      => self::storeName(),
            'url'       => url('/'),
            'inLanguage' => 'en',
            'publisher' => ['@id' => self::storeId()],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => url('/shop') . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * "Mo-Sa 10:00-20:00" (schema.org openingHours syntax, several ranges separated by ";" or ", ") to OpeningHoursSpecification.
     * @return array<int,array<string,mixed>> empty when the text does not parse
     */
    public static function openingHours(string $text): array
    {
        $days = ['Mo' => 'Monday', 'Tu' => 'Tuesday', 'We' => 'Wednesday', 'Th' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'Su' => 'Sunday'];
        $keys = array_keys($days);
        $out = [];
        foreach (preg_split('/\s*;\s*|,\s+(?=[A-Z][a-z]\b)/', $text) ?: [] as $chunk) {
            if (!preg_match('/^([A-Z][a-z](?:-[A-Z][a-z])?(?:,[A-Z][a-z])*)\s+(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})$/', trim($chunk), $m)) {
                return [];
            }
            $names = [];
            foreach (explode(',', $m[1]) as $part) {
                if (str_contains($part, '-')) {
                    [$a, $b] = explode('-', $part);
                    $ia = array_search($a, $keys, true);
                    $ib = array_search($b, $keys, true);
                    if ($ia === false || $ib === false || $ib < $ia) {
                        return [];
                    }
                    for ($i = $ia; $i <= $ib; $i++) {
                        $names[] = $days[$keys[$i]];
                    }
                } elseif (isset($days[$part])) {
                    $names[] = $days[$part];
                } else {
                    return [];
                }
            }
            $out[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $names, 'opens' => str_pad($m[2], 5, '0', STR_PAD_LEFT), 'closes' => str_pad($m[3], 5, '0', STR_PAD_LEFT)];
        }
        return $out;
    }

    /** A WebPage-family node with the speakable hint pointing at the quick answer. */
    public static function webPage(string $type, string $name, string $url, string $description, bool $speakable = true): array
    {
        $ld = [
            '@context'    => 'https://schema.org',
            '@type'       => $type,
            'name'        => $name,
            'url'         => $url,
            'description' => $description,
            'inLanguage'  => 'en',
            'isPartOf'    => ['@id' => self::websiteId()],
            'about'       => ['@id' => self::storeId()],
        ];
        if ($speakable) {
            $ld['speakable'] = ['@type' => 'SpeakableSpecification', 'cssSelector' => ['.quick-answer']];
        }
        return $ld;
    }

    /** @param array<int, array{0:string,1:?string}> $crumbs */
    public static function breadcrumbs(array $crumbs): array
    {
        $items = [];
        foreach ($crumbs as $i => [$label, $path]) {
            $item = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label];
            if ($path !== null) {
                $item['item'] = url($path);
            }
            $items[] = $item;
        }
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    // ------------------------------------------------------------------ products and listings

    /** @param array<int,array{path:string,kind:string}> $photos real photos of this exact copy (ProductPhotos::forProduct) */
    public static function product(array $p, string $description, array $photos = []): array
    {
        $available = $p['status'] === 'active' && (int) $p['stock'] > 0;
        $digital = Digital::is($p);
        // digital codes are always new; 'New' is NewCondition, Like New / Good / Fair are all UsedCondition
        $condition = $digital || $p['item_condition'] === 'New' ? 'NewCondition' : 'UsedCondition';
        $images = [];
        foreach ($photos as $ph) {
            $images[media((string) $ph['path'])] = true;
        }
        if ($p['image']) {
            $images[media($p['image'])] = true;
        } elseif ($digital || !$images) {
            $images[$digital ? self::logoUrl() : media(null)] = true;
        }
        $x = self::productExtras($p);

        $store = ['@type' => 'OnlineStore', '@id' => self::storeId(), 'name' => self::storeName()];
        $offer = [
            '@type'         => 'Offer',
            'url'           => url('/product/' . $p['slug']),
            'price'         => number_format((float) $p['price'], 2, '.', ''),
            'priceCurrency' => 'USD',
            'availability'  => 'https://schema.org/' . ($available ? 'InStock' : 'OutOfStock'),
            'itemCondition' => 'https://schema.org/' . $condition,
            'seller'        => $store,
        ];
        if (!$digital) {
            $offer['shippingDetails'] = self::shippingDetails();
        }

        $ld = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $p['title'],
            'description' => $description,
            'sku'         => 'CG-' . $p['id'],
            'image'       => array_keys($images),
            'url'         => url('/product/' . $p['slug']),
            'category'    => $p['category_name'],
            'itemCondition' => 'https://schema.org/' . $condition,
            'offers'      => $offer,
        ];

        // brand: the brand column; otherwise the platform maker, but only for hardware (a game's publisher is not tracked)
        $isHardware = ($x['kind'] ?? '') === 'hardware' || (($x['kind'] ?? '') === '' && $p['category_slug'] !== 'games');
        $brand = trim((string) ($x['brand'] ?? ''));
        if ($brand === '' && $isHardware) {
            $brand = (string) self::platformBrand((string) $p['platform_slug']);
        }
        if ($brand !== '') {
            $ld['brand'] = ['@type' => 'Brand', 'name' => $brand];
        }
        $model = trim((string) ($x['model'] ?? ''));
        if ($model !== '') {
            $ld['model'] = $model;
        }
        if ($p['year'] && !$digital) {
            $ld['releaseDate'] = (string) $p['year'];
        }

        $props = [];
        foreach (preg_split('/\R/', (string) ($x['specs'] ?? '')) ?: [] as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                if ($k !== '' && $v !== '') {
                    $props[] = ['@type' => 'PropertyValue', 'name' => $k, 'value' => $v];
                }
            }
        }
        if (!$digital && !empty($p['platform_name'])) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Platform', 'value' => (string) $p['platform_name']];
        }
        if (!$digital && !empty($p['genres'])) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Genre', 'value' => implode(', ', Ui::genres((string) $p['genres']))];
        }
        if (!$digital && (int) ($p['is_steelbook'] ?? 0) === 1) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Edition', 'value' => 'Steelbook'];
        }
        if (!$digital) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Condition grade', 'value' => (string) $p['item_condition']];
        }
        $included = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($x['included_items'] ?? '')) ?: [])));
        if ($included) {
            $props[] = ['@type' => 'PropertyValue', 'name' => 'Included in the box', 'value' => implode(', ', $included)];
        }
        if ($props) {
            $ld['additionalProperty'] = $props;
        }
        $months = (int) ($x['warranty_months'] ?? 0);
        if ($months > 0) {
            $ld['warranty'] = ['@type' => 'WarrantyPromise', 'durationOfWarranty' => ['@type' => 'QuantitativeValue', 'value' => $months, 'unitCode' => 'MON']];
        }
        return $ld;
    }

    /** brand / model / specs / included_items / warranty_months / kind: read from $p when the catalogue query has them, else one small lookup. Never serial_number. */
    private static function productExtras(array $p): array
    {
        $need = ['brand', 'model', 'specs', 'included_items', 'warranty_months'];
        $have = true;
        foreach ($need as $k) {
            if (!array_key_exists($k, $p)) {
                $have = false;
            }
        }
        if ($have && array_key_exists('category_kind', $p)) {
            return $p;
        }
        try {
            $row = db()->fetch(
                'SELECT p.brand, p.model, p.specs, p.included_items, p.warranty_months, c.kind FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?',
                [(int) $p['id']]
            );
        } catch (\Throwable) {
            $row = null;
        }
        return $row ?: [];
    }

    /** OfferShippingDetails: the cheapest active zone fee to Lebanon, handling 0-1 days, transit 1-3 days. */
    public static function shippingDetails(): array
    {
        return [
            '@type'               => 'OfferShippingDetails',
            'shippingRate'        => ['@type' => 'MonetaryAmount', 'value' => number_format(SeoCatalog::cheapestFee(), 2, '.', ''), 'currency' => 'USD'],
            'shippingDestination' => ['@type' => 'DefinedRegion', 'addressCountry' => 'LB'],
            'deliveryTime'        => [
                '@type'        => 'ShippingDeliveryTime',
                'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY'],
                'transitTime'  => ['@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 3, 'unitCode' => 'DAY'],
            ],
        ];
    }

    public static function platformBrand(string $slug): ?string
    {
        return match (true) {
            str_starts_with($slug, 'ps') => 'Sony PlayStation',
            str_starts_with($slug, 'xbox') => 'Microsoft Xbox',
            $slug === 'switch' => 'Nintendo',
            default => null,
        };
    }

    /** Maker used in the merchant feed when a product has no brand of its own: Sony / Nintendo / Microsoft. */
    public static function platformMaker(string $slug): ?string
    {
        return match (true) {
            str_starts_with($slug, 'ps') => 'Sony',
            str_starts_with($slug, 'xbox') => 'Microsoft',
            $slug === 'switch' => 'Nintendo',
            default => null,
        };
    }

    /** ItemList of product rows (first 24 are enough for a listing page). */
    public static function itemList(array $products, int $offset = 0): array
    {
        $items = [];
        foreach (array_slice($products, 0, 24) as $i => $p) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $offset + $i + 1,
                'url'      => url('/product/' . $p['slug']),
                'name'     => (string) $p['title'],
                'image'    => !empty($p['image']) ? media((string) $p['image']) : ((int) ($p['is_digital'] ?? 0) === 1 ? self::logoUrl() : media(null)),
            ];
        }
        return ['@type' => 'ItemList', 'numberOfItems' => count($items), 'itemListElement' => $items];
    }

    public static function collectionPage(string $name, string $url, string $description, array $products, int $offset = 0): array
    {
        return self::webPage('CollectionPage', $name, $url, $description) + ['mainEntity' => self::itemList($products, $offset)];
    }

    /** HowTo for a step-by-step page; the steps must be the ones shown on the page. @param array<int,array{0:string,1:string}> $steps [name, text] */
    public static function howTo(string $name, string $description, array $steps): array
    {
        $out = [];
        foreach ($steps as $i => [$n, $t]) {
            $out[] = ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $n, 'text' => self::plain($t)];
        }
        return ['@context' => 'https://schema.org', '@type' => 'HowTo', 'name' => $name, 'description' => $description, 'step' => $out];
    }

    public static function article(array $a, string $description, string $url, string $image): array
    {
        $published = (string) ($a['published_at'] ?: $a['created_at']);
        $ld = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => self::clip((string) $a['title'], 110),
            'description'      => $description,
            'datePublished'    => date('c', (int) strtotime($published)),
            'dateModified'     => date('c', (int) strtotime((string) ($a['updated_at'] ?: $published))),
            'author'           => ['@type' => 'Organization', 'name' => self::storeName(), 'url' => url('/')],
            'publisher'        => ['@type' => 'Organization', 'name' => self::storeName(), 'logo' => ['@type' => 'ImageObject', 'url' => self::logoUrl()]],
            'image'            => [$image],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'inLanguage'       => 'en',
        ];
        if (!empty($a['tag'])) {
            $ld['articleSection'] = (string) $a['tag'];
        }
        return $ld;
    }

    /** Listing (category / platform / shop) pages get CollectionPage + ItemList from the layout, unless the controller already did. */
    public static function listingLd(string $name, string $url, string $description, array $items, int $page, int $per): array
    {
        return self::collectionPage($name, $url, $description, $items, max(0, ($page - 1) * $per));
    }

    // ------------------------------------------------------------------ text helpers

    /** Trim to a search-snippet friendly length without cutting words. */
    public static function clip(string $text, int $max = 158): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $max - 1), " ,.;:-") . '…';
    }

    /** Make a title fit the 65-character search-result width: drop the brand suffix, then "in Lebanon", then clip at a word. */
    public static function fitTitle(string $title): string
    {
        if (mb_strlen($title) <= self::TITLE_MAX) {
            return $title;
        }
        $t = preg_replace('/\s*\|\s*CyberGaming\s*$/u', '', $title) ?? $title;
        if (mb_strlen($t) > self::TITLE_MAX) {
            $t = preg_replace('/\s+in Lebanon/u', '', $t, 1) ?? $t;
        }
        if (mb_strlen($t) > self::TITLE_MAX) {
            $t = self::clip($t, self::TITLE_MAX);
        }
        return $t;
    }

    /** Turn [[/path|label]] markers into plain text (label only). */
    public static function plain(string $text): string
    {
        return (string) preg_replace('/\[\[[^|\]]*\|([^\]]*)\]\]/', '$1', $text);
    }

    /** Escape text and turn [[/path|label]] markers into links. */
    public static function linkify(string $text): string
    {
        $html = e($text);
        return (string) preg_replace_callback('/\[\[([^|\]]*)\|([^\]]*)\]\]/', static function (array $m): string {
            $path = html_entity_decode($m[1], ENT_QUOTES);
            return '<a href="' . e(str_starts_with($path, 'http') ? $path : url($path)) . '">' . $m[2] . '</a>';
        }, $html);
    }

    /** @param array<int,array{0:string,1:string}> $faqs */
    public static function faqLd(array $faqs): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
            static fn (array $f): array => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => self::plain($f[1])]],
            $faqs
        )];
    }

    /** Accordion FAQ (<details>, no JS). Answers may contain [[/path|label]] links. */
    public static function faqHtml(array $faqs, string $heading = 'Frequently asked questions'): string
    {
        return self::render('faq', ['faqs' => $faqs, 'heading' => $heading]);
    }

    /** Render app/Views/site/seo/{name}.php in an isolated scope. */
    public static function render(string $name, array $vars = []): string
    {
        $file = base_path('app/Views/site/seo/' . $name . '.php');
        return (static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            require $__file;
            return (string) ob_get_clean();
        })($file, $vars);
    }

    // ------------------------------------------------------------------ quick answers

    private static function pct(mixed $v): string
    {
        return rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%';
    }

    /**
     * Answer-first paragraph (40 to 70 words, plain declarative sentences) for the top of a page. Numbers come from settings.
     * kinds: home, sell, trade, swap, credit, delivery, how, about, contact, listing, zone, collection.
     * listing context: what (e.g. "used PS4 games"), n, min (float|null), max (float|null). Other agents can call this for catalog pages.
     */
    public static function quickAnswer(string $kind, array $ctx = []): string
    {
        $buy = self::pct(setting('buyback_pct', 45));
        $trade = self::pct(setting('tradein_pct', 50));
        $pickup = money(Rules::pickupFee());
        $fee = money(SeoCatalog::cheapestFee());
        $local = Rules::nameList(Rules::names('local'));
        switch ($kind) {
            case 'home':
                $stats = Catalog::stats();
                $stock = $stats['items'] > 0
                    ? 'We have ' . $stats['items'] . ' inspected items in stock' . ($stats['min_price'] !== null ? ' from ' . money($stats['min_price']) : '') . ', and we deliver across Lebanon from ' . $fee . '.'
                    : 'We deliver across Lebanon from ' . $fee . '.';
                return "CyberGaming Lebanon is an online marketplace to buy, sell, trade and swap used and new video games and gaming gear. $stock "
                    . "You can sell games for $buy of their shop price in cash or $trade as store credit, and every item is inspected before delivery.";
            case 'sell':
                return "You can sell used PS4, PS5, Switch and Xbox games to CyberGaming for cash or store credit. Add your games on this page for an instant quote: cash pays $buy of our shop price and credit pays $trade, adjusted for condition. "
                    . "We inspect every game in person, and you can hand them over at our hub for free or choose a courier pickup for a $pickup fee.";
            case 'trade':
                return "You can trade in used games for CyberGaming store credit worth $trade of our shop price, then spend it on any game, steelbook or accessory in stock. List what you have and what you want in the calculator to see your balance instantly. "
                    . "We inspect your games, you accept our offer, and you pay only the difference. One credit is always worth one US dollar.";
            case 'swap':
                return 'The CyberGaming swap board lets you trade a game you own for a game you want with another player in Lebanon, without sharing your name or phone number. '
                    . 'Both games come to our hub, we inspect them and hand each player the game they wanted. A swap costs a flat ' . money((float) setting('swap_fee', 3)) . ' per side, charged only when the swap completes.';
            case 'credit':
                return "CyberGaming store credit is money in your wallet that you can spend in the shop: 1 credit is always worth 1 US dollar and it never expires. You earn it by selling us games, where credit pays $trade of the shop price against $buy in cash. "
                    . 'Apply it at checkout and pay any remaining amount on delivery, or by OMT or Whish.';
            case 'delivery':
                return "CyberGaming delivers across Lebanon. Local areas ($local) get our own courier for " . Rules::feeRange('local') . ', with cash on delivery and inspection at the door. '
                    . 'Other areas get a third-party courier for ' . (Rules::feeRange('remote') ?: $fee) . ', and you pay in advance by OMT or Whish because that courier cannot inspect the item. You can also collect your order at our pickup point.';
            case 'how':
                return 'CyberGaming is a marketplace where our store sits between buyers and sellers. To buy, you order online and we confirm on WhatsApp before we deliver. To sell, you get an instant quote, hand over your games and receive cash or store credit. '
                    . 'We inspect every item, and we never share buyer or seller identities.';
            case 'about':
                return 'CyberGaming Lebanon is a Lebanese marketplace for used and new games and gaming gear. We buy games from players, inspect every item and resell it in US dollars with delivery across Lebanon. '
                    . 'You can pay with store credit, cash on delivery, OMT or Whish, and we keep buyer and seller identities private. A person confirms every order on WhatsApp.';
            case 'contact':
                return 'The fastest way to contact CyberGaming Lebanon is WhatsApp, and we usually reply within a few hours. You can ask about an item, an order, selling, trading or swapping games. '
                    . 'We share the pickup point details on WhatsApp when we confirm your order, and we never take card details on the site.';
            case 'guides':
                return 'CyberGaming guides are short, practical articles on buying, selling, trading and checking used games and gaming gear in Lebanon. Each one follows our own process, so fees, delivery areas and store credit rules always match the shop. '
                    . 'Start with how to sell used PS4 games, how to check a used console, or how delivery works in your area.';
            case 'listing':
                $n = (int) ($ctx['n'] ?? 0);
                $what = (string) ($ctx['what'] ?? 'items');
                $range = isset($ctx['min'], $ctx['max']) && $ctx['min'] !== null && $ctx['max'] !== null
                    ? ($ctx['min'] === $ctx['max'] ? ', at ' . money($ctx['min']) : ', priced from ' . money($ctx['min']) . ' to ' . money($ctx['max'])) : '';
                return "CyberGaming Lebanon has $n $what in stock$range. Every item is inspected before sale, and prices are in US dollars. "
                    . "We deliver across Lebanon from $fee: local areas pay cash on delivery to our own courier, and other areas pay in advance by OMT or Whish. You can also pay with store credit earned by selling us games.";
            case 'zone':
                $z = $ctx['zone'];
                $isLocal = $z['mode'] === 'local';
                return 'Delivery to ' . $z['short'] . ' costs ' . money($z['fee']) . ' at CyberGaming. '
                    . ($isLocal
                        ? 'Our own courier delivers there, lets you inspect the item at the door, and accepts cash on delivery. '
                        : 'A third-party courier delivers there, so we inspect, photograph and seal your order at our hub first and you pay in advance by OMT or Whish' . (Rules::prepayOn() && Rules::codAfter() > 0 ? '; cash on delivery unlocks after ' . Rules::codAfter() . ' delivered orders. ' : '. ')
                    )
                    . 'We confirm the delivery time with you on WhatsApp before we ship' . ($isLocal ? ', and you can also collect your order at our pickup point.' : '.');
        }
        return '';
    }

    /** The visible quick-answer block. */
    public static function quickAnswerHtml(string $kind, array $ctx = []): string
    {
        $text = self::quickAnswer($kind, $ctx);
        return $text === '' ? '' : self::render('quick-answer', ['text' => $text]);
    }
}
