-- =================================================
-- SAMPLE MENU DATA
-- Insert carousel banners, categories and menu items with variants
-- =================================================

-- =================================================
-- CAROUSEL BANNERS
-- =================================================
INSERT INTO carousel_banners (title, description, image_url, link_url, is_active, display_order)
VALUES 
    ('Welcome to FlavorPOS', 'Experience delicious food with our amazing menu', 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200', '/menu', true, 1),
    ('Fresh Ingredients Daily', 'We use only the freshest ingredients for all our dishes', 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=1200', '/menu', true, 2),
    ('Special Offers', 'Check out our weekly specials and combo deals', 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1200', '/menu', true, 3);

-- =================================================
-- MENU CATEGORIES
-- =================================================
-- Insert categories
INSERT INTO menu_categories (name, description, display_order, is_active)
VALUES 
    ('mains', 'Main Course Dishes', 1, true),
    ('rice', 'Rice Dishes', 2, true),
    ('soup', 'Soups', 3, true),
    ('dessert', 'Desserts', 4, true)
ON CONFLICT (name) DO NOTHING;

-- =================================================
-- MENU ITEMS
-- =================================================

-- Hot Butter Cuttlefish
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Hot Butter Cuttlefish', 
    'Crispy cuttlefish tossed in spicy butter sauce with leeks and chili paste.',
    'https://bing.com/th?id=OSK.ebd5d6751da7d6c2a399e9806063c669',
    true,
    25
FROM menu_categories WHERE name = 'mains';

-- Add variant for Hot Butter Cuttlefish
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 2700.00, true, true
FROM menu_items WHERE name = 'Hot Butter Cuttlefish';

-- Chicken Fried Rice
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Chicken Fried Rice',
    'Classic wok-fried basmati rice with seasoned chicken chunks and spring onions.',
    'https://bing.com/th?id=OSK.c0647bba9da529b9bf2b640e5620d1ce',
    true,
    15
FROM menu_categories WHERE name = 'rice';

-- Add variants for Chicken Fried Rice
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 1300.00, true, true
FROM menu_items WHERE name = 'Chicken Fried Rice'
UNION ALL
SELECT id, 'Large', 1800.00, false, true
FROM menu_items WHERE name = 'Chicken Fried Rice';

-- Sweet Corn Soup
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Sweet Corn Soup',
    'Creamy sweet corn soup with a hint of ginger and ribbons of egg.',
    'https://images.unsplash.com/photo-1601050690597-df0568f70950',
    true,
    10
FROM menu_categories WHERE name = 'soup';

-- Add variant for Sweet Corn Soup
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 1100.00, true, true
FROM menu_items WHERE name = 'Sweet Corn Soup';

-- Special Chop Suey
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Special Chop Suey',
    'A mix of seafood, chicken and fresh vegetables in a savory oyster sauce.',
    'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe',
    true,
    20
FROM menu_categories WHERE name = 'mains';

-- Add variant for Special Chop Suey
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 1900.00, true, true
FROM menu_items WHERE name = 'Special Chop Suey';

-- Nasi Goreng
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Nasi Goreng',
    'Indonesian style fried rice with chili paste, topped with a bullseye egg.',
    'https://th.bing.com/th/id/OIP.znFpL6C4h2Aqcs-WJZeRHAHaGP?o=7rm=3&rs=1&pid=ImgDetMain&o=7&rm=3',
    true,
    15
FROM menu_categories WHERE name = 'rice';

-- Add variant for Nasi Goreng
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 1400.00, true, true
FROM menu_items WHERE name = 'Nasi Goreng';

-- Caramel Pudding
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Caramel Pudding',
    'Silky smooth caramel custard with a rich burnt sugar syrup.',
    'https://bing.com/th?id=OSK.f5d01692cc8ce6ac9e844c05b0dc8dcf',
    true,
    5
FROM menu_categories WHERE name = 'dessert';

-- Add variant for Caramel Pudding
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 900.00, true, true
FROM menu_items WHERE name = 'Caramel Pudding';

-- Spicy Crab Curry
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Spicy Crab Curry',
    'Fresh lagoon crab cooked in traditional Jaffna spices and thick coconut milk.',
    'https://images.unsplash.com/photo-1565557623262-b51c2513a641',
    true,
    30
FROM menu_categories WHERE name = 'mains';

-- Add variant for Spicy Crab Curry
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 3200.00, true, true
FROM menu_items WHERE name = 'Spicy Crab Curry';

-- Chocolate Lava Cake
INSERT INTO menu_items (category_id, name, description, image_url, is_available, preparation_time)
SELECT id, 'Chocolate Lava Cake',
    'Warm chocolate cake with a molten goo center, served with vanilla cream.',
    'https://images.unsplash.com/photo-1624353365286-3f8d62daad51',
    true,
    12
FROM menu_categories WHERE name = 'dessert';

-- Add variant for Chocolate Lava Cake
INSERT INTO menu_item_variants (menu_item_id, variant_name, price, is_default, is_available)
SELECT id, 'Regular', 1200.00, true, true
FROM menu_items WHERE name = 'Chocolate Lava Cake';
