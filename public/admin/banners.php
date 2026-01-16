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
                    $stmt = $pdo->prepare("
                        INSERT INTO carousel_banners (title, description, image_url, button_text, button_link, display_order, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['image_url'],
                        $_POST['button_text'],
                        $_POST['button_link'],
                        $_POST['display_order'],
                        isset($_POST['is_active']) ? 1 : 0
                    ]);
                    $success = 'Banner added successfully!';
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("
                        UPDATE carousel_banners 
                        SET title = ?, description = ?, image_url = ?, button_text = ?, 
                            button_link = ?, display_order = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['title'],
                        $_POST['description'],
                        $_POST['image_url'],
                        $_POST['button_text'],
                        $_POST['button_link'],
                        $_POST['display_order'],
                        isset($_POST['is_active']) ? 1 : 0,
                        $_POST['id']
                    ]);
                    $success = 'Banner updated successfully!';
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM carousel_banners WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $success = 'Banner deleted successfully!';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get all banners
try {
    $stmt = $pdo->query("SELECT * FROM carousel_banners ORDER BY display_order, id");
    $banners = $stmt->fetchAll();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carousel Banners - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .banner-preview {
            width: 150px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border);
        }
        
        .banner-preview-large {
            width: 100%;
            max-width: 600px;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Carousel Banners</h1>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Banner
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

        <!-- Banners Grid -->
        <div class="card">
            <?php if (empty($banners)): ?>
                <div class="empty-state">
                    <i class="fas fa-images"></i>
                    <p>No carousel banners yet</p>
                    <button onclick="showAddModal()" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Add First Banner
                    </button>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Preview</th>
                            <th>Title & Description</th>
                            <th>Button</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><strong><?php echo $banner['display_order']; ?></strong></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                                         class="banner-preview">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($banner['title']); ?></strong>
                                    <?php if ($banner['description']): ?>
                                        <br><small style="color: var(--text-muted);">
                                            <?php echo htmlspecialchars(substr($banner['description'], 0, 80)); ?>
                                            <?php echo strlen($banner['description']) > 80 ? '...' : ''; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($banner['button_text']) && !empty($banner['button_text'])): ?>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($banner['button_text']); ?>
                                        </span>
                                        <?php if (isset($banner['button_link']) && !empty($banner['button_link'])): ?>
                                            <br><small style="color: var(--text-muted); font-size: 0.75rem;">
                                                → <?php echo htmlspecialchars($banner['button_link']); ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">No button</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $banner['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $banner['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='editBanner(<?php echo json_encode($banner); ?>)' class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $banner['id']; ?>">
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
    <div id="bannerModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <h2 id="modalTitle">Add Banner</h2>
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="bannerId">
                
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" id="bannerTitle" class="form-control" required placeholder="e.g., Fresh & Delicious Food">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="bannerDescription" class="form-control" rows="3" placeholder="Short description of the banner..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Image URL *</label>
                    <input type="url" name="image_url" id="bannerImageUrl" class="form-control" required placeholder="https://example.com/image.jpg" onchange="previewImage(this.value)">
                    <small style="color: #666;">Recommended size: 1200x500 pixels</small>
                    <img id="imagePreview" class="banner-preview-large" style="display: none;" alt="Preview">
                </div>
                
                <div class="form-group">
                    <label>Button Text</label>
                    <input type="text" name="button_text" id="bannerButtonText" class="form-control" placeholder="e.g., Order Now">
                </div>
                
                <div class="form-group">
                    <label>Button Link</label>
                    <input type="text" name="button_link" id="bannerButtonLink" class="form-control" placeholder="e.g., /menu.php">
                </div>
                
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="bannerOrder" class="form-control" value="0" min="0">
                    <small style="color: #666;">Lower numbers appear first</small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" id="bannerActive" checked>
                        <span>Active (show on homepage)</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Banner</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Banner';
            document.getElementById('modalAction').value = 'add';
            document.getElementById('bannerId').value = '';
            document.getElementById('bannerTitle').value = '';
            document.getElementById('bannerDescription').value = '';
            document.getElementById('bannerImageUrl').value = '';
            document.getElementById('bannerButtonText').value = '';
            document.getElementById('bannerButtonLink').value = '';
            document.getElementById('bannerOrder').value = '0';
            document.getElementById('bannerActive').checked = true;
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('bannerModal').classList.add('active');
        }

        function editBanner(banner) {
            document.getElementById('modalTitle').textContent = 'Edit Banner';
            document.getElementById('modalAction').value = 'update';
            document.getElementById('bannerId').value = banner.id;
            document.getElementById('bannerTitle').value = banner.title;
            document.getElementById('bannerDescription').value = banner.description || '';
            document.getElementById('bannerImageUrl').value = banner.image_url;
            document.getElementById('bannerButtonText').value = banner.button_text || '';
            document.getElementById('bannerButtonLink').value = banner.button_link || '';
            document.getElementById('bannerOrder').value = banner.display_order;
            document.getElementById('bannerActive').checked = banner.is_active == 1;
            previewImage(banner.image_url);
            document.getElementById('bannerModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('bannerModal').classList.remove('active');
        }

        function previewImage(url) {
            const preview = document.getElementById('imagePreview');
            if (url) {
                preview.src = url;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        // Close modal on outside click
        document.getElementById('bannerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
