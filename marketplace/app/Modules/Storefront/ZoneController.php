<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

/** /delivery-to/{zone}: one useful page per active delivery zone (fee, local vs remote rules, selling from there, FAQ). */
final class ZoneController extends Controller
{
    public function hub(Request $request): void
    {
        Response::redirect('/delivery-and-payment', 301);
    }

    public function show(Request $request, string $zone): void
    {
        $z = SeoCatalog::zoneBySlug($zone) ?? Response::abort(404);
        $zones = SeoCatalog::zones();
        $isLocal = $z['mode'] === 'local';
        $fee = money($z['fee']);
        $short = $z['short'];
        $path = '/delivery-to/' . $z['slug'];
        $canonical = url($path);
        $crumbs = [['Home', '/'], ['Delivery & payment', '/delivery-and-payment'], [$short, null]];

        $title = 'Delivery to ' . $short . ': ' . $fee . ($isLocal ? ', Cash on Delivery' : ', Prepay by OMT or Whish') . ' | CyberGaming';
        $desc = $isLocal
            ? "Delivery to $short costs $fee with our own courier and cash on delivery. See how it works, how to sell games from there and what is in stock."
            : "Delivery to $short costs $fee with a third-party courier, paid in advance by OMT or Whish. See how it works, selling from there and what is in stock.";

        // a small, rotating slice of what is on the shelf right now (Catalog::latest applies the visibility rules)
        $shelf = Catalog::latest(24);
        $idx = 0;
        foreach ($zones as $i => $zz) {
            if ($zz['slug'] === $z['slug']) {
                $idx = $i;
            }
        }
        $products = [];
        if ($shelf) {
            for ($k = 0; $k < min(4, count($shelf)); $k++) {
                $products[] = $shelf[($idx * 4 + $k) % count($shelf)];
            }
            $products = array_values(array_column($products, null, 'id'));
        }

        $faqs = $this->faqs($z);
        $jsonld = [
            Seo::breadcrumbs($crumbs),
            Seo::webPage('WebPage', 'Delivery to ' . $short, $canonical, $desc),
            [
                '@context'    => 'https://schema.org',
                '@type'       => 'Service',
                'serviceType' => 'Delivery of used and new video games and gaming gear',
                'name'        => 'Delivery to ' . $short,
                'provider'    => ['@id' => Seo::storeId()],
                'areaServed'  => ['@type' => 'AdministrativeArea', 'name' => $short, 'containedInPlace' => ['@type' => 'Country', 'name' => 'Lebanon']],
                'offers'      => ['@type' => 'Offer', 'price' => number_format($z['fee'], 2, '.', ''), 'priceCurrency' => 'USD', 'description' => 'Flat delivery fee to ' . $short],
            ],
            Seo::faqLd($faqs),
        ];

        $this->render('site/zone/show', [
            'nav'      => 'delivery',
            'z'        => $z,
            'zones'    => $zones,
            'crumbs'   => $crumbs,
            'faqs'     => $faqs,
            'products' => $products,
            'meta'     => [
                'title'       => $title,
                'description' => Seo::clip($desc),
                'canonical'   => $canonical,
                'jsonld'      => $jsonld,
            ],
        ]);
    }

    /** @return array<int,array{0:string,1:string}> */
    private function faqs(array $z): array
    {
        $short = $z['short'];
        $fee = money($z['fee']);
        $isLocal = $z['mode'] === 'local';
        $free = Shipping::freeOver();
        $after = Rules::codAfter();
        $pickup = money(Rules::pickupFee());
        $minSell = Rules::minSell();
        $ret = money(Rules::returnFee());
        $days = Rules::holdDays();

        $faqs = [];
        $faqs[] = ["How much does delivery to $short cost?", "Delivery to $short costs $fee" . ($free > 0 ? ', and orders of ' . money($free) . ' or more get free delivery' : '') . '. You see the exact fee at checkout before you place your order. Every area is listed on [[/delivery-and-payment|delivery and payment]].'];
        if ($isLocal) {
            $faqs[] = ["Can I pay cash on delivery in $short?", "Yes. $short is a local area, so our own courier delivers and you pay cash on delivery. You can also use store credit, or pay by OMT or Whish. Cash on delivery is always available in local areas."];
            $faqs[] = ["Can I check the game before I pay in $short?", 'Yes. Our own courier can let you look at the item at the door before you pay. Every item is also inspected by our team before it leaves us.'];
        } else {
            $faqs[] = ["Can I pay cash on delivery in $short?", Rules::prepayOn()
                ? "Not at first. $short is a remote area, so you pay in advance by OMT or Whish and we ship as soon as your payment is confirmed." . ($after > 0 ? " After $after delivered orders, cash on delivery also unlocks in remote areas." : ' Cash on delivery is not offered in remote areas.')
                : "Yes, cash on delivery is currently available in $short. You can also use store credit, OMT or Whish."];
            $faqs[] = ["Can I check the game before I pay in $short?", "A third-party courier delivers to $short and cannot inspect anything for you. So we inspect, photograph and seal your order at our hub first. If something is not as described when it arrives, tell us on WhatsApp straight away."];
        }
        $faqs[] = ["How long does delivery to $short take?", 'Orders are usually handed to the courier within a day of confirmation and arrive within 1 to 3 days. We confirm the timing with you on WhatsApp before we ship.'];
        if ($isLocal) {
            $faqs[] = ["Can I sell my games from $short?", "Yes. You can bring them to our hub for free, or have our own courier collect them in $short: a $pickup pickup fee is deducted from your payout and the courier checks the games on the spot. Start with an [[/sell|instant quote]]."];
        } else {
            $faqs[] = ["Can I sell my games from $short?", "Yes. Ship them to our hub by courier: a $pickup pickup fee is deducted from your payout" . ($minSell > 0 ? ', and the shipment must be worth at least ' . money($minSell) : '') . ". We inspect on arrival. If an item is not acceptable you choose a revised offer, sending it back for $ret, or a free recycle, and we hold it $days days for your answer. Start with an [[/sell|instant quote]]."];
        }
        $faqs[] = ["Does the delivery fee cover every town in $short?", "Yes. The fee is flat for the whole area. Pick $short at checkout, and if you are not sure which area your town belongs to, message us on WhatsApp before you order."];
        return $faqs;
    }
}
