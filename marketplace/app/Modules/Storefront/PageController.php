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
        $this->page('about', 'About CyberGaming Lebanon', 'CyberGaming is a Lebanese shop selling used and new games and gaming gear, with every item inspected before it reaches you.', 'About us');
    }

    public function howItWorks(Request $request): void
    {
        $this->page('how-it-works', 'How CyberGaming Works: Buying & Delivery', 'How buying works at CyberGaming Lebanon: browse, order online as a guest, we confirm on WhatsApp, and pay cash on delivery, OMT or Whish.', 'How it works');
    }

    public function delivery(Request $request): void
    {
        $this->page('delivery', 'Delivery & Payment in Lebanon', 'Delivery fees by area across Lebanon, or pickup at our hub. Pay cash on delivery, OMT or Whish. Every order is confirmed on WhatsApp first.', 'Delivery & payment');
    }

    public function contact(Request $request): void
    {
        $this->page('contact', 'Contact CyberGaming Lebanon', 'Contact CyberGaming Lebanon on WhatsApp or Instagram. Questions about an item or an order? We usually reply within hours.', 'Contact');
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
