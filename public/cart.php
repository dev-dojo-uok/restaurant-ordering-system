<?php
// Start session before any output to avoid header warnings
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/config/database.php';
startSession();
$currentUserId = getCurrentUserId();
$currentUserName = getCurrentUserName();

// Get user's address if logged in
$userAddress = '';
if ($currentUserId) {
    try {
        $stmt = $pdo->prepare("SELECT address FROM users WHERE id = :id");
        $stmt->execute(['id' => $currentUserId]);
        $user = $stmt->fetch();
        if ($user && isset($user['address'])) {
            $userAddress = trim($user['address']);
        }
    } catch (Exception $e) {
        // Log error for debugging
        error_log("Cart.php - Error fetching user address: " . $e->getMessage());
    }
}
// Debug: You can temporarily uncomment this to see what's being fetched
// echo "<!-- Debug: User ID: $currentUserId, Address: '$userAddress' -->";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Gourmet POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* EXACT COLORS */
            --primary: #FF4757;       
            --primary-dark: #E8414F;
            --accent: #2ED573;
            --dark: #2F3542;          
            --dark-hover: #1e272e;
            --text-grey: #747D8C;
            --bg-body: #F1F2F6;
            --white: #FFFFFF;
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
            --radius: 20px;           
            
            /* SMOOTH ANIMATION CURVE */
            --transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); 
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--dark); padding-bottom: 50px; }
        
        /* LAYOUT */
        .cart-view { 
            max-width: 1100px; 
            margin: 0 auto; 
            padding: 0 5%; 
            display: grid; 
            grid-template-columns: 1.4fr 1fr; 
            gap: 40px; 
            align-items: start; 
        }

        /* LEFT: CART ITEMS */
        .cart-list { display: flex; flex-direction: column; gap: 20px; }
        
        .cart-item { 
            background: var(--white); 
            border-radius: var(--radius); 
            padding: 20px; 
            display: flex; 
            gap: 20px; 
            align-items: center; 
            box-shadow: var(--shadow);
            transition: var(--transition); 
            border: 1px solid transparent;
        }
        
        .cart-item:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.12); }

        .cart-item img { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; }
        .cart-item-details { flex: 1; }
        .cart-item-title { font-weight: 700; font-size: 16px; margin-bottom: 4px; color: var(--dark); }
        .cart-item-variant { font-size: 13px; color: var(--text-grey); margin-bottom: 4px; }
        .cart-item-price { font-weight: 700; color: var(--primary); font-size: 16px; }
        
        .cart-controls { display: flex; align-items: center; gap: 10px; background: #f1f2f6; border-radius: 12px; padding: 5px; }
        .cart-btn-qty { 
            width: 30px; height: 30px; border: none; background: #fff; border-radius: 8px; 
            cursor: pointer; font-weight: 700; color: var(--dark); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
            transition: var(--transition); 
        }
        .cart-btn-qty:hover { background: var(--primary); color: white; transform: translateY(-2px); }

        /* RIGHT: SUMMARY BOX */
        .cart-summary-box { 
            background: var(--white); 
            border-radius: var(--radius);
            padding: 0;
            box-shadow: var(--shadow); 
            position: sticky; 
            top: 20px; 
            overflow: hidden;
        }

        .summary-header {
            padding: 25px 25px 15px;
            border-bottom: 2px solid var(--bg-body);
        }
        .summary-header h3 { font-size: 20px; font-weight: 800; color: var(--dark); }
        
        .price-row {
            display: flex; justify-content: space-between; padding: 10px 25px;
            font-size: 15px; color: var(--text-grey); font-weight: 500;
        }
        .price-row.total {
            border-top: 2px dashed #eee;
            padding: 20px 25px;
            margin-top: 10px;
            font-weight: 800;
            font-size: 22px;
            color: var(--dark);
        }
        
        .tax-note { font-size: 11px; color: #999; text-align: right; padding: 0 25px; margin-top: -8px; margin-bottom: 10px;}

        /* TABS */
        .instruction-text { padding: 0 25px 10px; font-size: 14px; color: var(--text-grey); margin-top: 20px; }
        
        .tab-container {
            display: flex; margin: 0 25px 15px;
            border: 1px solid #eee; border-radius: 12px; overflow: hidden;
            background: #f8f9fa;
        }
        .tab-btn {
            flex: 1; padding: 15px; border: none; background: transparent;
            font-weight: 700; color: var(--text-grey); cursor: pointer;
            display: flex; flex-direction: column; align-items: center; gap: 5px;
            transition: var(--transition);
        }
        .tab-btn i { font-size: 18px; margin-bottom: 2px; }
        
        .tab-btn.active { background: var(--dark); color: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .tab-btn:hover:not(.active) { color: var(--primary); background: #fff; }

        /* TIME OPTION BUTTONS (Inline) */
        .time-option-container {
            display: flex; gap: 10px; margin: 0 0 15px;
        }
        .time-option-btn {
            flex: 1; padding: 12px 16px; border: 2px solid #e1e8ed;
            background: #fff; border-radius: 10px; cursor: pointer;
            font-weight: 700; font-size: 14px; color: var(--text-grey);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all 0.3s ease;
        }
        .time-option-btn i { font-size: 16px; }
        .time-option-btn.active {
            border-color: var(--primary); background: #fff5f6;
            color: var(--primary);
        }
        .time-option-btn:hover:not(.active) {
            border-color: #c3cfd9; background: #f8f9fa;
        }

        /* FORMS */
        .tab-content { padding: 0 25px 25px; display: none; animation: fadeIn 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); }
        .tab-content.active { display: block; }

        .input-group { 
            display: flex; align-items: center; justify-content: space-between;
            border: 2px solid #f1f2f6; padding: 14px 18px; margin-bottom: 12px;
            border-radius: 12px; background: #fff; position: relative;
            transition: var(--transition);
        }
        .input-group:hover, .input-group:focus-within { border-color: var(--primary); }
        
        .input-group input { 
            border: none; outline: none; width: 100%; font-size: 14px; 
            color: var(--dark); cursor: pointer; font-weight: 600;
        }
        .input-icon { color: var(--text-grey); font-size: 16px; }

        /* --- CHECKOUT BUTTON (FIXED TRANSITION) --- */
        .btn-checkout { 
            width: 100%; 
            padding: 18px; 
            background: var(--dark);  /* Default Dark */
            color: #fff;
            border: none; 
            border-radius: 12px; 
            font-weight: 800; 
            font-size: 16px;
            text-transform: uppercase; 
            letter-spacing: 0.5px;
            cursor: pointer; 
            
            /* The Smooth Transition */
            transition: var(--transition); 
        }

        .btn-checkout:hover { 
            background: var(--primary); /* Transitions to RED on hover */
            transform: translateY(-3px); 
            box-shadow: 0 10px 25px rgba(255, 71, 87, 0.3); /* Red glow shadow */
        }

        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; color: var(--text-grey); font-weight: 700;
            margin-bottom: 20px; transition: var(--transition);
        }
        .btn-back:hover { color: var(--primary); transform: translateX(-3px); }
        
        .empty-cart { text-align: center; padding: 60px; color: var(--text-grey); grid-column: 1 / -1; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 900px) { 
            .cart-view { grid-template-columns: 1fr; }
            .cart-summary-box { position: static; margin-top: 30px; }
        }

        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 10px; border-left: 4px solid var(--accent); display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="cart-view">
        
        <div class="cart-items-container">
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Menu</a>
            <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 25px; color: var(--dark);">Your Cart</h1>
            <div id="cartList" class="cart-list"></div>
        </div>

        <div class="cart-summary-box">
            <div class="summary-header">
                <h3>Order Summary</h3>
            </div>
            
            <div style="padding-top: 15px;">
                <div class="price-row">
                    <span>Subtotal</span>
                    <span id="summarySubtotal">Rs. 0.00</span>
                </div>
                <div class="price-row">
                    <span>Delivery Fee</span>
                    <span id="summaryDelivery">Rs. 0.00</span>
                </div>
                <div class="tax-note">Prices are inclusive of Taxes</div>
                
                <div class="price-row total">
                    <span>Total</span>
                    <span id="summaryTotal" style="color: var(--primary);">Rs. 0.00</span>
                </div>
            </div>

            <p class="instruction-text">Select Order Method:</p>

            <div class="tab-container">
                <button class="tab-btn active" onclick="switchTab('delivery')">
                    <i class="fas fa-globe-americas"></i> Delivery
                </button>
                <button class="tab-btn" onclick="switchTab('pickup')">
                    <i class="fas fa-store"></i> Store Pickup
                </button>
            </div>

            <div id="tab-delivery" class="tab-content active">
                <div class="input-group" style="margin-bottom: 15px;">
                    <input type="text" placeholder="Enter delivery address" id="deliveryAddress" required>
                    <i class="fas fa-map-marker-alt input-icon"></i>
                </div>
                
                <div class="time-option-container">
                    <button class="time-option-btn active" id="btnASAP" onclick="selectDeliveryTime('asap')">
                        <i class="fas fa-bolt"></i> ASAP
                    </button>
                    <button class="time-option-btn" id="btnLater" onclick="selectDeliveryTime('later')">
                        <i class="fas fa-clock"></i> Later
                    </button>
                </div>
                
                <div id="delivery-datetime" style="display: none;">
                    <div class="input-group">
                        <input type="text" placeholder="Pick a date" onfocus="(this.type='date')" id="delDate">
                        <i class="far fa-calendar-alt input-icon"></i>
                    </div>
                    <div class="input-group">
                        <input type="text" placeholder="Select Time" onfocus="(this.type='time')" id="delTime">
                        <i class="far fa-clock input-icon"></i>
                    </div>
                </div>
            </div>

            <div id="tab-pickup" class="tab-content">
                <p style="font-size: 14px; color: var(--text-grey); padding: 10px 0;">Your order will be ready for pickup shortly.</p>
            </div>

            <div style="padding: 0 25px 25px;">
                <button class="btn-checkout" onclick="placeOrder()">CONFIRM ORDER</button>
            </div>
        </div>
    </section>

    <div class="toast-container" id="toastContainer"></div>

<script>
    // 1. INIT STATE
    let cart = JSON.parse(localStorage.getItem('pos_cart')) || [];
    let currentTab = 'delivery'; 
    let deliveryTimeOption = 'asap'; // 'asap' or 'later'
    const DELIVERY_FEE = 350.00; 
    const currentUserId = <?php echo json_encode($currentUserId); ?>;
    const currentUserName = <?php echo json_encode($currentUserName); ?>;
    const userAddress = <?php echo json_encode($userAddress); ?>;
    
    // Helpers
    const formatCurrency = (val) => `Rs. ${Number(val||0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    const getTotals = () => {
        const subtotal = cart.reduce((sum, item) => sum + item.finalPrice, 0);
        const deliveryFee = cart.length > 0 && currentTab === 'delivery' ? DELIVERY_FEE : 0;
        const total = subtotal + deliveryFee;
        return { subtotal, deliveryFee, total };
    };

    // DEFINE FUNCTIONS EARLY FOR ONCLICK HANDLERS
    
    // Switch between delivery and pickup tabs
    window.switchTab = function(tab) {
        currentTab = tab;
        
        // Remove active class from all tab buttons
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(btn => btn.classList.remove('active'));
        
        // Remove active class from all tab contents
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => content.classList.remove('active'));

        // Add active class to selected tab and content
        if (tab === 'delivery') {
            tabButtons[0].classList.add('active');
            document.getElementById('tab-delivery').classList.add('active');
        } else if (tab === 'pickup') {
            tabButtons[1].classList.add('active');
            document.getElementById('tab-pickup').classList.add('active');
        }

        updateTotals();
    };
    
    // Select delivery time option (ASAP or Later)
    window.selectDeliveryTime = function(option) {
        deliveryTimeOption = option;
        
        const btnASAP = document.getElementById('btnASAP');
        const btnLater = document.getElementById('btnLater');
        const dateTimeDiv = document.getElementById('delivery-datetime');
        
        if (!btnASAP || !btnLater || !dateTimeDiv) return;
        
        if (option === 'asap') {
            btnASAP.classList.add('active');
            btnLater.classList.remove('active');
            dateTimeDiv.style.display = 'none';
        } else {
            btnASAP.classList.remove('active');
            btnLater.classList.add('active');
            dateTimeDiv.style.display = 'block';
        }
    };
    
    // Modify cart quantity
    window.modifyCartQty = function(id, size, change) {
        if(change === 1) {
            const item = cart.find(i => i.id === id && i.selectedSize === size);
            if(item) cart.push({ ...item, uniqueId: Date.now() });
        } else {
            const idx = cart.findIndex(i => i.id === id && i.selectedSize === size);
            if(idx > -1) cart.splice(idx, 1);
        }
        localStorage.setItem('pos_cart', JSON.stringify(cart));
        renderCartPageItems();
    };
    
    // Place order
    window.placeOrder = async function() {
        if(cart.length === 0) return;

        let orderInfo = '';
        let deliveryAddress = '';
        
        if (currentTab === 'delivery') {
            deliveryAddress = document.getElementById('deliveryAddress').value.trim();
            if (!deliveryAddress) {
                showToast("Please enter delivery address!");
                return;
            }
            
            if (deliveryTimeOption === 'later') {
                const date = document.getElementById('delDate').value;
                const time = document.getElementById('delTime').value;
                if (!date || !time) { 
                    showToast("Please select delivery date & time!"); 
                    return; 
                }
                orderInfo = `Delivery scheduled for ${date} at ${time}`;
            } else {
                orderInfo = 'Delivery ASAP';
            }
        } else {
            orderInfo = 'Store Pickup';
        }

        // Group items for API
        const grouped = {};
        cart.forEach(item => {
            const key = `${item.id}-${item.selectedSize}`;
            if (!grouped[key]) grouped[key] = { ...item, quantity: 0 };
            grouped[key].quantity += 1;
        });
        const itemsPayload = Object.values(grouped).map(it => ({
            menu_item_id: it.id,
            variant_id: it.variantId !== 'def' ? it.variantId : null,
            quantity: it.quantity,
            price: it.finalPrice,
            item_name: it.name,
            variant_name: it.selectedSize
        }));

        const { total } = getTotals();
        const payload = {
            order_type: currentTab === 'delivery' ? 'delivery' : 'takeaway',
            total_amount: total,
            payment_method: 'cash',
            payments: [{ method: 'cash', amount: total }],
            notes: orderInfo,
            delivery_address: deliveryAddress || null,
            user_id: currentUserId || null,
            customer_name: currentUserName || 'Guest',
            items: itemsPayload
        };

        const btn = document.querySelector('.btn-checkout');
        const prevText = btn ? btn.innerText : '';
        if (btn) { btn.innerText = 'Placing...'; btn.disabled = true; }

        try {
            const res = await fetch('/api/orders', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.error || 'Failed to place order');
            }

            showToast(`Order Confirmed! ${orderInfo}`);
            cart = [];
            localStorage.removeItem('pos_cart');
            setTimeout(() => window.location.href = '/orders.php', 800);
        } catch (err) {
            console.error('Order error', err);
            showToast(err.message || 'Could not place order');
        } finally {
            if (btn) { btn.innerText = prevText || 'CONFIRM ORDER'; btn.disabled = false; }
            renderCartPageItems();
        }
    };

    // 2. RENDER CART
    function renderCartPageItems() {
        const list = document.getElementById('cartList');
        
        if(cart.length === 0) {
            list.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket" style="font-size:48px; color:#ddd; margin-bottom:20px;"></i>
                    <p style="font-size:16px; font-weight:600;">Your cart is empty.</p>
                    <a href="index.php" style="color:var(--primary); font-weight:700; margin-top:15px; display:inline-block; text-decoration:none;">Browse Menu</a>
                </div>`;
            updateTotals();
            return;
        }

        const grouped = {};
        cart.forEach(item => {
            const key = `${item.id}-${item.selectedSize}`;
            if (!grouped[key]) grouped[key] = { ...item, qty: 0 };
            grouped[key].qty += 1;
        });
        const groupedArr = Object.values(grouped);

        list.innerHTML = groupedArr.map(item => `
            <div class="cart-item">
                <img src="${item.img}" alt="${item.name}">
                <div class="cart-item-details">
                    <div class="cart-item-title">${item.name}</div>
                    <div class="cart-item-variant">Size: ${item.selectedSize}</div>
                    <div class="cart-item-price">${formatCurrency(item.finalPrice * item.qty)}</div>
                </div>
                <div class="cart-controls">
                    <button class="cart-btn-qty" onclick="modifyCartQty(${item.id}, '${item.selectedSize}', -1)">-</button>
                    <span style="font-weight:600; font-size:14px; width:20px; text-align:center;">${item.qty}</span>
                    <button class="cart-btn-qty" onclick="modifyCartQty(${item.id}, '${item.selectedSize}', 1)">+</button>
                </div>
            </div>
        `).join('');

        updateTotals();
    }

    // 3. UPDATE TOTALS
    function updateTotals() {
        const { subtotal, deliveryFee, total } = getTotals();
        document.getElementById('summarySubtotal').innerText = formatCurrency(subtotal);
        document.getElementById('summaryDelivery').innerText = formatCurrency(deliveryFee);
        document.getElementById('summaryTotal').innerText = formatCurrency(total);
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.className = 'toast';
        t.innerHTML = `<i class="fas fa-check-circle" style="color:#2ED573"></i> <span>${msg}</span>`;
        document.getElementById('toastContainer').appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }

    // Initialize page
    window.addEventListener('DOMContentLoaded', () => {
        console.log('=== Cart Page Debug ===');
        console.log('Current User ID:', currentUserId);
        console.log('User Address from PHP:', userAddress);
        console.log('User Address type:', typeof userAddress);
        console.log('User Address length:', userAddress ? userAddress.length : 0);
        
        renderCartPageItems();
        
        // Pre-fill user address if available
        const addressField = document.getElementById('deliveryAddress');
        console.log('Address field element:', addressField);
        
        if (addressField) {
            if (userAddress && userAddress.trim() !== '') {
                addressField.value = userAddress;
                console.log('✓ Address filled successfully:', userAddress);
            } else {
                console.log('✗ No address to fill (userAddress is empty)');
            }
        } else {
            console.log('✗ Address field not found!');
        }
        
        // Ensure initial tab state is correct
        switchTab('delivery');
    });
</script>
</body>
</html>
