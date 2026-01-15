<?php 
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';

startSession();
requireLogin();

$userId = getCurrentUserId();
$userName = getCurrentUserName();

// Fetch user's orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
            COUNT(oi.id) as item_count,
            json_agg(
                json_build_object(
                    'item_name', oi.item_name,
                    'quantity', oi.quantity,
                    'price', oi.price
                ) ORDER BY oi.id
            ) as items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = :user_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 50
    ");
    $stmt->execute(['user_id' => $userId]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Flavor POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4757;
            --primary-dark: #E8414F;
            --accent: #2ED573;
            --dark: #2F3542;
            --text-grey: #747D8C;
            --bg-body: #F1F2F6;
            --white: #FFFFFF;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
            --radius: 20px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--dark); }

        /* PAGE CONTENT */
        .page-header {
            max-width: 1200px;
            margin: 40px auto 30px;
            padding: 0 5%;
        }

        .page-header h1 {
            font-size: 36px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--text-grey);
            font-size: 16px;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 5% 60px;
        }

        .order-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--bg-body);
        }

        .order-id {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }

        .order-date {
            color: var(--text-grey);
            font-size: 14px;
        }

        .order-status {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-ordered { background: #dff9fb; color: #22a6b3; }
        .status-preparing { background: #ffeaa7; color: #fdcb6e; }
        .status-ready { background: #dfe6e9; color: #636e72; }
        .status-completed { background: #d4edda; color: #28a745; }
        .status-cancelled { background: #f8d7da; color: #dc3545; }

        .order-items {
            margin-bottom: 20px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: var(--text-grey);
            font-size: 14px;
        }

        .order-item-name {
            font-weight: 600;
            color: var(--dark);
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 2px dashed #eee;
        }

        .order-total {
            font-size: 22px;
            font-weight: 800;
            color: var(--primary);
        }

        .order-type {
            background: var(--bg-body);
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-grey);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 20px;
            background: var(--primary);
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
            box-shadow: 0 8px 20px rgba(255, 71, 87, 0.3);
        }

        .empty-state a:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="page-header">
    <h1>My Orders</h1>
    <p>View and track all your orders</p>
</div>

<div class="orders-container">
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-receipt"></i>
            <h2>No Orders Yet</h2>
            <p>Start ordering delicious food from our menu!</p>
            <a href="/menu.php"><i class="fas fa-arrow-right"></i> Browse Menu</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): 
            $items = json_decode($order['items'], true) ?? [];
            $statusClass = 'status-' . strtolower($order['status']);
        ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                    <div class="order-date">
                        <i class="far fa-calendar"></i> 
                        <?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?>
                    </div>
                </div>
                <span class="order-status <?php echo $statusClass; ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="order-items">
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <span class="order-item-name">
                            <?php echo htmlspecialchars($item['item_name']); ?> 
                            <span style="color: var(--text-grey);">×<?php echo $item['quantity']; ?></span>
                        </span>
                        <span>Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-footer">
                <span class="order-type">
                    <i class="fas fa-<?php echo $order['order_type'] === 'delivery' ? 'truck' : 'store'; ?>"></i>
                    <?php echo ucfirst($order['order_type']); ?>
                </span>
                <span class="order-total">Rs. <?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
