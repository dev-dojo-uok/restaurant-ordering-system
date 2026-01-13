<?php
// Simple REST API for restaurant ordering system
// Routes under /api/{resource}/{id?}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/User.php';

function respond($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody() {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function parseRoute() {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $base = '/api/';
    $path = substr($uri, strlen($base));
    $segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));
    $resource = $segments[0] ?? '';
    $id = isset($segments[1]) && ctype_digit($segments[1]) ? intval($segments[1]) : null;
    return [$resource, $id];
}

[$resource, $id] = parseRoute();
$method = $_SERVER['REQUEST_METHOD'];

if ($resource === '') {
    respond(200, [
        'service' => 'restaurant-ordering-system API',
        'endpoints' => [
            '/api/users',
            '/api/menu-categories',
            '/api/menu-items',
            '/api/cart',
            '/api/orders',
            '/api/order-items',
        ]
    ]);
}

global $pdo;

// USERS
if ($resource === 'users') {
    $userModel = new User($pdo);
    switch ($method) {
        case 'GET':
            if ($id) {
                $u = $userModel->findById($id);
                if (!$u) respond(404, ['error' => 'User not found']);
                respond(200, $u);
            } else {
                $role = $_GET['role'] ?? null;
                if ($role) {
                    respond(200, $userModel->getAllByRole($role));
                } else {
                    $stmt = $pdo->query("SELECT id, username, email, full_name, role, phone, address, is_active, created_at, updated_at FROM users WHERE is_active = true ORDER BY created_at DESC");
                    respond(200, $stmt->fetchAll());
                }
            }
        case 'POST':
            $data = getJsonBody();
            $errors = [];

            // Username
            if (empty($data['username']) || strlen(trim($data['username'])) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            } elseif ($userModel->usernameExists(trim($data['username']))) {
                $errors['username'] = 'Username already taken';
            }

            // Email
            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif ($userModel->emailExists(trim($data['email']))) {
                $errors['email'] = 'Email already registered';
            }

            // Password
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }

            // Full name (required by schema)
            if (empty($data['full_name'])) {
                $errors['full_name'] = 'Full name is required';
            }

            if (!empty($errors)) {
                respond(400, ['errors' => $errors]);
            }

            $ok = $userModel->create($data);
            if (!$ok) respond(500, ['error' => 'Failed to create user']);
            respond(201, ['message' => 'User created']);
        case 'PUT':
            if (!$id) respond(400, ['error' => 'User ID required']);
            $data = getJsonBody();
            $ok = $userModel->update($id, $data);
            if (!$ok) respond(500, ['error' => 'Failed to update user']);
            respond(200, ['message' => 'User updated']);
        case 'DELETE':
            if (!$id) respond(400, ['error' => 'User ID required']);
            $ok = $userModel->deactivate($id);
            if (!$ok) respond(500, ['error' => 'Failed to deactivate user']);
            respond(200, ['message' => 'User deactivated']);
        default:
            respond(405, ['error' => 'Method not allowed']);
    }
}

// MENU CATEGORIES
if ($resource === 'menu-categories') {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM menu_categories WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row) respond(404, ['error' => 'Category not found']);
                respond(200, $row);
            } else {
                $stmt = $pdo->query('SELECT * FROM menu_categories WHERE is_active = true ORDER BY display_order, name');
                respond(200, $stmt->fetchAll());
            }
        case 'POST':
            $d = getJsonBody();
            if (!isset($d['name'])) respond(400, ['error' => 'name is required']);
            $stmt = $pdo->prepare('INSERT INTO menu_categories (name, description, display_order, is_active) VALUES (?, ?, ?, COALESCE(?, true))');
            $ok = $stmt->execute([$d['name'], $d['description'] ?? null, $d['display_order'] ?? 0, $d['is_active'] ?? true]);
            if (!$ok) respond(500, ['error' => 'Failed to create category']);
            respond(201, ['message' => 'Category created']);
        case 'PUT':
            if (!$id) respond(400, ['error' => 'Category ID required']);
            $d = getJsonBody();
            $fields = [];
            $vals = [];
            foreach (['name','description','display_order','is_active'] as $f) {
                if (array_key_exists($f, $d)) { $fields[] = "$f = ?"; $vals[] = $d[$f]; }
            }
            if (!$fields) respond(400, ['error' => 'No fields to update']);
            $vals[] = $id;
            $sql = 'UPDATE menu_categories SET ' . implode(', ', $fields) . ', created_at = created_at WHERE id = ?';
            $ok = $pdo->prepare($sql)->execute($vals);
            if (!$ok) respond(500, ['error' => 'Failed to update category']);
            respond(200, ['message' => 'Category updated']);
        case 'DELETE':
            if (!$id) respond(400, ['error' => 'Category ID required']);
            $ok = $pdo->prepare('DELETE FROM menu_categories WHERE id = ?')->execute([$id]);
            if (!$ok) respond(500, ['error' => 'Failed to delete category']);
            respond(200, ['message' => 'Category deleted']);
        default:
            respond(405, ['error' => 'Method not allowed']);
    }
}

