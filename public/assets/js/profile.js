(function () {
    const uid = window.__PROFILE__?.userId;
    if (!uid) {
        console.error("User ID not found.");
        return;
    }

    // =================================================================
    // TAB MANAGEMENT
    // =================================================================
    const tabs = document.querySelectorAll('.tab-btn');
    const panels = {
        info: document.getElementById('tab-info'),
        orders: document.getElementById('tab-orders'),
        password: document.getElementById('tab-password')
    };

    function showPanel(name) {
        Object.values(panels).forEach(p => p.style.display = 'none');
        tabs.forEach(t => t.classList.remove('active'));

        const tab = document.querySelector(`.tab-btn[data-tab="${name}"]`);
        const panel = panels[name];

        if (tab && panel) {
            tab.classList.add('active');
            panel.style.display = 'block';
            // Lazy load content
            if (name === 'orders') loadOrders();
            if (name === 'info') loadAddresses();
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => showPanel(tab.dataset.tab));
    });

    // =================================================================
    // ELEMENT REFERENCES
    // =================================================================
    const formInfo = document.getElementById('formInfo');
    const formPassword = document.getElementById('formPassword');
    const formAddress = document.getElementById('formAddress');

    // Personal Info fields
    const fullNameInput = document.getElementById('full_name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const usernameInput = document.getElementById('username');
    const infoMsg = document.getElementById('infoMsg');

    // Password fields
    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const pwdMsg = document.getElementById('pwdMsg');

    //const newPasswordInput = document.getElementById('new_password');
    //const confirmPasswordInput = document.getElementById('confirm_password');
    //const pwdMsg = document.getElementById('pwdMsg');

    // Address fields & containers
    const addressListContainer = document.getElementById('addressList');
    const addressModal = document.getElementById('addressModal');
    const showAddressModalBtn = document.getElementById('showAddressModalBtn');
    const closeAddressModalBtn = document.getElementById('closeAddressModalBtn');
    const cancelAddressBtn = document.getElementById('cancelAddressBtn');

    // =================================================================
    // INITIAL DATA LOADING
    // =================================================================
    function loadProfileData() {
        fetch(`/api/users/${uid}`)
            .then(res => res.ok ? res.json() : Promise.reject('Failed to load user'))
            .then(user => {
                if (!user) return;
                fullNameInput.value = user.full_name || '';
                emailInput.value = user.email || '';
                phoneInput.value = user.phone || '';
                usernameInput.value = user.username || '';
                document.getElementById('profileName').textContent = user.full_name || 'My Profile';
            })
            .catch(err => console.error("Error loading profile:", err));
    }

    // =================================================================
    // PERSONAL INFO FORM
    // =================================================================
    formInfo.addEventListener('submit', e => {
        e.preventDefault();
        infoMsg.textContent = 'Saving...';
        infoMsg.className = 'msg';

        fetch(`/api/users/${uid}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                full_name: fullNameInput.value.trim(),
                email: emailInput.value.trim(),
                phone: phoneInput.value.trim(),
            })
        })
        .then(res => res.ok ? res.json() : Promise.reject('Save failed'))
        .then(res => {
            infoMsg.textContent = 'Saved successfully!';
            infoMsg.classList.add('ok');
            document.getElementById('profileName').textContent = fullNameInput.value.trim();
        })
        .catch(() => {
            infoMsg.textContent = 'Save failed. Please try again.';
            infoMsg.classList.add('err');
        });
    });

    // =================================================================
    // PASSWORD CHANGE FORM
    // =================================================================
    
    formPassword.addEventListener('submit', e => {
    e.preventDefault();
    pwdMsg.textContent = '';
    pwdMsg.className = 'msg';

    const current = currentPasswordInput.value.trim();
    const p1 = newPasswordInput.value.trim();
    const p2 = confirmPasswordInput.value.trim();

    if (!current) {
        pwdMsg.textContent = 'Current password is required.';
        pwdMsg.classList.add('err');
        return;
    }

    if (p1.length < 6) {
        pwdMsg.textContent = 'New password must be at least 6 characters.';
        pwdMsg.classList.add('err');
        return;
    }

    if (p1 !== p2) {
        pwdMsg.textContent = 'Passwords do not match.';
        pwdMsg.classList.add('err');
        return;
    }

    fetch(`/api/users/${uid}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            current_password: current,
            new_password: p1
        })
    })
    .then(res => res.ok ? res.json() : Promise.reject('Invalid current password'))
    .then(() => {
        pwdMsg.textContent = 'Password updated successfully!';
        pwdMsg.classList.add('ok');
        formPassword.reset();
    })
    .catch(err => {
        pwdMsg.textContent = err || 'Password update failed.';
        pwdMsg.classList.add('err');
    });
    });

    /*
    formPassword.addEventListener('submit', e => {
        e.preventDefault();
        pwdMsg.textContent = '';
        pwdMsg.className = 'msg';

        const p1 = newPasswordInput.value;
        const p2 = confirmPasswordInput.value;

        if (p1.length < 6) {
            pwdMsg.textContent = 'Password must be at least 6 characters.';
            pwdMsg.classList.add('err');
            return;
        }
        if (p1 !== p2) {
            pwdMsg.textContent = 'Passwords do not match.';
            pwdMsg.classList.add('err');
            return;
        }

        fetch(`/api/users/${uid}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: p1 })
        })
        .then(res => res.ok ? res.json() : Promise.reject('Update failed'))
        .then(() => {
            pwdMsg.textContent = 'Password updated successfully!';
            pwdMsg.classList.add('ok');
            formPassword.reset();
        })
        .catch(() => {
            pwdMsg.textContent = 'Update failed. Please try again.';
            pwdMsg.classList.add('err');
        });
    });

    */

    // =================================================================
    // ADDRESS MANAGEMENT
    // =================================================================
    function toggleAddressModal(show) {
        addressModal.style.display = show ? 'flex' : 'none';
        if (!show) formAddress.reset();
    }

    showAddressModalBtn.addEventListener('click', () => toggleAddressModal(true));
    closeAddressModalBtn.addEventListener('click', () => toggleAddressModal(false));
    cancelAddressBtn.addEventListener('click', () => toggleAddressModal(false));

    function renderAddresses(addresses) {
        if (!Array.isArray(addresses) || addresses.length === 0) {
            addressListContainer.innerHTML = '<div class="muted">No saved addresses.</div>';
            return;
        }
        addressListContainer.innerHTML = addresses.map(addr => `
            <div class="address-card">
                ${addr.is_default ? '<span class="badge">Default</span>' : ''}
                <strong>${addr.address_type}</strong>
                <p>
                    ${addr.street_address}<br>
                    ${addr.city}, ${addr.state} ${addr.zip_code}<br>
                    Phone: ${addr.phone || 'N/A'}
                </p>
                <div class="address-actions">
                    <button class="btn-sm-icon" onclick="window.deleteAddress(${addr.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    function loadAddresses() {
        addressListContainer.innerHTML = '<div class="muted">Loading addresses...</div>';
        fetch(`/api/user-addresses?user_id=${uid}`)
            .then(res => res.ok ? res.json() : Promise.reject('Failed to load addresses'))
            .then(renderAddresses)
            .catch(() => {
                addressListContainer.innerHTML = '<div class="muted err">Could not load addresses.</div>';
            });
    }

    window.deleteAddress = (addressId) => {
        if (!confirm('Are you sure you want to delete this address?')) return;

        fetch(`/api/user-addresses/${addressId}`, { method: 'DELETE' })
            .then(res => {
                if (!res.ok) return Promise.reject('Deletion failed');
                loadAddresses(); // Refresh the list
            })
            .catch(err => alert('Could not delete address.'));
    };

    formAddress.addEventListener('submit', e => {
        e.preventDefault();
        const submitBtn = formAddress.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        const body = {
            user_id: uid,
            address_type: document.getElementById('address_type').value,
            street_address: document.getElementById('street_address').value,
            city: document.getElementById('city').value,
            state: document.getElementById('state').value,
            zip_code: document.getElementById('zip_code').value,
            phone: document.getElementById('address_phone').value,
        };

        fetch('/api/user-addresses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(res => res.ok ? res.json() : Promise.reject('Failed to save'))
        .then(() => {
            toggleAddressModal(false);
            loadAddresses();
        })
        .catch(err => alert('Could not save address. Please check all fields.'))
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Add Address';
        });
    });

    // =================================================================
    // ORDER HISTORY
    // =================================================================
    function loadOrders() {
        const ordersList = document.getElementById('ordersList');
        ordersList.innerHTML = '<div class="muted">Loading orders...</div>';

        fetch(`/api/orders?user_id=${uid}`)
            .then(res => res.ok ? res.json() : Promise.reject('Failed to load orders'))
            .then(rows => {
                if (!Array.isArray(rows) || rows.length === 0) {
                    ordersList.innerHTML = '<div class="muted">No orders found.</div>';
                    return;
                }
                ordersList.innerHTML = rows.map(o => `
                    <div class="order-card">
                        <div class="order-head">
                            <strong>Order #${o.id}</strong>
                            <span>Rs ${Number(o.total_amount || 0).toFixed(2)}</span>
                        </div>
                        <div class="order-meta">
                            ${new Date(o.created_at).toLocaleString()} • <span class="status status-${o.status}">${o.status}</span>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {
                ordersList.innerHTML = '<div class="muted err">Could not load orders.</div>';
            });
    }

    // =================================================================
    // INITIALIZATION
    // =================================================================
    function init() {
        showPanel('info'); // Show the default panel
        loadProfileData(); // Load the user's main profile data
    }

    init();
})();
