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
$barcode = sanitize($_GET['barcode'] ?? '');
$action = sanitize($_GET['action'] ?? 'search');

if ($action === 'batch_details') {
    $batch_id = (int)($_GET['batch_id'] ?? 0);
    $stmt = $db->prepare("
        SELECT b.*, m.name, m.generic_name, m.item_code, m.gst_rate, m.dosage_form, m.unit_type
        FROM medicine_batches b
        JOIN medicines m ON b.medicine_id = m.id
        WHERE b.id = :id
    ");
    $stmt->execute(['id' => $batch_id]);
    $batch = $stmt->fetch();
    echo json_encode(['success' => true, 'batch' => $batch]);
    exit;
}

// Search with active batches (FEFO: earliest non-expired expiry first)
$params = [];
$where = "m.status = 'active'";

if (!empty($barcode)) {
    $where .= " AND (m.barcode = :barcode OR b.batch_number = :barcode)";
    $params['barcode'] = $barcode;
} elseif (!empty($q)) {
    $where .= " AND (m.name LIKE :q OR m.generic_name LIKE :q OR m.item_code LIKE :q OR b.batch_number LIKE :q)";
    $params['q'] = "%{$q}%";
}

$sql = "
    SELECT 
        m.id AS medicine_id,
        m.name,
        m.generic_name,
        m.item_code,
        m.dosage_form,
        m.strength,
        m.rack_location,
        m.gst_rate,
        b.id AS batch_id,
        b.batch_number,
        b.expiry_date,
        b.mrp,
        b.selling_price,
        b.purchase_price,
        b.current_stock
    FROM medicines m
    JOIN medicine_batches b ON m.id = b.medicine_id
    WHERE {$where} 
      AND b.current_stock > 0 
      AND b.status = 'active'
      AND b.expiry_date >= CURDATE()
    ORDER BY b.expiry_date ASC, m.name ASC
    LIMIT 30
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

echo json_encode(['success' => true, 'count' => count($results), 'data' => $results]);
