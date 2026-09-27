<?php
/**
 * Soft Delete / Deactivate Medicine
 */
require_once __DIR__ . '/../includes/app.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if (!validate_csrf_token($token)) {
    set_flash('error', "Invalid security token.");
    header("Location: index.php");
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM medicines WHERE id = ?");
$stmt->execute([$id]);
$med = $stmt->fetch();

if ($med) {
    $db->prepare("UPDATE medicines SET status = 'inactive' WHERE id = ?")->execute([$id]);
    record_audit_log($db, 'MEDICINE_DEACTIVATE', 'medicines', $id, $med, ['status' => 'inactive']);
    set_flash('success', "Medicine '{$med['name']}' has been deactivated from formulary.");
} else {
    set_flash('error', "Medicine not found.");
}

header("Location: index.php");
exit;
