<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\Delivery;
use App\Support\InsufficientCreditException;
use App\Support\Wallet;

final class CheckoutController extends Controller
{
    /** Legacy area list (still used by the swap board and as a fallback when no delivery zones exist). */
    public const AREAS = ['Beirut', 'Mount Lebanon', 'North', 'South', 'Bekaa', 'Nabatieh', 'Akkar', 'Baalbek-Hermel'];

    public function form(Request $request): void
    {
        header('Cache-Control: no-store');
        $cart = Cart::lines();
        if (!$cart['lines']) {
            $this->back('/cart', 'Your cart is empty.');
        }

        $user = $this->customer();
        $zones = Shipping::zones();
        $names = array_column($zones, 'name');
        $balance = $user ? Wallet::balance((int) $user['id']) : 0.0;

        $pick = static function (string $key, string $field) use ($user): string {
            $v = (string) old($key, '');
            if ($v !== '') {
                return $v;
            }
            return $user ? (string) ($user[$field] ?? '') : '';
        };
        $values = [
            'name'    => $pick('name', 'name'),
            'phone'   => $pick('phone', 'phone'),
            'area'    => $pick('area', 'area'),
            'address' => $pick('address', 'address'),
            'note'    => (string) old('note', ''),
        ];
        if (!in_array($values['area'], $names, true)) {
            $values['area'] = '';
        }
        $needsDelivery = (bool) $cart['has_physical'];
        $userId = $user ? (int) $user['id'] : null;
        $mode = $needsDelivery && $values['area'] !== '' ? Shipping::mode($values['area']) : '';
        $prepay = $mode === 'remote' && Delivery::requiresPrepay($values['area'], $userId);
        // does this buyer have to pay first in the remote zones? (drives the live hint next to the area list)
        $remoteNames = Rules::names('remote');
        $remotePrepay = $needsDelivery && $remoteNames !== [] && Delivery::requiresPrepay($remoteNames[0], $userId);
        // credit only ever pays for physical items + delivery, so a digital-only cart has no credit option
        $useCredit = $needsDelivery && $user !== null && $balance > 0 && (string) old('use_credit', '1') === '1';

        $this->render('site/checkout', [
            'mode'         => $mode,
            'prepay'       => $prepay,
            'remotePrepay' => $remotePrepay,
            'delivered'    => Delivery::deliveredOrders($userId),
            'needsDelivery' => $needsDelivery,
            'cart'      => $cart,
            'zones'     => $zones,
            'user'      => $user,
            'balance'   => $balance,
            'useCredit' => $useCredit,
            'values'    => $values,
            'totals'    => self::totals($cart, $values['area'], $balance, $useCredit, $prepay),
            'freeOver'  => Shipping::freeOver(),
            'nav'       => 'cart',
            'meta'      => ['title' => 'Checkout | CyberGaming', 'noindex' => true, 'description' => 'Complete your order.'],
        ]);
    }

    /**
     * Display totals for the summary. $area '' means "not chosen yet" (delivery unknown).
     * Delivery applies to the physical part only; store credit pays for physical items + delivery, never digital lines;
     * the digital part is prepaid (OMT / Whish), so "cash due on delivery" covers the physical part only, and it is 0
     * as well when $prepay says the physical part must be paid first too (remote zone). 'prepaid' = all that is paid
     * by OMT / Whish before we ship.
     * @return array{subtotal:float,physical:float,digital:float,fee:?float,grand:?float,credit:float,cash:?float,prepaid:float,prepay:bool}
     */
    public static function totals(array $cart, string $area, float $balance, bool $useCredit, bool $prepay = false): array
    {
        $physical = (float) $cart['physical'];
        $digital = (float) $cart['digital'];
        $subtotal = round($physical + $digital, 2);
        if (!$cart['has_physical']) {
            $fee = 0.0;
        } else {
            $fee = $area !== '' ? Shipping::fee($area, $physical) : null;
        }
        $grand = $fee !== null ? round($subtotal + $fee, 2) : null;
        $creditable = round($physical + ($fee ?? 0.0), 2);
        $credit = $useCredit ? round(min($balance, $creditable), 2) : 0.0;
        $physicalDue = round($creditable - $credit, 2);
        return [
            'subtotal' => $subtotal,
            'physical' => round($physical, 2),
            'digital'  => round($digital, 2),
            'fee'      => $fee,
            'grand'    => $grand,
            'credit'   => $credit,
            'cash'     => $fee !== null ? ($prepay ? 0.0 : $physicalDue) : null,
            'prepaid'  => round($digital + ($fee !== null && $prepay ? $physicalDue : 0.0), 2),
            'prepay'   => $prepay,
        ];
    }

