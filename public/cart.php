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

        /* HEADER */
        header {
            background: rgba(241, 242, 246, 0.95); backdrop-filter: blur(10px);
            padding: 20px 5%; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); margin-bottom: 30px;
        }
        .brand h1 { font-size: 24px; font-weight: 800; color: var(--dark); }
        .brand span { color: var(--primary); }
        
        /* CART LAYOUT */
        .cart-view { 
            max-width: 1000px; 
            margin: 0 auto; 
            padding: 0 5%; 
            display: grid; 
            grid-template-columns: 1.5fr 1fr; 
            gap: 40px; 
            align-items: start; 
        }

        /* LEFT: ITEMS LIST */
        .cart-list { display: flex; flex-direction: column; gap: 20px; }
        
        .cart-item { 
            background: #fff; 
            border-radius: 15px; 
            padding: 15px; 
            display: flex; 
            gap: 15px; 
            align-items: center; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            transition: var(--transition);
        }
        
        .cart-item img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; }
        .cart-item-details { flex: 1; }
        .cart-item-title { font-weight: 700; font-size: 16px; margin-bottom: 5px; color: var(--dark); }
        .cart-item-variant { font-size: 13px; color: var(--text-grey); margin-bottom: 5px; }
        .cart-item-price { font-weight: 700; color: var(--primary); }
        
        /* CONTROLS (+/-) */
        .cart-controls { display: flex; align-items: center; gap: 10px; background: #f8f9fa; border-radius: 8px; padding: 5px; }
        .cart-btn-qty { width: 25px; height: 25px; border: none; background: #fff; border-radius: 5px; cursor: pointer; font-weight: 700; color: var(--dark); box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.2s; }
        .cart-btn-qty:hover { background: var(--primary); color: white; }

        /* RIGHT: SUMMARY BOX */
        .cart-summary-box { 
            background: #fff; 
            border-radius: 20px; 
            padding: 25px; 
            box-shadow: var(--shadow); 
            position: sticky; 
            top: 20px; 
        }
        
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: var(--text-grey); }
        .summary-row.total { border-top: 2px dashed #eee; padding-top: 20px; margin-top: 20px; font-weight: 800; font-size: 20px; color: var(--dark); }
        
        .btn-checkout { 
            width: 100%; 
            padding: 18px; 
            border: none; 
            border-radius: 12px; 
            background: var(--dark); 
            color: #fff; 
            font-weight: 700; 
            font-size: 16px; 
            cursor: pointer; 
            margin-top: 20px; 
            transition: 0.3s; 
        }
        .btn-checkout:hover { background: var(--primary); transform: translateY(-2px); }
        
        .btn-back {
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none; color: var(--text-grey); font-weight: 600;
            margin-bottom: 20px; transition: 0.2s;
        }
        .btn-back:hover { color: var(--primary); }

        /* EMPTY STATE */
        .empty-cart { text-align: center; padding: 50px; color: var(--text-grey); grid-column: 1 / -1; }
        .empty-cart i { font-size: 40px; margin-bottom: 15px; color: #ddd; }

        @media (max-width: 800px) { 
            .cart-view { grid-template-columns: 1fr; }
            .cart-summary-box { position: static; }
        }

        /* Toast */
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 10px; border-left: 4px solid var(--accent); display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body>

    <header>
        <div class="brand"><h1>FLAVOR <span>POS</span>.</h1></div>
        <a href="index.php" class="btn-back" style="margin:0"><i class="fas fa-home"></i> Menu</a>
    </header>

    <section class="cart-view">
        
        <div class="cart-items-container">
            <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Menu</a>
            <h1 style="font-size: 32px; font-weight: 800; margin-bottom: 25px;">Your Order</h1>
            
            <div id="cartList" class="cart-list">
                </div>
        </div>

        <div class="cart-summary-box">
            <h3 style="margin-bottom: 20px; font-weight: 700;">Payment Summary</h3>
            
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="summarySubtotal">Rs. 0.00</span>
            </div>
            <div class="summary-row">
                <span>Service Charge (10%)</span>
                <span id="summaryService">Rs. 0.00</span>
            </div>
            
            <div class="summary-row total">
                <span>Total</span>
                <span id="summaryTotal" style="color: var(--primary);">Rs. 0.00</span>
            </div>

            <button class="btn-checkout" onclick="placeOrder()">
                CONFIRM ORDER
            </button>
        </div>
    </section>

    <div class="toast-container" id="toastContainer"></div>

<script>
    // 1. INIT STATE
    // We read the 'pos_cart' from LocalStorage which was saved by the main page
    let cart = JSON.parse(localStorage.getItem('pos_cart')) || [];
    
    // Helpers
    const formatCurrency = (val) => `Rs. ${Number(val||0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

    // 2. RENDER CART
    function renderCartPageItems() {
        const list = document.getElementById('cartList');
        
        if(cart.length === 0) {
            list.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <p>Your cart is empty.</p>
                    <a href="index.php" style="color:var(--primary); font-weight:700; margin-top:10px; display:inline-block;">Browse Menu</a>
                </div>`;
            updateTotals();
            return;
        }

        // Group identical items
        const grouped = {};
        cart.forEach(item => {
            const key = `${item.id}-${item.selectedSize}`;
            if (!grouped[key]) grouped[key] = { ...item, qty: 0 };
            grouped[key].qty += 1;
        });
        const groupedArr = Object.values(grouped);

        // Generate HTML
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
        const subtotal = cart.reduce((sum, item) => sum + item.finalPrice, 0);
        const service = subtotal * 0.10;
        
        document.getElementById('summarySubtotal').innerText = formatCurrency(subtotal);
        document.getElementById('summaryService').innerText = formatCurrency(service);
        document.getElementById('summaryTotal').innerText = formatCurrency(subtotal + service);
    }

    // 4. MODIFY QTY
    window.modifyCartQty = (id, size, change) => {
        if(change === 1) {
            // Duplicate an item
            const item = cart.find(i => i.id === id && i.selectedSize === size);
            if(item) cart.push({ ...item, uniqueId: Date.now() });
        } else {
            // Remove an instance
            const idx = cart.findIndex(i => i.id === id && i.selectedSize === size);
            if(idx > -1) cart.splice(idx, 1);
        }
        
        // Save back to LocalStorage so changes persist if they go back to menu
        localStorage.setItem('pos_cart', JSON.stringify(cart));
        renderCartPageItems();
    }

    // 5. PLACE ORDER
    window.placeOrder = () => {
        if(cart.length === 0) return;

        // --- BACKEND INTEGRATION NOTE ---
        // To save to database, use fetch() here to send 'cart' to a PHP endpoint.
        /*
        fetch('api/place_order.php', {
            method: 'POST',
            body: JSON.stringify({ cart: cart, total: ... })
        }).then(...)
        */

        // For now, simulate success:
        showToast("Order Confirmed! Kitchen notified.");
        
        // Clear Cart
        cart = [];
        localStorage.removeItem('pos_cart');
        renderCartPageItems();
    }

    function showToast(msg) {
        const t = document.createElement('div');
        t.className = 'toast';
        t.innerHTML = `<i class="fas fa-check-circle" style="color:#2ED573"></i> <span>${msg}</span>`;
        document.getElementById('toastContainer').appendChild(t);
        setTimeout(() => t.remove(), 3000);
    }

    // Load on start
    renderCartPageItems();

</script>
<script src="/assets/js/profile-icon.js"></script>
</body>
</html>
