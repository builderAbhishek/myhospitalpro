<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = db();
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$limit = (int)($_GET['limit'] ?? 10);

$where = "status != 'cancelled'";
$params = [];

if ($supplier_id > 0) {
    $where .= " AND supplier_id = :supplier_id";
    $params['supplier_id'] = $supplier_id;
}

$sql = "
    SELECT p.*, s.name as supplier_name
    FROM purchases p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE {$where}
    ORDER BY p.purchase_date DESC
    LIMIT {$limit}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $purchases]);
