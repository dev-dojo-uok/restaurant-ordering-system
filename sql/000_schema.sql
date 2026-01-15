-- =================================================
-- FOOD ORDERING SYSTEM DATABASE SCHEMA
-- PostgreSQL Database
-- =================================================

-- Drop existing tables if they exist (for fresh start)
DROP TABLE IF EXISTS payment_transactions CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS cart CASCADE;
DROP TABLE IF EXISTS menu_item_variants CASCADE;
DROP TABLE IF EXISTS menu_items CASCADE;
DROP TABLE IF EXISTS menu_categories CASCADE;
DROP TABLE IF EXISTS carousel_banners CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- =================================================
-- 1. USERS TABLE
-- Stores all users (Admin, Cashier, Kitchen, Rider, Customer)
-- =================================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,                    -- Auto-incrementing ID
    username VARCHAR(50) UNIQUE NOT NULL,     -- Unique username for login
    email VARCHAR(100) UNIQUE NOT NULL,       -- Unique email
    password VARCHAR(255) NOT NULL,           -- Hashed password (using password_hash())
    full_name VARCHAR(100) NOT NULL,          -- User's full name
    role VARCHAR(20) NOT NULL DEFAULT 'customer', -- Role: admin, cashier, kitchen, rider, customer
    phone VARCHAR(15),                        -- Contact number
    address TEXT,                             -- Delivery address (for customers)
    is_active BOOLEAN DEFAULT true,           -- Account active/inactive
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Constraint: role must be one of these values
    CONSTRAINT check_role CHECK (role IN ('admin', 'cashier', 'kitchen', 'rider', 'customer'))
);

-- Create index on role for faster queries (when filtering by role)
CREATE INDEX idx_users_role ON users(role);

-- =================================================
-- 2. CAROUSEL BANNERS TABLE
-- Home page carousel/banner images
-- =================================================
CREATE TABLE carousel_banners (
    id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,               -- Banner title
    description TEXT,                          -- Banner description
    image_url VARCHAR(255) NOT NULL,           -- Path to banner image
    link_url VARCHAR(255),                     -- Optional link when clicked
    is_active BOOLEAN DEFAULT true,            -- Show/hide banner
    display_order INT DEFAULT 0,               -- For sorting banners
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =================================================
-- 3. MENU CATEGORIES TABLE
-- Pizza, Burgers, Drinks, etc.
-- =================================================
CREATE TABLE menu_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,        -- Category name
    description TEXT,                          -- Optional description
    display_order INT DEFAULT 0,               -- For sorting categories
    is_active BOOLEAN DEFAULT true,            -- Show/hide category
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =================================================
-- 4. MENU ITEMS TABLE
-- Individual food items
-- =================================================
CREATE TABLE menu_items (
    id SERIAL PRIMARY KEY,
    category_id INT NOT NULL,                  -- Foreign key to categories
    name VARCHAR(100) NOT NULL,                -- Item name
    description TEXT,                          -- Item description
    image_url VARCHAR(255),                    -- Path to image
    is_available BOOLEAN DEFAULT true,         -- In stock or not
    is_featured BOOLEAN DEFAULT false,         -- Featured/Hot item
    is_special BOOLEAN DEFAULT false,          -- Special offer
    is_bestseller BOOLEAN DEFAULT false,       -- Bestseller tag
    preparation_time INT DEFAULT 15,           -- Time in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key relationship
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
);

-- Index for faster category filtering
CREATE INDEX idx_menu_items_category ON menu_items(category_id);

-- =================================================
-- 5. MENU ITEM VARIANTS TABLE
-- Variants for menu items (e.g., Small, Medium, Large)
-- =================================================
CREATE TABLE menu_item_variants (
    id SERIAL PRIMARY KEY,
    menu_item_id INT NOT NULL,                 -- Which menu item
    variant_name VARCHAR(50) NOT NULL,         -- e.g., "Small", "Medium", "Large", "Regular"
    price DECIMAL(10, 2) NOT NULL,             -- Price for this variant
    is_default BOOLEAN DEFAULT false,          -- Default variant to show
    is_available BOOLEAN DEFAULT true,         -- Variant in stock
    display_order INT DEFAULT 0,               -- For sorting variants
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key relationship
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    
    -- Each item can have unique variant names
    UNIQUE(menu_item_id, variant_name)
);

-- Index for faster variant queries
CREATE INDEX idx_menu_item_variants_item ON menu_item_variants(menu_item_id);

-- =================================================
-- 6. CART TABLE
-- Temporary storage before order is placed
-- Only used for delivery and takeaway orders
-- =================================================
CREATE TABLE cart (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,                      -- Which customer's cart
    menu_item_id INT NOT NULL,                 -- Which item
    variant_id INT,                            -- Which variant (nullable for legacy)
    quantity INT NOT NULL DEFAULT 1,           -- How many
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES menu_item_variants(id) ON DELETE CASCADE,
    
    -- A user can't have duplicate items+variant in cart (update quantity instead)
    UNIQUE(user_id, menu_item_id, variant_id)
);

-- =================================================
-- 7. ORDERS TABLE
-- Main orders table
-- =================================================
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    user_id INT,                               -- NULL for dine-in (walk-in customer)
    order_type VARCHAR(20) NOT NULL,           -- delivery, dine_in, takeaway
    status VARCHAR(30) NOT NULL DEFAULT 'ordered', -- Order status
    total_amount DECIMAL(10, 2) NOT NULL,      -- Total price
    
    -- Customer details (stored in case user is deleted or for dine-in)
    customer_name VARCHAR(100),
    customer_phone VARCHAR(15),
    delivery_address TEXT,                     -- Only for delivery orders
    
    -- Assignment
    rider_id INT,                              -- Only for delivery orders
    
    -- Table number (only for dine-in)
    table_number INT,
    
    -- Payment and notes
    payment_status VARCHAR(20) DEFAULT 'pending', -- pending, completed, refunded
    notes TEXT,                                -- Special instructions or notes
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,                    -- When order finished
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL,
    
    -- Constraints
    CONSTRAINT check_order_type CHECK (order_type IN ('delivery', 'dine_in', 'takeaway')),
    CONSTRAINT check_status CHECK (status IN (
        'ordered',
        'under_preparation',
        'ready_to_collect',      -- delivery
        'ready_to_serve',        -- dine-in
        'ready_for_pickup',      -- takeaway
        'on_the_way',            -- delivery
        'delivered',             -- delivery
        'completed',             -- dine-in
        'collected',             -- takeaway
        'cancelled'
    ))
);

