<?php
require_once __DIR__ . '/../app/config/database.php';

// --- 1. BACKEND: FETCH DATA ---

// Fetch categories
$categoriesStmt = $pdo->query("
    SELECT id, name, description, display_order, is_active 
    FROM menu_categories 
    WHERE is_active = true 
    ORDER BY display_order, name
");
$categoriesData = $categoriesStmt->fetchAll();

// Fetch menu items with variants
$menuItemsStmt = $pdo->query("
    SELECT 
        mi.id,
        mi.category_id,
        mi.name,
        mi.description,
        mi.price,
        mi.image_url,
        mi.is_available,
        mi.is_featured,
        mi.is_special,
        mi.is_bestseller,
        COALESCE(
            json_agg(
                json_build_object(
                    'id', miv.id,
                    'variant_name', miv.variant_name,
                    'price', miv.price,
                    'is_default', miv.is_default,
                    'display_order', miv.display_order
                ) ORDER BY miv.display_order, miv.id
            ) FILTER (WHERE miv.id IS NOT NULL),
            '[]'::json
        ) as variants
    FROM menu_items mi
    LEFT JOIN menu_item_variants miv ON mi.id = miv.menu_item_id
    WHERE mi.is_available = true
    GROUP BY mi.id
    ORDER BY mi.name
");
$menuItemsData = $menuItemsStmt->fetchAll();

// Process Data for Frontend
$categorySlugMap = [];
$processedCategories = [['id' => 'all', 'name' => 'All Dishes']];

foreach ($categoriesData as $cat) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $cat['name']), '-'));
    $categorySlugMap[$cat['id']] = $slug;
    $processedCategories[] = ['id' => $slug, 'name' => $cat['name']];
}

