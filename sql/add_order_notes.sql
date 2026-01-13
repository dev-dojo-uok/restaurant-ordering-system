-- Add notes column to orders table
ALTER TABLE orders ADD COLUMN IF NOT EXISTS notes TEXT;

-- Add comment
COMMENT ON COLUMN orders.notes IS 'Special instructions or notes from customer or admin';
