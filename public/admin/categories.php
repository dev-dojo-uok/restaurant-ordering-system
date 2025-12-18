<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

// Check if user is admin
requireRole('admin', '../index.php');

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $pdo->prepare("INSERT INTO menu_categories (name, description, display_order) VALUES (?, ?, ?)");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['display_order']]);
                    $success = 'Category added successfully!';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE menu_categories SET name = ?, description = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$_POST['name'], $_POST['description'], $_POST['display_order'], isset($_POST['is_active']) ? 1 : 0, $_POST['id']]);
                    $success = 'Category updated successfully!';
                    break;
                    
                case 'delete':
                    // Check if category has items
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
                    $stmt->execute([$_POST['id']]);
                    $count = $stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $error = "Cannot delete category with {$count} menu items. Please move or delete items first.";
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM menu_categories WHERE id = ?");
                        $stmt->execute([$_POST['id']]);
                        $success = 'Category deleted successfully!';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get all categories with item counts
try {
    $stmt = $pdo->query("
        SELECT 
            mc.*,
            COUNT(mi.id) as item_count
        FROM menu_categories mc
        LEFT JOIN menu_items mi ON mc.id = mi.category_id
        GROUP BY mc.id
        ORDER BY mc.display_order, mc.name
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Menu Categories</h1>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Category
            </button>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="card">
            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>No categories yet</p>
                    <button onclick="showAddModal()" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Add First Category
                    </button>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Display Order</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><strong><?php echo $category['display_order']; ?></strong></td>
                                <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($category['description'] ?: 'N/A'); ?></td>
                                <td><?php echo $category['item_count']; ?> items</td>
                                <td>
                                    <span class="badge <?php echo $category['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='editCategory(<?php echo json_encode($category); ?>)' class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- Add/Edit Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Category</h2>
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="categoryId">
                
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" id="categoryName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="categoryDescription" class="form-control"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="categoryOrder" class="form-control" value="0" min="0">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" id="categoryActive" checked>
                        <span>Active</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Category';
            document.getElementById('modalAction').value = 'add';
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('categoryDescription').value = '';
            document.getElementById('categoryOrder').value = '0';
            document.getElementById('categoryActive').checked = true;
            document.getElementById('categoryModal').classList.add('active');
        }

        function editCategory(category) {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('modalAction').value = 'update';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryOrder').value = category.display_order;
            document.getElementById('categoryActive').checked = category.is_active == 1;
            document.getElementById('categoryModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('categoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
