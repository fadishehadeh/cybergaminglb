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
        $tagline = (string) setting('tagline', 'Buy, sell & trade games and gaming gear in Lebanon');

        $this->render('site/home', [
            'stats'      => $stats,
            'platforms'  => Catalog::platforms(),
            'categories' => Catalog::categories(),
            'steelbooks' => Catalog::steelbooks(4),
            'latest'     => Catalog::latest(8),
            'giftCards'  => Catalog::giftCards(4),
            'nav'        => 'home',
            'meta'       => [
                'title'       => 'CyberGaming Lebanon | Buy, Sell & Trade Used Games & Gaming Gear',
                'description' => 'Buy, sell and trade used PS4, PS5, Switch and Xbox games in Lebanon. ' . $stats['items'] . ' inspected titles in stock, fair USD prices, delivery across Lebanon, pay cash, OMT or Whish.',
                'canonical'   => url('/'),
                'og_type'     => 'website',
                'tagline'     => $tagline,
            ],
        ]);
    }
}
