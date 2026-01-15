-- =================================================
-- CREATE DEFAULT ADMIN USER
-- This script creates a default admin account if it doesn't exist
-- Runs automatically on Docker container initialization
-- =================================================

-- Insert default admin user if not exists
-- Username: admin
-- Password: admin123 (should be changed after first login)
-- Email: admin@flavorpos.com

INSERT INTO users (
    username, 
    email, 
    password, 
    full_name, 
    role, 
    phone, 
    is_active
)
SELECT 
    'admin',
    'admin@flavorpos.com',
    '$2y$10$uaOtmCciP2FKgOWrDGr4auQ/Icwtxm678N1LsNcIn7BunKMX9ozy6', -- password: admin123
    'System Administrator',
    'admin',
    '+1234567890',
    true
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin' OR email = 'admin@flavorpos.com'
);

-- Insert default cashier user if not exists
-- Username: cashier
-- Password: cashier123
-- Email: cashier@flavorpos.com

INSERT INTO users (
    username, 
    email, 
    password, 
    full_name, 
    role, 
    phone, 
    is_active
)
SELECT 
    'cashier',
    'cashier@flavorpos.com',
    '$2y$10$7X6FxsChjaqvOPNDmJZJseniE36YaQJHlvWssEKySC.yF0BMgR.9q', -- password: cashier123
    'Default Cashier',
    'cashier',
    '+1234567891',
    true
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'cashier' OR email = 'cashier@flavorpos.com'
);

-- Insert default kitchen user if not exists
-- Username: kitchen
-- Password: kitchen123
-- Email: kitchen@flavorpos.com

INSERT INTO users (
    username, 
    email, 
    password, 
    full_name, 
    role, 
    phone, 
    is_active
)
SELECT 
    'kitchen',
    'kitchen@flavorpos.com',
    '$2y$10$NOrK6oxYAvZ4TWLJ14/Ur.6Qc/tHVyOIXU.FpP9e4dAG/t.XkEFKG', -- password: kitchen123
    'Kitchen Staff',
    'kitchen',
    '+1234567892',
    true
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'kitchen' OR email = 'kitchen@flavorpos.com'
);

-- Display created users
SELECT username, email, role, full_name, created_at 
FROM users 
WHERE username IN ('admin', 'cashier', 'kitchen')
ORDER BY role;

-- Print confirmation message
DO $$
BEGIN
    RAISE NOTICE 'Default users created successfully!';
    RAISE NOTICE 'Admin credentials - Username: admin, Password: admin123';
    RAISE NOTICE 'Cashier credentials - Username: cashier, Password: cashier123';
    RAISE NOTICE 'Kitchen credentials - Username: kitchen, Password: kitchen123';
    RAISE NOTICE 'IMPORTANT: Please change these default passwords immediately after first login!';
END $$;
