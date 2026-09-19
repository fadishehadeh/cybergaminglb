<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

use App\Core\Controller;
use App\Core\Request;

final class CartController extends Controller
{
    public function index(Request $request): void
    {
        header('Cache-Control: no-store');
        $cart = Cart::lines();
        $this->render('site/cart', [
            'cart'    => $cart,
            'suggest' => $cart['lines'] ? [] : Catalog::latest(4),
            'minFee'  => Shipping::minFee(),
            'freeOver' => Shipping::freeOver(),
            'balance' => auth()->hasRole('customer') ? \App\Support\Wallet::balance((int) auth()->id()) : null,
            'nav'     => 'cart',
            'meta'    => ['title' => 'Your cart | CyberGaming', 'noindex' => true, 'description' => 'Your shopping cart.'],
        ]);
    }

    public function add(Request $request): void
    {
        $id = $this->id($request);
        $qty = max(1, (int) $request->input('qty', 1));
        $before = Cart::raw()[$id] ?? 0;
        $now = Cart::add($id, $qty);

        if ($now === 0) {
            $this->back('/cart', 'Sorry, that item is no longer available.');
        }
        $product = Catalog::purchasable([$id])[$id] ?? null;
        $name = $product['title'] ?? 'Item';
        if ($now === $before) {
            $this->app->session()->flash('success', 'You already have every available copy of ' . $name . ' in your cart.');
        } else {
            $this->app->session()->flash('success', 'Added to your cart: ' . $name);
        }
        $this->redirect('/cart');
    }

    public function update(Request $request): void
    {
        $id = $this->id($request);
        $qty = (int) $request->input('qty', 1);
        $now = Cart::set($id, $qty);
        if ($now > 0 && $now < $qty) {
            $this->app->session()->flash('success', 'Quantity limited to the copies we have in stock.');
        }
        $this->redirect('/cart');
    }

    public function remove(Request $request): void
    {
        Cart::remove($this->id($request));
        $this->redirect('/cart');
    }

    public function clear(Request $request): void
    {
        Cart::clear();
        $this->redirect('/cart');
    }

    private function id(Request $request): int
    {
        $id = $request->input('product_id', 0);
        return is_scalar($id) ? (int) $id : 0;
    }
}
