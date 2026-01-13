<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';
require_once '../../app/controllers/KitchenController.php';

// Check if user is admin or kitchen staff
requireRole(['admin', 'kitchen'], '../index.php');

$kitchenController = new KitchenController($pdo);
$orders = $kitchenController->getKitchenOrders();
$counts = $kitchenController->getOrderCounts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System - Food Order App</title>
    <link rel="stylesheet" href="../assets/css/kitchen.css">
</head>
<body>

    <div class="app-container">
        <header class="top-header">
            <div class="header-left">
                <a href="dashboard.php" class="menu-icon" title="Back to Dashboard">☰</a>
                <span>Kitchen Display System</span>
            </div>
            
            <div class="summary-buttons-container">
                <div class="summary-btn new">
                    <span class="count" id="count-new"><?= $counts['new'] ?></span>
                    <span class="label">New</span>
                </div>
                <div class="summary-btn process">
                    <span class="count" id="count-process"><?= $counts['process'] ?></span>
                    <span class="label">Process</span>
                </div>
                <div class="summary-btn ready">
                    <span class="count" id="count-ready"><?= $counts['ready'] ?></span>
                    <span class="label">Ready</span>
                </div>
                <div class="summary-btn served" id="btn-show-served">
                    <span class="count" id="count-served"><?= $counts['served'] ?></span>
                    <span class="label">Served (View)</span>
                </div>
            </div>
        </header>

        <main class="main-content-area">
            
            <!-- NEW ORDERS COLUMN -->
            <section class="column new-col">
                <div class="column-header">New Orders</div>
                <div class="cards-list" id="new-list">
                    <?php foreach ($orders['new'] as $order): 
                        // Items come as JSON from PostgreSQL array_agg
                        $items = is_string($order['items']) ? json_decode($order['items'], true) : $order['items'];
                        $items = is_array($items) ? $items : [];
                        $elapsed = KitchenController::getElapsedTime($order['created_at']);
                        $orderType = KitchenController::formatOrderType($order['order_type']);
                        $tableInfo = $order['table_number'] ? "Table {$order['table_number']}" : $orderType;
                    ?>
                    <article class="order-card" data-id="<?= $order['id'] ?>">
                        <div class="card-header-strip">
                            <?= htmlspecialchars($tableInfo) ?> (<?= htmlspecialchars($orderType) ?>) | #<?= $order['id'] ?> | <?= htmlspecialchars($elapsed) ?>
                        </div>
                        <div class="card-body">
                            <ul class="item-list">
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['name']) ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-gray-500">No items</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="action-btn" data-action="start" data-order-id="<?= $order['id'] ?>">Start</button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    
                    <?php if (empty($orders['new'])): ?>
                        <div class="empty-state">No new orders</div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- IN PROCESS COLUMN -->
            <section class="column process-col">
                <div class="column-header">In Process</div>
                <div class="cards-list" id="process-list">
                    <?php foreach ($orders['process'] as $order): 
                        // Items come as JSON from PostgreSQL array_agg
                        $items = is_string($order['items']) ? json_decode($order['items'], true) : $order['items'];
                        $items = is_array($items) ? $items : [];
                        $elapsed = KitchenController::getElapsedTime($order['created_at']);
                        $orderType = KitchenController::formatOrderType($order['order_type']);
                        $tableInfo = $order['table_number'] ? "Table {$order['table_number']}" : $orderType;
                    ?>
                    <article class="order-card" data-id="<?= $order['id'] ?>">
                        <div class="card-header-strip">
                            <?= htmlspecialchars($tableInfo) ?> (<?= htmlspecialchars($orderType) ?>) | #<?= $order['id'] ?> | <?= htmlspecialchars($elapsed) ?>
                        </div>
                        <div class="card-body">
                            <ul class="item-list">
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['name']) ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-gray-500">No items</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="action-btn" data-action="finish" data-order-id="<?= $order['id'] ?>">Finish</button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    
                    <?php if (empty($orders['process'])): ?>
                        <div class="empty-state">No orders in process</div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- READY/PASS COLUMN -->
            <section class="column ready-col">
                <div class="column-header">Ready / Pass</div>
                <div class="cards-list" id="ready-list">
                    <?php foreach ($orders['ready'] as $order): 
                        // Items come as JSON from PostgreSQL array_agg
                        $items = is_string($order['items']) ? json_decode($order['items'], true) : $order['items'];
                        $items = is_array($items) ? $items : [];
                        $elapsed = KitchenController::getElapsedTime($order['created_at']);
                        $orderType = KitchenController::formatOrderType($order['order_type']);
                        $tableInfo = $order['table_number'] ? "Table {$order['table_number']}" : $orderType;
                    ?>
                    <article class="order-card" data-id="<?= $order['id'] ?>">
                        <div class="card-header-strip">
                            <?= htmlspecialchars($tableInfo) ?> (<?= htmlspecialchars($orderType) ?>) | #<?= $order['id'] ?> | <?= htmlspecialchars($elapsed) ?>
                        </div>
                        <div class="card-body">
                            <ul class="item-list">
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['name']) ?></li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="text-gray-500">No items</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="card-footer">
                            <button class="action-btn" data-action="served" data-order-id="<?= $order['id'] ?>">Served</button>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    
                    <?php if (empty($orders['ready'])): ?>
                        <div class="empty-state">No orders ready</div>
                    <?php endif; ?>
                </div>
            </section>

        </main>
    </div>

    <!-- MODAL FOR SERVED ORDERS -->
    <div class="modal-overlay" id="served-modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="modal-title">Served Orders (Today)</span>
                <button class="close-btn" id="close-modal">&times;</button>
            </div>
            <div class="modal-body" id="served-history-list">
                <?php foreach ($orders['served'] as $order): 
                    $items = json_decode($order['items'], true);
                    $orderType = KitchenController::formatOrderType($order['order_type']);
                    $tableInfo = $order['table_number'] ? "Table {$order['table_number']}" : $orderType;
                    $completedTime = date('H:i', strtotime($order['completed_at']));
                ?>
                <article class="served-card-item">
                    <div class="card-header-strip">
                        <?= htmlspecialchars($tableInfo) ?> (<?= htmlspecialchars($orderType) ?>) | #<?= $order['id'] ?> | Completed: <?= $completedTime ?>
                    </div>
                    <div class="card-body">
                        <ul class="item-list">
                            <?php foreach ($items as $item): ?>
                                <li><?= htmlspecialchars($item['quantity']) ?>x <?= htmlspecialchars($item['name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </article>
                <?php endforeach; ?>
                
                <?php if (empty($orders['served'])): ?>
                    <div class="empty-state">No served orders today</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/kitchen.js"></script>
</body>
</html>
