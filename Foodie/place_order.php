<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// Require login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'not_logged_in']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty.']);
    exit;
}

$user_id        = (int)$_SESSION['user_id'];
$customer_name  = trim($data['customer_name']  ?? '');
$customer_phone = trim($data['customer_phone'] ?? '');
$customer_addr  = trim($data['customer_address'] ?? '');
$payment_method = ($data['payment_method'] === 'online') ? 'online' : 'cod';
$payment_status = ($payment_method === 'online') ? 'paid' : 'pending';

if (!$customer_name || !$customer_phone || !$customer_addr) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all delivery details.']);
    exit;
}

// Get real prices from DB
$ids = implode(',', array_map(fn($i) => (int)$i['id'], $data['cart']));
if (!$ids) {
    echo json_encode(['status' => 'error', 'message' => 'No valid products.']);
    exit;
}
$pres = $conn->query("SELECT id, price FROM products WHERE id IN ($ids)");
$db_prices = [];
while ($row = $pres->fetch_assoc()) {
    $db_prices[(int)$row['id']] = (float)$row['price'];
}

$total = 0;
foreach ($data['cart'] as $item) {
    $pid   = (int)$item['id'];
    $qty   = max(1, (int)($item['qty'] ?? 1));
    $price = $db_prices[$pid] ?? 0;
    $total += $price * $qty;
}

if ($total <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid cart.']);
    exit;
}

// Insert order using prepared statement
$ostmt = $conn->prepare(
    "INSERT INTO orders
        (user_id, total, status, payment_method, payment_status, customer_name, customer_phone, customer_address)
     VALUES (?, ?, 'pending', ?, ?, ?, ?, ?)"
);
if (!$ostmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$ostmt->bind_param("idsssss",
    $user_id, $total,
    $payment_method, $payment_status,
    $customer_name, $customer_phone, $customer_addr
);
if (!$ostmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Order insert failed: ' . $ostmt->error]);
    exit;
}
$order_id = (int)$conn->insert_id;

// Insert order items
$istmt = $conn->prepare(
    "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
);
if (!$istmt) {
    echo json_encode(['status' => 'error', 'message' => 'Items prepare failed: ' . $conn->error]);
    exit;
}
foreach ($data['cart'] as $item) {
    $pid   = (int)$item['id'];
    $qty   = max(1, (int)($item['qty'] ?? 1));
    $price = $db_prices[$pid] ?? 0;
    if ($price > 0) {
        $istmt->bind_param("iiid", $order_id, $pid, $qty, $price);
        $istmt->execute();
    }
}

echo json_encode([
    'status'   => 'success',
    'order_id' => $order_id,
    'total'    => number_format($total, 2)
]);
?>