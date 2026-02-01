<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../auth.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request
 $method = $_SERVER['REQUEST_METHOD'];
 $request = isset($_SERVER['PATH_INFO']) ? explode('/', trim($_SERVER['PATH_INFO'], '/')) : [];
 $resource = $request[0] ?? '';
 $id = $request[1] ?? null;

// Initialize database connection
 $database = new Database();
 $db = $database->getConnection();

// Process the request
switch ($resource) {
    case 'menu':
        handleMenuRequest($method, $db, $id);
        break;
    case 'tables':
        handleTablesRequest($method, $db, $id);
        break;
    case 'reservations':
        handleReservationsRequest($method, $db, $id);
        break;
    case 'orders':
        handleOrdersRequest($method, $db, $id);
        break;
    case 'inventory':
        handleInventoryRequest($method, $db, $id);
        break;
    case 'customers':
        handleCustomersRequest($method, $db, $id);
        break;
    case 'users':
        handleUsersRequest($method, $db, $id);
        break;
    case 'reports':
        handleReportsRequest($method, $db);
        break;
    default:
        http_response_code(404);
        echo json_encode(['message' => 'Resource not found']);
        break;
}

// Menu handlers
function handleMenuRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single menu item
                $query = 'SELECT mi.*, c.name as category_name FROM menu_items mi 
                         LEFT JOIN categories c ON mi.category_id = c.id 
                         WHERE mi.id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Menu item not found']);
                }
            } else {
                // Get all menu items
                $query = 'SELECT mi.*, c.name as category_name FROM menu_items mi 
                         LEFT JOIN categories c ON mi.category_id = c.id 
                         ORDER BY mi.name';
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($items);
            }
            break;
            
        case 'POST':
            // Add new menu item
            requireManager();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['price']) || !isset($data['category_id'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            $query = 'INSERT INTO menu_items (name, description, price, category_id, image, is_available) 
                     VALUES (:name, :description, :price, :category_id, :image, :is_available)';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':is_available', $data['is_available']);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Menu item created', 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create menu item']);
            }
            break;
            
        case 'PUT':
            // Update menu item
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Menu item ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = 'UPDATE menu_items SET 
                     name = :name, 
                     description = :description, 
                     price = :price, 
                     category_id = :category_id, 
                     image = :image, 
                     is_available = :is_available 
                     WHERE id = :id';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':image', $data['image']);
            $stmt->bindParam(':is_available', $data['is_available']);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Menu item updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update menu item']);
            }
            break;
            
        case 'DELETE':
            // Delete menu item
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Menu item ID is required']);
                break;
            }
            
            $query = 'DELETE FROM menu_items WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Menu item deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete menu item']);
            }
            break;
    }
}

// Tables handlers
function handleTablesRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single table
                $query = 'SELECT * FROM tables WHERE id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $table = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($table);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Table not found']);
                }
            } else {
                // Get all tables
                $query = 'SELECT * FROM tables ORDER BY table_number';
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($tables);
            }
            break;
            
        case 'POST':
            // Add new table
            requireManager();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['table_number']) || !isset($data['capacity'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            $query = 'INSERT INTO tables (table_number, capacity, status, location) 
                     VALUES (:table_number, :capacity, :status, :location)';
            $stmt = $db->prepare($query);
            
            $status = $data['status'] ?? 'available';
            $stmt->bindParam(':table_number', $data['table_number']);
            $stmt->bindParam(':capacity', $data['capacity']);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':location', $data['location']);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Table created', 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create table']);
            }
            break;
            
        case 'PUT':
            // Update table
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Table ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = 'UPDATE tables SET 
                     table_number = :table_number, 
                     capacity = :capacity, 
                     status = :status, 
                     location = :location 
                     WHERE id = :id';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':table_number', $data['table_number']);
            $stmt->bindParam(':capacity', $data['capacity']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':location', $data['location']);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Table updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update table']);
            }
            break;
            
        case 'DELETE':
            // Delete table
            requireAdmin();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Table ID is required']);
                break;
            }
            
            $query = 'DELETE FROM tables WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Table deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete table']);
            }
            break;
    }
}

