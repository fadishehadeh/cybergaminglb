<?php
declare(strict_types=1);

namespace App\Core;

/** Staff accounts only (admin, seller). Buyers check out as guests and never get an account. */
final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(private Database $db, private Session $session)
    {
    }

    public function user(): ?array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $id = $this->session->get('user_id');
            if ($id) {
                $user = $this->db->fetch('SELECT id, name, email, phone, area, address, role, status, credit_balance FROM users WHERE id = ?', [$id]);
                $this->user = ($user && $user['status'] === 'active') ? $user : null;
                if ($this->user === null) {
                    $this->session->remove('user_id');
                }
            }
        }
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user()['id'] ?? null;
    }

    public function hasRole(string ...$roles): bool
    {
        $user = $this->user();
        return $user !== null && in_array($user['role'], $roles, true);
    }

    /** Returns null on success, or an error message. */
    public function attempt(string $email, string $password, string $ip): ?string
    {
        $email = strtolower(trim($email));

        $recent = (int) $this->db->fetchValue(
            'SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR email = ?) AND created_at > (NOW() - INTERVAL ' . self::LOCK_MINUTES . ' MINUTE)',
            [$ip, $email]
        );
        if ($recent >= self::MAX_ATTEMPTS) {
            return 'Too many failed attempts. Please wait ' . self::LOCK_MINUTES . ' minutes and try again.';
        }

        $user = $this->db->fetch('SELECT * FROM users WHERE email = ?', [$email]);
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $this->db->execute('INSERT INTO login_attempts (ip, email, created_at) VALUES (?, ?, NOW())', [$ip, $email]);
            return 'Wrong email or password.';
        }

        $this->db->execute('DELETE FROM login_attempts WHERE email = ?', [$email]);
        $this->db->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$user['id']]);
        $this->session->regenerate();
        $this->session->put('user_id', (int) $user['id']);
        $this->loaded = false;
        return null;
    }

    public function logout(): void
    {
        $this->session->remove('user_id');
        $this->session->regenerate();
        $this->user = null;
        $this->loaded = false;
    }
}