    public function place(Request $request): void
    {
        // honeypot: real people never fill this hidden field
        if ((string) $request->input('website', '') !== '') {
            $this->redirect('/');
        }

        // InsufficientCreditException is declared inside Wallet.php, so it is only autoloadable once Wallet has been loaded.
        class_exists(Wallet::class);

        // Loading the cart drops anything that can no longer be bought (including digital lines while the switch is off).
        $summary = Cart::lines();
        if (!$summary['lines']) {
            $this->back('/cart', 'Your cart is empty.');
        }
        $needsDelivery = (bool) $summary['has_physical'];

        $user = $this->customer();
        $name = $this->text($request->input('name'), 120);
        $phone = $this->text($request->input('phone'), 40);
        $area = $needsDelivery ? $this->text($request->input('area'), 60) : '';
        $address = $needsDelivery ? $this->text($request->input('address'), 255) : '';
        $note = $this->text($request->input('note'), 1000);
        $useCredit = $needsDelivery && $user !== null && (string) $request->input('use_credit', '') === '1';
        $seenRaw = $request->input('credit_seen');
        $seen = is_scalar($seenRaw) && is_numeric($seenRaw) ? round((float) $seenRaw, 2) : 0.0;
        $input = compact('name', 'phone', 'area', 'address', 'note') + ['use_credit' => $useCredit ? '1' : '0'];

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Please enter your full name.';
        }
        $digits = strlen((string) preg_replace('/\D+/', '', $phone));
        if (!preg_match('/^[0-9+()\-\s]+$/', $phone) || $digits < 7 || $digits > 15) {
            $errors[] = 'Please enter a valid phone / WhatsApp number (7 to 15 digits).';
        }
        if ($needsDelivery && !in_array($area, Shipping::names(), true)) {
            $errors[] = 'Please choose your delivery area.';
        }
        if ($errors) {
            $this->back('/checkout', implode(' ', $errors), $input);
        }

        $cart = Cart::raw();
        if (!$cart) {
            $this->back('/cart', 'Your cart is empty.');
        }
        ksort($cart);
        $userId = $user ? (int) $user['id'] : null;

