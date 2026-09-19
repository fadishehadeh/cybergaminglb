<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;

final class AccountController extends AdminController
{
    public function index(Request $request): void
    {
        $this->view('account/index', [
            'me' => db()->fetch('SELECT id, name, email, last_login_at FROM users WHERE id = ?', [auth()->id()]),
        ]);
    }

    public function update(Request $request): void
    {
        $current = $request->input('current_password', '');
        $new     = $request->input('new_password', '');
        $confirm = $request->input('confirm_password', '');
        $current = is_string($current) ? $current : '';
        $new     = is_string($new) ? $new : '';
        $confirm = is_string($confirm) ? $confirm : '';

        $row = db()->fetch('SELECT id, password_hash FROM users WHERE id = ?', [auth()->id()]);
        $errors = [];
        if (!$row || !password_verify($current, $row['password_hash'])) {
            $errors[] = 'Your current password is not correct.';
        }
        if (mb_strlen($new) < 10) {
            $errors[] = 'The new password must be at least 10 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'The new password and its confirmation do not match.';
        }
        if ($current !== '' && $new === $current) {
            $errors[] = 'Choose a password different from the current one.';
        }
        if ($errors) {
            $this->back('/admin/account', implode("\n", $errors));
        }

        db()->execute('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $row['id']]);
        $this->app->session()->regenerate();
        $this->ok('Password changed.');
        $this->redirect('/admin/account');
    }
}
