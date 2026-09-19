<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;

/** Static, content-only info pages. */
final class PageController extends Controller
{
    public function about(Request $request): void
    {
        $this->page('about', 'About CyberGaming Lebanon', 'CyberGaming is a Lebanese marketplace to buy, sell, trade and swap used games and gaming gear, with every item inspected before it reaches you.', 'About us');
    }

    public function howItWorks(Request $request): void
    {
        $this->page('how-it-works', 'How CyberGaming Works: Buying, Selling & Safety', 'How buying, selling and store credit work at CyberGaming Lebanon: order online, pay with credit and cash on delivery, sell your games for cash or credit. We never share buyer or seller contact details.', 'How it works');
    }

    public function delivery(Request $request): void
    {
        $this->page('delivery', 'Delivery & Payment in Lebanon', 'Delivery fees by area across Lebanon, or pickup at our hub. Pay with store credit and cash on delivery, or OMT / Whish. Every order is confirmed on WhatsApp first.', 'Delivery & payment');
    }

    public function credit(Request $request): void
    {
        $this->page('credit', 'How Store Credit Works: Earn It, Spend It', 'How CyberGaming store credit works: earn credit by selling your games, spend it at checkout (1 credit = $1, no expiry), and see the delivery and commission fees.', 'Store credit');
    }

    public function contact(Request $request): void
    {
        $this->page('contact', 'Contact CyberGaming Lebanon', 'Contact CyberGaming Lebanon on WhatsApp or Instagram. Questions about an order, selling, trading or swapping games? We usually reply within hours.', 'Contact');
    }

    private function page(string $name, string $title, string $description, string $crumb): void
    {
        $path = '/' . ($name === 'delivery' ? 'delivery-and-payment' : $name);
        $crumbs = [['Home', '/'], [$crumb, null]];
        $this->render('site/pages/' . $name, [
            'nav'    => $name,
            'crumbs' => $crumbs,
            'meta'   => [
                'title'       => $title . ' | CyberGaming',
                'description' => Seo::clip($description),
                'canonical'   => url($path),
                'jsonld'      => [Seo::breadcrumbs($crumbs)],
            ],
        ]);
    }
}
