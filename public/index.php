<?php
require_once '../app/helpers/auth.php';
require_once '../app/config/database.php';

// Start session
startSession();

// Get user info if logged in
$isLoggedIn = isLoggedIn();
$userName = getCurrentUserName();
$userRole = getCurrentUserRole();

// Get active carousel banners
try {
    $stmt = $pdo->query("
        SELECT * FROM carousel_banners 
        WHERE is_active = true 
        ORDER BY display_order, id 
        LIMIT 5
    ");
    $banners = $stmt->fetchAll();
} catch (Exception $e) {
    $banners = [];
}

// Get today's specials
try {
    $stmt = $pdo->query("
        SELECT mi.*, mc.name as category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE mi.is_special = true AND mi.is_available = true
        ORDER BY mi.created_at DESC
        LIMIT 6
    ");
    $specials = $stmt->fetchAll();
} catch (Exception $e) {
    $specials = [];
}

// Get best sellers (top sales)
try {
    $stmt = $pdo->query("
        SELECT mi.*, mc.name as category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE mi.is_bestseller = true AND mi.is_available = true
        ORDER BY mi.sales_count DESC, mi.id DESC
        LIMIT 6
    ");
    $bestsellers = $stmt->fetchAll();
} catch (Exception $e) {
    $bestsellers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flavor POS - Food Ordering System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/home.css">
</head>
<body>
    <!-- NAVIGATION -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="logo">
                <i class="fas fa-utensils"></i> FLAVOR POS
            </a>
            <ul class="nav-links">
                <li><a href="/"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="/menu.php"><i class="fas fa-book-open"></i> Menu</a></li>
                <?php if ($isLoggedIn): ?>
                    <?php if ($userRole === 'customer'): ?>
                        <li><a href="/cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                        <li><a href="/orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                    <?php elseif ($userRole === 'admin'): ?>
                        <li><a href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <?php elseif ($userRole === 'cashier'): ?>
                        <li><a href="/cashier/dashboard.php"><i class="fas fa-cash-register"></i> Dashboard</a></li>
                    <?php elseif ($userRole === 'kitchen'): ?>
                        <li><a href="/kitchen/dashboard.php"><i class="fas fa-utensils"></i> Dashboard</a></li>
                    <?php elseif ($userRole === 'rider'): ?>
                        <li><a href="/rider/dashboard.php"><i class="fas fa-motorcycle"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="/logout.php" class="btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="/login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- CAROUSEL -->
    <?php if (!empty($banners)): ?>
    <div class="carousel">
        <?php foreach ($banners as $index => $banner): ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                <img src="<?php echo htmlspecialchars($banner['image_url']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                <div class="carousel-content">
                    <h1><?php echo htmlspecialchars($banner['title']); ?></h1>
                    <?php if ($banner['description']): ?>
                        <p><?php echo htmlspecialchars($banner['description']); ?></p>
                    <?php endif; ?>
                    <?php if ($banner['button_text'] && $banner['button_link']): ?>
                        <a href="<?php echo htmlspecialchars($banner['button_link']); ?>" class="carousel-btn">
                            <?php echo htmlspecialchars($banner['button_text']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (count($banners) > 1): ?>
            <button class="carousel-arrow prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-arrow next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="carousel-controls">
                <?php foreach ($banners as $index => $banner): ?>
                    <button class="carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- MAIN CONTENT -->
    <div class="container">
        <!-- TODAY'S SPECIALS -->
        <?php if (!empty($specials)): ?>
        <section class="section">
            <div class="section-header">
                <h2><i class="fas fa-star"></i> Today's Special</h2>
                <div class="line"></div>
                <p class="subtitle">Limited time offers - Don't miss out!</p>
            </div>
            
            <div class="food-grid">
                <?php foreach ($specials as $item): ?>
                    <div class="food-card">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/300x220?text=No+Image'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="food-image">
                            <span class="food-badge">SPECIAL</span>
                        </div>
                        <div class="food-info">
                            <div class="food-category">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                            </div>
                            <h3 class="food-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="food-description">
                                <?php echo htmlspecialchars(substr($item['description'] ?: 'Delicious food item', 0, 80)); ?>
                                <?php echo strlen($item['description'] ?: '') > 80 ? '...' : ''; ?>
                            </p>
                            <div class="food-footer">
                                <div>
                                    <div class="food-price">Rs <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="food-meta">
                                        <span><i class="fas fa-clock"></i> <?php echo $item['preparation_time']; ?>m</span>
                                    </div>
                                </div>
                                <button class="add-to-cart" onclick="addToCart(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- BEST FROM KITCHEN -->
        <?php if (!empty($bestsellers)): ?>
        <section class="section">
            <div class="section-header">
                <h2><i class="fas fa-fire"></i> Best from Kitchen</h2>
                <div class="line"></div>
                <p class="subtitle">Our most popular dishes - Customer favorites!</p>
            </div>
            
            <div class="food-grid">
                <?php foreach ($bestsellers as $item): ?>
                    <div class="food-card">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/300x220?text=No+Image'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="food-image">
                            <?php if ($item['sales_count'] > 0): ?>
                                <span class="food-badge">🔥 <?php echo $item['sales_count']; ?> sold</span>
                            <?php endif; ?>
                        </div>
                        <div class="food-info">
                            <div class="food-category">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                            </div>
                            <h3 class="food-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="food-description">
                                <?php echo htmlspecialchars(substr($item['description'] ?: 'Delicious food item', 0, 80)); ?>
                                <?php echo strlen($item['description'] ?: '') > 80 ? '...' : ''; ?>
                            </p>
                            <div class="food-footer">
                                <div>
                                    <div class="food-price">Rs <?php echo number_format($item['price'], 2); ?></div>
                                    <div class="food-meta">
                                        <span><i class="fas fa-clock"></i> <?php echo $item['preparation_time']; ?>m</span>
                                    </div>
                                </div>
                                <button class="add-to-cart" onclick="addToCart(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- EMPTY STATE -->
        <?php if (empty($specials) && empty($bestsellers)): ?>
        <div class="empty-state">
            <i class="fas fa-utensils"></i>
            <h3>No items available at the moment</h3>
            <p>Check back later for delicious food!</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <p><i class="fas fa-utensils"></i> FLAVOR POS - Food Ordering System</p>
        <p>&copy; 2025 All rights reserved</p>
    </footer>

    <script>
        // CAROUSEL FUNCTIONALITY
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');

        function showSlide(n) {
            if (slides.length === 0) return;
            
            if (n >= slides.length) currentSlide = 0;
            if (n < 0) currentSlide = slides.length - 1;
            
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[currentSlide].classList.add('active');
            if (dots[currentSlide]) dots[currentSlide].classList.add('active');
        }

        function changeSlide(direction) {
            currentSlide += direction;
            showSlide(currentSlide);
        }

        function goToSlide(n) {
            currentSlide = n;
            showSlide(currentSlide);
        }

        // Auto-advance carousel every 5 seconds
        if (slides.length > 1) {
            setInterval(() => {
                currentSlide++;
                showSlide(currentSlide);
            }, 5000);
        }

        // ADD TO CART FUNCTION
        function addToCart(itemId) {
            <?php if (!$isLoggedIn): ?>
                window.location.href = '/login.php';
                return;
            <?php endif; ?>
            
            // TODO: Implement add to cart functionality
            alert('Add to cart feature coming soon! Item ID: ' + itemId);
        }
    </script>
    <script src="/assets/js/profile-icon.js"></script>
</body>
</html>
