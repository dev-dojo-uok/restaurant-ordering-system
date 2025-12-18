-- =================================================
-- ADD HOME PAGE FEATURES
-- 1. Carousel banners table
-- 2. Menu items display flags
-- =================================================

-- Add display flags to menu_items
ALTER TABLE menu_items 
ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS is_special BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS is_bestseller BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS sales_count INT DEFAULT 0;

-- Create carousel_banners table
DROP TABLE IF EXISTS carousel_banners CASCADE;

CREATE TABLE carousel_banners (
    id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    button_text VARCHAR(50),
    button_link VARCHAR(255),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for active banners ordered by display_order
CREATE INDEX idx_carousel_active ON carousel_banners(is_active, display_order);

-- Sample carousel banners
INSERT INTO carousel_banners (title, description, image_url, button_text, button_link, display_order, is_active) VALUES
('Fresh & Delicious Food', 'Order your favorite meals from our kitchen. Fast delivery and great taste!', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200', 'Order Now', '/menu.php', 1, true),
('Today''s Special Offers', 'Get 20% off on all pizza orders today. Limited time offer!', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=1200', 'View Specials', '/menu.php?filter=special', 2, true),
('Fast Delivery Service', 'Hot food delivered to your door in 30 minutes or less.', 'https://images.unsplash.com/photo-1526367790999-0150786686a2?w=1200', 'Track Order', '/orders.php', 3, true);

-- Update some menu items to be featured/special/bestseller
UPDATE menu_items SET is_featured = true, is_bestseller = true, sales_count = 150 WHERE name = 'Margherita Pizza';
UPDATE menu_items SET is_special = true, sales_count = 95 WHERE name = 'Pepperoni Pizza';
UPDATE menu_items SET is_bestseller = true, sales_count = 120 WHERE name = 'Classic Burger';
UPDATE menu_items SET is_featured = true, is_special = true, sales_count = 85 WHERE name = 'Cheese Burger';

-- Comment on tables
COMMENT ON TABLE carousel_banners IS 'Homepage carousel/slider banners';
COMMENT ON COLUMN menu_items.is_featured IS 'Show on homepage featured section';
COMMENT ON COLUMN menu_items.is_special IS 'Show in Today''s Special section';
COMMENT ON COLUMN menu_items.is_bestseller IS 'Show in Best from Kitchen section';
COMMENT ON COLUMN menu_items.sales_count IS 'Total number of times this item has been ordered';
