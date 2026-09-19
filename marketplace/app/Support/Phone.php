<?php
declare(strict_types=1);

namespace App\Support;

/** Phone numbers are stored as digits with the country code (Lebanon 961) so they compare and link to wa.me reliably. */
final class Phone
{
    public static function normalize(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw) ?? '';
        if (str_starts_with($d, '00')) {
            $d = substr($d, 2);
        }
        if (str_starts_with($d, '961')) {
            return $d;
        }
        $d = ltrim($d, '0');
        if (strlen($d) >= 7 && strlen($d) <= 8) {
            return '961' . $d;
        }
        return $d;
    }

    public static function isValid(string $normalized): bool
    {
        return (bool) preg_match('/^\d{8,15}$/', $normalized);
    }

    /** Readable form for admin screens, e.g. +961 70 123 456 */
    public static function pretty(?string $normalized): string
    {
        $n = (string) $normalized;
        if (str_starts_with($n, '961') && strlen($n) >= 10) {
            $rest = substr($n, 3);
            $head = strlen($rest) === 8 ? 2 : 1;
            return "+961 " . substr($rest, 0, $head) . " " . substr($rest, $head, 3) . " " . substr($rest, $head + 3);
        }
        return $n === '' ? '' : '+' . $n;
    }
}
