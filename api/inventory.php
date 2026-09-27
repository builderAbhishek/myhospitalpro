<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = db();
$action = sanitize($_GET['action'] ?? 'summary');

if ($action === 'alerts') {
    // Low stock count
    $low_stmt = $db->query("
        SELECT COUNT(m.id) as low_count
        FROM medicines m
        LEFT JOIN (SELECT medicine_id, SUM(current_stock) as total_stock FROM medicine_batches WHERE status = 'active' GROUP BY medicine_id) b ON m.id = b.medicine_id
        WHERE m.status = 'active' AND COALESCE(b.total_stock, 0) <= m.min_stock_level
    ");
    $low_count = (int)$low_stmt->fetch()['low_count'];

    // Expiring within 30 days
    $exp_stmt = $db->query("
        SELECT COUNT(id) as exp_count
        FROM medicine_batches
        WHERE status = 'active' 
          AND current_stock > 0
          AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");
    $exp_count = (int)$exp_stmt->fetch()['exp_count'];

    echo json_encode([
        'success' => true,
        'low_stock_count' => $low_count,
        'expiring_soon_count' => $exp_count
    ]);
    exit;
}

// Default: summary metrics
$stmt = $db->query("
    SELECT 
        (SELECT COUNT(id) FROM medicines WHERE status = 'active') as active_medicines,
        (SELECT COALESCE(SUM(current_stock), 0) FROM medicine_batches WHERE status = 'active') as total_units,
        (SELECT COALESCE(SUM(current_stock * purchase_price), 0) FROM medicine_batches WHERE status = 'active') as total_cost_valuation
");
$summary = $stmt->fetch();

echo json_encode(['success' => true, 'data' => $summary]);
