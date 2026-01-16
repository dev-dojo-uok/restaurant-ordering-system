<?php
// Navbar component - Include auth helper if not already loaded
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../../app/helpers/auth.php';
    startSession();
}

$isLoggedIn = isLoggedIn();
$userName = $isLoggedIn ? getCurrentUserName() : '';
$userRole = $isLoggedIn ? getCurrentUserRole() : '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
:root {
    --primary: #FF4757;
    --primary-dark: #E8414F;
    --accent: #2ED573;
    --dark: #2F3542;
    --text-grey: #747D8C;
    --bg-body: #F1F2F6;
    --white: #FFFFFF;
    --shadow: 0 10px 30px rgba(0,0,0,0.08);
    --radius: 20px;
    --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

/* NAVBAR */
.unified-navbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(241, 242, 246, 0.95);
    backdrop-filter: blur(10px);
    padding: 20px 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    gap: 20px;
}

.unified-navbar .brand {
    flex: 1;
}

.unified-navbar .brand h1 {
    font-size: 24px;
    font-weight: 800;
    color: var(--dark);
    margin: 0;
    cursor: pointer;
}

.unified-navbar .brand span {
    color: var(--primary);
}

.navbar-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 24px;
}

.unified-navbar .nav-links {
    display: flex;
    gap: 24px;
    align-items: center;
}

.unified-navbar .nav-links a {
    color: var(--dark);
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
    position: relative;
    padding: 8px 0;
}

.unified-navbar .nav-links a:hover {
    color: var(--primary);
}

.unified-navbar .nav-links a.active {
    color: var(--primary);
}

.unified-navbar .nav-links a.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary);
}

.mobile-menu-icon {
    display: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--dark);
    transition: var(--transition);
}

.mobile-menu-icon:hover {
    color: var(--primary);
}

.unified-navbar .search-bar {
    position: relative;
    flex: 1;
    width: 100%;
    max-width: 480px;
}

.unified-navbar .search-bar input {
    width: 100%;
    padding: 12px 20px 12px 45px;
    border-radius: 50px;
    border: 2px solid transparent;
    background: var(--white);
    box-shadow: var(--shadow);
    outline: none;
    transition: var(--transition);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.unified-navbar .search-bar input:focus {
    border-color: var(--primary);
}

.unified-navbar .search-bar .search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-grey);
}

.unified-navbar .search-bar .search-loader {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    display: none;
    width: 16px;
    height: 16px;
    border: 2px solid var(--primary);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to { transform: translateY(-50%) rotate(360deg); }
}

.unified-navbar .search-bar .search-loader.active {
    display: block;
}

/* Search Dropdown */
.search-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    max-height: 400px;
    overflow-y: auto;
    display: none;
    z-index: 1000;
}

.search-dropdown.active {
    display: block;
}

.search-result-item {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    cursor: pointer;
    transition: var(--transition);
    border-bottom: 1px solid #f0f0f0;
}

.search-result-item:hover {
    background: var(--bg-body);
}

.search-result-item img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
}

.search-result-info {
    flex: 1;
}

.search-result-name {
    font-weight: 700;
    color: var(--dark);
    font-size: 14px;
    margin-bottom: 2px;
}

.search-result-price {
    font-size: 13px;
    color: var(--primary);
    font-weight: 600;
}

.search-view-all {
    padding: 15px;
    text-align: center;
    border-top: 2px solid var(--bg-body);
    background: #f8f9fa;
    border-radius: 0 0 var(--radius) var(--radius);
}

.search-view-all a {
    color: var(--primary);
    font-weight: 700;
    text-decoration: none;
    font-size: 14px;
}

.search-no-results {
    padding: 30px;
    text-align: center;
    color: var(--text-grey);
}

/* Profile Icon */
.profile-icon {
    font-size: 24px;
    cursor: pointer;
    color: var(--text-grey);
    position: relative;
    transition: var(--transition);
}

.profile-icon:hover {
    color: var(--primary);
}

.profile-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    min-width: 200px;
    display: none;
    z-index: 1000;
}

.profile-dropdown.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

.profile-dropdown::before {
    content: '';
    position: absolute;
    top: -8px;
    right: 20px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid var(--white);
}

