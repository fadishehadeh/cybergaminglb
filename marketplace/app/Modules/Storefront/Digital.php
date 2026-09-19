<?php
declare(strict_types=1);

namespace App\Modules\Storefront;

/** Presentation helpers for digital goods (gift cards, Steam gifts). Only ever reached when the master switch is on. */
final class Digital
{
    public const PAYMENT_NOTE = 'OMT or Whish, paid BEFORE we release the code (no cash on delivery for digital items).';
    public const BILLING_ADDRESS = 'Digital (WhatsApp)';

    public static function is(array $p): bool
    {
        return (int) ($p['is_digital'] ?? 0) === 1;
    }

    public static function kindLabel(?string $kind): string
    {
        return match ($kind) {
            'gift_card'  => 'Gift card',
            'steam_gift' => 'Steam game gift',
            'game_key'   => 'Game key',
            default      => 'Digital code',
        };
    }

    public static function region(array $p): string
    {
        return trim((string) ($p['digital_region'] ?? ''));
    }

    /** "Region-locked: only works on US accounts" (or a neutral line for Global cards). */
    public static function regionNote(string $region): string
    {
        if ($region === '') {
            return '';
        }
        if (strcasecmp($region, 'global') === 0) {
            return 'Global: works on accounts from any region.';
        }
        return 'Region-locked: only works on ' . $region . ' accounts.';
    }

    /** True when at least one line of a cart lines() result is digital. */
    public static function anyDigital(array $lines): bool
    {
        foreach ($lines as $l) {
            if (self::is($l['product'])) {
                return true;
            }
        }
        return false;
    }
}
