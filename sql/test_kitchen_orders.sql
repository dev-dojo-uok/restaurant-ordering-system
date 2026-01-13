-- Kitchen Display System - Test Data
-- Run this to create sample orders for testing the kitchen display

-- Insert sample orders (if you don't have any)
-- Make sure you have menu items and users first

-- Sample NEW order (dine-in)
INSERT INTO orders (order_type, status, total_amount, customer_name, customer_phone, table_number, created_at)
VALUES ('dine_in', 'ordered', 29.99, 'John Doe', '555-0100', 5, CURRENT_TIMESTAMP - INTERVAL '5 minutes')
RETURNING id;

-- Get the last order ID and add items
-- Replace <order_id> with the actual ID returned above
-- Example with order_id = 1:

-- Add items to the order (adjust menu_item_id based on your menu)
INSERT INTO order_items (order_id, menu_item_id, quantity, price, subtotal)
VALUES 
  (LASTVAL(), 1, 2, 12.99, 25.98),  -- Adjust menu_item_id as needed
  (LASTVAL(), 3, 1, 4.99, 4.99);

-- Sample IN PROCESS order (takeaway)
INSERT INTO orders (order_type, status, total_amount, customer_name, customer_phone, created_at)
VALUES ('takeaway', 'under_preparation', 15.50, 'Jane Smith', '555-0200', CURRENT_TIMESTAMP - INTERVAL '8 minutes')
RETURNING id;

INSERT INTO order_items (order_id, menu_item_id, quantity, price, subtotal)
VALUES 
  (LASTVAL(), 2, 1, 15.50, 15.50);

-- Sample READY order (delivery)
INSERT INTO orders (order_type, status, total_amount, customer_name, customer_phone, delivery_address, created_at)
VALUES ('delivery', 'ready_to_collect', 45.00, 'Bob Johnson', '555-0300', '123 Main St', CURRENT_TIMESTAMP - INTERVAL '15 minutes')
RETURNING id;

INSERT INTO order_items (order_id, menu_item_id, quantity, price, subtotal)
VALUES 
  (LASTVAL(), 1, 3, 12.99, 38.97),
  (LASTVAL(), 4, 2, 2.99, 5.98);

-- Sample COMPLETED order (for served history)
INSERT INTO orders (order_type, status, total_amount, customer_name, table_number, created_at, completed_at)
VALUES ('dine_in', 'completed', 22.00, 'Alice Brown', 3, CURRENT_TIMESTAMP - INTERVAL '30 minutes', CURRENT_TIMESTAMP - INTERVAL '5 minutes')
RETURNING id;

INSERT INTO order_items (order_id, menu_item_id, quantity, price, subtotal)
VALUES 
  (LASTVAL(), 2, 1, 15.50, 15.50),
  (LASTVAL(), 3, 2, 3.25, 6.50);

-- Verify the orders
SELECT 
    id, 
    order_type, 
    status, 
    customer_name, 
    table_number,
    total_amount,
    created_at
FROM orders 
ORDER BY created_at DESC 
LIMIT 10;
