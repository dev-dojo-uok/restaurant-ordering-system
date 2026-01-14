(function () {
    const uid = window.__PROFILE__?.userId;
    if (!uid) return;

    // STRICT TAB VISIBILITY
       
    const tabs = document.querySelectorAll('.tab-btn');

    const panels = {
        info: document.getElementById('tab-info'),
        orders: document.getElementById('tab-orders'),
        password: document.getElementById('tab-password')
    };

    function hideAllPanels() {
        Object.values(panels).forEach(panel => {
            panel.style.display = 'none';
        });
    }

    function deactivateAllTabs() {
        tabs.forEach(tab => tab.classList.remove('active'));
    }

    function showPanel(name) {
        hideAllPanels();
        deactivateAllTabs();

        const tab = document.querySelector(`.tab-btn[data-tab="${name}"]`);
        const panel = panels[name];

        if (tab && panel) {
            tab.classList.add('active');
            panel.style.display = 'block';

            if (name === 'orders') {
                loadOrders();
            }
        }
    }

    // Attach click handlers
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            showPanel(tab.dataset.tab);
        });
    });

    // Default view
    showPanel('info');

    
      // ELEMENT REFERENCES
       
    const full_name = document.getElementById('full_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const address = document.getElementById('address');
    const username = document.getElementById('username');
    const infoMsg = document.getElementById('infoMsg');
    const pwdMsg = document.getElementById('pwdMsg');
    const ordersList = document.getElementById('ordersList');

   
       //LOAD PROFILE DATA
     
    fetch(`/api/users/${uid}`)
        .then(res => res.json())
        .then(u => {
            if (!u || u.error) return;

            full_name.value = u.full_name || '';
            email.value = u.email || '';
            phone.value = u.phone || '';
            address.value = u.address || '';
            username.value = u.username || '';

            document.getElementById('profileName').textContent =
                u.full_name || 'My Profile';
        });

    
       // SAVE PERSONAL INFO
       
    document.getElementById('formInfo').addEventListener('submit', e => {
        e.preventDefault();

        infoMsg.textContent = '';
        infoMsg.className = 'msg';

        fetch(`/api/users/${uid}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                full_name: full_name.value.trim(),
                email: email.value.trim(),
                phone: phone.value.trim(),
                address: address.value.trim()
            })
        })
        .then(r => r.json())
        .then(res => {
            infoMsg.textContent = res?.message ? 'Saved successfully' : 'Save failed';
            infoMsg.classList.add(res?.message ? 'ok' : 'err');
        })
        .catch(() => {
            infoMsg.textContent = 'Save failed';
            infoMsg.classList.add('err');
        });
    });


    // CHANGE PASSWORD
       
    document.getElementById('formPassword').addEventListener('submit', e => {
        e.preventDefault();

        pwdMsg.textContent = '';
        pwdMsg.className = 'msg';

        const p1 = new_password.value;
        const p2 = confirm_password.value;

        if (p1.length < 6) {
            pwdMsg.textContent = 'Password must be at least 6 characters';
            pwdMsg.classList.add('err');
            return;
        }

        if (p1 !== p2) {
            pwdMsg.textContent = 'Passwords do not match';
            pwdMsg.classList.add('err');
            return;
        }

        fetch(`/api/users/${uid}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: p1 })
        })
        .then(r => r.json())
        .then(res => {
            pwdMsg.textContent = res?.message ? 'Password updated' : 'Update failed';
            pwdMsg.classList.add(res?.message ? 'ok' : 'err');

            if (res?.message) {
                new_password.value = '';
                confirm_password.value = '';
            }
        });
    });

    
    // LOAD ORDER HISTORY
       
    function loadOrders() {
        ordersList.innerHTML = '<div class="muted">Loading...</div>';

        fetch(`/api/orders?user_id=${uid}`)
            .then(r => r.json())
            .then(rows => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    ordersList.innerHTML = '<div class="muted">No orders found.</div>';
                    return;
                }

                ordersList.innerHTML = rows.map(o => `
                    <div class="order-card">
                        <div class="order-head">
                            <strong>#${o.id}</strong>
                            <span>Rs ${(o.total_amount || 0).toFixed(2)}</span>
                        </div>
                        <div class="order-meta">
                            ${new Date(o.created_at).toLocaleString()} • ${o.status}
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {
                ordersList.innerHTML = '<div class="muted">Failed to load orders</div>';
            });
    }
})();
