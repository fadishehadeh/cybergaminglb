<?php
declare(strict_types=1);

namespace App\Modules\Admin;

use App\Core\Request;

final class AuthController extends AdminController
{
    public function showLogin(Request $request): void
    {
        if (auth()->hasRole('admin')) {
            $this->redirect('/admin');
        }
        $this->render('admin/login', [], null);
    }

    public function login(Request $request): void
    {
        $email    = $this->str($request, 'email');
        $rawPass  = $request->input('password', '');
        $password = is_string($rawPass) ? $rawPass : '';

        if ($email === '' || $password === '') {
            $this->back('/admin/login', 'Please enter your email and password.', ['email' => $email]);
        }

        $error = auth()->attempt($email, $password, $request->ip());
        if ($error !== null) {
            $this->back('/admin/login', $error, ['email' => $email]);
        }

        if (!auth()->hasRole('admin')) {
            auth()->logout();
            $this->back('/admin/login', 'This account does not have admin access.', ['email' => $email]);
        }

        $this->redirect('/admin');
    }

    public function logout(Request $request): void
    {
        auth()->logout();
        $this->ok('You have been signed out.');
        $this->redirect('/admin/login');
    }
}
