<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$riderId = getCurrentUserId();

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'mark_picked_up':
        markPickedUp($riderId);
        break;
    
    case 'mark_delivered':
        markDelivered($riderId);
        break;
    
    case 'get_completed':
        getCompletedOrders($riderId);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function markPickedUp($riderId) {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }
    
    // Verify order belongs to this rider and is in correct status
    $stmt = $pdo->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND rider_id = ? AND status = 'ready_to_collect'
    ");
    $stmt->execute([$orderId, $riderId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found or invalid status']);
        return;
    }
    
    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'on_the_way', updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    if ($stmt->execute([$orderId])) {
        echo json_encode(['success' => true, 'message' => 'Order marked as picked up']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update order']);
    }
}

function markDelivered($riderId) {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    $orderId = $data['order_id'] ?? null;
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        return;
    }
    
    // Verify order belongs to this rider and is in correct status
    $stmt = $pdo->prepare("
        SELECT id FROM orders 
        WHERE id = ? AND rider_id = ? AND status = 'on_the_way'
    ");
    $stmt->execute([$orderId, $riderId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Order not found or invalid status']);
        return;
    }
    
    // Update order status
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = 'delivered', 
            completed_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP,
            payment_status = 'completed'
        WHERE id = ?
    ");
    
    if ($stmt->execute([$orderId])) {
        echo json_encode(['success' => true, 'message' => 'Order marked as delivered']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update order']);
    }
}

function getCompletedOrders($riderId) {
    global $pdo;
    // Get completed orders for this rider from today
    $stmt = $pdo->prepare("
        SELECT 
            o.id,
            o.customer_name,
            o.customer_phone,
            o.delivery_address,
            o.total_amount,
            o.completed_at,
            o.notes
        FROM orders o
        WHERE o.rider_id = ? 
        AND o.status = 'delivered'
        AND DATE(o.completed_at) = CURRENT_DATE
        ORDER BY o.completed_at DESC
    ");
    $stmt->execute([$riderId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get order items for each order
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT 
                oi.quantity,
                mi.name as item_name,
                miv.variant_name
            FROM order_items oi
            JOIN menu_item_variants miv ON oi.variant_id = miv.id
            JOIN menu_items mi ON miv.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}
