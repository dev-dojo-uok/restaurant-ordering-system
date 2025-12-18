<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

requireRole('admin', '../index.php');

$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($customerId <= 0) {
    header('Location: customers.php');
    exit;
}

$errors = [];
$success = '';

try {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = \'customer\'');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        header('Location: customers.php');
        exit;
    }
} catch (Exception $e) {
    $errors[] = $e->getMessage();
    $customer = null;
}

$input = [
    'full_name' => $customer['full_name'] ?? '',
    'username' => $customer['username'] ?? '',
    'email' => $customer['email'] ?? '',
    'phone' => $customer['phone'] ?? '',
    'address' => $customer['address'] ?? '',
    'is_active' => ($customer['is_active'] ?? 1) == 1,
    'password' => '',
    'confirm_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['full_name'] = trim($_POST['full_name'] ?? '');
    $input['email'] = trim($_POST['email'] ?? '');
    $input['phone'] = trim($_POST['phone'] ?? '');
    $input['address'] = trim($_POST['address'] ?? '');
    $input['is_active'] = isset($_POST['is_active']);
    $input['password'] = $_POST['password'] ?? '';
    $input['confirm_password'] = $_POST['confirm_password'] ?? '';

    if ($input['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if ($input['password'] !== '' && $input['password'] !== $input['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }

    // Ensure email uniqueness if changed
    if (empty($errors) && $input['email'] !== $customer['email']) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$input['email'], $customerId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Email already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $fields = [
                'full_name' => $input['full_name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?: null,
                'address' => $input['address'] ?: null,
                'is_active' => $input['is_active'] ? 1 : 0,
            ];

            $sql = 'UPDATE users SET full_name = :full_name, email = :email, phone = :phone, address = :address, is_active = :is_active, updated_at = CURRENT_TIMESTAMP';

            if ($input['password'] !== '') {
                $sql .= ', password = :password';
                $fields['password'] = password_hash($input['password'], PASSWORD_DEFAULT);
            }

            $sql .= ' WHERE id = :id AND role = \'customer\'';
            $fields['id'] = $customerId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($fields);

            $success = 'Customer updated successfully.';
        } catch (Exception $e) {
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
    <title>Edit Customer - Admin</title>
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
                    <h1>Edit Customer</h1>
                    <div class="page-subtitle">Update contact details, credentials, or deactivate access.</div>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a class="btn btn-outline" href="customers.php"><i class="fas fa-arrow-left"></i> Back to Customers</a>
                    <a class="btn-secondary" href="add-customer.php"><i class="fas fa-user-plus"></i> Add New Customer</a>
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
                    <h3>Customer Details</h3>
                </div>
                <div class="panel-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($input['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($input['phone']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($input['username']); ?>" disabled>
                                <div class="note">Username cannot be changed.</div>
                            </div>
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($input['email']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Residential Address</label>
                            <textarea name="address" rows="3" placeholder="Enter full address..."><?php echo htmlspecialchars($input['address']); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="password" value="<?php echo htmlspecialchars($input['password']); ?>" placeholder="Leave blank to keep current password">
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <input type="password" name="confirm_password" value="<?php echo htmlspecialchars($input['confirm_password']); ?>" placeholder="Retype the new password">
                            </div>
                        </div>

                        <label style="font-weight: 700; color: var(--text-dark);">Account Status</label>
                        <div class="pill-row">
                            <label class="pill">
                                <input type="checkbox" name="is_active" <?php echo $input['is_active'] ? 'checked' : ''; ?>>
                                <span>Active</span>
                            </label>
                        </div>

                        <div class="form-actions">
                            <a class="btn btn-outline" href="customers.php">Cancel</a>
                            <button type="submit" class="btn-add">Update Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
