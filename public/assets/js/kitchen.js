/**
 * Kitchen Display System - JavaScript
 * Handles real-time order updates and UI interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    // Modal Elements
    const servedModal = document.getElementById('served-modal');
    const showServedBtn = document.getElementById('btn-show-served');
    const closeModalBtn = document.getElementById('close-modal');
    const mainContentArea = document.querySelector('.main-content-area');

    // Auto-refresh interval (in milliseconds)
    const REFRESH_INTERVAL = 30000; // 30 seconds
    let refreshTimer = null;

    // Initialize
    initializeEventListeners();
    startAutoRefresh();

    /**
     * Initialize all event listeners
     */
    function initializeEventListeners() {
        // Modal controls
        if (showServedBtn) {
            showServedBtn.addEventListener('click', () => {
                servedModal.style.display = 'flex';
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                servedModal.style.display = 'none';
            });
        }

        // Close modal on background click
        servedModal.addEventListener('click', (e) => {
            if (e.target === servedModal) {
                servedModal.style.display = 'none';
            }
        });

        // Order action buttons (using event delegation)
        mainContentArea.addEventListener('click', handleOrderAction);
    }

    /**
     * Handle order action button clicks
     */
    async function handleOrderAction(e) {
        if (!e.target.classList.contains('action-btn')) return;

        const btn = e.target;
        const orderId = btn.dataset.orderId;
        const action = btn.dataset.action;

        if (!orderId || !action) {
            console.error('Missing order ID or action');
            return;
        }

        // Disable button and show loading state
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Processing...';

        try {
            const response = await fetch('../api/kitchen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    action: action
                })
            });

            const data = await response.json();

            if (data.success) {
                // Move card to next column
                moveCardToNextStage(btn.closest('.order-card'), action);
                showToast('Order updated successfully', 'success');
            } else {
                showToast(data.message || 'Failed to update order', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        } catch (error) {
            console.error('Error updating order:', error);
            showToast('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }

    /**
     * Move card to the next stage (column)
     */
    function moveCardToNextStage(card, action) {
        const currentList = card.parentElement;
        let targetList = null;
        let newBtnAction = '';
        let newBtnText = '';

        // Determine target column based on action
        if (action === 'start') {
            targetList = document.getElementById('process-list');
            newBtnAction = 'finish';
            newBtnText = 'Finish';
        } else if (action === 'finish') {
            targetList = document.getElementById('ready-list');
            newBtnAction = 'served';
            newBtnText = 'Served';
        } else if (action === 'served') {
            // Remove from board (served orders)
            card.remove();
            updateCounts();
            return;
        }

        if (targetList) {
            // Update button
            const btn = card.querySelector('.action-btn');
            btn.dataset.action = newBtnAction;
            btn.textContent = newBtnText;
            btn.disabled = false;

            // Move card
            targetList.appendChild(card);

            // Remove empty state if exists
            const emptyState = targetList.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }

            // Add empty state to old list if needed
            if (currentList.children.length === 0) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                
                if (currentList.id === 'new-list') {
                    emptyDiv.textContent = 'No new orders';
                } else if (currentList.id === 'process-list') {
                    emptyDiv.textContent = 'No orders in process';
                } else if (currentList.id === 'ready-list') {
                    emptyDiv.textContent = 'No orders ready';
                }
                
                currentList.appendChild(emptyDiv);
            }

            // Update counts
            updateCounts();

            // Scroll to new card
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    /**
     * Update order counts in header
     */
    function updateCounts() {
        const counts = {
            new: document.querySelectorAll('#new-list .order-card').length,
            process: document.querySelectorAll('#process-list .order-card').length,
            ready: document.querySelectorAll('#ready-list .order-card').length,
        };

        document.getElementById('count-new').textContent = counts.new;
        document.getElementById('count-process').textContent = counts.process;
        document.getElementById('count-ready').textContent = counts.ready;

        // Update served count (increment)
        if (counts.served !== undefined) {
            const servedEl = document.getElementById('count-served');
            let current = parseInt(servedEl.textContent) || 0;
            servedEl.textContent = current + 1;
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create new toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Auto-refresh page to get latest orders
     */
    function startAutoRefresh() {
        refreshTimer = setInterval(() => {
            // Only refresh if modal is not open
            if (servedModal.style.display !== 'flex') {
                location.reload();
            }
        }, REFRESH_INTERVAL);
    }

    /**
     * Stop auto-refresh (useful when user is actively interacting)
     */
    function stopAutoRefresh() {
        if (refreshTimer) {
            clearInterval(refreshTimer);
            refreshTimer = null;
        }
    }

    /**
     * Update elapsed time for all orders
     */
    function updateElapsedTimes() {
        document.querySelectorAll('.order-card').forEach(card => {
            const header = card.querySelector('.card-header-strip');
            const timeMatch = header.textContent.match(/\| ([^|]+)$/);
            
            if (timeMatch) {
                // This would require storing the original timestamp
                // For now, we'll rely on page refresh
            }
        });
    }

    // Update times every minute
    setInterval(updateElapsedTimes, 60000);

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // ESC to close modal
        if (e.key === 'Escape' && servedModal.style.display === 'flex') {
            servedModal.style.display = 'none';
        }
        
        // R to refresh
        if (e.key === 'r' || e.key === 'R') {
            if (e.ctrlKey || e.metaKey) {
                return; // Allow browser refresh
            }
            location.reload();
        }
    });

    // Visibility change - pause refresh when tab not visible
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAutoRefresh();
        } else {
            startAutoRefresh();
        }
    });
});
