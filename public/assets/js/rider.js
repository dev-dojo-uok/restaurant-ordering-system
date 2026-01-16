// DOM Elements
const ordersGrid = document.getElementById('ordersGrid');
const noOrders = document.getElementById('noOrders');
const modalOverlay = document.getElementById('modalOverlay');
const completedOrdersList = document.getElementById('completedOrdersList');
const noCompleted = document.getElementById('noCompleted');
const viewCompletedBtn = document.getElementById('viewCompletedBtn');
const closeModal = document.getElementById('closeModal');
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const closeSidebar = document.getElementById('closeSidebar');

// Mark order as picked up
async function markPickedUp(orderId) {
    if (!confirm('Confirm that you have picked up this order?')) {
        return;
    }

    try {
        const response = await fetch('/api/rider.php?action=mark_picked_up', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId })
        });

        const data = await response.json();

        if (data.success) {
            // Update the card UI
            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderCard) {
                // Update status badge
                const statusBadge = orderCard.querySelector('.status-badge');
                statusBadge.className = 'status-badge status-on_the_way';
                statusBadge.textContent = 'On The Way';

                // Replace button
                const button = orderCard.querySelector('.pickup-btn');
                button.className = 'finish-btn';
                button.textContent = 'DELIVERED';
                button.setAttribute('onclick', `markDelivered(${orderId})`);
            }
            showToast('Order marked as picked up!');
        } else {
            alert('Failed to update order: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating the order');
    }
}

// Mark order as delivered
async function markDelivered(orderId) {
    if (!confirm('Confirm that this order has been delivered?')) {
        return;
    }

    try {
        const response = await fetch('/api/rider.php?action=mark_delivered', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId })
        });

        const data = await response.json();

        if (data.success) {
            // Remove the card with animation
            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderCard) {
                orderCard.classList.add('finishing');
                setTimeout(() => {
                    orderCard.remove();
                    checkEmptyOrders();
                }, 500);
            }
            showToast('Order completed successfully!');
        } else {
            alert('Failed to update order: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating the order');
    }
}

// Check if there are no more orders
function checkEmptyOrders() {
    const remainingOrders = ordersGrid.querySelectorAll('.order-card');
    if (remainingOrders.length === 0) {
        noOrders.style.display = 'block';
    }
}

// Show completed orders modal
viewCompletedBtn.addEventListener('click', async () => {
    await loadCompletedOrders();
    modalOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
});

// Load completed orders from server
async function loadCompletedOrders() {
    noCompleted.textContent = 'Loading...';
    noCompleted.style.display = 'block';

    // Clear existing items
    const existingItems = completedOrdersList.querySelectorAll('.completed-order-item');
    existingItems.forEach(item => item.remove());

    try {
        const response = await fetch('/api/rider.php?action=get_completed');
        const data = await response.json();

        if (data.success && data.orders.length > 0) {
            noCompleted.style.display = 'none';
            renderCompletedOrders(data.orders);
        } else {
            noCompleted.textContent = 'No completed orders today.';
        }
    } catch (error) {
        console.error('Error loading completed orders:', error);
        noCompleted.textContent = 'Failed to load completed orders.';
    }
}

// Render completed orders in modal
function renderCompletedOrders(orders) {
    orders.forEach(order => {
        const orderElement = document.createElement('div');
        orderElement.className = 'completed-order-item';
        
        // Build items list
        let itemsList = '';
        order.items.forEach(item => {
            const variantText = item.variant_name !== 'Regular' ? ` (${item.variant_name})` : '';
            itemsList += `<p>${item.quantity}x ${item.item_name}${variantText}</p>`;
        });

        orderElement.innerHTML = `
            <h4>Order #${order.id}</h4>
            <div class="order-info">
                <p><strong>Name:</strong> ${order.customer_name}</p>
                <p><strong>Address:</strong> ${order.delivery_address}</p>
                <p><strong>Contact:</strong> ${order.customer_phone}</p>
                <p><strong>Amount:</strong> Rs. ${parseFloat(order.total_amount).toFixed(2)}</p>
                ${order.notes ? `<p><strong>Notes:</strong> ${order.notes}</p>` : ''}
            </div>
            <div class="order-items-info">
                <strong>Items:</strong>
                ${itemsList}
            </div>
            <p class="completed-time">✓ Completed at: ${new Date(order.completed_at).toLocaleString()}</p>
        `;
        completedOrdersList.appendChild(orderElement);
    });
}

// Close modal
closeModal.addEventListener('click', closeModalHandler);
modalOverlay.addEventListener('click', (e) => {
    if (e.target === modalOverlay) {
        closeModalHandler();
    }
});

function closeModalHandler() {
    modalOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Sidebar toggle
menuBtn.addEventListener('click', () => {
    sidebar.classList.add('active');
    sidebarOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
});

closeSidebar.addEventListener('click', closeSidebarHandler);
sidebarOverlay.addEventListener('click', closeSidebarHandler);

function closeSidebarHandler() {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Toast notification
function showToast(message) {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Close modal on Escape
    if (e.key === 'Escape') {
        if (modalOverlay.classList.contains('active')) {
            closeModalHandler();
        }
        if (sidebar.classList.contains('active')) {
            closeSidebarHandler();
        }
    }
});

// Auto-refresh orders every 30 seconds
setInterval(() => {
    location.reload();
}, 30000);