// MENU ITEMS
if ($resource === 'menu-items') {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single menu item with its variants
                $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
                $stmt->execute([$id]);
                $item = $stmt->fetch();
                if (!$item) respond(404, ['error' => 'Menu item not found']);
                
                // Get variants for this item
                $variantsStmt = $pdo->prepare('SELECT id, variant_name, price, is_default, is_available, display_order FROM menu_item_variants WHERE menu_item_id = ? AND is_available = true ORDER BY display_order, id');
                $variantsStmt->execute([$id]);
                $item['variants'] = $variantsStmt->fetchAll();
                
                respond(200, $item);
            } else {
                // Get all menu items with their variants
                $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
                if ($categoryId) {
                    $stmt = $pdo->prepare('SELECT * FROM menu_items WHERE category_id = ? AND is_available = true ORDER BY name');
                    $stmt->execute([$categoryId]);
                } else {
                    $stmt = $pdo->query('SELECT * FROM menu_items WHERE is_available = true ORDER BY name');
                }
                $items = $stmt->fetchAll();
                
                // Fetch variants for each item
                foreach ($items as &$item) {
                    $variantsStmt = $pdo->prepare('SELECT id, variant_name, price, is_default, is_available, display_order FROM menu_item_variants WHERE menu_item_id = ? AND is_available = true ORDER BY display_order, id');
                    $variantsStmt->execute([$item['id']]);
                    $item['variants'] = $variantsStmt->fetchAll();
                }
                
                respond(200, $items);
            }
        case 'POST':
            $d = getJsonBody();
            foreach (['category_id','name','price'] as $req) { if (!isset($d[$req])) respond(400, ['error' => "$req is required"]); }
            $stmt = $pdo->prepare('INSERT INTO menu_items (category_id, name, description, price, image_url, is_available, preparation_time) VALUES (?, ?, ?, ?, ?, COALESCE(?, true), COALESCE(?, 15))');
            $ok = $stmt->execute([
                intval($d['category_id']), $d['name'], $d['description'] ?? null, $d['price'], $d['image_url'] ?? null, $d['is_available'] ?? true, $d['preparation_time'] ?? 15
            ]);
            if (!$ok) respond(500, ['error' => 'Failed to create menu item']);
            respond(201, ['message' => 'Menu item created']);
        case 'PUT':
            if (!$id) respond(400, ['error' => 'Menu item ID required']);
            $d = getJsonBody();
            $fields = [];$vals=[];
            foreach (['category_id','name','description','price','image_url','is_available','preparation_time'] as $f) {
                if (array_key_exists($f, $d)) { $fields[] = "$f = ?"; $vals[] = $d[$f]; }
            }
            if (!$fields) respond(400, ['error' => 'No fields to update']);
            $vals[] = $id;
            $sql = 'UPDATE menu_items SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';
            $ok = $pdo->prepare($sql)->execute($vals);
            if (!$ok) respond(500, ['error' => 'Failed to update menu item']);
            respond(200, ['message' => 'Menu item updated']);
        case 'DELETE':
            if (!$id) respond(400, ['error' => 'Menu item ID required']);
            $ok = $pdo->prepare('DELETE FROM menu_items WHERE id = ?')->execute([$id]);
            if (!$ok) respond(500, ['error' => 'Failed to delete menu item']);
            respond(200, ['message' => 'Menu item deleted']);
        default:
            respond(405, ['error' => 'Method not allowed']);
    }
}

