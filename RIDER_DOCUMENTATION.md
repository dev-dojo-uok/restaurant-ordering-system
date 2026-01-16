# Rider Interface Documentation

## Overview
The rider interface allows delivery riders to manage their assigned delivery orders in real-time.

## Features

### 1. **Order Management**
- View all assigned delivery orders
- See customer details (name, address, phone)
- View order items and total amount
- Track order status (Ready to Collect / On the Way)

### 2. **Order Status Updates**
- **Picked Up**: Mark when order is collected from restaurant
- **Delivered**: Mark when order is delivered to customer
- Automatic status progression

### 3. **Completed Orders**
- View today's completed deliveries
- See delivery history with timestamps
- Track performance

### 4. **Real-time Updates**
- Auto-refresh every 30 seconds
- Instant UI updates on status changes
- Toast notifications for actions

## File Structure

```
public/
├── rider.php              # Main rider dashboard
├── api/
│   └── rider.php         # API endpoint for rider actions
└── assets/
    ├── css/
    │   └── rider.css     # Rider interface styles
    └── js/
        └── rider.js      # Rider interface JavaScript
```

## Database Integration

### Orders Table
The rider interface uses the following order statuses:
- `ready_to_collect` - Order is ready to be picked up by rider
- `on_the_way` - Order has been picked up and is being delivered
- `delivered` - Order has been successfully delivered

### Assignment Process
1. Kitchen marks delivery order as "ready_to_collect"
2. Admin/Kitchen assigns rider to the order
3. Order appears in rider's dashboard
4. Rider marks as "picked up" → status: `on_the_way`
5. Rider marks as "delivered" → status: `delivered`, `completed_at` timestamp set

## API Endpoints

### `/api/rider.php`

#### Mark Order as Picked Up
```
POST /api/rider.php?action=mark_picked_up
Body: { "order_id": 123 }
```

#### Mark Order as Delivered
```
POST /api/rider.php?action=mark_delivered
Body: { "order_id": 123 }
```

#### Get Completed Orders
```
GET /api/rider.php?action=get_completed
```

## Kitchen Integration

### Rider Assignment
In the kitchen display (`/admin/kitchen.php`):
1. When delivery order reaches "Ready / Pass" column
2. Select rider from dropdown
3. Click "Assign" button
4. Order is removed from kitchen and appears in rider's dashboard

### SQL for Rider Assignment
```sql
UPDATE orders 
SET rider_id = ? 
WHERE id = ? 
AND order_type = 'delivery' 
AND status = 'ready_to_collect'
```

## User Setup

### Creating Rider Accounts

Run the SQL migration:
```bash
psql -U your_username -d your_database -f sql/003_add_riders.sql
```

Or manually create riders:
```sql
INSERT INTO users (username, name, email, password, phone, role) 
VALUES ('rider1', 'John Rider', 'rider1@example.com', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        '0771234567', 'rider');
```

Default password: `password123`

### Login
Riders login at `/login.php` using their credentials and are automatically redirected to `/rider.php`.

## UI Components

### Order Card
- **Header**: Order ID and status badge
- **Body**: Customer details and order items
- **Footer**: Action button (Picked Up / Delivered)

### Status Badge Colors
- `ready_to_collect` - Light overlay
- `on_the_way` - Semi-transparent overlay

### Completed Orders Modal
- Shows today's completed deliveries
- Displays completion time
- Shows customer details and items

## Responsive Design
- Mobile-friendly interface
- Touch-optimized buttons
- Responsive grid layout (1-4 columns based on screen size)

## Auto-refresh
The interface automatically refreshes every 30 seconds to show newly assigned orders.

## Security
- Requires authentication (session-based)
- Riders can only:
  - View orders assigned to them
  - Update status of their own orders
  - View their own completed orders
- All API calls validate rider ownership

## Future Enhancements
- GPS tracking integration
- Route optimization
- Push notifications
- Delivery proof (photo upload)
- Customer signature
- Earnings tracking
