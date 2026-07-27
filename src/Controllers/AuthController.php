<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;

use function App\Helpers\clean_string;
use function App\Helpers\current_user;
use function App\Helpers\flash;
use function App\Helpers\rate_limit;
use function App\Helpers\redirect;
use function App\Helpers\valid_phone;
use function App\Helpers\verify_csrf_or_fail;
use function App\Helpers\rotate_csrf_token;
use function App\Helpers\view;

final class AuthController
{
    public function loginForm(): void
    {
        view('login', [
            'title' => "Login - Cheryne's",
            'description' => "Sign in to Cheryne's.",
        ]);
    }

    public function login(): void
    {
        verify_csrf_or_fail();

        if (!rate_limit('login', 6, 300)) {
            flash('danger', 'Too many login attempts. Please try again shortly.');
            redirect('/auth/login');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = (string) filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

        if (!$email || $password === '') {
            flash('danger', 'Invalid email or password.');
            redirect('/auth/login');
        }

        $user = User::findByEmail((string) $email);

        $isLocked = $user !== null && !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time();
        $isValid = $user !== null && !$isLocked && password_verify($password, (string) ($user['password_hash'] ?? ''));

        if (!$isValid) {
            if ($user !== null && !$isLocked) {
                User::recordFailedLogin((string) $email);
            }
            flash('danger', 'Invalid email or password.');
            redirect('/auth/login');
        }

        session_regenerate_id(true);
        rotate_csrf_token();
        User::resetFailedLogin((int) $user['id']);
        $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
        ];

        flash('success', 'Welcome back.');
        redirect(($user['role'] ?? '') === 'admin' ? '/admin' : '/');
    }

    public function registerForm(): void
    {
        view('register', [
            'title' => "Register - Cheryne's",
            'description' => "Create a Cheryne's customer account.",
            'errors' => [],
            'old' => [],
        ]);
    }

    public function register(): void
    {
        verify_csrf_or_fail();

        if (!rate_limit('register', 5, 600)) {
            flash('danger', 'Too many registration attempts. Please try again shortly.');
            redirect('/auth/register');
        }

        $name = clean_string(filter_input(INPUT_POST, 'name', FILTER_DEFAULT), 120);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $phone = clean_string(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT), 30);
        $password = (string) filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

        $errors = [];
        $old = compact('name', 'email', 'phone');

        if ($name === '') {
            $errors['name'] = 'Please enter your full name.';
        }
        if (!$email) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!valid_phone($phone)) {
            $errors['phone'] = 'Please enter a valid phone number (7–20 digits).';
        }
        if (!$this->strongPassword($password)) {
            $errors['password'] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.';
        }

        if (!empty($errors)) {
            view('register', [
                'title' => "Register - Cheryne's",
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        if (User::findByEmail((string) $email) !== null) {
            $errors['general'] = 'We could not create your account with the provided details. Please try again or contact support if you need help.';
            view('register', [
                'title' => "Register - Cheryne's",
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $user = User::create($name, (string) $email, $password, $phone);
        session_regenerate_id(true);
        rotate_csrf_token();
        $_SESSION['fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        
        $_SESSION['user'] = $user;
        flash('success', 'Your account is ready.');
        redirect('/');
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        redirect('/');
    }

    public function forgotPasswordForm(): void
    {
        view('forgot-password', ['title' => "Reset Password - Cheryne's", 'errors' => [], 'oldEmail' => '']);
    }

    public function forgotPassword(): void
    {
        verify_csrf_or_fail();
        flash('info', 'Password reset isn’t available yet. Please contact us directly to reset your password.');
        redirect('/auth/login');
    }

    public function resetPasswordForm(): void
    {
        view('reset-password', ['title' => "Reset Password - Cheryne's"]);
    }

    public function resetPassword(): void
    {
        verify_csrf_or_fail();
        flash('info', 'Password reset isn’t available yet. Please contact us directly to reset your password.');
        redirect('/auth/login');
    }

    private function strongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}