        try {
            $code = db()->transaction(function ($db) use ($cart, $name, $phone, $area, $address, $note, $userId, $useCredit, $seen): string {
                $lines = [];
                $total = 0.0;
                $physical = 0.0;
                $hasPhysical = false;
                $hasDigital = false;
                foreach ($cart as $productId => $qty) {
                    $productId = (int) $productId;
                    $qty = max(1, (int) $qty);
                    // Catalog::gate(): a digital product is treated as non-existent while the master switch is off.
                    $p = $db->fetch(
                        'SELECT p.id, p.title, p.price, p.seller_price, p.seller_id, p.is_digital FROM products p WHERE p.id = :id' . Catalog::gate(),
                        ['id' => $productId]
                    );
                    $taken = $p ? $db->execute(
                        "UPDATE products SET stock = stock - :q WHERE id = :id AND status = 'active' AND stock >= :need",
                        ['q' => $qty, 'id' => $productId, 'need' => $qty]
                    ) : 0;
                    if ($taken !== 1) {
                        throw new \DomainException((string) ($p['title'] ?? 'One of the items'), $productId);
                    }
                    $db->execute("UPDATE products SET status = 'sold' WHERE id = :id AND stock <= 0", ['id' => $productId]);
                    $lines[] = [$p, $qty];
                    $lineTotal = (float) $p['price'] * $qty;
                    $total += $lineTotal;
                    if ((int) $p['is_digital'] !== 1) {
                        $physical += $lineTotal;
                        $hasPhysical = true;
                    } else {
                        $hasDigital = true;
                    }
                }

                // Everything money-related is computed here. Nothing price-like is read from the form.
                $total = round($total, 2);
                $physical = round($physical, 2);
                if ($hasPhysical && !in_array($area, Shipping::names(), true)) {
                    throw new \InvalidArgumentException('area');
                }
                // Delivery is charged on the physical part only; a digital-only order has none and needs no address.
                $fee = $hasPhysical ? Shipping::fee($area, $physical) : 0.0;
                $grand = round($total + $fee, 2);
                // Credit can pay for physical items + delivery, never for digital lines.
                $creditable = round($physical + $fee, 2);
                $credit = 0.0;
                if ($userId !== null && $useCredit && $creditable > 0) {
                    // Lock the wallet row and read the balance inside the transaction (Wallet::debit re-locks the same row).
                    $balance = (float) $db->fetchValue('SELECT credit_balance FROM users WHERE id = ? FOR UPDATE', [$userId]);
                    $credit = round(min($balance, $creditable), 2);
                    if ($credit + 0.004 < min($seen, $creditable)) {
                        // the customer was shown more credit than is available now
                        throw new InsufficientCreditException('The credit balance changed.');
                    }
                }

                // Local vs remote is decided here from the zone table, never from the form. A remote zone means a
                // third-party courier that cannot inspect, so the physical part is paid first (OMT / Whish) unless the
                // customer has earned cash on delivery. Digital lines are always prepaid.
                $zoneMode = $hasPhysical ? Delivery::mode($area) : 'digital';
                $prepayPhysical = $hasPhysical && $zoneMode === 'remote' && Delivery::requiresPrepay($area, $userId);
                // nothing to wait for when store credit already covers the physical part and there are no digital lines
                $owed = ($hasDigital ? 1.0 : 0.0) + ($prepayPhysical ? round($creditable - $credit, 2) : 0.0);
                $paymentStatus = $owed > 0 ? 'awaiting' : 'not_required';

                $code = $this->uniqueCode($db);
                $orderId = $db->insert(
                    "INSERT INTO orders (code, user_id, buyer_name, buyer_phone, buyer_area, zone, zone_mode, buyer_address, buyer_note, status, total, delivery_fee, grand_total, credit_used, payment_status)
                     VALUES (:code, :user, :name, :phone, :area, :zone, :zmode, :address, :note, 'new', :total, :fee, :grand, :credit, :pay)",
                    [
                        'code' => $code, 'user' => $userId, 'name' => $name, 'phone' => $phone,
                        'zone' => $hasPhysical ? $area : null, 'zmode' => $zoneMode, 'pay' => $paymentStatus,
                        'area' => $hasPhysical ? $area : Digital::BILLING_ADDRESS,
                        'address' => $hasPhysical && $address !== '' ? $address : null, 'note' => $note !== '' ? $note : null,
                        'total' => number_format($total, 2, '.', ''),
                        'fee' => number_format($fee, 2, '.', ''),
                        'grand' => number_format($grand, 2, '.', ''),
                        'credit' => number_format($credit, 2, '.', ''),
                    ]
                );
                foreach ($lines as [$p, $qty]) {
                    $db->execute(
                        'INSERT INTO order_items (order_id, product_id, seller_id, title, qty, unit_price, seller_price, is_digital)
                         VALUES (:order, :product, :seller, :title, :qty, :unit, :sp, :dig)',
                        [
                            'order' => $orderId, 'product' => $p['id'], 'seller' => $p['seller_id'], 'title' => $p['title'],
                            'qty' => $qty, 'unit' => $p['price'], 'sp' => $p['seller_price'], 'dig' => (int) $p['is_digital'] === 1 ? 1 : 0,
                        ]
                    );
                }
                if ($credit > 0) {
                    Wallet::debit((int) $userId, $credit, 'order_payment', 'order', $orderId, 'Order ' . $code);
                }
                return $code;
            });
        } catch (\DomainException $e) {
            // Someone else bought it a moment ago (or it was withdrawn). Nothing was written (transaction rolled back, credit untouched).
            Cart::remove((int) $e->getCode());
            $this->back('/cart', '“' . $e->getMessage() . '” just sold out or is no longer available in that quantity, so we removed it from your cart. Please review your order.', $input);
        } catch (\InvalidArgumentException) {
            $this->back('/checkout', 'Please choose your delivery area.', $input);
        } catch (InsufficientCreditException) {
            // Balance changed between the checkout page and the order. Everything was rolled back, including the stock.
            $now = $userId !== null ? Wallet::balance($userId) : 0.0;
            $this->back('/checkout', 'Your credit balance changed while you were checking out (you now have ' . money($now) . '). Nothing was charged or reserved. Please review your order and place it again.', $input);
        }

