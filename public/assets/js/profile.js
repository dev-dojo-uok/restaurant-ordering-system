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
        password: document.getElementById('tab-password')
    };

    function showPanel(name) {
        Object.values(panels).forEach(p => {
            if (p) p.classList.remove('active');
        });
        tabs.forEach(t => t.classList.remove('active'));

        const tab = document.querySelector(`.tab-btn[data-tab="${name}"]`);
        const panel = panels[name];

        if (tab && panel) {
            tab.classList.add('active');
            panel.classList.add('active');
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
    const addressInput = document.getElementById('address');
    const infoMsg = document.getElementById('infoMsg');

    // Password fields
    const currentPasswordInput = document.getElementById('current_password');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const pwdMsg = document.getElementById('pwdMsg');

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
                addressInput.value = user.address || '';
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
                address: addressInput.value.trim(),
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

    // =================================================================
    // INITIALIZATION
    // =================================================================
    function init() {
        showPanel('info'); // Show the default panel
        loadProfileData(); // Load the user's main profile data
    }

    init();
})();
