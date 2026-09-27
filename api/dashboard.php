<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = db();

// Today's KPIs
$today_sales = $db->query("
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(id) as count 
    FROM sales 
    WHERE DATE(sale_date) = CURDATE() AND status != 'cancelled'
")->fetch();

$today_purchases = $db->query("
    SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(id) as count 
    FROM purchases 
    WHERE purchase_date = CURDATE() AND status != 'cancelled'
")->fetch();

$total_medicines = $db->query("SELECT COUNT(id) as count FROM medicines WHERE status = 'active'")->fetch();

$low_stock = $db->query("
    SELECT COUNT(m.id) as count
    FROM medicines m
    LEFT JOIN (SELECT medicine_id, SUM(current_stock) as total_stock FROM medicine_batches WHERE status = 'active' GROUP BY medicine_id) b ON m.id = b.medicine_id
    WHERE m.status = 'active' AND COALESCE(b.total_stock, 0) <= m.min_stock_level
")->fetch();

echo json_encode([
    'success' => true,
    'kpis' => [
        'today_sales' => (float)$today_sales['total'],
        'today_sales_count' => (int)$today_sales['count'],
        'today_purchases' => (float)$today_purchases['total'],
        'today_purchases_count' => (int)$today_purchases['count'],
        'active_medicines' => (int)$total_medicines['count'],
        'low_stock_count' => (int)$low_stock['count']
    ]
]);
