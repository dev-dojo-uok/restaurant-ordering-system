<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

requireRole('admin', '../index.php');

$errors = [];
$success = '';

$uploadDir = realpath(__DIR__ . '/..') . '/uploads/menu/';
$uploadBaseUrl = '/uploads/menu/';

// Fetch active categories for dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM menu_categories WHERE is_active = true ORDER BY display_order, name");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    $errors[] = $e->getMessage();
}

$input = [
    'name' => '',
    'category_id' => '',
    'price' => '',
    'description' => '',
    'image_url' => '',
    'preparation_time' => 15,
    'is_available' => true,
    'is_featured' => false,
    'is_special' => false,
    'is_bestseller' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['name'] = trim($_POST['name'] ?? '');
    $input['category_id'] = (int)($_POST['category_id'] ?? 0);
    $input['price'] = trim($_POST['price'] ?? '');
    $input['description'] = trim($_POST['description'] ?? '');
    $input['image_url'] = trim($_POST['image_url'] ?? '');
    $input['preparation_time'] = (int)($_POST['preparation_time'] ?? 15);
    $input['is_available'] = isset($_POST['is_available']);
    $input['is_featured'] = isset($_POST['is_featured']);
    $input['is_special'] = isset($_POST['is_special']);
    $input['is_bestseller'] = isset($_POST['is_bestseller']);

    // Handle image upload (optional)
    if (!empty($_FILES['image_file']['name'])) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $fileName = $_FILES['image_file']['name'];
        $tmpPath = $_FILES['image_file']['tmp_name'];
        $size = (int)($_FILES['image_file']['size'] ?? 0);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = 'Please upload a valid image (jpg, jpeg, png, gif, webp).';
        } elseif ($size > $maxSize) {
            $errors[] = 'Image must be 2MB or smaller.';
        } elseif (!is_uploaded_file($tmpPath)) {
            $errors[] = 'Upload failed, please try again.';
        } else {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newName = 'menu_' . uniqid('', true) . '.' . $ext;
            $destPath = $uploadDir . $newName;

            if (move_uploaded_file($tmpPath, $destPath)) {
                $input['image_url'] = $uploadBaseUrl . $newName;
            } else {
                $errors[] = 'Could not save the uploaded image.';
            }
        }
    }

    if ($input['name'] === '') {
        $errors[] = 'Item name is required.';
    }
    if ($input['category_id'] <= 0) {
        $errors[] = 'Please choose a category.';
    }
    $variantNames = $_POST['variant_name'] ?? [];
    $variantPrices = $_POST['variant_price'] ?? [];
    $variantOrders = $_POST['variant_order'] ?? [];
    $variantAvailable = $_POST['variant_available'] ?? [];
    $variantDefault = isset($_POST['variant_default']) ? (int)$_POST['variant_default'] : null;

    $variants = [];
    foreach ($variantNames as $i => $vName) {
        $vName = trim($vName);
        $vPrice = trim($variantPrices[$i] ?? '');
        $vOrder = (int)($variantOrders[$i] ?? $i);
        $vAvail = isset($variantAvailable[$i]);
        if ($vName === '' || $vPrice === '' || !is_numeric($vPrice)) {
            continue;
        }
        $variants[] = [
            'name' => $vName,
            'price' => (float)$vPrice,
            'order' => $vOrder,
            'available' => $vAvail,
        ];
    }

    $hasVariantPrice = count(array_filter($variants, fn($v) => is_numeric($v['price']))) > 0;

    if (($input['price'] === '' || !is_numeric($input['price'])) && !$hasVariantPrice) {
        $errors[] = 'Please provide a base price or at least one variant with price.';
    }
    if ($input['preparation_time'] < 0) {
        $errors[] = 'Preparation time cannot be negative.';
    }

    if (empty($errors)) {
        try {
            $basePrice = is_numeric($input['price']) ? $input['price'] : null;
            if ($basePrice === null && $hasVariantPrice) {
                $basePrice = $variants[0]['price'];
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO menu_items 
                    (category_id, name, description, price, image_url, is_available, is_featured, is_special, is_bestseller, preparation_time)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $input['category_id'],
                $input['name'],
                $input['description'] ?: null,
                $basePrice,
                $input['image_url'] ?: null,
                $input['is_available'] ? 1 : 0,
                $input['is_featured'] ? 1 : 0,
                $input['is_special'] ? 1 : 0,
                $input['is_bestseller'] ? 1 : 0,
                $input['preparation_time'] ?: 15,
            ]);

            $itemId = $pdo->lastInsertId();

            // Insert variants when provided
            if (!empty($variants)) {
                $chosenDefault = $variantDefault !== null && isset($variants[$variantDefault]) ? $variantDefault : 0;
                $variantStmt = $pdo->prepare("INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, display_order, is_available) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($variants as $idx => $variant) {
                    $variantStmt->execute([
                        $itemId,
                        $variant['name'],
                        $variant['price'],
                        $idx === $chosenDefault ? 1 : 0,
                        $variant['order'],
                        $variant['available'] ? 1 : 0,
                    ]);
                }

                // If base price missing, sync to default variant price
                if ($basePrice === null) {
                    $defaultPrice = $variants[$chosenDefault]['price'];
                    $pdo->prepare("UPDATE menu_items SET price = ? WHERE id = ?")->execute([$defaultPrice, $itemId]);
                }
            } else {
                // Seed a default variant if table exists and no variants supplied
                try {
                    $pdo->prepare("INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, display_order) VALUES (?, 'Regular', ?, true, 1)")
                        ->execute([$itemId, $basePrice]);
                } catch (Exception $variantError) {
                    // Ignore if variants table not present or constraint issues
                }
            }

            $pdo->commit();

            $success = 'Menu item created successfully.';
            $input = [
                'name' => '',
                'category_id' => '',
                'price' => '',
                'description' => '',
                'image_url' => '',
                'preparation_time' => 15,
                'is_available' => true,
                'is_featured' => false,
                'is_special' => false,
                'is_bestseller' => false,
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Menu Item - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/admin-forms.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <div class="form-shell">
            <div class="page-header">
                <div>
                    <h1>Add New Item</h1>
                    <div class="page-subtitle">Use the form to publish a new menu item with pricing and home page flags.</div>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a class="btn btn-outline" href="menu.php"><i class="fas fa-arrow-left"></i> Back to Menu</a>
                    <a class="btn-secondary" href="categories.php"><i class="fas fa-tags"></i> Manage Categories</a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars(implode(' ', $errors)); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <div class="panel-header">
                    <h3>Enter Food Details</h3>
                </div>
                <div class="panel-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Item Name *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($input['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category_id" required>
                                    <option value="" disabled <?php echo $input['category_id'] === '' ? 'selected' : ''; ?>>Select category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo (int)$input['category_id'] === (int)$category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($categories)): ?>
                                    <div class="note">Add a category first to enable item creation.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Price (Rs) *</label>
                                <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($input['price']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Preparation Time (minutes)</label>
                                <input type="number" min="0" name="preparation_time" value="<?php echo htmlspecialchars((string)$input['preparation_time']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Image URL</label>
                                <input type="url" name="image_url" value="<?php echo htmlspecialchars($input['image_url']); ?>" placeholder="https://...">
                                <div class="note">Paste an image URL to preview below.</div>
                            </div>
                            <div class="form-group">
                                <label>Or upload an image</label>
                                <input type="file" name="image_file" class="file-input" accept="image/*">
                                <div class="note">Max 2MB. Allowed: jpg, jpeg, png, gif, webp.</div>
                            </div>
                        </div>

                        <div class="preview-box">
                            <?php if ($input['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($input['image_url']); ?>" alt="Preview" id="imagePreview">
                            <?php else: ?>
                                <div class="preview-placeholder" id="imagePreviewPlaceholder">Add an image URL to preview.</div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="4" placeholder="Ingredients, serving size, notes..."><?php echo htmlspecialchars($input['description']); ?></textarea>
                        </div>

                        <div class="form-card" style="border: 1px solid var(--border); padding: 1rem; margin: 1rem 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <div>
                                    <strong>Variants (sizes/prices)</strong>
                                    <div class="note">Add sizes like Small, Regular, Large with their prices. One variant will be the default.</div>
                                </div>
                                <button type="button" class="btn btn-outline" id="addVariantBtn" style="padding: 0.5rem 0.75rem;">+ Add Variant</button>
                            </div>
                            <div id="variantList" class="form-group" style="margin-bottom: 0;">
                                <!-- Rows injected by JS -->
                            </div>
                        </div>

                        <label style="font-weight: 700; color: var(--text-dark);">Display Flags</label>
                        <div class="pill-row">
                            <label class="pill">
                                <input type="checkbox" name="is_available" <?php echo $input['is_available'] ? 'checked' : ''; ?>>
                                <span>Available</span>
                            </label>
                            <label class="pill">
                                <input type="checkbox" name="is_featured" <?php echo $input['is_featured'] ? 'checked' : ''; ?>>
                                <span>Featured</span>
                            </label>
                            <label class="pill">
                                <input type="checkbox" name="is_special" <?php echo $input['is_special'] ? 'checked' : ''; ?>>
                                <span>Today&apos;s Special</span>
                            </label>
                            <label class="pill">
                                <input type="checkbox" name="is_bestseller" <?php echo $input['is_bestseller'] ? 'checked' : ''; ?>>
                                <span>Bestseller</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <a class="btn btn-outline" href="menu.php">Cancel</a>
                            <button type="submit" class="btn-add">Save Menu Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const urlInput = document.querySelector('input[name="image_url"]');
        const fileInput = document.querySelector('input[name="image_file"]');
        const previewBox = document.querySelector('.preview-box');
        const variantList = document.getElementById('variantList');
        const addVariantBtn = document.getElementById('addVariantBtn');

        function setPreview(src) {
            const existingImg = previewBox.querySelector('img');
            const placeholder = document.getElementById('imagePreviewPlaceholder');
            if (placeholder) placeholder.remove();

            if (existingImg) {
                existingImg.src = src;
                existingImg.style.display = 'block';
            } else {
                const img = document.createElement('img');
                img.id = 'imagePreview';
                img.alt = 'Preview';
                img.src = src;
                previewBox.prepend(img);
            }
        }

        function clearPreview(message) {
            const existingImg = previewBox.querySelector('img');
            if (existingImg) existingImg.remove();
            let placeholder = document.getElementById('imagePreviewPlaceholder');
            if (!placeholder) {
                placeholder = document.createElement('div');
                placeholder.id = 'imagePreviewPlaceholder';
                placeholder.className = 'preview-placeholder';
                previewBox.appendChild(placeholder);
            }
            placeholder.textContent = message;
        }

        function handleUrlChange() {
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                return; // file takes precedence
            }
            const url = urlInput.value.trim();
            if (url) {
                setPreview(url);
            } else {
                clearPreview('Add an image URL to preview.');
            }
        }

        function handleFileChange() {
            const file = fileInput.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = () => setPreview(reader.result);
                reader.readAsDataURL(file);
            } else {
                handleUrlChange();
            }
        }

        if (urlInput) {
            urlInput.addEventListener('input', handleUrlChange);
        }
        if (fileInput) {
            fileInput.addEventListener('change', handleFileChange);
        }

        function createVariantRow(name = '', price = '', order = '', available = true, isDefault = false) {
            const row = document.createElement('div');
            row.className = 'form-row';
            row.style.marginBottom = '0.75rem';
            row.innerHTML = `
                <div class="form-group" style="flex: 1.2;">
                    <label>Variant Name</label>
                    <input type="text" name="variant_name[]" value="${name}" placeholder="e.g., Small">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Price (Rs)</label>
                    <input type="number" step="0.01" min="0" name="variant_price[]" value="${price}">
                </div>
                <div class="form-group" style="width: 110px;">
                    <label>Order</label>
                    <input type="number" name="variant_order[]" value="${order}" placeholder="0">
                </div>
                <div class="form-group" style="width: 120px; display: flex; align-items: center; gap: 6px; margin-bottom: 0;">
                    <input type="radio" name="variant_default" class="variant-default" title="Default" ${isDefault ? 'checked' : ''}>
                    <label style="margin: 0; font-weight: 500;">Default</label>
                </div>
                <div class="form-group" style="width: 140px; display: flex; align-items: center; gap: 6px; margin-bottom: 0;">
                    <input type="checkbox" name="variant_available[]" ${available ? 'checked' : ''}>
                    <label style="margin: 0; font-weight: 500;">Available</label>
                </div>
                <div class="form-group" style="width: 120px; margin-bottom: 0; display: flex; flex-direction: column; align-items: flex-start;">
                    <label style="visibility: hidden;">Remove</label>
                    <button type="button" class="btn btn-outline remove-variant" style="padding: 0.55rem 0.9rem;">Remove</button>
                </div>
            `;
            return row;
        }

        function normalizeDefaultRadios() {
            const rows = Array.from(variantList.children);
            rows.forEach((row, idx) => {
                const radio = row.querySelector('.variant-default');
                if (radio) {
                    radio.value = idx;
                }
                const avail = row.querySelector('input[type="checkbox"][name^="variant_available"]');
                if (avail) {
                    avail.name = `variant_available[${idx}]`;
                }
            });
            const radios = variantList.querySelectorAll('.variant-default');
            const anyChecked = Array.from(radios).some(r => r.checked);
            if (!anyChecked && radios[0]) {
                radios[0].checked = true;
            }
        }

        function attachRowEvents(row) {
            const removeBtn = row.querySelector('.remove-variant');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    row.remove();
                    normalizeDefaultRadios();
                });
            }
            const radio = row.querySelector('.variant-default');
            if (radio) {
                radio.addEventListener('change', () => {
                    // ensure only one checked (radio handles by name once values aligned)
                });
            }
        }

        if (addVariantBtn && variantList) {
            addVariantBtn.addEventListener('click', () => {
                const row = createVariantRow('', '', '', variantList.children.length === 0, variantList.children.length === 0);
                variantList.appendChild(row);
                normalizeDefaultRadios();
                attachRowEvents(row);
            });
            // Seed one row by default
            const initialRow = createVariantRow('Regular', '', '0', true, true);
            variantList.appendChild(initialRow);
            normalizeDefaultRadios();
            attachRowEvents(initialRow);
        }
    </script>
</body>
</html>