// Reservations handlers
function handleReservationsRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single reservation
                $query = 'SELECT r.*, c.name as customer_name, c.email, c.phone, 
                         t.table_number, t.capacity 
                         FROM reservations r 
                         LEFT JOIN customers c ON r.customer_id = c.id 
                         LEFT JOIN tables t ON r.table_id = t.id 
                         WHERE r.id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($reservation);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Reservation not found']);
                }
            } else {
                // Get all reservations
                $date = $_GET['date'] ?? date('Y-m-d');
                
                $query = 'SELECT r.*, c.name as customer_name, c.email, c.phone, 
                         t.table_number, t.capacity 
                         FROM reservations r 
                         LEFT JOIN customers c ON r.customer_id = c.id 
                         LEFT JOIN tables t ON r.table_id = t.id 
                         WHERE r.reservation_date = :date 
                         ORDER BY r.reservation_time';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':date', $date);
                $stmt->execute();
                
                $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($reservations);
            }
            break;
            
        case 'POST':
            // Add new reservation
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['customer_name']) || !isset($data['table_id']) || 
                !isset($data['reservation_date']) || !isset($data['reservation_time']) || 
                !isset($data['party_size'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            // Check if customer exists, if not create one
            $customer_id = null;
            if (isset($data['customer_id']) && $data['customer_id']) {
                $customer_id = $data['customer_id'];
            } else {
                $query = 'INSERT INTO customers (name, email, phone) 
                         VALUES (:name, :email, :phone)';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $data['customer_name']);
                $stmt->bindParam(':email', $data['customer_email']);
                $stmt->bindParam(':phone', $data['customer_phone']);
                
                if ($stmt->execute()) {
                    $customer_id = $db->lastInsertId();
                } else {
                    http_response_code(500);
                    echo json_encode(['message' => 'Failed to create customer']);
                    break;
                }
            }
            
            // Create reservation
            $query = 'INSERT INTO reservations (customer_id, table_id, reservation_date, 
                     reservation_time, party_size, status, special_requests) 
                     VALUES (:customer_id, :table_id, :reservation_date, :reservation_time, 
                     :party_size, :status, :special_requests)';
            $stmt = $db->prepare($query);
            
            $status = $data['status'] ?? 'pending';
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':table_id', $data['table_id']);
            $stmt->bindParam(':reservation_date', $data['reservation_date']);
            $stmt->bindParam(':reservation_time', $data['reservation_time']);
            $stmt->bindParam(':party_size', $data['party_size']);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':special_requests', $data['special_requests']);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Reservation created', 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create reservation']);
            }
            break;
            
        case 'PUT':
            // Update reservation
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Reservation ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = 'UPDATE reservations SET 
                     table_id = :table_id, 
                     reservation_date = :reservation_date, 
                     reservation_time = :reservation_time, 
                     party_size = :party_size, 
                     status = :status, 
                     special_requests = :special_requests 
                     WHERE id = :id';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':table_id', $data['table_id']);
            $stmt->bindParam(':reservation_date', $data['reservation_date']);
            $stmt->bindParam(':reservation_time', $data['reservation_time']);
            $stmt->bindParam(':party_size', $data['party_size']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':special_requests', $data['special_requests']);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Reservation updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update reservation']);
            }
            break;
            
        case 'DELETE':
            // Delete reservation
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Reservation ID is required']);
                break;
            }
            
            $query = 'DELETE FROM reservations WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Reservation deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete reservation']);
            }
            break;
    }
}

