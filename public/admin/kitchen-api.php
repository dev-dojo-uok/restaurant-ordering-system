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
$validActions = ['start', 'finish', 'served', 'assign_rider'];
if (!in_array($action, $validActions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Handle rider assignment separately
if ($action === 'assign_rider') {
    if (!isset($input['rider_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Rider ID required']);
        exit;
    }
    
    $riderId = filter_var($input['rider_id'], FILTER_VALIDATE_INT);
    if (!$riderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid rider ID']);
        exit;
    }
    
    try {
        // Assign rider to order
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET rider_id = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND order_type = 'delivery' AND status = 'ready_to_collect'
        ");
        $stmt->execute([$riderId, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Rider assigned successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Order not found or invalid status']);
        }
        exit;
    } catch (PDOException $e) {
        error_log("Rider Assignment Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
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