-- Indexes for faster queries
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_rider ON orders(rider_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_type ON orders(order_type);

-- =================================================
-- 8. ORDER ITEMS TABLE
-- Individual items within an order
-- =================================================
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,                     -- Which order
    menu_item_id INT NOT NULL,                 -- Which item was ordered
    variant_id INT,                            -- Which variant was ordered
    quantity INT NOT NULL,                     -- How many
    price DECIMAL(10, 2) NOT NULL,             -- Price at time of order
    
    -- Store item details in case menu item is deleted later
    item_name VARCHAR(100) NOT NULL,
    variant_name VARCHAR(50),                  -- Store variant name
    
    -- Foreign keys
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES menu_item_variants(id) ON DELETE SET NULL
);

-- Index for faster order queries
CREATE INDEX idx_order_items_order ON order_items(order_id);

-- =================================================
-- 9. PAYMENT TRANSACTIONS TABLE
-- Track split payments for orders
-- =================================================
CREATE TABLE payment_transactions (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(20) NOT NULL,       -- cash, card, online
    amount DECIMAL(10, 2) NOT NULL,
    transaction_reference VARCHAR(100),        -- For card/online transactions
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE INDEX idx_payment_transactions_order ON payment_transactions(order_id);

-- =================================================
-- TABLE COMMENTS
-- =================================================
COMMENT ON TABLE users IS 'Stores all system users with role-based access';
COMMENT ON TABLE carousel_banners IS 'Home page carousel/banner images';
COMMENT ON TABLE menu_categories IS 'Food categories for organizing menu';
COMMENT ON TABLE menu_items IS 'Individual menu items without pricing';
COMMENT ON TABLE menu_item_variants IS 'Size/variant options with pricing for menu items';
COMMENT ON TABLE cart IS 'Temporary cart for customers before checkout';
COMMENT ON TABLE orders IS 'Main orders table with order type and status';
COMMENT ON TABLE order_items IS 'Line items for each order';
COMMENT ON TABLE payment_transactions IS 'Payment records for split/multiple payments';