// Orders handlers
function handleOrdersRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single order with items
                $query = 'SELECT o.*, c.name as customer_name, t.table_number, u.full_name as staff_name 
                         FROM orders o 
                         LEFT JOIN customers c ON o.customer_id = c.id 
                         LEFT JOIN tables t ON o.table_id = t.id 
                         LEFT JOIN users u ON o.staff_id = u.id 
                         WHERE o.id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get order items
                    $itemsQuery = 'SELECT oi.*, mi.name as item_name 
                                  FROM order_items oi 
                                  LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                                  WHERE oi.order_id = :order_id';
                    $itemsStmt = $db->prepare($itemsQuery);
                    $itemsStmt->bindParam(':order_id', $id);
                    $itemsStmt->execute();
                    
                    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($order);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Order not found']);
                }
            } else {
                // Get all orders
                $date = $_GET['date'] ?? date('Y-m-d');
                $status = $_GET['status'] ?? null;
                
                $query = 'SELECT o.*, c.name as customer_name, t.table_number, u.full_name as staff_name 
                         FROM orders o 
                         LEFT JOIN customers c ON o.customer_id = c.id 
                         LEFT JOIN tables t ON o.table_id = t.id 
                         LEFT JOIN users u ON o.staff_id = u.id 
                         WHERE DATE(o.order_date) = :date';
                
                if ($status) {
                    $query .= ' AND o.status = :status';
                }
                
                $query .= ' ORDER BY o.order_date DESC';
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':date', $date);
                
                if ($status) {
                    $stmt->bindParam(':status', $status);
                }
                
                $stmt->execute();
                
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($orders);
            }
            break;
            
        case 'POST':
            // Add new order
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['table_id']) || !isset($data['items']) || empty($data['items'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            // Generate order number
            $orderNumber = 'ORD' . date('YmdHis');
            
            // Calculate total amount
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }
            
            // Create order
            $query = 'INSERT INTO orders (table_id, customer_id, order_number, status, total_amount, staff_id) 
                     VALUES (:table_id, :customer_id, :order_number, :status, :total_amount, :staff_id)';
            $stmt = $db->prepare($query);
            
            $status = 'pending';
            $staffId = $_SESSION['user_id'] ?? null;
            
            $stmt->bindParam(':table_id', $data['table_id']);
            $stmt->bindParam(':customer_id', $data['customer_id']);
            $stmt->bindParam(':order_number', $orderNumber);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':total_amount', $totalAmount);
            $stmt->bindParam(':staff_id', $staffId);
            
            if ($stmt->execute()) {
                $orderId = $db->lastInsertId();
                
                // Add order items
                foreach ($data['items'] as $item) {
                    $itemQuery = 'INSERT INTO order_items (order_id, menu_item_id, quantity, price, notes) 
                                 VALUES (:order_id, :menu_item_id, :quantity, :price, :notes)';
                    $itemStmt = $db->prepare($itemQuery);
                    
                    $itemStmt->bindParam(':order_id', $orderId);
                    $itemStmt->bindParam(':menu_item_id', $item['menu_item_id']);
                    $itemStmt->bindParam(':quantity', $item['quantity']);
                    $itemStmt->bindParam(':price', $item['price']);
                    $itemStmt->bindParam(':notes', $item['notes']);
                    
                    $itemStmt->execute();
                }
                
                // Update table status
                $tableQuery = 'UPDATE tables SET status = "occupied" WHERE id = :table_id';
                $tableStmt = $db->prepare($tableQuery);
                $tableStmt->bindParam(':table_id', $data['table_id']);
                $tableStmt->execute();
                
                http_response_code(201);
                echo json_encode(['message' => 'Order created', 'id' => $orderId, 'order_number' => $orderNumber]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create order']);
            }
            break;
            
        case 'PUT':
            // Update order
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Order ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = 'UPDATE orders SET 
                     status = :status 
                     WHERE id = :id';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                // If order is paid or cancelled, update table status
                if ($data['status'] === 'paid' || $data['status'] === 'cancelled') {
                    $orderQuery = 'SELECT table_id FROM orders WHERE id = :id';
                    $orderStmt = $db->prepare($orderQuery);
                    $orderStmt->bindParam(':id', $id);
                    $orderStmt->execute();
                    
                    if ($orderStmt->rowCount() > 0) {
                        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                        $tableId = $order['table_id'];
                        
                        $tableQuery = 'UPDATE tables SET status = "available" WHERE id = :table_id';
                        $tableStmt = $db->prepare($tableQuery);
                        $tableStmt->bindParam(':table_id', $tableId);
                        $tableStmt->execute();
                    }
                }
                
                echo json_encode(['message' => 'Order updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update order']);
            }
            break;
            
        case 'DELETE':
            // Delete order
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Order ID is required']);
                break;
            }
            
            $query = 'DELETE FROM orders WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Order deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete order']);
            }
            break;
    }
}

