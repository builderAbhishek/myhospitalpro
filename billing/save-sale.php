<?php
/**
 * Transactional POS Sale Processor
 * Row locks batches (FOR UPDATE), deducts FEFO stock, writes sales, sale_items, 
 * stock_movements, and customer credit ledgers.
 */
require_once __DIR__ . '/../includes/app.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// If not JSON, check $_POST
if (!$data && !empty($_POST)) {
    $data = $_POST;
    if (isset($data['items']) && is_string($data['items'])) {
        $data['items'] = json_decode($data['items'], true);
    }
}

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid or empty request payload.']);
    exit;
}

// CSRF check
$csrf = $data['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!validate_csrf_token($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session token. Please refresh.']);
    exit;
}

$customer_id = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
$customer_name = trim($data['customer_name'] ?? 'Walk-in Customer');
$customer_mobile = trim($data['customer_mobile'] ?? '');
$doctor_name = trim($data['doctor_name'] ?? '');
$payment_method = trim($data['payment_method'] ?? 'Cash'); // Cash, UPI, Card, Credit
$paid_amount = (float)($data['paid_amount'] ?? 0.0);
$items = $data['items'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Cannot checkout an empty cart.']);
    exit;
}

$db = get_db();

try {
    $db->beginTransaction();

    $subtotal = 0.0;
    $total_gst = 0.0;
    $total_discount = 0.0;
    $sale_items_to_insert = [];

    // Process and lock each batch row
    foreach ($items as $item) {
        $med_id = (int)($item['medicine_id'] ?? 0);
        $batch_id = (int)($item['batch_id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);
        $disc_pct = (float)($item['discount'] ?? 0.0);

        if ($med_id <= 0 || $batch_id <= 0 || $qty <= 0) {
            continue;
        }

        // Lock batch row
        $b_stmt = $db->prepare("
            SELECT b.*, m.name as medicine_name, m.gst_rate as med_gst, m.hsn_code
            FROM medicine_batches b
            JOIN medicines m ON b.medicine_id = m.id
            WHERE b.id = ? AND b.medicine_id = ?
            FOR UPDATE
        ");
        $b_stmt->execute([$batch_id, $med_id]);
        $batch = $b_stmt->fetch();

        if (!$batch) {
            throw new Exception("Batch record not found for medicine #{$med_id}.");
        }

        if ($batch['expiry_date'] < date('Y-m-d')) {
            throw new Exception("Batch '{$batch['batch_number']}' for '{$batch['medicine_name']}' is expired and cannot be sold!");
        }

        if ((int)$batch['quantity'] < $qty) {
            throw new Exception("Insufficient stock for '{$batch['medicine_name']}' (Batch {$batch['batch_number']}). Requested: {$qty}, Available: {$batch['quantity']}.");
        }

        // Server-side financial calculation
        $selling_price = (float)$batch['selling_price'];
        $cost_price = (float)$batch['purchase_price']; // Actual batch acquisition cost
        $gst_rate = (float)$batch['med_gst'];

        $item_base = $selling_price * $qty;
        $item_discount = $item_base * ($disc_pct / 100.0);
        $taxable = $item_base - $item_discount;
        $item_gst = $taxable * ($gst_rate / 100.0);
        $item_total = $taxable + $item_gst;

        $subtotal += $taxable;
        $total_gst += $item_gst;
        $total_discount += $item_discount;

        // Deduct batch quantity
        $new_batch_qty = (int)$batch['quantity'] - $qty;
        $new_batch_status = ($new_batch_qty <= 0) ? 'depleted' : 'active';
        $db->prepare("UPDATE medicine_batches SET quantity = ?, status = ? WHERE id = ?")->execute([$new_batch_qty, $new_batch_status, $batch_id]);

        $sale_items_to_insert[] = [
            'medicine_id' => $med_id,
            'batch_id' => $batch_id,
            'medicine_name' => $batch['medicine_name'],
            'batch_number' => $batch['batch_number'],
            'expiry_date' => $batch['expiry_date'],
            'quantity' => $qty,
            'selling_price' => $selling_price,
            'cost_price' => $cost_price,
            'discount' => $item_discount,
            'gst_rate' => $gst_rate,
            'gst_amount' => $item_gst,
            'total' => $item_total
        ];
    }

    if (empty($sale_items_to_insert)) {
        throw new Exception("No valid items to dispense.");
    }

    $raw_grand = $subtotal + $total_gst;
    $round_off = round(round($raw_grand) - $raw_grand, 2);
    $grand_total = round($raw_grand + $round_off, 2);

    $due_amount = 0.0;
    $payment_status = 'Paid';

    if ($payment_method === 'Credit') {
        $due_amount = $grand_total - $paid_amount;
        $payment_status = ($due_amount <= 0) ? 'Paid' : (($paid_amount > 0) ? 'Partial' : 'Pending');

        // Check customer credit limit
        if ($customer_id) {
            $c_stmt = $db->prepare("SELECT credit_limit, current_balance FROM customers WHERE id = ? FOR UPDATE");
            $c_stmt->execute([$customer_id]);
            $cust = $c_stmt->fetch();
            if ($cust && ($cust['current_balance'] + $due_amount) > $cust['credit_limit']) {
                throw new Exception("Sale exceeds patient credit limit! Current dues: ₹{$cust['current_balance']}, Credit Limit: ₹{$cust['credit_limit']}.");
            }
        }
    } else {
        $paid_amount = $grand_total;
    }

    $invoice_number = generate_invoice_number();
    $user_id = $_SESSION['user_id'] ?? null;

    // 1. Insert Sale Header
    $stmt = $db->prepare("
        INSERT INTO sales (
            invoice_number, customer_id, customer_name, customer_mobile, doctor_name,
            subtotal, discount, gst_amount, round_off, grand_total, paid_amount, due_amount,
            payment_method, payment_status, status, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, NOW())
    ");
    $stmt->execute([
        $invoice_number, $customer_id, $customer_name, $customer_mobile, $doctor_name,
        $subtotal, $total_discount, $total_gst, $round_off, $grand_total, $paid_amount,
        $due_amount, $payment_method, $payment_status, $user_id
    ]);
    $sale_id = (int)$db->lastInsertId();

    // 2. Insert Sale Items & Record Stock Movements
    $si_stmt = $db->prepare("
        INSERT INTO sale_items (
            sale_id, medicine_id, batch_id, medicine_name, batch_number, expiry_date,
            quantity, selling_price, cost_price, discount, gst_rate, gst_amount, total
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($sale_items_to_insert as $si) {
        $si_stmt->execute([
            $sale_id, $si['medicine_id'], $si['batch_id'], $si['medicine_name'],
            $si['batch_number'], $si['expiry_date'], $si['quantity'], $si['selling_price'],
            $si['cost_price'], $si['discount'], $si['gst_rate'], $si['gst_amount'], $si['total']
        ]);

        record_stock_movement($db, 'SALE', $si['medicine_id'], $si['batch_id'], -$si['quantity'], $sale_id, $invoice_number, "Counter POS Sale");
    }

    // 3. Update Customer Ledger
    if ($customer_id && $due_amount > 0) {
        $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?")->execute([$due_amount, $customer_id]);
    }

    // 4. Record Customer Payment if paid
    if ($customer_id && $paid_amount > 0 && $payment_method === 'Credit') {
        $cpay_ref = 'CPAY-' . date('Y') . '-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
        $db->prepare("
            INSERT INTO customer_payments (customer_id, receipt_number, payment_date, amount, payment_method, notes, created_by)
            VALUES (?, ?, CURDATE(), ?, 'Cash', 'Counter credit sale down payment', ?)
        ")->execute([$customer_id, $cpay_ref, $paid_amount, $user_id]);
    }

    record_audit_log($db, 'SALE_CREATE', 'billing', $sale_id, null, ['invoice' => $invoice_number, 'grand_total' => $grand_total]);
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Sale completed successfully!',
        'sale_id' => $sale_id,
        'invoice_number' => $invoice_number,
        'invoice_url' => "invoice.php?id={$sale_id}"
    ]);
    exit;
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
