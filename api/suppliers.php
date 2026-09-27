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
$supplier_id = (int)($_GET['id'] ?? 0);

if ($supplier_id > 0) {
    $stmt = $db->prepare("SELECT * FROM suppliers WHERE id = :id");
    $stmt->execute(['id' => $supplier_id]);
    $supplier = $stmt->fetch();
    echo json_encode(['success' => true, 'supplier' => $supplier]);
    exit;
}

$params = [];
$where = "status = 'active'";

if (!empty($q)) {
    $where .= " AND (name LIKE :q OR company_name LIKE :q OR phone LIKE :q OR gstin LIKE :q)";
    $params['q'] = "%{$q}%";
}

$sql = "SELECT id, name, company_name, phone, gstin, current_balance FROM suppliers WHERE {$where} ORDER BY name ASC LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

echo json_encode(['success' => true, 'count' => count($suppliers), 'data' => $suppliers]);
