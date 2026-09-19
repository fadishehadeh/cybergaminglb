<?php
declare(strict_types=1);

namespace App\Modules\Account;

/** Small presentation + validation helpers for the customer account area. */
final class AccountUi
{
    /** Only app-internal paths are accepted as a post-login destination (no open redirects). */
    public static function safeNext(mixed $next, string $default = '/account'): string
    {
        if (!is_string($next)) {
            return $default;
        }
        $next = trim($next);
        if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '://')
            || str_contains($next, '\\') || preg_match('/[\x00-\x1F\x7F]/', $next)) {
            return $default;
        }
        // never bounce back into the sign-in / sign-up forms
        $path = (string) parse_url($next, PHP_URL_PATH);
        if (in_array(rtrim($path, '/'), ['/account/login', '/account/register', '/account/logout'], true)) {
            return $default;
        }
        return $next;
    }

    /** Clean a single-line text field: trims, drops control characters, caps the length. */
    public static function clean(mixed $v, int $max): string
    {
        if (!is_string($v)) {
            return '';
        }
        $v = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $v) ?? '';
        return mb_substr(trim(preg_replace('/\s+/u', ' ', $v) ?? ''), 0, $max);
    }

    /** $12.50 (always two decimals: this is money in a wallet). */
    public static function amount(float|int|string|null $v): string
    {
        return '$' . number_format((float) $v, 2);
    }

    /** +$5.00 / -$3.50 */
    public static function signed(float|int|string|null $v): string
    {
        $v = (float) $v;
        return ($v < 0 ? '-' : '+') . '$' . number_format(abs($v), 2);
    }

    public static function date(?string $dt, bool $time = false): string
    {
        $ts = $dt ? strtotime($dt) : false;
        return $ts ? date($time ? 'j M Y, H:i' : 'j M Y', $ts) : '';
    }

    /**
     * Customer-language status of a sell / trade request. A rejected item stays 'rejected' after a 'new offer' choice
     * (we complete it), so the choice is needed to tell "please choose" from "waiting for us".
     * @return array{0:string,1:string} label, css modifier
     */
    public static function offerStatus(string $status, ?string $choice = null): array
    {
        if ($status === 'rejected' && $choice === 'new_offer') {
            return ['New offer chosen: we will complete it', 'info'];
        }
        return match ($status) {
            'rejected'         => ['We couldn\'t accept it: please choose', 'warn'],
            'return_pending'   => ['Being returned to you', 'warn'],
            'returned'         => ['Returned to you', 'done'],
            'recycled'         => ['Recycled', 'off'],
            'new', 'contacted' => ['Under review', 'info'],
            'offered'          => ['Offer ready', 'warn'],
            'accepted'         => ['Accepted', 'ok'],
            'collected'        => ['Games received', 'info'],
            'completed'        => ['Completed', 'done'],
            'declined'         => ['Declined', 'off'],
            'cancelled'        => ['Cancelled', 'off'],
            default            => [ucfirst($status), 'info'],
        };
    }

    /** @return array{0:string,1:string} label, css modifier */
    public static function orderStatus(string $status): array
    {
        return match ($status) {
            'new'       => ['Received', 'info'],
            'confirmed' => ['Confirmed', 'ok'],
            'picked_up' => ['On its way', 'warn'],
            'delivered' => ['Delivered', 'done'],
            'cancelled' => ['Cancelled', 'off'],
            default     => [ucfirst($status), 'info'],
        };
    }

    /** Payment state of an order that has to be paid before we ship. @return ?array{0:string,1:string} label, css modifier */
    public static function paymentStatus(?string $status): ?array
    {
        return match ($status) {
            'awaiting' => ['Awaiting your payment', 'warn'],
            'received' => ['Payment received', 'ok'],
            default    => null,
        };
    }

    public static function pill(string $label, string $mod): string
    {
        return '<span class="acc-pill acc-pill-' . e($mod) . '">' . e($label) . '</span>';
    }

    /** @return array<int, array<string,mixed>> decoded JSON list of items (never throws) */
    public static function decodeItems(?string $json): array
    {
        $data = $json !== null && $json !== '' ? json_decode($json, true) : null;
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $row) {
            if (is_array($row)) {
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * Adds 'label' and 'link' to wallet ledger rows, looking up the request / order codes in two queries
     * (only rows that belong to $userId are resolved).
     *
     * @param array<int, array<string,mixed>> $rows
     * @return array<int, array<string,mixed>>
     */
    public static function describeLedger(array $rows, int $userId): array
    {
        $offerIds = [];
        $orderIds = [];
        foreach ($rows as $r) {
            $refId = (int) ($r['ref_id'] ?? 0);
            if ($refId <= 0) {
                continue;
            }
            $refType = (string) ($r['ref_type'] ?? '');
            if (in_array($r['type'], ['offer_credit', 'payout_credit'], true) && str_contains($refType, 'buyback')) {
                $offerIds[$refId] = true;
            } elseif (in_array($r['type'], ['order_payment', 'order_refund'], true) && ($refType === '' || $refType === 'order' || $refType === 'orders')) {
                $orderIds[$refId] = true;
            }
        }
        $offerCodes = [];
        if ($offerIds) {
            $in = implode(',', array_fill(0, count($offerIds), '?'));
            foreach (db()->fetchAll("SELECT id, code FROM buyback_requests WHERE user_id = ? AND id IN ($in)", array_merge([$userId], array_keys($offerIds))) as $o) {
                $offerCodes[(int) $o['id']] = (string) $o['code'];
            }
        }
        $orderCodes = [];
        if ($orderIds) {
            $in = implode(',', array_fill(0, count($orderIds), '?'));
            foreach (db()->fetchAll("SELECT id, code FROM orders WHERE user_id = ? AND id IN ($in)", array_merge([$userId], array_keys($orderIds))) as $o) {
                $orderCodes[(int) $o['id']] = (string) $o['code'];
            }
        }

        foreach ($rows as &$r) {
            $refId = (int) ($r['ref_id'] ?? 0);
            $note = trim((string) ($r['note'] ?? ''));
            $label = '';
            $link = null;
            switch ($r['type']) {
                case 'offer_credit':
                    if (isset($offerCodes[$refId])) {
                        $label = 'Credit for your sell request ' . $offerCodes[$refId];
                        $link = '/account/offers/' . $offerCodes[$refId];
                    } else {
                        $label = 'Credit for your sell request';
                    }
                    break;
                case 'payout_credit':
                    $label = 'Credit from your sold games';
                    if (isset($offerCodes[$refId])) {
                        $label = 'Credit for your sell request ' . $offerCodes[$refId];
                        $link = '/account/offers/' . $offerCodes[$refId];
                    }
                    break;
                case 'order_payment':
                    $label = isset($orderCodes[$refId]) ? 'Order ' . $orderCodes[$refId] : 'Paid with credit at checkout';
                    $link = isset($orderCodes[$refId]) ? '/account/orders/' . $orderCodes[$refId] : null;
                    break;
                case 'order_refund':
                    $label = isset($orderCodes[$refId]) ? 'Refund for order ' . $orderCodes[$refId] : 'Order refund';
                    $link = isset($orderCodes[$refId]) ? '/account/orders/' . $orderCodes[$refId] : null;
                    break;
                case 'admin_adjust':
                    $label = $note !== '' ? $note : 'Adjustment by CyberGaming';
                    break;
                default:
                    $label = $note !== '' ? $note : 'Wallet entry';
            }
            $r['label'] = $label;
            $r['link'] = $link;
        }
        unset($r);
        return $rows;
    }

    /** Numbered pager using the storefront's .pagination styles. */
    public static function pager(int $page, int $pages, string $path): string
    {
        if ($pages <= 1) {
            return '';
        }
        $href = static fn (int $p): string => e(url($path . ($p > 1 ? '?page=' . $p : '')));
        $html = '<nav class="pagination" aria-label="Pages"><ul>';
        $html .= $page > 1
            ? '<li><a class="page-link" rel="prev" href="' . $href($page - 1) . '">Previous</a></li>'
            : '<li><span class="page-link is-disabled">Previous</span></li>';
        $from = max(1, $page - 2);
        $to = min($pages, $page + 2);
        for ($p = $from; $p <= $to; $p++) {
            $html .= $p === $page
                ? '<li><span class="page-link is-current" aria-current="page">' . $p . '</span></li>'
                : '<li><a class="page-link" href="' . $href($p) . '">' . $p . '</a></li>';
        }
        $html .= $page < $pages
            ? '<li><a class="page-link" rel="next" href="' . $href($page + 1) . '">Next</a></li>'
            : '<li><span class="page-link is-disabled">Next</span></li>';
        return $html . '</ul></nav>';
    }
}
