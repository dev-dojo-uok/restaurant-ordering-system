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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/profile.css">
</head>

<body class="page-profile">

<nav class="navbar">
    <div class="nav-container">
        <a href="/" class="logo">
            <i class="fas fa-utensils"></i>
            FLAVOR POS
        </a>

        <ul class="nav-links">
            <li><a href="/"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="/menu.php"><i class="fas fa-book-open"></i> Menu</a></li>
            <li><a href="/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li><a href="/orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
            <li><a href="/profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li>
                <a href="/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.navbar {
    background: white;
    box-shadow: 0 2px 10px var(--shadow);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
    text-decoration: none;
}

.logo i {
    margin-right: 0.5rem;
}

.nav-links {
    display: flex;
    gap: 2rem;
    list-style: none;
    align-items: center;
}

.nav-links a {
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 500;
    transition: color 0.3s;
}

.nav-links a:hover {
    color: var(--primary);
}

.nav-links .btn {
    background: var(--primary);
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    transition: background 0.3s;
}

.nav-links .btn:hover {
    background: var(--primary-dark);
}


/* LOGO */
.logo {
    color: #ff6a2c;
    font-size: 22px;
    font-weight: 700;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
}

.logo i {
    margin-right: 0.5rem;
}

/* LINKS */
.nav-links {
    display: flex;
    list-style: none;
    gap: 28px;
    margin: 0;
    padding: 0;
}

.nav-links li a {
    color: #222;
    font-weight: 500;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: 0.2s;
}

.nav-links li a:hover {
    color: #ff6a2c;
}

/* LOGOUT BUTTON */
.btn-logout {
    background: #ff6a2c;
    color: #fff !important;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
}

.btn-logout:hover {
    background: #e55b1f;
}
</style>


<main class="profile-page">
    <div class="profile-container">

        <header class="profile-header">
            <div class="avatar"><i class="fas fa-user"></i></div>
            <div>
                <h2 id="profileName"><?php echo htmlspecialchars($userName ?: 'My Profile'); ?></h2>
                <p class="muted">Manage your personal details, orders and security</p>
            </div>
        </header>

        <div class="tabs">
            <button class="tab-btn active" data-tab="info">
                <i class="fas fa-id-card"></i> Personal Info
            </button>
            <button class="tab-btn" data-tab="orders">
                <i class="fas fa-list"></i> Order History
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
                    </div>

                    <div class="grid-2">
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

            <section id="tab-orders" class="tab-panel">
                <div id="ordersList" class="list"></div>
            </section>

            <section id="tab-password" class="tab-panel">
                <form id="formPassword" class="card">
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
<script src="/assets/js/profile.js"></script>
</body>
</html>

