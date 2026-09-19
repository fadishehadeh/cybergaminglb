<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Controller;
use App\Core\Request;
use App\Modules\Admin\Forms;
use App\Support\Pricing;

/** Public seller pages: apply, thank-you, login, logout (they use the storefront layout). */
final class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    public function showApply(Request $request): void
    {
        if (auth()->hasRole('seller')) {
            $this->redirect('/seller');
        }
        $this->render('seller/apply', [
            'nav'        => 'sell',
            'commission' => Pricing::commissionPct(),
        ], 'site');
    }

    public function apply(Request $request): void
    {
        // honeypot: real people never fill this hidden field
        if ((string) $request->input('website', '') !== '') {
            $this->redirect('/');
        }

        $store    = Forms::text($request->input('store_name'));
        $contact  = Forms::text($request->input('contact_name'));
        $phone    = Forms::text($request->input('phone'));
        $area     = Forms::text($request->input('area'));
        $email    = strtolower(Forms::text($request->input('email')));
        $rawPass  = $request->input('password', '');
        $password = is_string($rawPass) ? $rawPass : '';
        $about    = Forms::text($request->input('about'));
        $terms    = $request->input('terms') === '1';
        $input    = [
            'store_name' => $store, 'contact_name' => $contact, 'phone' => $phone, 'area' => $area,
            'email' => $email, 'about' => $about, 'terms' => $terms ? '1' : '',
        ];

        $errors = [];
        if ($store === '' || mb_strlen($store) > 150) {
            $errors[] = 'Please enter your store or your name (max 150 characters).';
        }
        if ($contact === '' || mb_strlen($contact) > 120) {
            $errors[] = 'Please enter the contact person\'s name.';
        }
        $digits = strlen((string) preg_replace('/\D+/', '', $phone));
        if (!preg_match('/^[0-9+()\-\s]{6,40}$/', $phone) || $digits < 7 || $digits > 15) {
            $errors[] = 'Please enter a valid phone / WhatsApp number (7 to 15 digits).';
        }
        if ($area === '' || mb_strlen($area) > 120) {
            $errors[] = 'Please tell us your area (e.g. Beirut, Jounieh, Tripoli).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Choose a password of at least 8 characters.';
        }
        if (mb_strlen($about) < 10 || mb_strlen($about) > 2000) {
            $errors[] = 'Tell us what you sell (10 to 2000 characters).';
        }
        if (!$terms) {
            $errors[] = 'Please accept the seller terms to continue.';
        }
        if (!$errors && (int) db()->fetchValue('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0) {
            $errors[] = 'An account with this email already exists. Try signing in, or use another email.';
        }
        if ($errors) {
            $this->back('/seller/apply', implode("\n", $errors), $input);
        }

        try {
            db()->transaction(function () use ($store, $contact, $phone, $area, $email, $password, $about): void {
                $userId = db()->insert(
                    "INSERT INTO users (name, email, password_hash, role, status) VALUES (?, ?, ?, 'seller', 'disabled')",
                    [$contact, $email, password_hash($password, PASSWORD_DEFAULT)]
                );
                $notes = "Application via /seller/apply\nContact person: $contact\nWhat they sell: $about";
                db()->insert(
                    "INSERT INTO sellers (user_id, code, name, phone, area, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')",
                    [$userId, $this->newCode(), $store, $phone, $area, $notes]
                );
            });
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->back('/seller/apply', 'An account with this email already exists. Try signing in, or use another email.', $input);
            }
            throw $e;
        }

        $this->app->session()->put('seller_applied', $store);
        $this->redirect('/seller/apply/thanks');
    }

    public function thanks(Request $request): void
    {
        $store = $this->app->session()->get('seller_applied');
        if (!is_string($store)) {
            $this->redirect('/seller/apply');
        }
        $this->render('seller/thanks', ['store' => $store, 'nav' => 'sell'], 'site');
    }

    public function showLogin(Request $request): void
    {
        if (auth()->hasRole('seller')) {
            $this->redirect('/seller');
        }
        $this->render('seller/login', ['nav' => 'sell'], 'site');
    }

    public function login(Request $request): void
    {
        $email    = strtolower(Forms::text($request->input('email')));
        $rawPass  = $request->input('password', '');
        $password = is_string($rawPass) ? $rawPass : '';
        $ip       = $request->ip();

        // Always exits (redirects back with the message).
        $fail = function (string $message) use ($email): void {
            $this->back('/seller/login', $message, ['email' => $email]);
        };

        $recent = (int) db()->fetchValue(
            'SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR email = ?) AND created_at > (NOW() - INTERVAL ' . self::LOCK_MINUTES . ' MINUTE)',
            [$ip, $email]
        );
        if ($recent >= self::MAX_ATTEMPTS) {
            $fail('Too many failed attempts. Please wait ' . self::LOCK_MINUTES . ' minutes and try again.');
        }

        // Only seller accounts may sign in here (an admin password never opens the seller portal).
        $user = db()->fetch('SELECT id, password_hash, role, status FROM users WHERE email = ?', [$email]);
        if (!$user || $user['role'] !== 'seller' || !password_verify($password, $user['password_hash'])) {
            db()->execute('INSERT INTO login_attempts (ip, email, created_at) VALUES (?, ?, NOW())', [$ip, $email]);
            $fail('Wrong email or password.');
        }

        // Correct password: tell pending / suspended sellers exactly where they stand.
        $seller = db()->fetch('SELECT id, status FROM sellers WHERE user_id = ?', [$user['id']]);
        if ($seller === null) {
            $fail('This account is not linked to a seller profile. Please contact CyberGaming.');
        }
        if ($seller['status'] === 'pending') {
            $fail('Your account is awaiting approval. We will contact you on WhatsApp or email as soon as it is approved.');
        }
        if ($seller['status'] === 'suspended' || $user['status'] !== 'active') {
            $fail('Your seller account is suspended. Please contact CyberGaming.');
        }

        $error = auth()->attempt($email, $password, $ip);
        if ($error !== null) {
            $fail($error);
        }
        $this->redirect('/seller');
    }

    public function logout(Request $request): void
    {
        auth()->logout();
        $this->app->session()->flash('success', 'You have been signed out.');
        $this->redirect('/seller/login');
    }

    /** Anonymous public code: S-1234, unique. */
    private function newCode(): string
    {
        for ($i = 0; $i < 50; $i++) {
            $code = 'S-' . random_int(1000, 9999);
            if ((int) db()->fetchValue('SELECT COUNT(*) FROM sellers WHERE code = ?', [$code]) === 0) {
                return $code;
            }
        }
        return 'S-' . random_int(100000, 999999);
    }
}
