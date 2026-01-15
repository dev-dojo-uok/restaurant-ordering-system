<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

// Check if user is admin
requireRole('admin', '../index.php');

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_status') {
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $_SESSION['success'] = "Order status updated successfully!";
        }
        
        elseif ($action === 'update_payment') {
            $paymentStatus = $_POST['payment_status'];
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$paymentStatus, $orderId]);
            $_SESSION['success'] = "Payment status updated successfully!";
        }
        
        elseif ($action === 'update_item_quantity') {
            $itemId = $_POST['item_id'];
            $quantity = max(1, intval($_POST['quantity']));
            
            // Update quantity
            $stmt = $pdo->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $itemId]);
            
            // Recalculate order total
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET total_amount = (SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?)
                WHERE id = ?
            ");
            $stmt->execute([$orderId, $orderId]);
            
            $_SESSION['success'] = "Item quantity updated!";
        }
        
        elseif ($action === 'remove_item') {
            $itemId = $_POST['item_id'];
            
            // Check if order has more than one item
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $itemCount = $stmt->fetchColumn();
            
            if ($itemCount > 1) {
                $stmt = $pdo->prepare("DELETE FROM order_items WHERE id = ?");
                $stmt->execute([$itemId]);
                
                // Recalculate order total
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET total_amount = (SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?)
                    WHERE id = ?
                ");
                $stmt->execute([$orderId, $orderId]);
                
                $_SESSION['success'] = "Item removed from order!";
            } else {
                $_SESSION['error'] = "Cannot remove the last item. Cancel the order instead.";
            }
        }
        
        elseif ($action === 'update_notes') {
            $notes = $_POST['notes'];
            $stmt = $pdo->prepare("UPDATE orders SET notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$notes, $orderId]);
            $_SESSION['success'] = "Notes updated successfully!";
        }
        
        elseif ($action === 'update_delivery_address') {
            $address = $_POST['delivery_address'];
            $stmt = $pdo->prepare("UPDATE orders SET delivery_address = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$address, $orderId]);
            $_SESSION['success'] = "Delivery address updated!";
        }
        
        header("Location: order-details.php?id=$orderId");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Fetch order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, 
               u.full_name as customer_name, 
               u.username, 
               u.phone as customer_phone,
               r.full_name as rider_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users r ON o.rider_id = r.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Fetch order items with menu item details
    $stmt = $pdo->prepare("
        SELECT oi.*, 
               mi.name as item_name, 
               mi.image_url,
               miv.variant_name
        FROM order_items oi
        LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
        LEFT JOIN menu_item_variants miv ON oi.variant_id = miv.id
        WHERE oi.order_id = ?
        ORDER BY oi.id
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();
    
    // Fetch payment transactions
    $stmt = $pdo->prepare("
        SELECT * FROM payment_transactions 
        WHERE order_id = ?
        ORDER BY created_at
    ");
    $stmt->execute([$orderId]);
    $paymentTransactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error loading order: " . $e->getMessage());
}

$pageTitle = "Order #" . $orderId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-details-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 1024px) {
            .order-details-container {
                grid-template-columns: 1fr;
            }
        }
        
        .details-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .details-card h3 {
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            color: #333;
            font-size: 18px;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .order-items-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .order-items-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        
        .item-info h4 {
            margin: 0 0 5px 0;
            font-size: 15px;
            color: #333;
        }
        
        .item-variant {
            font-size: 13px;
            color: #6c757d;
        }
        
        .qty-input {
            width: 60px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .btn-icon {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            color: #6c757d;
            font-size: 16px;
            transition: color 0.2s;
        }
        
        .btn-icon:hover {
            color: #007bff;
        }
        
        .btn-icon.danger:hover {
            color: #dc3545;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-preparing { background: #d4edda; color: #155724; }
        .status-ready { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-pending { background: #fff3cd; color: #856404; }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 18px;
            font-weight: 700;
            color: #333;
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
        }
        
        .form-select, .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-update {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }
        
        .btn-update:hover {
            background: #0056b3;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include '_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-header">
            <div>
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <p class="subtitle">View and edit order details</p>
            </div>
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="order-details-container">
            <!-- Left Column: Order Items -->
            <div class="details-card">
                <h3><i class="fas fa-shopping-cart"></i> Order Items</h3>
                
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                                 alt="<?= htmlspecialchars($item['item_name']) ?>" 
                                                 class="item-image">
                                        <?php endif; ?>
                                        <div class="item-info">
                                            <h4><?= htmlspecialchars($item['item_name']) ?></h4>
                                            <?php if ($item['variant_name']): ?>
                                                <div class="item-variant"><?= htmlspecialchars($item['variant_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>LKR <?= number_format($item['price'], 2) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_item_quantity">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                               min="1" class="qty-input" 
                                               onchange="this.form.submit()">
                                    </form>
                                </td>
                                <td>LKR <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Remove this item from the order?');">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn-icon danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total-row">
                    <span>Total Amount:</span>
                    <span>LKR <?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
            
            <!-- Right Column: Order Details -->
            <div>
                <!-- Order Status Card -->
                <div class="details-card" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-info-circle"></i> Order Status</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <label class="info-label">Order Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="preparing" <?= $order['status'] === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                            <option value="ready" <?= $order['status'] === 'ready' ? 'selected' : '' ?>>Ready</option>
                            <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <div class="action-buttons">
                            <button type="submit" class="btn-update">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Payment Status Card -->
                <div class="details-card" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-credit-card"></i> Payment</h3>
                    
                    <?php if (!empty($paymentTransactions)): ?>
                        <div style="margin-bottom: 15px;">
                            <label class="info-label">Payment Methods:</label>
                            <?php foreach ($paymentTransactions as $payment): ?>
                                <div class="info-row">
                                    <span class="info-value" style="text-transform: uppercase; font-weight: 600;">
                                        <?= htmlspecialchars($payment['payment_method']) ?>
                                    </span>
                                    <span class="info-value">LKR <?= number_format($payment['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_payment">
                        <label class="info-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="completed" <?= $order['payment_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="partial" <?= $order['payment_status'] === 'partial' ? 'selected' : '' ?>>Partial</option>
                            <option value="refunded" <?= $order['payment_status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                        <div class="action-buttons">
                            <button type="submit" class="btn-update">
                                <i class="fas fa-save"></i> Update Payment
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Customer Info Card -->
                <div class="details-card" style="margin-bottom: 20px;">
                    <h3><i class="fas fa-user"></i> Customer Info</h3>
                    
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?= htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Type:</span>
                        <span class="info-value"><?= ucfirst(str_replace('_', ' ', $order['order_type'])) ?></span>
                    </div>
                    
                    <?php if ($order['order_type'] === 'delivery'): ?>
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="update_delivery_address">
                            <label class="info-label">Delivery Address</label>
                            <textarea name="delivery_address" class="form-textarea"><?= htmlspecialchars($order['delivery_address'] ?? '') ?></textarea>
                            <div class="action-buttons">
                                <button type="submit" class="btn-update">
                                    <i class="fas fa-save"></i> Update Address
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Order Notes Card -->
                <div class="details-card">
                    <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_notes">
                        <textarea name="notes" class="form-textarea" placeholder="Add notes about this order..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                        <div class="action-buttons">
                            <button type="submit" class="btn-update">
                                <i class="fas fa-save"></i> Update Notes
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Order Timeline -->
                <div class="details-card" style="margin-top: 20px;">
                    <h3><i class="fas fa-clock"></i> Timeline</h3>
                    
                    <div class="info-row">
                        <span class="info-label">Created:</span>
                        <span class="info-value"><?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Last Updated:</span>
                        <span class="info-value"><?= date('M d, Y g:i A', strtotime($order['updated_at'])) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