// CART
if ($resource === 'cart') {
    switch ($method) {
        case 'GET':
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
            if (!$userId) respond(400, ['error' => 'user_id is required']);
            $stmt = $pdo->prepare('SELECT c.*, m.name AS item_name, m.price FROM cart c JOIN menu_items m ON c.menu_item_id = m.id WHERE c.user_id = ? ORDER BY c.created_at DESC');
            $stmt->execute([$userId]);
            respond(200, $stmt->fetchAll());
        case 'POST':
            $d = getJsonBody();
            foreach (['user_id','menu_item_id'] as $req) { if (!isset($d[$req])) respond(400, ['error' => "$req is required"]); }
            $quantity = isset($d['quantity']) ? intval($d['quantity']) : 1;
            // Upsert style: try update quantity if exists
            $stmt = $pdo->prepare('INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?) ON CONFLICT (user_id, menu_item_id) DO UPDATE SET quantity = cart.quantity + EXCLUDED.quantity');
            $ok = $stmt->execute([intval($d['user_id']), intval($d['menu_item_id']), $quantity]);
            if (!$ok) respond(500, ['error' => 'Failed to add to cart']);
            respond(201, ['message' => 'Cart updated']);
        case 'PUT':
            if (!$id) respond(400, ['error' => 'Cart ID required']);
            $d = getJsonBody();
            if (!isset($d['quantity'])) respond(400, ['error' => 'quantity is required']);
            $ok = $pdo->prepare('UPDATE cart SET quantity = ?, created_at = created_at WHERE id = ?')->execute([intval($d['quantity']), $id]);
            if (!$ok) respond(500, ['error' => 'Failed to update cart item']);
            respond(200, ['message' => 'Cart item updated']);
        case 'DELETE':
            if (!$id) respond(400, ['error' => 'Cart ID required']);
            $ok = $pdo->prepare('DELETE FROM cart WHERE id = ?')->execute([$id]);
            if (!$ok) respond(500, ['error' => 'Failed to remove cart item']);
            respond(200, ['message' => 'Cart item removed']);
        default:
            respond(405, ['error' => 'Method not allowed']);
    }
}

