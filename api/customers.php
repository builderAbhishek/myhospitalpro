<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = db();
$q = sanitize($_GET['q'] ?? '');
$customer_id = (int)($_GET['id'] ?? 0);

if ($customer_id > 0) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = :id");
    $stmt->execute(['id' => $customer_id]);
    $customer = $stmt->fetch();
    echo json_encode(['success' => true, 'customer' => $customer]);
    exit;
}

$params = [];
$where = "status = 'active'";

if (!empty($q)) {
    $where .= " AND (name LIKE :q OR phone LIKE :q OR email LIKE :q)";
    $params['q'] = "%{$q}%";
}

$sql = "SELECT id, name, phone, email, current_balance, credit_limit FROM customers WHERE {$where} ORDER BY name ASC LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

echo json_encode(['success' => true, 'count' => count($customers), 'data' => $customers]);
