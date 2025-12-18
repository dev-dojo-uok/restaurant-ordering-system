<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';

requireRole('admin', '../index.php');

$errors = [];
$success = '';
$input = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'password' => '',
    'confirm_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['full_name'] = trim($_POST['full_name'] ?? '');
    $input['username'] = trim($_POST['username'] ?? '');
    $input['email'] = trim($_POST['email'] ?? '');
    $input['phone'] = trim($_POST['phone'] ?? '');
    $input['address'] = trim($_POST['address'] ?? '');
    $input['password'] = $_POST['password'] ?? '';
    $input['confirm_password'] = $_POST['confirm_password'] ?? '';

    if ($input['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($input['username'] === '') {
        $errors[] = 'Username is required.';
    }
    if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if ($input['password'] === '') {
        $errors[] = 'Password is required.';
    }
    if ($input['password'] !== $input['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }

    // Uniqueness checks
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
        $stmt->execute([$input['username']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Username already exists.';
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$input['email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Email already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $hash = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password, full_name, role, phone, address, is_active) VALUES (?, ?, ?, ?, \'customer\', ?, ?, true)');
            $stmt->execute([
                $input['username'],
                $input['email'],
                $hash,
                $input['full_name'],
                $input['phone'] ?: null,
                $input['address'] ?: null,
            ]);

            $success = 'Customer created successfully.';
            $input = [
                'full_name' => '',
                'username' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'password' => '',
                'confirm_password' => '',
            ];
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
    <title>Add Customer - Admin</title>
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
                    <h1>Register Customer</h1>
                    <div class="page-subtitle">Capture contact and login details for a new customer account.</div>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a class="btn btn-outline" href="customers.php"><i class="fas fa-arrow-left"></i> Back to Customers</a>
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
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($input['phone']); ?>" placeholder="e.g. +94 712345678">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($input['username']); ?>" required>
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
                                <label>Password *</label>
                                <input type="password" name="password" value="<?php echo htmlspecialchars($input['password']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password *</label>
                                <input type="password" name="confirm_password" value="<?php echo htmlspecialchars($input['confirm_password']); ?>" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a class="btn btn-outline" href="customers.php">Cancel</a>
                            <button type="submit" class="btn-add">Register Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
