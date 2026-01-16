<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

// Check if user is admin
requireRole('admin', '../index.php');

// Get all menu items with categories and variant price range
try {
    $stmt = $pdo->query("
        SELECT mi.*, 
               mc.name as category_name,
               MIN(miv.price) as min_price,
               MAX(miv.price) as max_price,
               COUNT(miv.id) as variant_count
        FROM menu_items mi
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id
        LEFT JOIN menu_item_variants miv ON mi.id = miv.menu_item_id
        GROUP BY mi.id, mc.name, mc.display_order
        ORDER BY mc.display_order, mi.name
    ");
    $menuItems = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Items - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Menu Items</h1>
            <div style="display: flex; gap: 10px;">
                <a href="categories.php" class="btn btn-outline">
                    <i class="fas fa-tags"></i> Manage Categories
                </a>
                <a href="add-menu-item.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Menu Items Table -->
        <div class="card">
            <?php if (empty($menuItems)): ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <p>No menu items yet</p>
                    <a href="add-menu-item.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Add First Item
                    </a>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Display On</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td>
                                        <div class="image-preview">
                                            <?php if ($item['image_url']): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-image" style="color: var(--text-muted);"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <?php if ($item['description']): ?>
                                            <br><small style="color: var(--text-muted);">
                                                <?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>
                                                <?php echo strlen($item['description']) > 50 ? '...' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong>
                                            <?php if ($item['min_price'] && $item['max_price']): ?>
                                                <?php if ($item['min_price'] == $item['max_price']): ?>
                                                    Rs <?php echo number_format($item['min_price'], 2); ?>
                                                <?php else: ?>
                                                    Rs <?php echo number_format($item['min_price'], 2); ?> - Rs <?php echo number_format($item['max_price'], 2); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color: #999;">No variants</span>
                                            <?php endif; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <?php if ($item['is_featured']): ?>
                                                <span class="badge badge-warning" style="font-size: 0.75rem;">⭐ Featured</span>
                                            <?php endif; ?>
                                            <?php if ($item['is_special']): ?>
                                                <span class="badge badge-primary" style="font-size: 0.75rem;">🎯 Special</span>
                                            <?php endif; ?>
                                            <?php if ($item['is_bestseller']): ?>
                                                <span class="badge badge-danger" style="font-size: 0.75rem;">🔥 Bestseller</span>
                                            <?php endif; ?>
                                            <?php if (!$item['is_featured'] && !$item['is_special'] && !$item['is_bestseller']): ?>
                                                <span style="color: #999; font-size: 0.85rem;">None</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['is_available'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit-menu-item.php?id=<?php echo $item['id']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </div>
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
