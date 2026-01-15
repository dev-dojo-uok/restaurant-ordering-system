-- Add payment_method and notes columns to orders table
-- Add payment_transactions table for split payment support
-- Run this migration to fix the POS order creation error

-- Add notes column to orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS notes TEXT;

-- Add payment_status to track payment completion
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS payment_status VARCHAR(20) DEFAULT 'pending';

-- Add constraint for payment_status
ALTER TABLE orders 
ADD CONSTRAINT check_payment_status 
CHECK (payment_status IN ('pending', 'partial', 'completed', 'refunded'));

-- Create payment_transactions table for split payments
CREATE TABLE IF NOT EXISTS payment_transactions (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    transaction_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT check_payment_method CHECK (payment_method IN ('cash', 'card'))
);

-- Create index for faster queries
CREATE INDEX IF NOT EXISTS idx_payment_transactions_order ON payment_transactions(order_id);

-- Add comments
COMMENT ON COLUMN orders.notes IS 'Additional notes or special instructions for the order';
COMMENT ON COLUMN orders.payment_status IS 'Payment status: pending, partial, completed, refunded';
COMMENT ON TABLE payment_transactions IS 'Stores individual payment transactions for orders (supports split payments)';