.profile-dropdown-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--bg-body);
}

.profile-dropdown-name {
    font-weight: 700;
    color: var(--dark);
    font-size: 14px;
    margin-bottom: 3px;
}

.profile-dropdown-role {
    font-size: 12px;
    color: var(--text-grey);
    text-transform: capitalize;
}

.profile-dropdown-item {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    color: var(--dark);
    border-bottom: 1px solid #f5f5f5;
    font-size: 14px;
    font-weight: 500;
}

.profile-dropdown-item:last-child {
    border-bottom: none;
}

.profile-dropdown-item:hover {
    background: var(--bg-body);
    color: var(--primary);
}

.profile-dropdown-item i {
    width: 18px;
    text-align: center;
    font-size: 14px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .unified-navbar {
        padding: 15px 5%;
    }
    
    .unified-navbar .search-bar {
        display: none;
    }
    
    .unified-navbar .nav-links {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: var(--white);
        flex-direction: column;
        padding: 20px;
        box-shadow: var(--shadow);
        display: none;
        gap: 0;
        margin-left: 0;
    }
    
    .unified-navbar .nav-links.active {
        display: flex;
    }
    
    .unified-navbar .nav-links a {
        width: 100%;
        padding: 15px;
        border-bottom: 1px solid var(--bg-body);
    }
    
    .unified-navbar .nav-links a:last-child {
        border-bottom: none;
    }
    
    .unified-navbar .nav-links a.active::after {
        display: none;
    }
    
    .mobile-menu-icon {
        display: block;
        margin-left: auto;
        margin-right: 15px;
    }
}
</style>