$processedMenuItems = [];
foreach ($menuItemsData as $item) {
    $categorySlug = $categorySlugMap[$item['category_id']] ?? '';
    
    // Determine Tag
    $tag = '';
    if ($item['is_featured']) $tag = 'hot';
    elseif ($item['is_special']) $tag = 'new';
    elseif ($item['is_bestseller']) $tag = 'popular';
    
    // Parse variants
    $variants = json_decode($item['variants'], true) ?? [];
    
    // Calculate Base Price and Range
    $price = (float)$item['price'];
    $minPrice = $price;
    $maxPrice = $price;

    if (!empty($variants)) {
        $variantPrices = array_column($variants, 'price');
        $minPrice = min($variantPrices);
        $maxPrice = max($variantPrices);
        
        // Find default variant price
        foreach ($variants as $v) {
            if ($v['is_default']) {
                $price = (float)$v['price'];
                break;
            }
        }
        // Fallback to first variant if no default
        if ($price == (float)$item['price'] && isset($variants[0])) {
            $price = (float)$variants[0]['price'];
        }
    }

    $processedMenuItems[] = [
        'id' => (int)$item['id'],
        'name' => $item['name'],
        'price' => $price,
        'price_min' => $minPrice,
        'price_max' => $maxPrice,
        'category' => $categorySlug,
        'tag' => $tag,
        'img' => $item['image_url'] ?: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c', 
        'desc' => $item['description'] ?: '',
        'variants' => $variants
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gourmet POS Interface</title>
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
        body { background-color: var(--bg-body); color: var(--dark); padding-bottom: 100px; }

        /* UTILS */
        .hidden { display: none !important; }
        .fade-in { animation: fadeIn 0.4s ease forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* HEADER */
        header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(241, 242, 246, 0.95); backdrop-filter: blur(10px);
            padding: 20px 5%; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .brand h1 { font-size: 24px; font-weight: 800; color: var(--dark); }
        .brand span { color: var(--primary); }
        .search-bar { position: relative; width: 100%; max-width: 400px; display: none; }
        @media(min-width: 600px) { .search-bar { display: block; } }
        .search-bar input { width: 100%; padding: 12px 20px 12px 45px; border-radius: 50px; border: 2px solid transparent; background: var(--white); box-shadow: var(--shadow); outline: none; transition: var(--transition); }
        .search-bar input:focus { border-color: var(--primary); }
        .search-bar i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-grey); }

        /* CATEGORIES */
        .categories { padding: 30px 5%; display: flex; gap: 15px; overflow-x: auto; scrollbar-width: none; }
        .categories::-webkit-scrollbar { display: none; }
        .chip { padding: 10px 24px; border-radius: 50px; background: var(--white); color: var(--text-grey); font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: var(--transition); white-space: nowrap; border: 1px solid transparent; }
        .chip:hover { transform: translateY(-2px); color: var(--primary); }
        .chip.active { background: var(--primary); color: var(--white); box-shadow: 0 8px 20px rgba(255, 71, 87, 0.3); }

        /* MENU GRID */
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; padding: 0 5%; max-width: 1400px; margin: 0 auto; }

        /* CARD */
        .card { background: var(--white); border-radius: var(--radius); overflow: hidden; position: relative; box-shadow: var(--shadow); transition: var(--transition); cursor: pointer; }
        .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }
        .card-img-wrapper { height: 200px; overflow: hidden; position: relative; }
        .card-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .card:hover .card-img-wrapper img { transform: scale(1.1); }
        .card-body { padding: 20px; }
        .card-meta { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .badge { font-size: 10px; text-transform: uppercase; font-weight: 700; padding: 4px 10px; border-radius: 8px; letter-spacing: 0.5px; }
        .badge.hot { background: #FFCCCB; color: #D63031; }
        .badge.new { background: #dff9fb; color: #22a6b3; }
        .card-title { font-size: 18px; font-weight: 700; margin-bottom: 5px; color: var(--dark); }
        .card-desc { font-size: 13px; color: var(--text-grey); margin-bottom: 20px; line-height: 1.5; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; }
        .price { font-size: 18px; font-weight: 700; color: var(--primary); }
        .btn-view { width: 40px; height: 40px; border-radius: 50%; background: var(--bg-body); color: var(--dark); border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; transition: var(--transition); }
        .card:hover .btn-view { background: var(--primary); color: white; }

        /* PRODUCT DETAIL VIEW */
        .product-view { max-width: 1200px; margin: 20px auto; padding: 0 5%; display: grid; grid-template-columns: 1.2fr 1fr; gap: 50px; align-items: start; }
        .pv-image-container img { width: 100%; border-radius: var(--radius); box-shadow: var(--shadow); }
        .pv-details h1 { font-size: 36px; font-weight: 800; margin-bottom: 15px; color: var(--dark); }
        .pv-desc { color: var(--text-grey); line-height: 1.6; font-size: 16px; margin-bottom: 30px; }
        .pv-price { font-size: 32px; font-weight: 700; color: var(--primary); margin-bottom: 30px; }
        .label { font-size: 14px; font-weight: 700; margin-bottom: 12px; display: block; color: var(--dark); }
        .size-options { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .size-btn { padding: 12px 30px; border: 2px solid #eee; background: #fff; border-radius: 12px; cursor: pointer; font-weight: 700; transition: var(--transition); color: var(--text-grey); }
        .size-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); box-shadow: 0 10px 20px rgba(255, 71, 87, 0.2); }
        .qty-wrapper { display: flex; align-items: center; background: #fff; border: 2px solid #eee; width: fit-content; border-radius: 12px; margin-bottom: 40px; }
        .qty-btn { background: transparent; border: none; padding: 15px 25px; font-size: 18px; cursor: pointer; color: var(--dark); }
        .qty-input { width: 50px; text-align: center; border: none; font-weight: 700; font-size: 18px; color: var(--dark); pointer-events: none; }
        .btn-large-add { width: 100%; padding: 18px; border: none; border-radius: 15px; background: var(--primary); color: #fff; font-weight: 700; font-size: 18px; cursor: pointer; transition: var(--transition); box-shadow: 0 10px 25px rgba(255, 71, 87, 0.3); display: flex; justify-content: center; gap: 10px; }
        .btn-large-add:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-back { padding: 10px 0; background: transparent; border: none; color: var(--text-grey); font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-size: 16px; }

        @media (max-width: 800px) { 
            .product-view { grid-template-columns: 1fr; }
        }

        /* FLOATING CART & TOAST */
        .floating-cart { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px); background: var(--dark); color: white; padding: 15px 30px; border-radius: 50px; display: flex; align-items: center; gap: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.3); z-index: 1000; transition: var(--transition); cursor: pointer; }
        .floating-cart.visible { transform: translateX(-50%) translateY(0); }
        .cart-count { background: var(--primary); padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 14px; }
        .cart-total { font-weight: 600; font-size: 16px; border-left: 1px solid #555; padding-left: 20px; }
        
        .toast-container { position: fixed; top: 100px; right: 20px; z-index: 9999; }
        .toast { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 10px; border-left: 4px solid var(--accent); display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        .toast i { color: var(--accent); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        /* Skeleton */
        .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    </style>
</head>
<body>

    <header>
        <div class="brand"><h1>FLAVOR <span>POS</span>.</h1></div>
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search for dishes...">
        </div>
        <div style="font-size: 24px; cursor: pointer;"><i class="far fa-user-circle"></i></div>
    </header>

    <div id="menuViewWrapper">
        <nav class="categories" id="categoryContainer"></nav>
        <main class="menu-grid" id="menuContainer"></main>
    </div>

    <section id="productViewWrapper" class="product-view hidden">
        <div class="pv-image-container">
            <button class="btn-back" onclick="goBack()"><i class="fas fa-arrow-left"></i> Back to Menu</button>
            <img id="detailImg" src="" alt="Food Image">
        </div>
        <div class="pv-details">
            <h1 id="detailTitle">Dish Name</h1>
            <p class="pv-desc" id="detailDesc">Description goes here...</p>
            <div class="pv-price" id="detailPrice">Rs. 0.00</div>

            <label class="label">Option / Size</label>
            <div class="size-options">
                </div>

            <label class="label">Quantity</label>
            <div class="qty-wrapper">
                <button class="qty-btn" onclick="updateQty(-1)">-</button>
                <input type="text" id="qtyInput" class="qty-input" value="1" readonly>
                <button class="qty-btn" onclick="updateQty(1)">+</button>
            </div>

            <button class="btn-large-add" onclick="addToCartFromDetail()">
                <span>ADD TO CART</span>
                <i class="fas fa-shopping-bag"></i>
            </button>
            <p style="margin-top: 15px; font-size: 12px; color: #999;">Prices are inclusive of Taxes & Service Charge</p>
        </div>
    </section>

    <div class="floating-cart" id="floatingCart">
        <div class="cart-info" style="display:flex; align-items:center; gap:15px;">
            <i class="fas fa-shopping-bag"></i>
            <span class="cart-count" id="cartCount">0 Items</span>
        </div>
        <div class="cart-total" id="cartTotal">Rs. 0.00</div>
        <i class="fas fa-chevron-right" style="margin-left: 10px; font-size: 12px;"></i>
    </div>

    <div class="toast-container" id="toastContainer"></div>

<script>
    // --- DATA FROM PHP ---
    const menuData = <?php echo json_encode($processedMenuItems, JSON_PRETTY_PRINT); ?>;
    const categories = <?php echo json_encode($processedCategories, JSON_PRETTY_PRINT); ?>;

    // --- STATE ---
    // Load existing cart from LocalStorage to keep count accurate
    let cart = JSON.parse(localStorage.getItem('pos_cart')) || []; 
    
    let currentCategory = 'all';
    let activeItem = null;
    let currentQty = 1;
    let currentSize = 'Regular';

    // --- DOM ELEMENTS ---
    const menuViewWrapper = document.getElementById('menuViewWrapper');
    const productViewWrapper = document.getElementById('productViewWrapper');
    const menuContainer = document.getElementById('menuContainer');
    const categoryContainer = document.getElementById('categoryContainer');
    const cartEl = document.getElementById('floatingCart');
    const toastContainer = document.getElementById('toastContainer');

    // --- HELPERS ---
    const formatCurrency = (val) => `Rs. ${Number(val||0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    const formatPriceRange = (min, max) => (Math.abs(min-max)<0.1) ? formatCurrency(min) : `${formatCurrency(min)} - ${formatCurrency(max)}`;

    // --- INIT ---
    function init() {
        renderCategories();
        updateCartUI(); // Show cart bar if items exist
        menuContainer.innerHTML = Array(4).fill(0).map(()=>`<div class="card"><div class="card-img-wrapper skeleton" style="height:200px;"></div><div class="card-body"><div class="skeleton" style="height:20px; width:70%; margin-bottom:10px;"></div><div class="skeleton" style="height:15px; width:40%;"></div></div></div>`).join('');
        setTimeout(() => renderMenu(menuData), 300);
        
        // Navigation Listener to separate cart page
        cartEl.addEventListener('click', () => {
            window.location.href = 'cart.php';
        });
    }

    function renderCategories() {
        categoryContainer.innerHTML = categories.map(cat => 
            `<button class="chip ${cat.id === currentCategory ? 'active' : ''}" onclick="setCategory('${cat.id}')">${cat.name}</button>`
        ).join('');
    }

    function setCategory(id) {
        currentCategory = id;
        renderCategories();
        const filtered = id === 'all' ? menuData : menuData.filter(i => i.category === id);
        menuContainer.style.opacity = '0';
        setTimeout(() => { renderMenu(filtered); menuContainer.style.opacity = '1'; }, 200);
    }

    function renderMenu(items) {
        if(items.length === 0) {
            menuContainer.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:50px;color:#aaa;">No items found.</div>`;
            return;
        }
        menuContainer.innerHTML = items.map((item, i) => `
            <article class="card" style="animation-delay: ${i*0.05}s" onclick="openProduct(${item.id})">
                <div class="card-img-wrapper"><img src="${item.img}" loading="lazy" alt="${item.name}"></div>
                <div class="card-body">
                    <div class="card-meta">
                        <div class="card-title">${item.name}</div>
                        ${item.tag ? `<span class="badge ${item.tag==='hot'?'hot':'new'}">${item.tag}</span>` : ''}
                    </div>
                    <p class="card-desc">${item.desc.substring(0, 50)}...</p>
                    <div class="card-footer">
                        <div class="price">${formatPriceRange(item.price_min, item.price_max)}</div>
                        <button class="btn-view"><i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </article>
        `).join('');
    }

    // --- NAVIGATION ---
    window.openProduct = (id) => {
        const item = menuData.find(i => i.id === id);
        activeItem = item;
        currentQty = 1;
        document.getElementById('qtyInput').value = 1;

        document.getElementById('detailImg').src = item.img;
        document.getElementById('detailTitle').innerText = item.name;
        document.getElementById('detailDesc').innerText = item.desc;

        // Variants
        const sizeContainer = document.querySelector('.size-options');
        if (item.variants && item.variants.length > 0) {
            sizeContainer.innerHTML = item.variants.map((v, idx) => 
                `<button class="size-btn ${idx === 0 ? 'active' : ''}" onclick="selectSize('${v.variant_name}')">${v.variant_name}</button>`
            ).join('');
            currentSize = item.variants[0].variant_name;
        } else {
            sizeContainer.innerHTML = '<button class="size-btn active" onclick="selectSize(\'Regular\')">Regular</button>';
            currentSize = 'Regular';
        }
        
        updatePriceDisplay();
        
        // Show Product Details Overlay
        menuViewWrapper.classList.add('hidden');
        productViewWrapper.classList.remove('hidden');
        productViewWrapper.classList.add('fade-in');
        window.scrollTo(0,0);
    }

    window.goBack = () => {
        productViewWrapper.classList.add('hidden');
        menuViewWrapper.classList.remove('hidden');
        menuViewWrapper.classList.add('fade-in');
    }

    // --- PRODUCT LOGIC ---
    window.selectSize = (size) => {
        currentSize = size;
        document.querySelectorAll('.size-btn').forEach(b => {
            b.innerText === size ? b.classList.add('active') : b.classList.remove('active');
        });
        updatePriceDisplay();
    }

    window.updateQty = (change) => {
        const newQty = currentQty + change;
        if(newQty >= 1) { currentQty = newQty; document.getElementById('qtyInput').value = currentQty; }
    }

    function updatePriceDisplay() {
        let price = activeItem.price_min;
        if (activeItem.variants && activeItem.variants.length > 0) {
            const v = activeItem.variants.find(v => v.variant_name === currentSize);
            if (v) price = parseFloat(v.price);
        }
        document.getElementById('detailPrice').innerText = formatCurrency(price);
    }

    // --- CART LOGIC ---
    window.addToCartFromDetail = () => {
        let finalPrice = activeItem.price_min;
        let vId = 'def';
        
        if (activeItem.variants && activeItem.variants.length > 0) {
            const v = activeItem.variants.find(v => v.variant_name === currentSize);
            if (v) { finalPrice = parseFloat(v.price); vId = v.id; }
        }

        const cartItem = { ...activeItem, selectedSize: currentSize, variantId: vId, finalPrice: finalPrice };
        
        // Push items to cart array
        for(let i=0; i<currentQty; i++) { 
            cart.push({ ...cartItem, uniqueId: Date.now()+Math.random() }); 
        }

        // SAVE TO LOCAL STORAGE (So cart.php can see it)
        localStorage.setItem('pos_cart', JSON.stringify(cart));

        updateCartUI();
        showToast(`${activeItem.name} added!`);
        goBack();
    }

    function updateCartUI() {
        const count = cart.length;
        const total = cart.reduce((sum, item) => sum + item.finalPrice, 0);
        document.getElementById('cartCount').innerText = `${count} Items`;
        document.getElementById('cartTotal').innerText = formatCurrency(total);
        
        if (count > 0) cartEl.classList.add('visible');
        else cartEl.classList.remove('visible');
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.className = 'toast';
        t.innerHTML = `<i class="fas fa-check-circle"></i> <span>${msg}</span>`;
        toastContainer.appendChild(t);
        setTimeout(() => { t.style.animation='slideIn 0.3s reverse forwards'; setTimeout(()=>t.remove(), 300); }, 3000);
    }

    document.getElementById('searchInput').addEventListener('input', (e) => {
        const term = e.target.value.toLowerCase();
        if(menuViewWrapper.classList.contains('hidden')) goBack();
        renderMenu(menuData.filter(i => i.name.toLowerCase().includes(term) || i.desc.toLowerCase().includes(term)));
    });

    init();
</script>
<script src="/assets/js/profile-icon.js"></script>
</body>
</html>
