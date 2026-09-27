<?php
/**
 * Safe Purchase Cancellation & Stock Reversal
 * Validates that stock has not already been consumed in sales
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

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM purchases WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $purch = $stmt->fetch();

    if (!$purch) {
        throw new Exception("Purchase record not found.");
    }

    if ($purch['status'] === 'cancelled') {
        throw new Exception("Purchase has already been cancelled.");
    }

    // Fetch items
    $items = $db->prepare("SELECT * FROM purchase_items WHERE purchase_id = ?");
    $items->execute([$id]);
    $p_items = $items->fetchAll();

    // Check if any batch has already been sold
    foreach ($p_items as $item) {
        $total_inward = $item['quantity'] + $item['free_quantity'];
        $batch_stmt = $db->prepare("SELECT quantity FROM medicine_batches WHERE id = ? FOR UPDATE");
        $batch_stmt->execute([$item['batch_id']]);
        $curr_qty = (int)$batch_stmt->fetchColumn();

        if ($curr_qty < $total_inward) {
            throw new Exception("Cannot cancel purchase! Stock from batch '{$item['batch_number']}' has already been sold or dispensed (Available: {$curr_qty}, Inward: {$total_inward}).");
        }
    }

    // Reversal execution
    foreach ($p_items as $item) {
        $total_inward = $item['quantity'] + $item['free_quantity'];

        // Deduct batch quantity
        $db->prepare("UPDATE medicine_batches SET quantity = quantity - ? WHERE id = ?")->execute([$total_inward, $item['batch_id']]);

        // Record reversal movement
        record_stock_movement($db, 'PURCHASE_RETURN', $item['medicine_id'], $item['batch_id'], -$total_inward, $id, $purch['invoice_number'], "Purchase consignment cancellation rollback");
    }

    // Reverse supplier due balance if applicable
    if ((float)$purch['due_amount'] > 0) {
        $db->prepare("UPDATE suppliers SET current_balance = GREATEST(0, current_balance - ?) WHERE id = ?")->execute([$purch['due_amount'], $purch['supplier_id']]);
    }

    // Update purchase status
    $db->prepare("UPDATE purchases SET status = 'cancelled' WHERE id = ?")->execute([$id]);

    record_audit_log($db, 'PURCHASE_CANCEL', 'purchases', $id, $purch, ['status' => 'cancelled']);
    $db->commit();

    set_flash('success', "Purchase consignment {$purch['purchase_number']} cancelled and stock reversed successfully.");
} catch (Exception $e) {
    $db->rollBack();
    set_flash('error', $e->getMessage());
}

header("Location: view.php?id={$id}");
exit;