<header class="unified-navbar">
    <div class="brand" onclick="window.location.href='/'">
        <h1>FLAVOR <span>POS</span>.</h1>
    </div>

    <div class="search-bar">
        <i class="fas fa-search search-icon"></i>
        <input 
            type="text" 
            id="navbarSearchInput" 
            placeholder="Search for dishes..."
            autocomplete="off"
        >
        <div class="search-loader" id="navbarSearchLoader"></div>
        <div class="search-dropdown" id="navbarSearchDropdown"></div>
    </div>

    <div class="navbar-right">
        <div class="mobile-menu-icon" id="mobileMenuIcon">
            <i class="fas fa-bars"></i>
        </div>

        <nav class="nav-links" id="navLinks">
            <a href="/index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="/menu.php" class="<?php echo $currentPage === 'menu.php' ? 'active' : ''; ?>">Menu</a>
            <?php if ($isLoggedIn): ?>
                <a href="/orders.php" class="<?php echo $currentPage === 'orders.php' ? 'active' : ''; ?>">Orders</a>
            <?php endif; ?>
        </nav>

        <div class="profile-icon" id="navbarProfileIcon">
            <i class="far fa-user-circle"></i>
            <div class="profile-dropdown" id="navbarProfileDropdown">
                <?php if ($isLoggedIn): ?>
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="profile-dropdown-role"><?php echo htmlspecialchars($userRole); ?></div>
                    </div>
                    
                    <?php if ($userRole === 'admin'): ?>
                        <a href="/admin/dashboard.php" class="profile-dropdown-item">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Admin Dashboard</span>
                        </a>
                    <?php elseif ($userRole === 'cashier'): ?>
                        <a href="/pos" class="profile-dropdown-item">
                            <i class="fas fa-cash-register"></i>
                            <span>POS System</span>
                        </a>
                    <?php elseif ($userRole === 'kitchen'): ?>
                        <a href="/admin/kitchen.php" class="profile-dropdown-item">
                            <i class="fas fa-utensils"></i>
                            <span>Kitchen Display</span>
                        </a>
                    <?php elseif ($userRole === 'rider'): ?>
                        <a href="/rider/dashboard.php" class="profile-dropdown-item">
                            <i class="fas fa-motorcycle"></i>
                            <span>Rider Dashboard</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($userRole === 'customer'): ?>
                        <a href="/profile.php" class="profile-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="/orders.php" class="profile-dropdown-item">
                            <i class="fas fa-receipt"></i>
                            <span>My Orders</span>
                        </a>
                        <a href="/cart.php" class="profile-dropdown-item">
                            <i class="fas fa-shopping-cart"></i>
                            <span>My Cart</span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="/logout.php" class="profile-dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="profile-dropdown-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login</span>
                    </a>
                    <a href="/register.php" class="profile-dropdown-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    const searchInput = document.getElementById('navbarSearchInput');
    const searchDropdown = document.getElementById('navbarSearchDropdown');
    const searchLoader = document.getElementById('navbarSearchLoader');
    const profileIcon = document.getElementById('navbarProfileIcon');
    const profileDropdown = document.getElementById('navbarProfileDropdown');
    const mobileMenuIcon = document.getElementById('mobileMenuIcon');
    const navLinks = document.getElementById('navLinks');
    
    let searchTimeout;
    let menuData = [];

    const formatCurrency = (val) => `Rs. ${Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    const getVariantMinPrice = (item) => {
        const variants = Array.isArray(item.variants) ? item.variants : [];
        if (variants.length > 0) {
            return Math.min(...variants.map(v => Number(v.price) || 0));
        }
        return Number(item.price) || 0;
    };

    // Prefill search from query param
    const urlParams = new URLSearchParams(window.location.search);
    const initialQuery = (urlParams.get('q') || '').trim();
    if (initialQuery) {
        searchInput.value = initialQuery;
        // Trigger search to show results/dropdown
        searchInput.dispatchEvent(new Event('input'));
    }

    // Mobile menu toggle
    if (mobileMenuIcon) {
        mobileMenuIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            navLinks.classList.toggle('active');
            profileDropdown.classList.remove('active');
            searchDropdown.classList.remove('active');
            
            // Toggle icon
            const icon = mobileMenuIcon.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    // Profile dropdown toggle
    profileIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('active');
        searchDropdown.classList.remove('active');
        if (navLinks) navLinks.classList.remove('active');
        if (mobileMenuIcon) {
            const icon = mobileMenuIcon.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', () => {
        profileDropdown.classList.remove('active');
        searchDropdown.classList.remove('active');
        if (navLinks) navLinks.classList.remove('active');
        if (mobileMenuIcon) {
            const icon = mobileMenuIcon.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    });

    // Prevent dropdown from closing when clicking inside
    searchDropdown.addEventListener('click', (e) => e.stopPropagation());
    profileDropdown.addEventListener('click', (e) => e.stopPropagation());

    // Search functionality
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchDropdown.classList.remove('active');
            return;
        }

        searchLoader.classList.add('active');
        
        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch('/api/menu-items');
                const data = await response.json();
                
                const filteredItems = data.filter(item => 
                    item.name.toLowerCase().includes(query.toLowerCase()) ||
                    (item.description && item.description.toLowerCase().includes(query.toLowerCase()))
                ).slice(0, 5);

                displaySearchResults(filteredItems, query);
            } catch (error) {
                console.error('Search error:', error);
            } finally {
                searchLoader.classList.remove('active');
            }
        }, 300);
    });

    function displaySearchResults(items, query) {
        if (items.length === 0) {
            searchDropdown.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-search" style="font-size: 24px; opacity: 0.3; margin-bottom: 10px;"></i>
                    <p>No dishes found</p>
                </div>
            `;
            searchDropdown.classList.add('active');
            return;
        }

        const encodedQuery = encodeURIComponent(query);

        const resultsHTML = items.map(item => `
            <div class="search-result-item" onclick="window.location.href='/menu.php?q=${encodedQuery}'">
                <img src="${item.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c'}" alt="${item.name}">
                <div class="search-result-info">
                    <div class="search-result-name">${item.name}</div>
                    <div class="search-result-price">${formatCurrency(getVariantMinPrice(item))}</div>
                </div>
            </div>
        `).join('');

        searchDropdown.innerHTML = `
            ${resultsHTML}
            <div class="search-view-all">
                <a href="/menu.php?q=${encodedQuery}">See all results</a>
            </div>
        `;
        
        searchDropdown.classList.add('active');
    }

    // Close search dropdown when pressing Escape
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            searchDropdown.classList.remove('active');
            searchInput.blur();
        }
    });
})();
</script>
