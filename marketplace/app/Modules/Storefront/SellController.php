<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/** Instant buy-back quote: customers sell us used games for cash. */
final class SellController extends QuoteFlow
{
    protected function mode(): string
    {
        return 'sell';
    }
}
