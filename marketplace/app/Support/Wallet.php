<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Customer credit wallet (1 credit = 1 USD). The credit_ledger table is the source of truth and is append-only;
 * users.credit_balance is a cache updated in the SAME transaction under a row lock, so two simultaneous
 * purchases can never spend the same credit twice. Never write to credit_ledger or credit_balance anywhere else.
 */
final class Wallet
{
    private const TYPES = ['offer_credit', 'payout_credit', 'order_payment', 'order_refund', 'admin_adjust'];

    public static function balance(int $userId): float
    {
        return (float) db()->fetchValue('SELECT credit_balance FROM users WHERE id = ?', [$userId]);
    }

    /** Add credit. Returns the new balance. */
    public static function credit(int $userId, float $amount, string $type, ?string $refType = null, ?int $refId = null, ?string $note = null, ?int $adminId = null): float
    {
        return self::post($userId, self::positive($amount), $type, $refType, $refId, $note, $adminId);
    }

    /** Spend credit. Throws InsufficientCreditException when the balance is too low. Returns the new balance. */
    public static function debit(int $userId, float $amount, string $type, ?string $refType = null, ?int $refId = null, ?string $note = null, ?int $adminId = null): float
    {
        return self::post($userId, -self::positive($amount), $type, $refType, $refId, $note, $adminId);
    }

    /** Staff correction (positive or negative). A note is required. */
    public static function adjust(int $userId, float $signedAmount, string $note, int $adminId): float
    {
        if (trim($note) === '') {
            throw new \InvalidArgumentException('An adjustment needs a reason.');
        }
        $signedAmount = round($signedAmount, 2);
        if ($signedAmount == 0.0 || abs($signedAmount) > 100000) {
            throw new \InvalidArgumentException('Invalid adjustment amount.');
        }
        return self::post($userId, $signedAmount, 'admin_adjust', null, null, $note, $adminId);
    }

    /** True when a ledger row of this type already exists for the reference (use for idempotency). */
    public static function hasEntry(string $type, string $refType, int $refId, ?int $userId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM credit_ledger WHERE type = ? AND ref_type = ? AND ref_id = ?';
        $params = [$type, $refType, $refId];
        if ($userId !== null) {
            $sql .= ' AND user_id = ?';
            $params[] = $userId;
        }
        return (int) db()->fetchValue($sql, $params) > 0;
    }

    public static function history(int $userId, int $limit = 50, int $offset = 0): array
    {
        return db()->fetchAll(
            'SELECT id, amount, type, ref_type, ref_id, note, balance_after, created_at
               FROM credit_ledger WHERE user_id = ? ORDER BY id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            [$userId]
        );
    }

    /** Total credit we owe all customers. */
    public static function totalLiability(): float
    {
        return (float) db()->fetchValue('SELECT COALESCE(SUM(credit_balance), 0) FROM users WHERE role = \'customer\'');
    }

    /** Customers whose cached balance differs from the ledger sum. Should always be empty. */
    public static function audit(): array
    {
        return db()->fetchAll(
            'SELECT u.id, u.name, u.credit_balance AS cached, COALESCE(SUM(l.amount), 0) AS ledger
               FROM users u LEFT JOIN credit_ledger l ON l.user_id = u.id
              GROUP BY u.id, u.name, u.credit_balance
             HAVING ROUND(u.credit_balance, 2) <> ROUND(COALESCE(SUM(l.amount), 0), 2)'
        );
    }

    private static function positive(float $amount): float
    {
        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > 100000) {
            throw new \InvalidArgumentException('Amount must be between 0.01 and 100000.');
        }
        return $amount;
    }

    private static function post(int $userId, float $signed, string $type, ?string $refType, ?int $refId, ?string $note, ?int $adminId): float
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Unknown wallet entry type: $type");
        }
        return (float) db()->transaction(static function ($db) use ($userId, $signed, $type, $refType, $refId, $note, $adminId) {
            $row = $db->fetch('SELECT credit_balance FROM users WHERE id = ? FOR UPDATE', [$userId]);
            if ($row === null) {
                throw new \InvalidArgumentException('Unknown user.');
            }
            $new = round((float) $row['credit_balance'] + $signed, 2);
            if ($new < 0) {
                throw new InsufficientCreditException('Not enough credit.');
            }
            $db->execute(
                'INSERT INTO credit_ledger (user_id, amount, type, ref_type, ref_id, note, balance_after, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$userId, $signed, $type, $refType, $refId, $note !== null ? mb_substr($note, 0, 255) : null, $new, $adminId]
            );
            $db->execute('UPDATE users SET credit_balance = ? WHERE id = ?', [$new, $userId]);
            return $new;
        });
    }
}
