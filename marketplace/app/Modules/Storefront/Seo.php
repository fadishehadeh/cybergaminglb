<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/** Builders for schema.org JSON-LD and meta text. */
final class Seo
{
    public const BRAND = 'CyberGaming';

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

    /** @param array<int,array{path:string,kind:string}> $photos real photos of this exact copy (ProductPhotos::forProduct) */
    public static function product(array $p, string $description, array $photos = []): array
    {
        $available = $p['status'] === 'active' && (int) $p['stock'] > 0;
        $digital = Digital::is($p);
        // digital codes are always new; 'New' is NewCondition, Like New / Good / Fair are all UsedCondition
        $condition = $digital || $p['item_condition'] === 'New' ? 'NewCondition' : 'UsedCondition';
        // real photos of this exact copy first, then the cover (deduplicated)
        $images = [];
        foreach ($photos as $ph) {
            $images[media((string) $ph['path'])] = true;
        }
        if ($p['image']) {
            $images[media($p['image'])] = true;
        } elseif ($digital || !$images) {
            $images[$digital ? asset('img/logo.jpg') : media(null)] = true;
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
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => url('/product/' . $p['slug']),
                'price'         => number_format((float) $p['price'], 2, '.', ''),
                'priceCurrency' => 'USD',
                'availability'  => 'https://schema.org/' . ($available ? 'InStock' : 'OutOfStock'),
                'itemCondition' => 'https://schema.org/' . $condition,
                'seller'        => ['@type' => 'Organization', 'name' => (string) setting('site_name', 'CyberGaming Lebanon')],
            ],
        ];

        // Games have publishers we do not track; for hardware the platform maker is the brand.
        $brand = self::platformBrand((string) $p['platform_slug']);
        if ($brand !== null && $p['category_slug'] !== 'games') {
            $ld['brand'] = ['@type' => 'Brand', 'name' => $brand];
        }
        if ($p['year'] && !$digital) {
            $ld['releaseDate'] = (string) $p['year'];
        }
        return $ld;
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

    /** Trim to a search-snippet friendly length without cutting words. */
    public static function clip(string $text, int $max = 158): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $max - 1), " ,.;:-") . '…';
    }
}
