<?php
// API endpoint for authentication
// Handles login/logout for POS system

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';
require_once __DIR__ . '/../../app/models/User.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getJsonBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

$userModel = new User($pdo);

// Route handling
switch ($path) {
    case '/login':
        if ($method !== 'POST') {
            respond(405, ['error' => 'Method not allowed']);
        }
        
        $data = getJsonBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            respond(400, ['success' => false, 'message' => 'Username and password required']);
        }
        
        // Get user by username
        $user = $userModel->getUserByUsername($username);
        
        if (!$user) {
            respond(401, ['success' => false, 'message' => 'Invalid credentials']);
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            respond(401, ['success' => false, 'message' => 'Invalid credentials']);
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            respond(403, ['success' => false, 'message' => 'Account is inactive']);
        }
        
        // Only allow cashier and admin to access POS
        if (!in_array($user['role'], ['cashier', 'admin'])) {
            respond(403, ['success' => false, 'message' => 'Access denied. POS is only for cashiers and admins.']);
        }
        
        // Start session and store user data
        login($user);
        
        // Return user data (without password)
        unset($user['password']);
        respond(200, [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ]);
        break;
    
    case '/logout':
        if ($method !== 'POST') {
            respond(405, ['error' => 'Method not allowed']);
        }
        
        logout();
        respond(200, ['success' => true, 'message' => 'Logged out successfully']);
        break;
    
    case '/check':
        // Check if user is logged in
        if (isLoggedIn()) {
            $userId = getCurrentUserId();
            $user = $userModel->getUserById($userId);
            
            if ($user) {
                unset($user['password']);
                respond(200, ['success' => true, 'user' => $user]);
            }
        }
        
        respond(401, ['success' => false, 'message' => 'Not authenticated']);
        break;
    
    default:
        respond(404, ['error' => 'Not found']);
}
