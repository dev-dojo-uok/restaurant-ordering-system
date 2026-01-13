<?php
/**
 * Kitchen Display System - API Endpoint
 * Handles AJAX requests for order status updates
 */

header('Content-Type: application/json');

// Start session and include required files
require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';
require_once '../../app/controllers/KitchenController.php';

// Check authentication
startSession();
$userRole = getCurrentUserRole();

if (!$userRole || !in_array($userRole, ['admin', 'kitchen'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (!isset($input['order_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$orderId = filter_var($input['order_id'], FILTER_VALIDATE_INT);
$action = trim($input['action']);

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Validate action
$validActions = ['start', 'finish', 'served'];
if (!in_array($action, $validActions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $kitchenController = new KitchenController($pdo);
    $result = $kitchenController->updateOrderStatus($orderId, $action);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Kitchen API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}
