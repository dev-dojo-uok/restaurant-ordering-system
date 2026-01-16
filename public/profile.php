<?php 
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';
startSession();
requireLogin();
$userId = getCurrentUserId();
$userName = getCurrentUserName();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Flavor POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>
<body class="page-profile">

<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="profile-page">
    <div class="profile-container">
        <header class="profile-header">
            <div class="avatar"><i class="fas fa-user"></i></div>
            <div>
                <h2 id="profileName"><?php echo htmlspecialchars($userName ?: 'My Profile'); ?></h2>
                <p class="muted">Manage your personal details and security</p>
            </div>
        </header>
        <div class="tabs">
            <button class="tab-btn active" data-tab="info">
                <i class="fas fa-id-card"></i> Personal Info
            </button>
            <button class="tab-btn" data-tab="password">
                <i class="fas fa-lock"></i> Change Password
            </button>
        </div>
        <div class="tab-content">
            <section id="tab-info" class="tab-panel active">
                <form id="formInfo" class="card">
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" id="full_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" id="phone">
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" id="username" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea id="address" rows="3"></textarea>
                    </div>
                    <div class="actions">
                        <button class="btn-primary" type="submit">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <span id="infoMsg" class="msg"></span>
                    </div>
                </form>
        </section>
            </section>
            <section id="tab-password" class="tab-panel">
                <form id="formPassword" class="card">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" id="current_password" required>
                    </div>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" id="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" id="confirm_password" required>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-primary" type="submit">
                            <i class="fas fa-key"></i> Update Password
                        </button>
                        <span id="pwdMsg" class="msg"></span>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>
<script>
window.__PROFILE__ = { userId: <?php echo (int)$userId; ?> };
</script>
<script src="/assets/js/profile.js?v=1.1"></script>
</body>
</html>

