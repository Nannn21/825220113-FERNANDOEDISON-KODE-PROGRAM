<?php

require_once 'config.php';
requireLogin();

$user = getCurrentUser();

function initializeDatabase() {
    $conn = getDBConnection();
    $sql = "CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        description TEXT,
        quantity INT NOT NULL DEFAULT 0,
        price DECIMAL(10, 2) DEFAULT 0.00,
        category VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === FALSE) {
        die("Error creating table: " . $conn->error);
    }
    $conn->close();
}

function getAllInventoryItems() {
    $conn = getDBConnection();
    $sql = "SELECT * FROM inventory ORDER BY item_name ASC";
    $result = $conn->query($sql);
    $items = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    $conn->close();
    return $items;
}

function getInventoryItem($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $item;
}

function addInventoryItem($itemName, $description, $quantity, $price, $category) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO inventory (item_name, description, quantity, price, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssids", $itemName, $description, $quantity, $price, $category);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return true;
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return $error;
    }
}

function updateInventoryItem($id, $itemName, $description, $quantity, $price, $category) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE inventory SET item_name = ?, description = ?, quantity = ?, price = ?, category = ? WHERE id = ?");
    $stmt->bind_param("ssidsi", $itemName, $description, $quantity, $price, $category, $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return true;
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return $error;
    }
}

function deleteInventoryItem($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return true;
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return $error;
    }
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    initializeDatabase();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $result = addInventoryItem(
                    $_POST['item_name'] ?? '',
                    $_POST['description'] ?? '',
                    $_POST['quantity'] ?? 0,
                    $_POST['price'] ?? 0.00,
                    $_POST['category'] ?? ''
                );
                if ($result === true) {
                    $message = 'Item added successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding item: ' . $result;
                    $messageType = 'error';
                }
                break;
                
            case 'update':
                $result = updateInventoryItem(
                    $_POST['id'] ?? 0,
                    $_POST['item_name'] ?? '',
                    $_POST['description'] ?? '',
                    $_POST['quantity'] ?? 0,
                    $_POST['price'] ?? 0.00,
                    $_POST['category'] ?? ''
                );
                if ($result === true) {
                    $message = 'Item updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating item: ' . $result;
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                $result = deleteInventoryItem($_POST['id'] ?? 0);
                if ($result === true) {
                    $message = 'Item deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error deleting item: ' . $result;
                    $messageType = 'error';
                }
                break;
        }
    }
}

initializeDatabase();
$inventoryItems = getAllInventoryItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .actions form {
            display: inline;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            margin: -30px -30px 30px -30px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            color: white;
            font-size: 1.8em;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-menu span {
            font-size: 0.95em;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: 2px solid white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s;
        }
        
        .btn-logout:hover {
            background: white;
            color: #667eea;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: 2px solid white;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s;
            margin-right: 10px;
        }
        
        .btn-back:hover {
            background: white;
            color: #667eea;
        }
    </style>
    <link rel="stylesheet" href="https:
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cubes"></i> Inventory Management System</h1>
            <div class="user-menu">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['nama_lengkap']); ?></span>
                <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <h1 style="display:none;">📦 Inventory Management System</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-section">
            <h2>Add New Item</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="item_name">Item Name *</label>
                    <input type="text" id="item_name" name="item_name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category">
                </div>
                <button type="submit">Add Item</button>
            </form>
        </div>
        
        <div class="form-section">
            <h2>Inventory List</h2>
            <?php if (empty($inventoryItems)): ?>
                <div class="empty-state">
                    <p>No items in inventory. Add your first item above!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventoryItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['id']); ?></td>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? ''); ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

