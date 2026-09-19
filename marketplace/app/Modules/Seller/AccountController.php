<?php
declare(strict_types=1);

namespace App\Modules\Seller;

use App\Core\Request;
use App\Modules\Admin\Forms;
use App\Support\Pricing;

final class AccountController extends PortalController
{
    public function index(Request $request): void
    {
        $seller = $this->seller();
        $user   = db()->fetch('SELECT email FROM users WHERE id = ?', [auth()->id()]);
        $this->view('account', [
            'email'      => $user['email'] ?? '',
            'commission' => Pricing::commissionPct($seller),
            'isOverride' => $seller['commission_pct'] !== null,
        ]);
    }

    /** Profile: name, phone, area, payout method. Never commission, status or code. */
    public function update(Request $request): void
    {
        $seller = $this->seller();
        $errors = [];

        $name = $this->str($request, 'name');
        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = 'Name is required (max 150 characters).';
        }
        $phone = $this->str($request, 'phone');
        $digits = strlen((string) preg_replace('/\D+/', '', $phone));
        if (!preg_match('/^[0-9+()\-\s]{6,40}$/', $phone) || $digits < 7 || $digits > 15) {
            $errors[] = 'Please enter a valid phone / WhatsApp number (7 to 15 digits).';
        }
        $area = $this->str($request, 'area');
        if (mb_strlen($area) > 120) {
            $errors[] = 'Area is too long (max 120 characters).';
        }
        $payout = $this->str($request, 'payout_method');
        if (mb_strlen($payout) > 120) {
            $errors[] = 'Payout method is too long (max 120 characters).';
        }
        if ($errors) {
            $this->invalid('/seller/account', $errors, $request);
        }

        db()->execute(
            'UPDATE sellers SET name = ?, phone = ?, area = ?, payout_method = ? WHERE id = ?',
            [$name, $phone, $area !== '' ? $area : null, $payout !== '' ? $payout : null, $seller['id']]
        );
        $this->ok('Your details were saved.');
        $this->redirect('/seller/account');
    }

    public function password(Request $request): void
    {
        $this->seller();
        $current = $request->input('current_password', '');
        $new     = $request->input('new_password', '');
        $confirm = $request->input('confirm_password', '');
        $current = is_string($current) ? $current : '';
        $new     = is_string($new) ? $new : '';
        $confirm = is_string($confirm) ? $confirm : '';

        $user = db()->fetch('SELECT id, password_hash FROM users WHERE id = ?', [auth()->id()]);
        $error = null;
        if (!$user || !password_verify($current, $user['password_hash'])) {
            $error = 'Your current password is not correct.';
        } elseif (mb_strlen($new) < 10) {
            $error = 'The new password must be at least 10 characters.';
        } elseif ($new !== $confirm) {
            $error = 'The two new passwords do not match.';
        } elseif ($new === $current) {
            $error = 'Choose a password different from your current one.';
        }
        if ($error !== null) {
            $this->back('/seller/account#password', $error);
        }

        db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        $this->app->session()->regenerate();
        $this->ok('Your password was changed.');
        $this->redirect('/seller/account');
    }
}