// Inventory handlers
function handleInventoryRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single inventory item with recent transactions
                $query = 'SELECT * FROM inventory WHERE id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get recent transactions
                    $transQuery = 'SELECT it.*, u.username 
                                  FROM inventory_transactions it 
                                  LEFT JOIN users u ON it.created_by = u.id 
                                  WHERE it.inventory_id = :inventory_id 
                                  ORDER BY it.created_at DESC 
                                  LIMIT 10';
                    $transStmt = $db->prepare($transQuery);
                    $transStmt->bindParam(':inventory_id', $id);
                    $transStmt->execute();
                    
                    $item['transactions'] = $transStmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Inventory item not found']);
                }
            } else {
                // Get all inventory items
                $query = 'SELECT * FROM inventory ORDER BY name';
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($items);
            }
            break;
            
        case 'POST':
            // Add new inventory item
            requireManager();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || !isset($data['quantity']) || !isset($data['unit'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            $query = 'INSERT INTO inventory (name, description, quantity, unit, minimum_stock, unit_cost) 
                     VALUES (:name, :description, :quantity, :unit, :minimum_stock, :unit_cost)';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':quantity', $data['quantity']);
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':minimum_stock', $data['minimum_stock']);
            $stmt->bindParam(':unit_cost', $data['unit_cost']);
            
            if ($stmt->execute()) {
                $itemId = $db->lastInsertId();
                
                // Add initial transaction
                $transQuery = 'INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, reference, notes, created_by) 
                              VALUES (:inventory_id, :transaction_type, :quantity, :reference, :notes, :created_by)';
                $transStmt = $db->prepare($transQuery);
                
                $transactionType = 'in';
                $reference = 'Initial Stock';
                $notes = 'Initial inventory setup';
                $createdBy = $_SESSION['user_id'] ?? null;
                
                $transStmt->bindParam(':inventory_id', $itemId);
                $transStmt->bindParam(':transaction_type', $transactionType);
                $transStmt->bindParam(':quantity', $data['quantity']);
                $transStmt->bindParam(':reference', $reference);
                $transStmt->bindParam(':notes', $notes);
                $transStmt->bindParam(':created_by', $createdBy);
                
                $transStmt->execute();
                
                http_response_code(201);
                echo json_encode(['message' => 'Inventory item created', 'id' => $itemId]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create inventory item']);
            }
            break;
            
        case 'PUT':
            // Update inventory item
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Inventory item ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = 'UPDATE inventory SET 
                     name = :name, 
                     description = :description, 
                     quantity = :quantity, 
                     unit = :unit, 
                     minimum_stock = :minimum_stock, 
                     unit_cost = :unit_cost 
                     WHERE id = :id';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':quantity', $data['quantity']);
            $stmt->bindParam(':unit', $data['unit']);
            $stmt->bindParam(':minimum_stock', $data['minimum_stock']);
            $stmt->bindParam(':unit_cost', $data['unit_cost']);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Inventory item updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update inventory item']);
            }
            break;
            
        case 'DELETE':
            // Delete inventory item
            requireAdmin();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Inventory item ID is required']);
                break;
            }
            
            $query = 'DELETE FROM inventory WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Inventory item deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete inventory item']);
            }
            break;
    }
}

// Customers handlers
function handleCustomersRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single customer
                $query = 'SELECT * FROM customers WHERE id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($customer);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'Customer not found']);
                }
            } else {
                // Get all customers
                $query = 'SELECT * FROM customers ORDER BY name';
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($customers);
            }
            break;
            
        case 'POST':
            // Add new customer
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            $query = 'INSERT INTO customers (name, email, phone) 
                     VALUES (:name, :email, :phone)';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'Customer created', 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create customer']);
            }
            break;
            
        case 'PUT':
            // Update customer
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Customer ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = 'UPDATE customers SET 
                     name = :name, 
                     email = :email, 
                     phone = :phone 
                     WHERE id = :id';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':phone', $data['phone']);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Customer updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update customer']);
            }
            break;
            
        case 'DELETE':
            // Delete customer
            requireManager();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'Customer ID is required']);
                break;
            }
            
            $query = 'DELETE FROM customers WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'Customer deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete customer']);
            }
            break;
    }
}

