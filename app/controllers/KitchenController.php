<?php

class KitchenController {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get orders grouped by kitchen status
     * Maps database statuses to kitchen workflow stages
     */
    public function getKitchenOrders() {
        try {
            // Map statuses to kitchen stages
            $statusMap = [
                'new' => ['ordered'],
                'process' => ['under_preparation'],
                'ready' => ['ready_to_collect', 'ready_to_serve', 'ready_for_pickup']
            ];
            
            $result = [
                'new' => [],
                'process' => [],
                'ready' => [],
                'served' => []
            ];
            
            // Get NEW orders
            $stmt = $this->pdo->prepare("
                SELECT o.id, o.order_type, o.table_number, o.created_at, o.status,
                       o.customer_name, o.customer_phone,
                       COALESCE(u.full_name, o.customer_name) as customer,
                       COALESCE(
                           json_agg(
                               json_build_object(
                                   'name', COALESCE(mi.name, oi.item_name),
                                   'variant', v.variant_name,
                                   'quantity', oi.quantity
                               )
                           ) FILTER (WHERE oi.id IS NOT NULL),
                           '[]'::json
                       ) as items
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                LEFT JOIN menu_item_variants v ON oi.variant_id = v.id
                WHERE o.status = ANY(:statuses)
                GROUP BY o.id, u.full_name
                ORDER BY o.created_at ASC
            ");
            $stmt->execute(['statuses' => '{' . implode(',', $statusMap['new']) . '}']);
            $result['new'] = $stmt->fetchAll();
            
            // Get IN PROCESS orders
            $stmt->execute(['statuses' => '{' . implode(',', $statusMap['process']) . '}']);
            $result['process'] = $stmt->fetchAll();
            
            // Get READY orders
            $stmt->execute(['statuses' => '{' . implode(',', $statusMap['ready']) . '}']);
            $result['ready'] = $stmt->fetchAll();
            
            // Get SERVED/COMPLETED orders (today only)
            $stmt = $this->pdo->prepare("
                SELECT o.id, o.order_type, o.table_number, o.created_at, o.status,
                       o.customer_name, o.completed_at,
                       COALESCE(u.full_name, o.customer_name) as customer,
                       COALESCE(
                           json_agg(
                               json_build_object(
                                   'name', COALESCE(mi.name, oi.item_name),
                                   'variant', v.variant_name,
                                   'quantity', oi.quantity
                               )
                           ) FILTER (WHERE oi.id IS NOT NULL),
                           '[]'::json
                       ) as items
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                LEFT JOIN menu_item_variants v ON oi.variant_id = v.id
                WHERE o.status IN ('delivered', 'completed', 'collected')
                  AND DATE(o.completed_at) = CURRENT_DATE
                GROUP BY o.id, u.full_name
                ORDER BY o.completed_at DESC
                LIMIT 100
            ");
            $stmt->execute();
            $result['served'] = $stmt->fetchAll();
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Kitchen Orders Error: " . $e->getMessage());
            return ['new' => [], 'process' => [], 'ready' => [], 'served' => []];
        }
    }
    
    /**
     * Update order status based on kitchen action
     */
    public function updateOrderStatus($orderId, $action) {
        try {
            // Determine new status based on action
            $statusMap = [
                'start' => 'under_preparation',
                'finish' => 'ready_to_serve', // Default, will be adjusted based on order type
                'served' => 'completed'
            ];
            
            if (!isset($statusMap[$action])) {
                return ['success' => false, 'message' => 'Invalid action'];
            }
            
            // Get current order
            $stmt = $this->pdo->prepare("SELECT order_type, status FROM orders WHERE id = :id");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found'];
            }
            
            $newStatus = $statusMap[$action];
            
            // Adjust ready status based on order type
            if ($action === 'finish') {
                switch ($order['order_type']) {
                    case 'delivery':
                        $newStatus = 'ready_to_collect';
                        break;
                    case 'dine_in':
                        $newStatus = 'ready_to_serve';
                        break;
                    case 'takeaway':
                        $newStatus = 'ready_for_pickup';
                        break;
                }
            }
            
            // Adjust served/completed status
            if ($action === 'served') {
                switch ($order['order_type']) {
                    case 'delivery':
                        $newStatus = 'on_the_way'; // Move to delivery, not complete yet
                        break;
                    case 'dine_in':
                        $newStatus = 'completed';
                        break;
                    case 'takeaway':
                        $newStatus = 'collected';
                        break;
                }
            }
            
            // Update order
            $updateQuery = "UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP";
            
            // Set completed_at for final statuses
            if (in_array($newStatus, ['delivered', 'completed', 'collected'])) {
                $updateQuery .= ", completed_at = CURRENT_TIMESTAMP";
            }
            
            $updateQuery .= " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($updateQuery);
            $stmt->execute([
                'status' => $newStatus,
                'id' => $orderId
            ]);
            
            return [
                'success' => true,
                'message' => 'Order updated successfully',
                'new_status' => $newStatus
            ];
            
        } catch (PDOException $e) {
            error_log("Update Order Status Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error'];
        }
    }
    
    /**
     * Get order counts for dashboard
     */
    public function getOrderCounts() {
        try {
            $counts = [
                'new' => 0,
                'process' => 0,
                'ready' => 0,
                'served' => 0
            ];
            
            // Count by status
            $stmt = $this->pdo->query("
                SELECT 
                    CASE 
                        WHEN status = 'ordered' THEN 'new'
                        WHEN status = 'under_preparation' THEN 'process'
                        WHEN status IN ('ready_to_collect', 'ready_to_serve', 'ready_for_pickup') THEN 'ready'
                        WHEN status IN ('delivered', 'completed', 'collected') AND DATE(completed_at) = CURRENT_DATE THEN 'served'
                    END as stage,
                    COUNT(*) as count
                FROM orders
                WHERE status NOT IN ('cancelled', 'on_the_way')
                GROUP BY stage
            ");
            
            foreach ($stmt->fetchAll() as $row) {
                if ($row['stage']) {
                    $counts[$row['stage']] = $row['count'];
                }
            }
            
            return $counts;
            
        } catch (PDOException $e) {
            error_log("Get Order Counts Error: " . $e->getMessage());
            return ['new' => 0, 'process' => 0, 'ready' => 0, 'served' => 0];
        }
    }
    
    /**
     * Format elapsed time from order creation
     */
    public static function getElapsedTime($createdAt) {
        $created = new DateTime($createdAt);
        $now = new DateTime();
        $diff = $now->diff($created);
        
        if ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'm';
        } elseif ($diff->i > 0) {
            return $diff->i . 'm';
        } else {
            return 'Just now';
        }
    }
    
    /**
     * Format order type for display
     */
    public static function formatOrderType($type) {
        $types = [
            'dine_in' => 'Dine In',
            'delivery' => 'Delivery',
            'takeaway' => 'Takeaway'
        ];
        return $types[$type] ?? ucfirst($type);
    }
}
