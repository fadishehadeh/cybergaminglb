<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Support\Phone;

/** Customer registration, sign-in and sign-out (storefront layout). */
final class AuthController extends BaseController
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;
    private const MAX_REGISTRATIONS_PER_HOUR = 10;
    /** bcrypt hash of a random string: verified against when the account does not exist, to keep timing similar. */
    private const DUMMY_HASH = '$2y$10$u51SHMkug2hbJldPeMi3suXGvmeY.kRBiQP/xSu2qSXLH1qmBkvO.';

    // ---------- register ----------

    public function showRegister(Request $request): void
    {
        if (auth()->hasRole('customer')) {
            $this->redirect(AccountUi::safeNext($request->query('next')));
        }
        $this->page('account/register', [
            'zones' => $this->zoneNames(),
            'next'  => AccountUi::safeNext($request->query('next'), ''),
        ]);
    }

    public function register(Request $request): void
    {
        $next = AccountUi::safeNext($request->input('next'), '');
        $back = '/account/register' . ($next !== '' ? '?next=' . rawurlencode($next) : '');

        // honeypot: real people never see or fill this field
        if ($this->str($request, 'website', 100) !== '') {
            $this->redirect('/');
        }

        $name    = $this->str($request, 'name', 120);
        $phoneIn = $this->str($request, 'phone', 40);
        $email   = strtolower($this->str($request, 'email', 190));
        $area    = $this->str($request, 'area', 120);
        $address = $this->str($request, 'address', 255);
        $pass    = $this->rawString($request, 'password');
        $confirm = $this->rawString($request, 'password_confirm');
        $terms   = $request->input('terms') === '1';
        $phone   = Phone::normalize($phoneIn);
        $input   = ['name' => $name, 'phone' => $phoneIn, 'email' => $email, 'area' => $area, 'address' => $address, 'terms' => $terms ? '1' : ''];

        $session = $this->app->session();
        $times = array_values(array_filter((array) $session->get('acct_reg_times', []), static fn ($t): bool => is_int($t) && $t > time() - 3600));
        if (count($times) >= self::MAX_REGISTRATIONS_PER_HOUR) {
            $this->invalid($back, ['Too many accounts were created from this device. Please try again in a little while.'], $input);
        }

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Please enter your name (at least 2 characters).';
        }
        if (!Phone::isValid($phone)) {
            $errors[] = 'Please enter a valid phone number (with or without +961), e.g. 70 123 456.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (mb_strlen($pass) < 8) {
            $errors[] = 'Choose a password of at least 8 characters.';
        } elseif ($pass !== $confirm) {
            $errors[] = 'The two passwords do not match.';
        }
        $zones = $this->zoneNames();
        if ($area === '' || ($zones && !in_array($area, $zones, true))) {
            $errors[] = 'Please choose your area so we can quote delivery.';
        }
        if (!$terms) {
            $errors[] = 'Please accept the terms to create your account.';
        }
        if (!$errors) {
            if ((int) db()->fetchValue('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0) {
                $errors[] = 'We could not create an account with those details. If you already have one, sign in instead.';
            }
            if (Phone::isValid($phone) && (int) db()->fetchValue('SELECT COUNT(*) FROM users WHERE phone = ?', [$phone]) > 0) {
                $errors[] = 'We could not create an account with those details. If you already have one, sign in instead.';
            }
        }
        if ($errors) {
            $this->invalid($back, $errors, $input);
        }

        try {
            $newUserId = db()->insert(
                "INSERT INTO users (name, email, phone, area, address, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active')",
                [$name, $email, $phone, $area, $address !== '' ? $address : null, password_hash($pass, PASSWORD_DEFAULT)]
            );
            \App\Support\Alias::ensure($newUserId);
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->invalid($back, ['We could not create an account with those details. If you already have one, sign in instead.'], $input);
            }
            throw $e;
        }

        $times[] = time();
        $session->put('acct_reg_times', $times);

        $error = auth()->attempt($email, $pass, $request->ip());
        if ($error !== null) {
            $session->flash('success', 'Your account is ready. Please sign in.');
            $this->redirect('/account/login' . ($next !== '' ? '?next=' . rawurlencode($next) : ''));
        }
        $session->flash('success', 'Welcome to CyberGaming, ' . explode(' ', $name)[0] . '! Your account is ready.');
        $this->redirect(AccountUi::safeNext($next));
    }

    // ---------- login / logout ----------

    public function showLogin(Request $request): void
    {
        if (auth()->hasRole('customer')) {
            $this->redirect(AccountUi::safeNext($request->query('next')));
        }
        $this->page('account/login', ['next' => AccountUi::safeNext($request->query('next'), '')]);
    }

    public function login(Request $request): void
    {
        $identifier = $this->str($request, 'identifier', 190);
        $password   = $this->rawString($request, 'password');
        $next       = AccountUi::safeNext($request->input('next'), '');
        $ip         = $request->ip();
        $back       = '/account/login' . ($next !== '' ? '?next=' . rawurlencode($next) : '');
        $fail       = function (string $message) use ($back, $identifier): void {
            $this->back($back, $message, ['identifier' => $identifier]);
        };

        if ($identifier === '' || $password === '') {
            $fail('Please enter your email or phone number and your password.');
        }

        $isEmail = str_contains($identifier, '@');
        $key     = $isEmail ? strtolower($identifier) : Phone::normalize($identifier);

        $recent = (int) db()->fetchValue(
            'SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR email = ?) AND created_at > (NOW() - INTERVAL ' . self::LOCK_MINUTES . ' MINUTE)',
            [$ip, $key]
        );
        if ($recent >= self::MAX_ATTEMPTS) {
            $fail('Too many failed attempts. Please wait ' . self::LOCK_MINUTES . ' minutes and try again.');
        }

        $user = null;
        if ($isEmail) {
            $user = db()->fetch('SELECT email, password_hash, role, status FROM users WHERE email = ?', [$key]);
        } elseif (Phone::isValid($key)) {
            $user = db()->fetch('SELECT email, password_hash, role, status FROM users WHERE phone = ?', [$key]);
        }

        if ($user === null) {
            password_verify($password, self::DUMMY_HASH);
            db()->execute('INSERT INTO login_attempts (ip, email, created_at) VALUES (?, ?, NOW())', [$ip, mb_substr($key, 0, 190)]);
            $fail('Wrong email/phone or password.');
        }

        if ($user['role'] !== 'customer') {
            // Never open a staff session from here. Only tell the person once the password is right.
            if (password_verify($password, $user['password_hash'])) {
                $fail('This sign-in is for customer accounts. Please use the admin or seller sign-in instead.');
            }
            db()->execute('INSERT INTO login_attempts (ip, email, created_at) VALUES (?, ?, NOW())', [$ip, mb_substr($key, 0, 190)]);
            $fail('Wrong email/phone or password.');
        }

        $error = auth()->attempt((string) $user['email'], $password, $ip);
        if ($error !== null) {
            $fail($error === 'Wrong email or password.' ? 'Wrong email/phone or password.' : $error);
        }
        $this->redirect(AccountUi::safeNext($next));
    }

    public function logout(Request $request): void
    {
        auth()->logout();
        $this->app->session()->flash('success', 'You have been signed out.');
        $this->redirect('/');
    }
}
