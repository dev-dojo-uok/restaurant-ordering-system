-- =================================================
-- ADD MENU ITEM VARIANTS (SIZES/PRICES)
-- Allows items to have multiple sizes with different prices
-- Example: Coca-Cola can have Regular ($2.50) and Large ($3.50)
-- =================================================

-- Create menu_item_variants table
DROP TABLE IF EXISTS menu_item_variants CASCADE;

CREATE TABLE menu_item_variants (
    id SERIAL PRIMARY KEY,
    menu_item_id INT NOT NULL,
    variant_name VARCHAR(50) NOT NULL,       -- e.g., "Regular", "Large", "Small", "Medium"
    price DECIMAL(10, 2) NOT NULL,           -- Price for this variant
    is_default BOOLEAN DEFAULT false,        -- Which variant to show by default
    is_available BOOLEAN DEFAULT true,       -- Can this variant be ordered
    display_order INT DEFAULT 0,             -- For sorting (Small, Medium, Large)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    
    -- Ensure variant names are unique per item
    UNIQUE(menu_item_id, variant_name)
);

-- Create index for faster queries
CREATE INDEX idx_variants_menu_item ON menu_item_variants(menu_item_id);
CREATE INDEX idx_variants_available ON menu_item_variants(is_available);

-- Remove price from menu_items (now in variants table)
-- Keep it for backward compatibility but we won't use it
ALTER TABLE menu_items ALTER COLUMN price DROP NOT NULL;

-- Migrate existing menu items to have a default variant
-- For each existing menu item, create a "Regular" variant with its current price
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, display_order)
SELECT id, 'Regular', price, true, 1
FROM menu_items
WHERE price IS NOT NULL;

-- Add sample variants for some items
-- Pizza variants
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, display_order)
SELECT id, 'Small (8")', 7.99, false, 0
FROM menu_items
WHERE name = 'Margherita Pizza';

INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, display_order)
SELECT id, 'Large (14")', 14.99, false, 2
FROM menu_items
WHERE name = 'Margherita Pizza';

-- Update the Regular to Medium for pizza
UPDATE menu_item_variants
SET variant_name = 'Medium (12")', display_order = 1, is_default = true
WHERE menu_item_id IN (SELECT id FROM menu_items WHERE name = 'Margherita Pizza')
AND variant_name = 'Regular';

-- Drinks variants (assuming Coca-Cola exists or will be added)
-- You can run similar inserts for other items

-- Comments
COMMENT ON TABLE menu_item_variants IS 'Different sizes/variants of menu items with their prices';
COMMENT ON COLUMN menu_item_variants.variant_name IS 'Size or variant name (e.g., Regular, Large, Small)';
COMMENT ON COLUMN menu_item_variants.is_default IS 'Show this variant by default on menu';
COMMENT ON COLUMN menu_item_variants.display_order IS 'Order to display variants (0=first, higher=later)';

-- Note: Update cart and order_items tables to reference variant_id
-- Let's add variant_id to cart and order_items
ALTER TABLE cart ADD COLUMN IF NOT EXISTS variant_id INT REFERENCES menu_item_variants(id) ON DELETE CASCADE;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variant_id INT REFERENCES menu_item_variants(id) ON DELETE CASCADE;

-- Create indexes for the new foreign keys
CREATE INDEX IF NOT EXISTS idx_cart_variant ON cart(variant_id);
CREATE INDEX IF NOT EXISTS idx_order_items_variant ON order_items(variant_id);

-- Update existing cart/order_items to use default variant
-- (This assumes default variants exist for all items)
UPDATE cart c
SET variant_id = (
    SELECT v.id 
    FROM menu_item_variants v 
    WHERE v.menu_item_id = c.menu_item_id 
    AND v.is_default = true 
    LIMIT 1
)
WHERE variant_id IS NULL;

UPDATE order_items oi
SET variant_id = (
    SELECT v.id 
    FROM menu_item_variants v 
    WHERE v.menu_item_id = oi.menu_item_id 
    AND v.is_default = true 
    LIMIT 1
)
WHERE variant_id IS NULL;

COMMENT ON COLUMN cart.variant_id IS 'Which variant/size was added to cart';
COMMENT ON COLUMN order_items.variant_id IS 'Which variant/size was ordered';
