<?php
session_start();
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/helpers/auth.php';

// Require login and check if rider
requireLogin();
$riderId = getCurrentUserId();

// Get rider details
$stmt = $pdo->prepare("SELECT username, full_name FROM users WHERE id = ?");
$stmt->execute([$riderId]);
$rider = $stmt->fetch(PDO::FETCH_ASSOC);

// Get pending delivery orders assigned to this rider
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        COALESCE(o.customer_name, u.full_name) AS customer_name,
        COALESCE(o.customer_phone, u.phone) AS customer_phone,
        o.delivery_address,
        o.total_amount,
        o.status,
        o.created_at,
        o.notes
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.rider_id = ? 
    AND o.order_type = 'delivery'
    AND o.status IN ('ready_to_collect', 'on_the_way')
    ORDER BY o.created_at ASC
");
$stmt->execute([$riderId]);
$pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order items for each order
foreach ($pendingOrders as &$order) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Dashboard - Food Ordering System</title>
    <link rel="stylesheet" href="assets/css/rider.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <div class="header-left">
                <button class="menu-btn" id="menuBtn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <h1 class="rider-name"><?php echo htmlspecialchars($rider['full_name'] ?? $rider['username']); ?></h1>
            </div>
            <div class="header-right">
                <button class="completed-btn" id="viewCompletedBtn">Completed</button>
            </div>
        </header>

        <!-- Page Title -->
        <div class="page-title">
            <h2>Rider Dashboard</h2>
        </div>

        <!-- Orders Grid -->
        <div class="orders-grid" id="ordersGrid">
            <?php if (empty($pendingOrders)): ?>
                <div class="no-orders" id="noOrders">
                    <p>No pending deliveries at the moment!</p>
                </div>
            <?php else: ?>
                <?php foreach ($pendingOrders as $order): ?>
                    <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                        <div class="order-header">
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>
                        <div class="order-body">
                            <div class="order-details">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? ''); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></p>
                                <p><strong>Amount:</strong> Rs. <?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
                                <?php if (!empty($order['notes'])): ?>
                                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="order-items">
                                <strong>Items:</strong>
                                <?php foreach ($order['items'] as $item): ?>
                                    <p><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['item_name']); ?> 
                                    <?php if ($item['variant_name'] !== 'Regular'): ?>
                                        (<?php echo htmlspecialchars($item['variant_name']); ?>)
                                    <?php endif; ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php if ($order['status'] === 'ready_to_collect'): ?>
                            <button class="pickup-btn" onclick="markPickedUp(<?php echo $order['id']; ?>)">
                                PICKED UP
                            </button>
                        <?php else: ?>
                            <button class="finish-btn" onclick="markDelivered(<?php echo $order['id']; ?>)">
                                DELIVERED
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- No Orders Message (hidden initially if there are orders) -->
        <?php if (!empty($pendingOrders)): ?>
            <div class="no-orders" id="noOrders" style="display: none;">
                <p>No pending deliveries at the moment!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Orders Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-header">
                <h2>Completed Orders</h2>
                <button class="close-modal" id="closeModal">&times;</button>
            </div>
            <div class="modal-body" id="completedOrdersList">
                <p class="no-completed" id="noCompleted">Loading...</p>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Menu</h3>
            <button class="close-sidebar" id="closeSidebar">&times;</button>
        </div>
        <nav class="sidebar-nav">
            <a href="rider.php" class="active">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <script src="assets/js/rider.js"></script>
</body>
</html>
