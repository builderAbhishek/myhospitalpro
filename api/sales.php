<?php
require_once __DIR__ . '/../includes/app.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = db();
$action = sanitize($_GET['action'] ?? 'recent');

if ($action === 'chart_7_days') {
    $stmt = $db->query("
        SELECT 
            DATE(sale_date) AS date,
            DATE_FORMAT(sale_date, '%a') AS day_name,
            COALESCE(SUM(total_amount), 0) AS total_sales,
            COUNT(id) AS bill_count
        FROM sales
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
          AND status != 'cancelled'
        GROUP BY DATE(sale_date), DATE_FORMAT(sale_date, '%a')
        ORDER BY date ASC
    ");
    $data = $stmt->fetchAll();
    echo json_encode(['success' => true, 'chart' => $data]);
    exit;
}

// Recent sales
$stmt = $db->query("
    SELECT s.*, c.name as customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.status != 'cancelled'
    ORDER BY s.sale_date DESC
    LIMIT 10
");
$recent = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $recent]);