        Cart::clear();
        $this->app->session()->put('last_order', $code);
        $this->redirect('/order/' . $code);
    }

    public function confirmation(Request $request, string $code): void
    {
        header('Cache-Control: no-store');
        $code = strtoupper($code);
        if (!preg_match('/^CG-[A-Z0-9]{6}$/', $code)) {
            Response::abort(404);
        }
        $order = db()->fetch(
            'SELECT id, code, user_id, buyer_name, buyer_phone, buyer_area, zone, zone_mode, buyer_address, buyer_note, status, total, delivery_fee, grand_total, credit_used, payment_status, created_at
               FROM orders WHERE code = :code',
            ['code' => $code]
        ) ?? Response::abort(404);
        // Orders placed from an account are private to that account (guest orders stay reachable by their random code).
        if ($order['user_id'] !== null && (int) $order['user_id'] !== (int) ($this->customer()['id'] ?? 0)) {
            Response::abort(404);
        }
        $items = db()->fetchAll('SELECT title, qty, unit_price, is_digital FROM order_items WHERE order_id = :id ORDER BY id', ['id' => $order['id']]);

        $subtotal = (float) $order['total'];
        $fee = (float) $order['delivery_fee'];
        $grand = (float) $order['grand_total'] > 0 ? (float) $order['grand_total'] : round($subtotal + $fee, 2);
        $credit = (float) $order['credit_used'];
        // Digital lines are prepaid (OMT / Whish) and credit never pays for them. The physical part is cash on delivery
        // in the local area, and prepaid too for a remote zone that needs prepayment (Rules::physicalPrepaid).
        $digital = 0.0;
        $nDigital = 0;
        $hasPhysical = false;
        foreach ($items as $i) {
            if ((int) $i['is_digital'] === 1) {
                $digital += (float) $i['unit_price'] * (int) $i['qty'];
                $nDigital++;
            } else {
                $hasPhysical = true;
            }
        }
        $digital = round($digital, 2);
        $hasDigital = $nDigital > 0;
        $physPrepaid = Rules::physicalPrepaid($order, $hasDigital, $hasPhysical);
        $due = Rules::due($grand, $credit, $digital, $physPrepaid);
        $cash = $due['cash'];
        $prepay = $due['prepay'];
        $zoneMode = (string) ($order['zone_mode'] ?? '');

        $summary = implode(', ', array_map(static fn (array $i): string => $i['qty'] . 'x ' . $i['title'] . ((int) $i['is_digital'] === 1 ? ' (digital)' : ''), $items));
        $waMessage = 'Hi CyberGaming, ' . ($hasDigital && !$hasPhysical ? 'DIGITAL order ' : 'my order ') . $order['code'] . " — $summary — total " . money($grand)
            . ($order['zone'] ? ' — zone ' . $order['zone'] . ($zoneMode === 'remote' ? ' (remote)' : '') : '')
            . ($credit > 0 ? ', paid with credit ' . money($credit) : '')
            . ($prepay > 0 ? ', PREPAY ' . money($prepay) . ' via OMT/Whish (please send me the payment details)' : '')
            . ($hasPhysical ? ', cash due on delivery ' . money($cash) : '')
            . ". Name: {$order['buyer_name']}";

        $this->render('site/order', [
            'order'       => $order,
            'items'       => $items,
            'subtotal'    => $subtotal,
            'fee'         => $fee,
            'grand'       => $grand,
            'credit'      => $credit,
            'cash'        => $cash,
            'prepaid'     => $digital,
            'prepay'      => $prepay,
            'physPrepaid' => $physPrepaid,
            'zoneMode'    => $zoneMode,
            'hasDigital'  => $hasDigital,
            'hasPhysical' => $hasPhysical,
            'waLink'      => wa_link($waMessage),
            'nav'      => 'cart',
            'meta'     => ['title' => 'Order ' . $order['code'] . ' | CyberGaming', 'noindex' => true, 'description' => 'Your order confirmation.'],
        ]);
    }

    /** The logged-in customer, or null for guests (and for staff accounts, who check out as guests). */
    private function customer(): ?array
    {
        return auth()->hasRole('customer') ? auth()->user() : null;
    }

    private function text(mixed $v, int $max): string
    {
        return is_string($v) ? mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v) ?? ''), 0, $max) : '';
    }

    private function uniqueCode(\App\Core\Database $db): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        for ($try = 0; $try < 20; $try++) {
            $code = 'CG-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            if ($db->fetchValue('SELECT 1 FROM orders WHERE code = :c', ['c' => $code]) === null) {
                return $code;
            }
        }
        throw new \RuntimeException('Could not generate an order code');
    }
}
