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
        SELECT 
            mi.*, 
            mc.name as category_name,
            COALESCE(MIN(miv.price), 0) AS min_price
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        LEFT JOIN menu_item_variants miv ON miv.menu_item_id = mi.id AND miv.is_available = true
        WHERE mi.is_special = true AND mi.is_available = true
        GROUP BY mi.id, mc.name
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
        SELECT 
            mi.*, 
            mc.name as category_name,
            COALESCE(MIN(miv.price), 0) AS min_price
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        LEFT JOIN menu_item_variants miv ON miv.menu_item_id = mi.id AND miv.is_available = true
        WHERE mi.is_bestseller = true AND mi.is_available = true
        GROUP BY mi.id, mc.name
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--dark); padding-bottom: 50px; }

        /* CAROUSEL */
        .carousel {
            position: relative; width: 100%; height: 500px; overflow: hidden; background: #000; margin-bottom: 40px;
        }
        .carousel-slide { display: none; position: relative; width: 100%; height: 100%; }
        .carousel-slide.active { display: block; }
        .carousel-slide img { width: 100%; height: 100%; object-fit: cover; opacity: 0.7; }
        .carousel-content {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            text-align: center; color: white; width: 90%; max-width: 800px;
        }
        .carousel-content h1 {
            font-size: 48px; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); font-weight: 800;
        }
        .carousel-content p {
            font-size: 18px; margin-bottom: 30px; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        .carousel-btn {
            display: inline-block; background: var(--primary); color: white; padding: 15px 40px;
            border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 16px;
            transition: var(--transition); border: none; cursor: pointer;
            box-shadow: 0 10px 25px rgba(255, 71, 87, 0.3);
        }
        .carousel-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        
        .carousel-controls {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px;
        }
        .carousel-dot {
            width: 12px; height: 12px; border-radius: 50%; background: rgba(255, 255, 255, 0.5);
            border: none; cursor: pointer; transition: all 0.3s;
        }
        .carousel-dot.active { background: white; width: 30px; border-radius: 6px; }
        
        .carousel-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.3); color: white; border: none;
            font-size: 24px; padding: 15px 20px; cursor: pointer; transition: background 0.3s;
            border-radius: 8px;
        }
        .carousel-arrow:hover { background: rgba(255, 255, 255, 0.5); }
        .carousel-arrow.prev { left: 20px; }
        .carousel-arrow.next { right: 20px; }

        /* SECTIONS */
        .container { max-width: 1400px; margin: 0 auto; padding: 0 5%; }
        .section { margin-bottom: 60px; }
        .section-header { text-align: center; margin-bottom: 40px; }
        .section-header h2 { font-size: 36px; font-weight: 800; color: var(--dark); margin-bottom: 10px; }
        .section-header .subtitle { color: var(--text-grey); font-size: 16px; }
        .section-header .line {
            width: 80px; height: 4px; background: var(--primary);
            margin: 20px auto; border-radius: var(--radius);
        }

        /* CARDS - EXACT MENU.PHP STYLE */
        .food-grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 30px; 
        }
        .card { 
            background: var(--white); border-radius: var(--radius); overflow: hidden; 
            position: relative; box-shadow: var(--shadow); transition: var(--transition); cursor: pointer; 
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
        .card-img-wrapper { height: 200px; overflow: hidden; position: relative; }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .card:hover .card-img-wrapper img { transform: scale(1.1); }
        .card-body { padding: 20px; }
        .card-meta { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .badge { 
            font-size: 10px; text-transform: uppercase; font-weight: 700; 
            padding: 4px 10px; border-radius: 8px; letter-spacing: 0.5px; 
        }
        .badge.hot { background: #FFCCCB; color: #D63031; }
        .badge.new { background: #dff9fb; color: #22a6b3; }
        .card-title { font-size: 18px; font-weight: 700; margin-bottom: 5px; color: var(--dark); }
        .card-desc { font-size: 13px; color: var(--text-grey); margin-bottom: 20px; line-height: 1.5; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; }
        .price { font-size: 18px; font-weight: 700; color: var(--primary); }
        .btn-view { 
            width: 40px; height: 40px; border-radius: 50%; background: var(--bg-body); 
            color: var(--dark); border: none; display: flex; align-items: center; 
            justify-content: center; cursor: pointer; transition: var(--transition); 
        }
        .card:hover .btn-view { background: var(--primary); color: white; }

        /* FOOTER */
        .footer {
            background: var(--dark); color: white; padding: 40px 5%;
            text-align: center; margin-top: 60px;
        }
        .footer p { margin-bottom: 10px; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .carousel { height: 350px; }
            .carousel-content h1 { font-size: 32px; }
            .carousel-content p { font-size: 14px; }
            .header-actions { gap: 10px; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

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
                    <?php if (!empty($banner['button_text']) && !empty($banner['button_link'])): ?>
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
                    <article class="card" onclick="window.location.href='/menu.php'">
                        <div class="card-img-wrapper">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="card-body">
                            <div class="card-meta">
                                <div class="card-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                <span class="badge new">SPECIAL</span>
                            </div>
                            <p class="card-desc">
                                <?php echo htmlspecialchars(substr($item['description'] ?: 'Delicious food item', 0, 50)); ?>...
                            </p>
                            <div class="card-footer">
                                <div class="price">Rs <?php echo number_format($item['min_price'], 2); ?></div>
                                <button class="btn-view" onclick="event.stopPropagation(); window.location.href='/menu.php'">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </article>
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
                    <article class="card" onclick="window.location.href='/menu.php'">
                        <div class="card-img-wrapper">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="card-body">
                            <div class="card-meta">
                                <div class="card-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                <span class="badge hot">HOT</span>
                            </div>
                            <p class="card-desc">
                                <?php echo htmlspecialchars(substr($item['description'] ?: 'Delicious food item', 0, 50)); ?>...
                            </p>
                            <div class="card-footer">
                                <div class="price">Rs <?php echo number_format($item['min_price'], 2); ?></div>
                                <button class="btn-view" onclick="event.stopPropagation(); window.location.href='/menu.php'">
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <p><i class="fas fa-utensils"></i> FLAVOR POS - Food Ordering System</p>
        <p>&copy; 2026 All rights reserved</p>
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
    </script>
</body>
</html>
