<?php
define('ADMIN_PAGE', true);

require_once '../../app/helpers/auth.php';
require_once '../../app/config/database.php';
require_once '../../app/models/User.php';

// Check if user is admin
requireRole('admin', '../index.php');

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $user = new User($pdo);
            
            switch ($_POST['action']) {
                case 'add':
                    // Validate inputs
                    if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password'])) {
                        $error = 'Please fill all required fields';
                        break;
                    }
                    
                    if ($user->usernameExists($_POST['username'])) {
                        $error = 'Username already exists';
                        break;
                    }
                    
                    if ($user->emailExists($_POST['email'])) {
                        $error = 'Email already exists';
                        break;
                    }
                    
                    $userId = $user->create(
                        $_POST['username'],
                        $_POST['email'],
                        $_POST['password'],
                        $_POST['full_name'],
                        $_POST['phone'],
                        $_POST['role']
                    );
                    
                    if ($userId) {
                        $success = 'Staff member added successfully!';
                    } else {
                        $error = 'Failed to create staff member';
                    }
                    break;
                    
                case 'update':
                    $updateData = [
                        'full_name' => $_POST['full_name'],
                        'phone' => $_POST['phone'],
                        'role' => $_POST['role'],
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];
                    
                    // Only update password if provided
                    if (!empty($_POST['password'])) {
                        $updateData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    }
                    
                    if ($user->update($_POST['id'], $updateData)) {
                        $success = 'Staff member updated successfully!';
                    } else {
                        $error = 'Failed to update staff member';
                    }
                    break;
                    
                case 'deactivate':
                    if ($user->deactivate($_POST['id'])) {
                        $success = 'Staff member deactivated successfully!';
                    } else {
                        $error = 'Failed to deactivate staff member';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get filter from URL
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';

// Get all staff members (exclude customers)
try {
    $query = "
        SELECT 
            u.*,
            COUNT(DISTINCT o.id) as total_orders
        FROM users u
        LEFT JOIN orders o ON o.rider_id = u.id
        WHERE u.role != 'customer'
    ";
    
    if ($roleFilter) {
        $query .= " AND u.role = :role";
    }
    
    $query .= " GROUP BY u.id ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    if ($roleFilter) {
        $stmt->bindParam(':role', $roleFilter);
    }
    $stmt->execute();
    $staff = $stmt->fetchAll();
    
    // Get counts by role
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users WHERE role != 'customer' AND is_active = TRUE GROUP BY role");
    $roleCounts = [];
    while ($row = $stmt->fetch()) {
        $roleCounts[$row['role']] = $row['count'];
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
    <title>Staff - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '_sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1>Staff Management</h1>
            <button onclick="showAddModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Staff
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Admins</span>
                    <span class="stat-value"><?php echo $roleCounts['admin'] ?? 0; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Cashiers</span>
                    <span class="stat-value"><?php echo $roleCounts['cashier'] ?? 0; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Kitchen</span>
                    <span class="stat-value"><?php echo $roleCounts['kitchen'] ?? 0; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <i class="fas fa-motorcycle"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-label">Riders</span>
                    <span class="stat-value"><?php echo $roleCounts['rider'] ?? 0; ?></span>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card" style="margin-bottom: 1rem;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
                <div class="form-group" style="margin: 0; flex: 1;">
                    <label>Filter by Role</label>
                    <select name="role" class="form-control">
                        <option value="">All Roles</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="cashier" <?php echo $roleFilter === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                        <option value="kitchen" <?php echo $roleFilter === 'kitchen' ? 'selected' : ''; ?>>Kitchen</option>
                        <option value="rider" <?php echo $roleFilter === 'rider' ? 'selected' : ''; ?>>Rider</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <?php if ($roleFilter): ?>
                    <a href="staff.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Staff Table -->
        <div class="card">
            <?php if (empty($staff)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No staff members found</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Orders Delivered</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $member): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($member['full_name'] ?: 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $roleColors = [
                                        'admin' => 'badge-danger',
                                        'cashier' => 'badge-warning',
                                        'kitchen' => 'badge-info',
                                        'rider' => 'badge-primary'
                                    ];
                                    $roleIcons = [
                                        'admin' => 'fa-user-shield',
                                        'cashier' => 'fa-cash-register',
                                        'kitchen' => 'fa-utensils',
                                        'rider' => 'fa-motorcycle'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $roleColors[$member['role']]; ?>">
                                        <i class="fas <?php echo $roleIcons[$member['role']]; ?>"></i>
                                        <?php echo ucfirst($member['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $member['total_orders']; ?></td>
                                <td>
                                    <span class="badge <?php echo $member['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick='editStaff(<?php echo json_encode($member); ?>)' class="btn btn-outline" style="padding: 5px 10px; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($member['is_active']): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this staff member?');">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.85rem;">
                                                    <i class="fas fa-user-slash"></i> Deactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Staff Member</h2>
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="staffId">
                
                <div class="form-group">
                    <label>Username * <span id="usernameNote" style="color: #666; font-size: 0.85rem;">(cannot be changed)</span></label>
                    <input type="text" name="username" id="staffUsername" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Email * <span id="emailNote" style="color: #666; font-size: 0.85rem;">(cannot be changed)</span></label>
                    <input type="email" name="email" id="staffEmail" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Password <span id="passwordNote">*</span></label>
                    <input type="password" name="password" id="staffPassword" class="form-control">
                    <small style="color: #666;">Leave blank to keep current password (when editing)</small>
                </div>
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="staffFullName" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="tel" name="phone" id="staffPhone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" id="staffRole" class="form-control" required>
                        <option value="admin">Admin</option>
                        <option value="cashier">Cashier</option>
                        <option value="kitchen">Kitchen</option>
                        <option value="rider">Rider</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active" id="staffActive" checked>
                        <span>Active</span>
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Staff Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Staff Member';
            document.getElementById('modalAction').value = 'add';
            document.getElementById('staffId').value = '';
            document.getElementById('staffUsername').value = '';
            document.getElementById('staffUsername').disabled = false;
            document.getElementById('staffEmail').value = '';
            document.getElementById('staffEmail').disabled = false;
            document.getElementById('staffPassword').value = '';
            document.getElementById('staffPassword').required = true;
            document.getElementById('staffFullName').value = '';
            document.getElementById('staffPhone').value = '';
            document.getElementById('staffRole').value = 'cashier';
            document.getElementById('staffActive').checked = true;
            document.getElementById('usernameNote').style.display = 'none';
            document.getElementById('emailNote').style.display = 'none';
            document.getElementById('passwordNote').textContent = '*';
            document.getElementById('staffModal').classList.add('active');
        }

        function editStaff(staff) {
            document.getElementById('modalTitle').textContent = 'Edit Staff Member';
            document.getElementById('modalAction').value = 'update';
            document.getElementById('staffId').value = staff.id;
            document.getElementById('staffUsername').value = staff.username;
            document.getElementById('staffUsername').disabled = true;
            document.getElementById('staffEmail').value = staff.email;
            document.getElementById('staffEmail').disabled = true;
            document.getElementById('staffPassword').value = '';
            document.getElementById('staffPassword').required = false;
            document.getElementById('staffFullName').value = staff.full_name || '';
            document.getElementById('staffPhone').value = staff.phone || '';
            document.getElementById('staffRole').value = staff.role;
            document.getElementById('staffActive').checked = staff.is_active == 1;
            document.getElementById('usernameNote').style.display = 'inline';
            document.getElementById('emailNote').style.display = 'inline';
            document.getElementById('passwordNote').textContent = '';
            document.getElementById('staffModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('staffModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('staffModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
