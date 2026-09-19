<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public function __construct(private Session $session)
    {
    }

    public function token(): string
    {
        if (!$this->session->has('_csrf')) {
            $this->session->put('_csrf', bin2hex(random_bytes(32)));
        }
        return (string) $this->session->get('_csrf');
    }

    public function validate(?string $token): bool
    {
        return $token !== null && $token !== '' && hash_equals($this->token(), $token);
    }
}
