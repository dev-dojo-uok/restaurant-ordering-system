<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/auth.php';

class AuthController {
    private $userModel;

    public function __construct() {
        global $pdo;
        $this->userModel = new User($pdo);
    }

    public function register($data) {
        $errors = [];

        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters';
        } elseif ($this->userModel->usernameExists($data['username'])) {
            $errors[] = 'Username already taken';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif ($this->userModel->emailExists($data['email'])) {
            $errors[] = 'Email already registered';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if (empty($data['full_name'])) {
            $errors[] = 'Full name is required';
        }

        if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        $userData = [
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'password' => $data['password'],
            'full_name' => trim($data['full_name']),
            'role' => $data['role'] ?? 'customer',
            'phone' => trim($data['phone'] ?? ''),
            'address' => trim($data['address'] ?? '')
        ];

        $result = $this->userModel->create($userData);

        if ($result) {
            return ['success' => true, 'message' => 'Registration successful! Please login.'];
        }

        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }

    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required'];
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Your account has been deactivated'];
        }

        login($user);
        $redirectUrl = $this->getRedirectUrl($user['role']);

        return ['success' => true, 'message' => 'Login successful!', 'redirect' => $redirectUrl];
    }

    public function logout() {
        logout();
        return ['success' => true, 'message' => 'Logged out successfully', 'redirect' => '/login.php'];
    }

    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return '/admin/dashboard.php';
            case 'cashier':
                return '/cashier/dashboard.php';
            case 'kitchen':
                return '/kitchen/dashboard.php';
            case 'rider':
                return '/rider/dashboard.php';
            case 'customer':
                return '/index.php';
            default:
                return '/index.php';
        }
    }

    public function validateRegistration($data) {
        $errors = [];

        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        }

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        if (!empty($data['password']) && isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        }

        return $errors;
    }
}
