-- =================================================
-- FOOD ORDERING SYSTEM DATABASE SCHEMA
-- PostgreSQL Database
-- =================================================

-- Drop existing tables if they exist (for fresh start)
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS cart CASCADE;
DROP TABLE IF EXISTS menu_items CASCADE;
DROP TABLE IF EXISTS menu_categories CASCADE;
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
-- 2. MENU CATEGORIES TABLE
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
-- 3. MENU ITEMS TABLE
-- Individual food items
-- =================================================
CREATE TABLE menu_items (
    id SERIAL PRIMARY KEY,
    category_id INT NOT NULL,                  -- Foreign key to categories
    name VARCHAR(100) NOT NULL,                -- Item name
    description TEXT,                          -- Item description
    price DECIMAL(10, 2) NOT NULL,             -- Price (e.g., 9.99)
    image_url VARCHAR(255),                    -- Path to image
    is_available BOOLEAN DEFAULT true,         -- In stock or not
    preparation_time INT DEFAULT 15,           -- Time in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key relationship
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
);

-- Index for faster category filtering
CREATE INDEX idx_menu_items_category ON menu_items(category_id);

-- =================================================
-- 4. CART TABLE
-- Temporary storage before order is placed
-- Only used for delivery and takeaway orders
-- =================================================
CREATE TABLE cart (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,                      -- Which customer's cart
    menu_item_id INT NOT NULL,                 -- Which item
    quantity INT NOT NULL DEFAULT 1,           -- How many
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    
    -- A user can't have duplicate items in cart (update quantity instead)
    UNIQUE(user_id, menu_item_id)
);

-- =================================================
-- 5. ORDERS TABLE
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
-- 6. ORDER ITEMS TABLE
-- Individual items within an order
-- =================================================
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,                     -- Which order
    menu_item_id INT NOT NULL,                 -- Which item was ordered
    quantity INT NOT NULL,                     -- How many
    price DECIMAL(10, 2) NOT NULL,             -- Price at time of order
    
    -- Store item details in case menu item is deleted later
    item_name VARCHAR(100) NOT NULL,
    
    -- Foreign keys
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
);

-- Index for faster order queries
CREATE INDEX idx_order_items_order ON order_items(order_id);

-- =================================================
-- SAMPLE DATA (Optional - for testing)
-- =================================================

-- Insert an admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');

-- Note: The password above is hashed. To create it in PHP:
-- password_hash('admin123', PASSWORD_DEFAULT)

-- Insert some sample categories
INSERT INTO menu_categories (name, description, display_order) VALUES
('Pizzas', 'Delicious wood-fired pizzas', 1),
('Burgers', 'Juicy gourmet burgers', 2),
('Drinks', 'Refreshing beverages', 3),
('Desserts', 'Sweet treats', 4);

-- Insert some sample menu items
INSERT INTO menu_items (category_id, name, description, price, is_available, preparation_time) VALUES
(1, 'Margherita Pizza', 'Classic pizza with tomato, mozzarella, and basil', 12.99, true, 20),
(1, 'Pepperoni Pizza', 'Pizza loaded with pepperoni slices', 14.99, true, 20),
(2, 'Classic Burger', 'Beef patty with lettuce, tomato, and cheese', 9.99, true, 15),
(2, 'Chicken Burger', 'Grilled chicken with special sauce', 10.99, true, 15),
(3, 'Coca Cola', 'Chilled soft drink', 2.99, true, 2),
(3, 'Fresh Orange Juice', 'Freshly squeezed orange juice', 4.99, true, 5);

COMMENT ON TABLE users IS 'Stores all system users with role-based access';
COMMENT ON TABLE menu_categories IS 'Food categories for organizing menu';
COMMENT ON TABLE menu_items IS 'Individual menu items with pricing';
COMMENT ON TABLE cart IS 'Temporary cart for customers before checkout';
COMMENT ON TABLE orders IS 'Main orders table with order type and status';
COMMENT ON TABLE order_items IS 'Line items for each order';
