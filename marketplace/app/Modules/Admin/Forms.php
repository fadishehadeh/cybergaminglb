<?php
declare(strict_types=1);

namespace App\Modules\Admin;

/** Small input/format helpers shared by the admin controllers and views. */
final class Forms
{
    private const LABELS = [
        'picked_up' => 'Picked up',
        'trade_in'  => 'Trade-in',
        'sell'      => 'Buy-back',
        'disabled'  => 'Suspended',
    ];

    /** Trimmed string with control characters removed; non-strings become ''. */
    public static function text(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return trim($clean ?? '');
    }

    /** Money/percent: up to 7 integer digits, up to 2 decimals; comma accepted as decimal point. null = invalid. */
    public static function decimal(mixed $value): ?float
    {
        $v = str_replace(',', '.', self::text($value));
        if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/', $v)) {
            return null;
        }
        return round((float) $v, 2);
    }

    public static function int(mixed $value): ?int
    {
        $v = self::text($value);
        return preg_match('/^-?\d{1,9}$/', $v) ? (int) $v : null;
    }

    /** Digits-only WhatsApp number. Lebanese local numbers (7-8 digits, optional leading 0) get the 961 prefix. */
    public static function waNumber(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }
        if (in_array(strlen($digits), [7, 8], true)) {
            $digits = '961' . $digits;
        }
        return $digits;
    }

    /** wa.me deep link for any phone number, or '' when there is no usable number. */
    public static function waLink(?string $phone, string $message = ''): string
    {
        $number = self::waNumber($phone);
        if ($number === '') {
            return '';
        }
        return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
    }

    /** Value to show in a form field: the old (failed) submission if there was one, else the stored value. */
    public static function val(string $key, mixed $default = ''): mixed
    {
        return old('_form') ? old($key, '') : $default;
    }

    public static function checked(string $key, bool $default): bool
    {
        return old('_form') ? (bool) old($key, false) : $default;
    }

    /** Current admin path + query string (relative to the app root), for "return to this page" fields. */
    public static function here(): string
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        return request()->path() . ($qs !== '' ? '?' . $qs : '');
    }

    /** Only allow returning to admin pages (blocks open redirects). */
    public static function safeReturn(mixed $path, string $default): string
    {
        if (!is_string($path) || $path === '' || str_contains($path, '//') || str_contains($path, '\\') || str_contains($path, '..')) {
            return $default;
        }
        if (!preg_match('#^/admin(?:/[A-Za-z0-9_\-./]*)?(?:\?[A-Za-z0-9_\-=&%.+\[\]]*)?$#', $path)) {
            return $default;
        }
        return $path;
    }

    /** <option> tags from a value => label map. */
    public static function options(array $map, mixed $selected = ''): string
    {
        $html = '';
        foreach ($map as $value => $label) {
            $sel = (string) $value === (string) $selected ? ' selected' : '';
            $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($label) . '</option>';
        }
        return $html;
    }

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    private const LEDGER_TYPES = [
        'offer_credit'  => 'Sell offer credit',
        'payout_credit' => 'Sale payout credit',
        'order_payment' => 'Order payment',
        'order_refund'  => 'Order refund',
        'admin_adjust'  => 'Admin adjustment',
    ];

    public static function ledgerType(string $type): string
    {
        return self::LEDGER_TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    /** Link (HTML) to whatever a ledger row refers to, or ''. */
    public static function ledgerRef(?string $refType, mixed $refId): string
    {
        if ($refType === null || $refId === null) {
            return '';
        }
        $id = (int) $refId;
        return match ($refType) {
            'buyback' => '<a href="' . e(url('/admin/requests/buyback/' . $id)) . '">Sell request #' . $id . '</a>',
            'order'   => '<a href="' . e(url('/admin/orders/' . $id)) . '">Order #' . $id . '</a>',
            'payout'  => '<a href="' . e(url('/admin/payouts')) . '">Payout #' . $id . '</a>',
            default   => e($refType . ' #' . $id),
        };
    }

    /** "+$5" / "-$2.50" with a colour class. */
    public static function signed(float|int|string $amount): string
    {
        $a = (float) $amount;
        $cls = $a > 0 ? 'amt-plus' : ($a < 0 ? 'amt-minus' : 'muted');
        return '<span class="' . $cls . '">' . ($a > 0 ? '+' : ($a < 0 ? '-' : '')) . e(money(abs($a))) . '</span>';
    }

    /** Signed decimal such as "15", "-5.50", "+2": null when invalid. */
    public static function signedDecimal(mixed $value): ?float
    {
        $v = str_replace(',', '.', self::text($value));
        return preg_match('/^[+-]?\d{1,7}(\.\d{1,2})?$/', $v) ? round((float) $v, 2) : null;
    }

    public static function pill(string $status): string
    {
        return '<span class="pill pill-' . e(str_replace('_', '-', $status)) . '">' . e(self::label($status)) . '</span>';
    }

    /** "12" for whole numbers, "12.5" otherwise: for percentages. */
    public static function pct(float|int|string|null $value): string
    {
        $v = (float) $value;
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . '%';
    }

    /** Safe removal of a file that lives under public/uploads/products (never touches seeded covers/). */
    public static function deleteUpload(?string $path): void
    {
        if ($path === null || !preg_match('#^products/[A-Za-z0-9_.-]+$#', $path)) {
            return;
        }
        $file = PUBLIC_PATH . '/uploads/' . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
