<?php
// Ensure this is included from an admin page
if (!defined('ADMIN_PAGE')) {
    die('Direct access not permitted');
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar">
    <div class="logo">FOOD POS</div>
    
    <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> 
        <span>Dashboard</span>
    </a>
    
    <a href="orders.php" class="nav-link <?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-bag"></i> 
        <span>Orders</span>
    </a>
    
    <a href="kitchen.php" class="nav-link <?php echo $currentPage === 'kitchen.php' ? 'active' : ''; ?>">
        <i class="fas fa-fire"></i> 
        <span>Kitchen Display</span>
    </a>
    
    <a href="menu.php" class="nav-link <?php echo $currentPage === 'menu.php' ? 'active' : ''; ?>">
        <i class="fas fa-utensils"></i> 
        <span>Menu Items</span>
    </a>
    
    <a href="customers.php" class="nav-link <?php echo $currentPage === 'customers.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> 
        <span>Customers</span>
    </a>
    
    <a href="categories.php" class="nav-link <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
        <i class="fas fa-tags"></i> 
        <span>Categories</span>
    </a>
    
    <a href="banners.php" class="nav-link <?php echo $currentPage === 'banners.php' ? 'active' : ''; ?>">
        <i class="fas fa-images"></i> 
        <span>Carousel</span>
    </a>
    
    <a href="staff.php" class="nav-link <?php echo $currentPage === 'staff.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-tie"></i> 
        <span>Staff</span>
    </a>
    
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr(getCurrentUserName(), 0, 1)); ?>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: white;"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div style="font-size: 0.75rem;">Administrator</div>
            </div>
        </div>
        <a href="../logout.php" class="nav-link" style="margin-top: 1rem;">
            <i class="fas fa-sign-out-alt"></i> 
            <span>Logout</span>
        </a>
    </div>
</nav>