// Users handlers
function handleUsersRequest($method, $db, $id) {
    switch ($method) {
        case 'GET':
            requireAdmin();
            if ($id) {
                // Get single user
                $query = 'SELECT id, username, email, full_name, role, created_at FROM users WHERE id = :id';
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo json_encode($user);
                } else {
                    http_response_code(404);
                    echo json_encode(['message' => 'User not found']);
                }
            } else {
                // Get all users
                $query = 'SELECT id, username, email, full_name, role, created_at FROM users ORDER BY full_name';
                $stmt = $db->prepare($query);
                $stmt->execute();
                
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
            }
            break;
            
        case 'POST':
            // Add new user
            requireAdmin();
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['username']) || !isset($data['password']) || !isset($data['email']) || !isset($data['full_name'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Missing required fields']);
                break;
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $query = 'INSERT INTO users (username, password, email, full_name, role) 
                     VALUES (:username, :password, :email, :full_name, :role)';
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':role', $data['role']);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(['message' => 'User created', 'id' => $db->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to create user']);
            }
            break;
            
        case 'PUT':
            // Update user
            requireAdmin();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'User ID is required']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if password is being updated
            if (isset($data['password']) && !empty($data['password'])) {
                // Hash new password
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                
                $query = 'UPDATE users SET 
                         username = :username, 
                         password = :password, 
                         email = :email, 
                         full_name = :full_name, 
                         role = :role 
                         WHERE id = :id';
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':username', $data['username']);
                $stmt->bindParam(':password', $hashedPassword);
                $stmt->bindParam(':email', $data['email']);
                $stmt->bindParam(':full_name', $data['full_name']);
                $stmt->bindParam(':role', $data['role']);
                $stmt->bindParam(':id', $id);
            } else {
                // Update without changing password
                $query = 'UPDATE users SET 
                         username = :username, 
                         email = :email, 
                         full_name = :full_name, 
                         role = :role 
                         WHERE id = :id';
                $stmt = $db->prepare($query);
                
                $stmt->bindParam(':username', $data['username']);
                $stmt->bindParam(':email', $data['email']);
                $stmt->bindParam(':full_name', $data['full_name']);
                $stmt->bindParam(':role', $data['role']);
                $stmt->bindParam(':id', $id);
            }
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'User updated']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to update user']);
            }
            break;
            
        case 'DELETE':
            // Delete user
            requireAdmin();
            if (!$id) {
                http_response_code(400);
                echo json_encode(['message' => 'User ID is required']);
                break;
            }
            
            // Prevent deletion of the current user
            if ($id == $_SESSION['user_id']) {
                http_response_code(400);
                echo json_encode(['message' => 'Cannot delete your own account']);
                break;
            }
            
            $query = 'DELETE FROM users WHERE id = :id';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['message' => 'User deleted']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to delete user']);
            }
            break;
    }
}

// Reports handlers
function handleReportsRequest($method, $db) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        return;
    }
    
    $type = $_GET['type'] ?? '';
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-t');
    
    switch ($type) {
        case 'sales':
            // Sales report
            $query = 'SELECT DATE(o.order_date) as date, COUNT(*) as order_count, 
                     SUM(o.total_amount) as total_sales 
                     FROM orders o 
                     WHERE DATE(o.order_date) BETWEEN :start_date AND :end_date 
                     AND o.status = "paid" 
                     GROUP BY DATE(o.order_date) 
                     ORDER BY date';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get top selling items
            $itemsQuery = 'SELECT mi.name, SUM(oi.quantity) as total_quantity, 
                          SUM(oi.quantity * oi.price) as total_revenue 
                          FROM order_items oi 
                          LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id 
                          LEFT JOIN orders o ON oi.order_id = o.id 
                          WHERE DATE(o.order_date) BETWEEN :start_date AND :end_date 
                          AND o.status = "paid" 
                          GROUP BY mi.id 
                          ORDER BY total_quantity DESC 
                          LIMIT 10';
            $itemsStmt = $db->prepare($itemsQuery);
            $itemsStmt->bindParam(':start_date', $startDate);
            $itemsStmt->bindParam(':end_date', $endDate);
            $itemsStmt->execute();
            
            $topItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'sales_data' => $salesData,
                'top_items' => $topItems
            ]);
            break;
            
        case 'inventory':
            // Inventory report (low stock items)
            $query = 'SELECT * FROM inventory WHERE quantity <= minimum_stock ORDER BY name';
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get inventory value
            $valueQuery = 'SELECT SUM(quantity * unit_cost) as total_value FROM inventory';
            $valueStmt = $db->prepare($valueQuery);
            $valueStmt->execute();
            
            $totalValue = $valueStmt->fetch(PDO::FETCH_ASSOC)['total_value'];
            
            echo json_encode([
                'low_stock_items' => $lowStockItems,
                'total_value' => $totalValue
            ]);
            break;
            
        case 'reservations':
            // Reservations report
            $query = 'SELECT DATE(reservation_date) as date, COUNT(*) as reservation_count, 
                     SUM(party_size) as total_guests 
                     FROM reservations 
                     WHERE DATE(reservation_date) BETWEEN :start_date AND :end_date 
                     AND status IN ("confirmed", "completed") 
                     GROUP BY DATE(reservation_date) 
                     ORDER BY date';
            $stmt = $db->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $reservationData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'reservation_data' => $reservationData
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['message' => 'Invalid report type']);
            break;
    }
}
?>