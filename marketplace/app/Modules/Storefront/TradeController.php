<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/** Trade-in calculator: customers give games, take products from the shop, and settle the balance. */
final class TradeController extends QuoteFlow
{
    protected function mode(): string
    {
        return 'trade';
    }
}
