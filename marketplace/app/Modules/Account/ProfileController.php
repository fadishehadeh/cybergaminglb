<?php
declare(strict_types=1);

namespace App\Modules\Account;

use App\Core\Request;
use App\Support\Phone;

final class ProfileController extends BaseController
{
    public function index(Request $request): void
    {
        $me = $this->me();
        $zones = $this->zoneNames();
        $current = (string) ($me['area'] ?? '');
        if ($current !== '' && !in_array($current, $zones, true)) {
            $zones[] = $current; // keep an area that is no longer in the delivery list selectable
        }
        $seller = db()->fetch('SELECT status FROM sellers WHERE user_id = ? ORDER BY id LIMIT 1', [(int) $me['id']]);

        $this->page('account/profile', [
            'me'     => $me,
            'zones'  => $zones,
            'seller' => $seller,
        ]);
    }

    public function update(Request $request): void
    {
        $me = $this->me();
        $name    = $this->str($request, 'name', 120);
        $phoneIn = $this->str($request, 'phone', 40);
        $area    = $this->str($request, 'area', 120);
        $address = $this->str($request, 'address', 255);
        $phone   = Phone::normalize($phoneIn);
        $input   = ['name' => $name, 'phone' => $phoneIn, 'area' => $area, 'address' => $address];

        $errors = [];
        if (mb_strlen($name) < 2) {
            $errors[] = 'Please enter your name (at least 2 characters).';
        }
        if (!Phone::isValid($phone)) {
            $errors[] = 'Please enter a valid phone number (with or without +961).';
        } elseif ((int) db()->fetchValue('SELECT COUNT(*) FROM users WHERE phone = ? AND id <> ?', [$phone, (int) $me['id']]) > 0) {
            $errors[] = 'Another account already uses this phone number.';
        }
        $zones = $this->zoneNames();
        $unchangedArea = $area !== '' && $area === (string) ($me['area'] ?? '');
        if ($area === '' || ($zones && !$unchangedArea && !in_array($area, $zones, true))) {
            $errors[] = 'Please choose your area.';
        }
        if ($errors) {
            $this->invalid('/account/profile', $errors, $input);
        }

        try {
            db()->execute(
                'UPDATE users SET name = ?, phone = ?, area = ?, address = ? WHERE id = ?',
                [$name, $phone, $area, $address !== '' ? $address : null, (int) $me['id']]
            );
        } catch (\PDOException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->invalid('/account/profile', ['Another account already uses this phone number.'], $input);
            }
            throw $e;
        }
        $this->app->session()->flash('success', 'Your details were saved.');
        $this->redirect('/account/profile');
    }

    public function password(Request $request): void
    {
        $me = $this->me();
        $current = $this->rawString($request, 'current_password');
        $new     = $this->rawString($request, 'new_password');
        $confirm = $this->rawString($request, 'confirm_password');

        $row = db()->fetch('SELECT password_hash FROM users WHERE id = ?', [(int) $me['id']]);
        $error = null;
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $error = 'Your current password is not correct.';
        } elseif (mb_strlen($new) < 8) {
            $error = 'The new password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'The two new passwords do not match.';
        } elseif ($new === $current) {
            $error = 'Choose a password different from your current one.';
        }
        if ($error !== null) {
            $this->invalid('/account/profile#password', [$error]);
        }

        db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), (int) $me['id']]);
        $this->app->session()->regenerate();
        $this->app->session()->flash('success', 'Your password was changed.');
        $this->redirect('/account/profile');
    }
}
