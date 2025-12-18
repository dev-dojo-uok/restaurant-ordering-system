<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

// Check if user is admin
requireRole('admin', '../index.php');

// Get all customers with order statistics
try {
    $stmt = $pdo->query("
        SELECT 
            u.*,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY total_spent DESC
    ");
    $customers = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Customer List</h1>
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

        <!-- Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <h2><?php echo count($customers); ?></h2>
            </div>
            <div class="stat-card">
                <h3>Active Customers</h3>
                <h2><?php echo count(array_filter($customers, fn($c) => $c['is_active'])); ?></h2>
            </div>
            <div class="stat-card">
                <h3>With Orders</h3>
                <h2><?php echo count(array_filter($customers, fn($c) => $c['total_orders'] > 0)); ?></h2>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card">
            <?php if (empty($customers)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No customers registered yet</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Total Orders</th>
                                <th>Total Spent</th>
                                <th>Last Order</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                        <br><small style="color: var(--text-muted);">@<?php echo htmlspecialchars($customer['username']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                                    <td><?php echo $customer['total_orders']; ?></td>
                                    <td><strong>Rs <?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($customer['last_order_date']): ?>
                                            <?php
                                            $date = new DateTime($customer['last_order_date']);
                                            $now = new DateTime();
                                            $diff = $now->diff($date);
                                            
                                            if ($diff->days == 0) {
                                                echo 'Today';
                                            } elseif ($diff->days == 1) {
                                                echo 'Yesterday';
                                            } elseif ($diff->days < 7) {
                                                echo $diff->days . ' days ago';
                                            } else {
                                                echo $date->format('M d, Y');
                                            }
                                            ?>
                                        <?php else: ?>
                                            <em style="color: var(--text-muted);">No orders</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $customer['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="customer-details.php?id=<?php echo $customer['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
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
