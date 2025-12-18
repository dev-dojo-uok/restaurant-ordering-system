<?php

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function login($user) {
    startSession();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    session_regenerate_id(true);
}

function logout() {
    startSession();
    $_SESSION = [];

    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    session_destroy();
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    startSession();
    return $_SESSION['role'] ?? null;
}

function getCurrentUserName() {
    startSession();
    return $_SESSION['full_name'] ?? null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($allowedRoles, $redirectTo = '/login.php') {
    requireLogin();

    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    $userRole = getCurrentUserRole();

    if (!in_array($userRole, $allowedRoles)) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

function hasRole($role) {
    return getCurrentUserRole() === $role;
}

function isAdmin() { return hasRole('admin'); }
function isCashier() { return hasRole('cashier'); }
function isKitchen() { return hasRole('kitchen'); }
function isRider() { return hasRole('rider'); }
function isCustomer() { return hasRole('customer'); }

function redirectToDashboard() {
    $role = getCurrentUserRole();

    switch ($role) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'cashier':
            header('Location: /cashier/dashboard.php');
            break;
        case 'kitchen':
            header('Location: /kitchen/dashboard.php');
            break;
        case 'rider':
            header('Location: /rider/dashboard.php');
            break;
        case 'customer':
            header('Location: /index.php');
            break;
        default:
            header('Location: /login.php');
    }
    exit;
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        redirectToDashboard();
    }
}

function getUserSession() {
    startSession();
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email' => $_SESSION['email'] ?? null,
    ];
}
