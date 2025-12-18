<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

// Check if user is admin
requireRole('admin', '../index.php');

// Get dashboard statistics
try {
    // Total income
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total_income FROM orders WHERE status IN ('delivered', 'completed', 'collected')");
    $totalIncome = $stmt->fetch()['total_income'];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $totalOrders = $stmt->fetch()['total_orders'];
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM users WHERE role = 'customer'");
    $totalCustomers = $stmt->fetch()['total_customers'];
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status IN ('ordered', 'under_preparation')");
    $pendingOrders = $stmt->fetch()['pending_orders'];
    
    // Recent orders
    $stmt = $pdo->query("
        SELECT o.*, u.full_name, u.username 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll();
    
    // Orders by status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM orders 
        GROUP BY status
    ");
    $ordersByStatus = $stmt->fetchAll();
    
    // Daily sales for the last 7 days
    $stmt = $pdo->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as daily_income
        FROM orders
        WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $dailySales = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Ordering System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Dashboard Overview</h1>
            <a href="../status.php" class="btn btn-outline">
                <i class="fas fa-info-circle"></i> System Status
            </a>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Income</h3>
                <h2>Rs <?php echo number_format($totalIncome, 2); ?></h2>
                <p style="color: var(--success); font-size: 0.85rem; margin-top: 0.5rem;">
                    <i class="fas fa-arrow-up"></i> From completed orders
                </p>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <h2><?php echo $totalOrders; ?></h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
                    <?php echo $pendingOrders; ?> pending
                </p>
            </div>
            
            <div class="stat-card">
                <h3>Total Customers</h3>
                <h2><?php echo $totalCustomers; ?></h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
                    Registered users
                </p>
            </div>
        </div>

        <!-- Chart -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">Income & Orders Trend (Last 7 Days)</h3>
            <canvas id="salesChart" style="max-height: 350px;"></canvas>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">Recent Orders</h3>
            <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <p>No orders yet</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? $order['full_name'] ?? 'Walk-in'); ?></td>
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
                                <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="orders.php" class="btn btn-outline">View All Orders</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Prepare data for chart
        const salesData = <?php echo json_encode($dailySales); ?>;
        const labels = salesData.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const incomeData = salesData.map(d => parseFloat(d.daily_income));
        const orderData = salesData.map(d => parseInt(d.order_count));

        // Create chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Income (Rs)',
                        data: incomeData,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Orders',
                        data: orderData,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