// ORDERS
if ($resource === 'orders') {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
                $stmt->execute([$id]);
                $order = $stmt->fetch();
                if (!$order) respond(404, ['error' => 'Order not found']);
                $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $itemsStmt->execute([$id]);
                $order['items'] = $itemsStmt->fetchAll();
                respond(200, $order);
            } else {
                $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
                if ($userId) {
                    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC');
                }
                respond(200, $stmt->fetchAll());
            }
        case 'POST':
            $d = getJsonBody();
            foreach (['order_type','total_amount'] as $req) { if (!isset($d[$req])) respond(400, ['error' => "$req is required"]); }
            $userId = $d['user_id'] ?? null;
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, order_type, status, total_amount, customer_name, customer_phone, delivery_address, rider_id, table_number, payment_method, notes) VALUES (?, ?, COALESCE(?, \'' . 'ordered' . '\'), ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id');
            $stmt->execute([
                $userId, 
                $d['order_type'], 
                $d['status'] ?? 'ordered', 
                $d['total_amount'], 
                $d['customer_name'] ?? null, 
                $d['customer_phone'] ?? null, 
                $d['delivery_address'] ?? null, 
                $d['rider_id'] ?? null, 
                $d['table_number'] ?? null,
                $d['payment_method'] ?? 'cash',
                $d['notes'] ?? null
            ]);
            $orderId = $stmt->fetchColumn();
            // optional items array to create order_items
            if (isset($d['items']) && is_array($d['items'])) {
                $oi = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, variant_id, quantity, price, item_name) VALUES (?, ?, ?, ?, ?, ?)');
                foreach ($d['items'] as $it) {
                    $oi->execute([
                        $orderId, 
                        $it['menu_item_id'] ?? null, 
                        $it['variant_id'] ?? null,
                        intval($it['quantity'] ?? 1), 
                        $it['price'] ?? 0, 
                        $it['item_name'] ?? 'Item'
                    ]);
                }
            }
            respond(201, ['message' => 'Order created', 'id' => intval($orderId)]);
        case 'PUT':
            if (!$id) respond(400, ['error' => 'Order ID required']);
            $d = getJsonBody();
            $fields = [];$vals=[];
            foreach (['status','rider_id','delivery_address','table_number','total_amount','customer_name','customer_phone','payment_method','notes'] as $f) {
                if (array_key_exists($f, $d)) { $fields[] = "$f = ?"; $vals[] = $d[$f]; }
            }
            if (isset($d['status']) && in_array($d['status'], ['delivered','completed','collected'])) {
                $fields[] = 'completed_at = CURRENT_TIMESTAMP';
            }
            if (!$fields) respond(400, ['error' => 'No fields to update']);
            $vals[] = $id;
            $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';
            $ok = $pdo->prepare($sql)->execute($vals);
            if (!$ok) respond(500, ['error' => 'Failed to update order']);
            respond(200, ['message' => 'Order updated']);
        case 'DELETE':
            if (!$id) respond(400, ['error' => 'Order ID required']);
            $ok = $pdo->prepare("UPDATE orders SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);
            if (!$ok) respond(500, ['error' => 'Failed to cancel order']);
            respond(200, ['message' => 'Order cancelled']);
        default:
            respond(405, ['error' => 'Method not allowed']);
    }
}

// ORDER ITEMS
if ($resource === 'order-items') {
    switch ($method) {
        case 'GET':
            $orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
            if ($id) {
                $stmt = $pdo->prepare('SELECT * FROM order_items WHERE id = ?');
                $stmt->execute([$id]);
                $row = $stmt->fetch();
                if (!$row) respond(404, ['error' => 'Order item not found']);
                respond(200, $row);
            } elseif ($orderId) {
                $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
                $stmt->execute([$orderId]);
                respond(200, $stmt->fetchAll());
            } else {
                $stmt = $pdo->query('SELECT * FROM order_items ORDER BY id DESC');
                respond(200, $stmt->fetchAll());
            }
        case 'POST':
            $d = getJsonBody();
            foreach (['order_id','quantity','price','item_name'] as $req) { if (!isset($d[$req])) respond(400, ['error' => "$req is required"]); }
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price, item_name) VALUES (?, ?, ?, ?, ?)');
            $ok = $stmt->execute([$d['order_id'], $d['menu_item_id'] ?? null, intval($d['quantity']), $d['price'], $d['item_name']]);
            if (!$ok) respond(500, ['error' => 'Failed to create order item']);
            respond(201, ['message' => 'Order item created']);
        case 'PUT':
            if (!$id) respond(400, ['error' => 'Order item ID required']);
            $d = getJsonBody();
            $fields = [];$vals=[];
            foreach (['menu_item_id','quantity','price','item_name'] as $f) { if (array_key_exists($f, $d)) { $fields[] = "$f = ?"; $vals[] = $d[$f]; } }
            if (!$fields) respond(400, ['error' => 'No fields to update']);
            $vals[] = $id;
            $sql = 'UPDATE order_items SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $ok = $pdo->prepare($sql)->execute($vals);
            if (!$ok) respond(500, ['error' => 'Failed to update order item']);
            respond(200, ['message' => 'Order item updated']);
        case 'DELETE':
            if (!$id) respond(400, ['error' => 'Order item ID required']);
            $ok = $pdo->prepare('DELETE FROM order_items WHERE id = ?')->execute([$id]);
            if (!$ok) respond(500, ['error' => 'Failed to delete order item']);
            respond(200, ['message' => 'Order item deleted']);
        default:
            respond(405, ['error' => 'Method not allowed']);
    }
}

respond(404, ['error' => 'Endpoint not found']);
