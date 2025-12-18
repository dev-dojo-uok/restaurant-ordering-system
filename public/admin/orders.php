<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

// Check if user is admin
requireRole('admin', '../index.php');

// Get filter parameters
$filterStatus = $_GET['status'] ?? 'all';
$filterType = $_GET['type'] ?? 'all';

// Build query
$query = "
    SELECT o.*, u.full_name, u.username, r.full_name as rider_name
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN users r ON o.rider_id = r.id
    WHERE 1=1
";

if ($filterStatus !== 'all') {
    $query .= " AND o.status = :status";
}
if ($filterType !== 'all') {
    $query .= " AND o.order_type = :type";
}

$query .= " ORDER BY o.created_at DESC LIMIT 100";

try {
    $stmt = $pdo->prepare($query);
    
    if ($filterStatus !== 'all') {
        $stmt->bindValue(':status', $filterStatus);
    }
    if ($filterType !== 'all') {
        $stmt->bindValue(':type', $filterType);
    }
    
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    // Get counts for filter badges
    $statusCounts = [];
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    foreach ($stmt->fetchAll() as $row) {
        $statusCounts[$row['status']] = $row['count'];
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Order Management</h1>
            <div style="display: flex; gap: 10px;">
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card">
            <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <label>Filter by Status</label>
                    <select name="status" class="form-control">
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="ordered" <?php echo $filterStatus === 'ordered' ? 'selected' : ''; ?>>
                            Ordered <?php echo isset($statusCounts['ordered']) ? '(' . $statusCounts['ordered'] . ')' : ''; ?>
                        </option>
                        <option value="under_preparation" <?php echo $filterStatus === 'under_preparation' ? 'selected' : ''; ?>>
                            Under Preparation
                        </option>
                        <option value="ready_to_collect" <?php echo $filterStatus === 'ready_to_collect' ? 'selected' : ''; ?>>
                            Ready to Collect
                        </option>
                        <option value="on_the_way" <?php echo $filterStatus === 'on_the_way' ? 'selected' : ''; ?>>
                            On the Way
                        </option>
                        <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>
                            Delivered
                        </option>
                    </select>
                </div>
                
                <div class="form-group" style="margin: 0; flex: 1;">
                    <label>Filter by Type</label>
                    <select name="type" class="form-control">
                        <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="delivery" <?php echo $filterType === 'delivery' ? 'selected' : ''; ?>>Delivery</option>
                        <option value="dine_in" <?php echo $filterType === 'dine_in' ? 'selected' : ''; ?>>Dine-In</option>
                        <option value="takeaway" <?php echo $filterType === 'takeaway' ? 'selected' : ''; ?>>Takeaway</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                
                <a href="orders.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Clear
                </a>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">
                <?php echo count($orders); ?> Order(s) Found
            </h3>
            
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <p>No orders found</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Rider</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td>
                                        <?php 
                                        $customerName = $order['customer_name'] ?? $order['full_name'] ?? 'Walk-in Customer';
                                        echo htmlspecialchars($customerName); 
                                        ?>
                                        <?php if ($order['customer_phone']): ?>
                                            <br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = 'badge-warning';
                                        if (in_array($order['status'], ['delivered', 'completed', 'collected'])) {
                                            $statusClass = 'badge-success';
                                        } elseif ($order['status'] === 'cancelled') {
                                            $statusClass = 'badge-danger';
                                        }
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['order_type'] === 'delivery'): ?>
                                            <?php echo $order['rider_name'] ? htmlspecialchars($order['rider_name']) : '<em style="color: var(--text-muted);">Not assigned</em>'; ?>
                                        <?php else: ?>
                                            <em style="color: var(--text-muted);">N/A</em>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>Rs <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
