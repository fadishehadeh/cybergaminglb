<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;

final class HomeController extends Controller
{
    public function index(Request $request): void
    {
        $stats = Catalog::stats();
        $gamesOn = games_enabled();
        $tagline = (string) setting('tagline', $gamesOn ? 'Buy games and gaming gear in Lebanon' : 'Gaming gear in Lebanon: keyboards, mice and more');

        $this->render('site/home', [
            'stats'      => $stats,
            'platforms'  => Catalog::platforms(),
            // empty categories stay out of the home tiles too (the nav and the sitemap already skip them)
            'categories' => array_values(array_filter(Catalog::categories(), static fn (array $c): bool => (int) $c['product_count'] > 0)),
            'steelbooks' => Catalog::steelbooks(4),
            'latest'     => Catalog::latest(8),
            'giftCards'  => Catalog::giftCards(4),
            'nav'        => 'home',
            'meta'       => [
                'title'       => $gamesOn ? 'CyberGaming Lebanon | Used & New PS4 Games and Gaming Gear' : 'CyberGaming Lebanon | Gaming Gear: Keyboards & Mice',
                'description' => $gamesOn
                    ? 'Buy used and new PS4 games and gaming gear in Lebanon. ' . $stats['items'] . ' inspected items in stock, fair USD prices, delivery across Lebanon, pay cash, OMT or Whish.'
                    : 'Gaming gear in Lebanon: keyboards, mice and more for gamers. ' . $stats['items'] . ' inspected items in stock, fair USD prices, delivery across Lebanon, pay cash, OMT or Whish.',
                'canonical'   => url('/'),
                'og_type'     => 'website',
                'tagline'     => $tagline,
            ],
        ]);
    }
}
