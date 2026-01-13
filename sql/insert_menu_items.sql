-- Insert menu items from menu.php into database

-- First, insert categories if they don't exist
INSERT INTO menu_categories (name, description, display_order, is_active)
VALUES 
    ('mains', 'Main Course Dishes', 1, true),
    ('rice', 'Rice Dishes', 2, true),
    ('soup', 'Soups', 3, true),
    ('dessert', 'Desserts', 4, true)
ON CONFLICT (name) DO NOTHING;

-- Insert menu items with their variants
-- Hot Butter Cuttlefish
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Hot Butter Cuttlefish', 
    'Crispy cuttlefish tossed in spicy butter sauce with leeks and chili paste.',
    2700.00,
    'https://bing.com/th?id=OSK.ebd5d6751da7d6c2a399e9806063c669',
    true,
    25
FROM menu_categories WHERE name = 'mains'
ON CONFLICT DO NOTHING;

-- Chicken Fried Rice
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Chicken Fried Rice',
    'Classic wok-fried basmati rice with seasoned chicken chunks and spring onions.',
    1300.00,
    'https://bing.com/th?id=OSK.c0647bba9da529b9bf2b640e5620d1ce',
    true,
    15
FROM menu_categories WHERE name = 'rice'
ON CONFLICT DO NOTHING;

-- Sweet Corn Soup
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Sweet Corn Soup',
    'Creamy sweet corn soup with a hint of ginger and ribbons of egg.',
    1100.00,
    'https://images.unsplash.com/photo-1601050690597-df0568f70950',
    true,
    10
FROM menu_categories WHERE name = 'soup'
ON CONFLICT DO NOTHING;

-- Special Chop Suey
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Special Chop Suey',
    'A mix of seafood, chicken and fresh vegetables in a savory oyster sauce.',
    1900.00,
    'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe',
    true,
    20
FROM menu_categories WHERE name = 'mains'
ON CONFLICT DO NOTHING;

-- Nasi Goreng
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Nasi Goreng',
    'Indonesian style fried rice with chili paste, topped with a bullseye egg.',
    1400.00,
    'https://th.bing.com/th/id/OIP.znFpL6C4h2Aqcs-WJZeRHAHaGP?o=7rm=3&rs=1&pid=ImgDetMain&o=7&rm=3',
    true,
    15
FROM menu_categories WHERE name = 'rice'
ON CONFLICT DO NOTHING;

-- Caramel Pudding
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Caramel Pudding',
    'Silky smooth caramel custard with a rich burnt sugar syrup.',
    900.00,
    'https://bing.com/th?id=OSK.f5d01692cc8ce6ac9e844c05b0dc8dcf',
    true,
    5
FROM menu_categories WHERE name = 'dessert'
ON CONFLICT DO NOTHING;

-- Spicy Crab Curry
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Spicy Crab Curry',
    'Fresh lagoon crab cooked in traditional Jaffna spices and thick coconut milk.',
    3200.00,
    'https://images.unsplash.com/photo-1565557623262-b51c2513a641',
    true,
    30
FROM menu_categories WHERE name = 'mains'
ON CONFLICT DO NOTHING;

-- Chocolate Lava Cake
INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time)
SELECT id, 'Chocolate Lava Cake',
    'Warm chocolate cake with a molten goo center, served with vanilla cream.',
    1200.00,
    'https://images.unsplash.com/photo-1624353365286-3f8d62daad51',
    true,
    12
FROM menu_categories WHERE name = 'dessert'
ON CONFLICT DO NOTHING;

-- Add Regular variants for items that don't have variants yet
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', price, true, true
FROM menu_items
WHERE id NOT IN (SELECT DISTINCT menu_item_id FROM menu_item_variants)
ON CONFLICT DO NOTHING;
