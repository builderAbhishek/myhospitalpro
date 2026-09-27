<?php
/**
 * Inward Purchase Consignment Creator
 * Transactional Multi-Item Inward Bill Processor
 */
$page_title = "New Inward Purchase";
$current_page = "purchases";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$error = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $invoice_number = trim($_POST['invoice_number'] ?? '');
    $invoice_date = trim($_POST['invoice_date'] ?? date('Y-m-d'));
    $payment_type = trim($_POST['payment_type'] ?? 'Credit');
    $paid_amount = (float)($_POST['paid_amount'] ?? 0.0);
    $notes = trim($_POST['notes'] ?? '');

    $items_medicine = $_POST['medicine_id'] ?? [];
    $items_batch = $_POST['batch_number'] ?? [];
    $items_expiry = $_POST['expiry_date'] ?? [];
    $items_qty = $_POST['quantity'] ?? [];
    $items_free_qty = $_POST['free_quantity'] ?? [];
    $items_rate = $_POST['purchase_price'] ?? [];
    $items_mrp = $_POST['mrp'] ?? [];
    $items_selling = $_POST['selling_price'] ?? [];
    $items_gst = $_POST['gst_rate'] ?? [];

    if (empty($supplier_id)) {
        $error = "Please select a distributor/supplier.";
    } elseif (empty($invoice_number)) {
        $error = "Supplier Invoice Number is required.";
    } elseif (empty($items_medicine) || count($items_medicine) === 0) {
        $error = "Please add at least one medicine item to the purchase bill.";
    } else {
        try {
            $db->beginTransaction();

            $subtotal = 0.0;
            $total_gst = 0.0;
            $parsed_items = [];

            for ($i = 0; $i < count($items_medicine); $i++) {
                $med_id = (int)$items_medicine[$i];
                $batch_no = trim($items_batch[$i] ?? '');
                $expiry = trim($items_expiry[$i] ?? '');
                $qty = (int)($items_qty[$i] ?? 0);
                $free_qty = (int)($items_free_qty[$i] ?? 0);
                $rate = (float)($items_rate[$i] ?? 0.0);
                $mrp = (float)($items_mrp[$i] ?? 0.0);
                $selling = (float)($items_selling[$i] ?? 0.0);
                $gst = (float)($items_gst[$i] ?? 12.0);

                if ($med_id <= 0 || empty($batch_no) || empty($expiry) || $qty <= 0) {
                    continue; // Skip invalid rows
                }

                $row_base = $qty * $rate;
                $row_gst = $row_base * ($gst / 100.0);
                $row_total = $row_base + $row_gst;

                $subtotal += $row_base;
                $total_gst += $row_gst;

                $parsed_items[] = [
                    'medicine_id' => $med_id,
                    'batch_number' => $batch_no,
                    'expiry_date' => $expiry,
                    'quantity' => $qty,
                    'free_quantity' => $free_qty,
                    'purchase_price' => $rate,
                    'mrp' => $mrp,
                    'selling_price' => $selling,
                    'gst_rate' => $gst,
                    'total' => $row_total
                ];
            }

            if (empty($parsed_items)) {
                throw new Exception("No valid items in purchase bill. Please verify medicine, batch, and quantities.");
            }

            $grand_total = round($subtotal + $total_gst, 2);
            $due_amount = max(0, $grand_total - $paid_amount);
            $payment_status = ($due_amount <= 0) ? 'Paid' : (($paid_amount > 0) ? 'Partial' : 'Pending');

            $purchase_number = generate_purchase_number();
            $user_id = $_SESSION['user_id'] ?? null;

            // 1. Insert Purchase
            $stmt = $db->prepare("
                INSERT INTO purchases (
                    purchase_number, invoice_number, supplier_id, invoice_date, subtotal, 
                    discount, gst_amount, round_off, grand_total, paid_amount, due_amount, 
                    payment_type, payment_status, status, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, 0.00, ?, 0.00, ?, ?, ?, ?, ?, 'completed', ?, ?)
            ");
            $stmt->execute([
                $purchase_number, $invoice_number, $supplier_id, $invoice_date, $subtotal,
                $total_gst, $grand_total, $paid_amount, $due_amount, $payment_type,
                $payment_status, $notes, $user_id
            ]);
            $purchase_id = (int)$db->lastInsertId();

            // 2. Loop Items -> Insert item, create/update batch, record stock movement
            foreach ($parsed_items as $item) {
                // Check if batch exists for this medicine
                $chk_batch = $db->prepare("SELECT id, quantity FROM medicine_batches WHERE medicine_id = ? AND batch_number = ? LIMIT 1");
                $chk_batch->execute([$item['medicine_id'], $item['batch_number']]);
                $existing_batch = $chk_batch->fetch();

                $total_inward = $item['quantity'] + $item['free_quantity'];
                if ($existing_batch) {
                    $batch_id = (int)$existing_batch['id'];
                    $up_batch = $db->prepare("
                        UPDATE medicine_batches 
                        SET quantity = quantity + ?, 
                            expiry_date = ?, purchase_price = ?, mrp = ?, selling_price = ?, status = 'active'
                        WHERE id = ?
                    ");
                    $up_batch->execute([$total_inward, $item['expiry_date'], $item['purchase_price'], $item['mrp'], $item['selling_price'], $batch_id]);
                } else {
                    $in_batch = $db->prepare("
                        INSERT INTO medicine_batches (medicine_id, batch_number, expiry_date, purchase_price, mrp, selling_price, quantity, free_quantity, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $in_batch->execute([
                        $item['medicine_id'], $item['batch_number'], $item['expiry_date'],
                        $item['purchase_price'], $item['mrp'], $item['selling_price'],
                        $total_inward, $item['free_quantity']
                    ]);
                    $batch_id = (int)$db->lastInsertId();
                }

                // Insert purchase item
                $in_pi = $db->prepare("
                    INSERT INTO purchase_items (
                        purchase_id, medicine_id, batch_id, batch_number, expiry_date,
                        quantity, free_quantity, purchase_price, mrp, gst_rate, discount, total
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?)
                ");
                $in_pi->execute([
                    $purchase_id, $item['medicine_id'], $batch_id, $item['batch_number'],
                    $item['expiry_date'], $item['quantity'], $item['free_quantity'],
                    $item['purchase_price'], $item['mrp'], $item['gst_rate'], $item['total']
                ]);

                // Record stock movement
                record_stock_movement($db, 'PURCHASE', $item['medicine_id'], $batch_id, $total_inward, $purchase_id, $invoice_number, "Supplier Inward: {$item['quantity']} + {$item['free_quantity']} free");
            }

            // 3. Update Supplier Balance
            if ($due_amount > 0) {
                $db->prepare("UPDATE suppliers SET current_balance = current_balance + ? WHERE id = ?")->execute([$due_amount, $supplier_id]);
            }

            // 4. Record Supplier Payment if paid amount > 0
            if ($paid_amount > 0) {
                $pay_ref = 'SPAY-' . date('Y') . '-' . str_pad($purchase_id, 4, '0', STR_PAD_LEFT);
                $db->prepare("
                    INSERT INTO supplier_payments (supplier_id, payment_number, payment_date, amount, payment_method, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ")->execute([$supplier_id, $pay_ref, $invoice_date, $paid_amount, $payment_type, "Initial payment on purchase {$invoice_number}", $user_id]);
            }

            record_audit_log($db, 'PURCHASE_CREATE', 'purchases', $purchase_id, null, ['invoice' => $invoice_number, 'grand_total' => $grand_total]);
            $db->commit();

            set_flash('success', "Purchase consignment {$purchase_number} created and inventory batches stocked!");
            header("Location: view.php?id={$purchase_id}");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error saving purchase: " . $e->getMessage();
        }
    }
}

// Fetch suppliers and medicines for dynamic form
$suppliers = $db->query("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$medicines = $db->query("SELECT id, name, generic_name, purchase_price, selling_price, mrp, gst_rate, unit FROM medicines WHERE status = 'active' ORDER BY name ASC")->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">New Inward Purchase</h1>
                    <p class="text-xs text-slate-500">Record vendor delivery consignment and auto-stock inventory batches</p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="create.php" id="purchase-form" class="space-y-6">
            <?= csrf_field() ?>

            <!-- Bill Master Details -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-teal-50 text-teal-700 flex items-center justify-center text-xs font-bold">1</span>
                        <span>Distributor &amp; Invoice Header</span>
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Distributor / Supplier <span class="text-rose-500">*</span></label>
                        <select name="supplier_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= e($s['name']) ?> (<?= e($s['city']) ?>) • Outstanding: <?= format_currency($s['current_balance']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Supplier Invoice # <span class="text-rose-500">*</span></label>
                        <input type="text" name="invoice_number" required placeholder="e.g. PUR-2026-0912" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Invoice Date <span class="text-rose-500">*</span></label>
                        <input type="date" name="invoice_date" value="<?= date('Y-m-d') ?>" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>
                </div>
            </div>

            <!-- Dynamic Line Items Table -->
            <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
                <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-6 h-6 rounded bg-teal-50 text-teal-700 flex items-center justify-center text-xs font-bold">2</span>
                            <span>Consignment Line Items (Medicine Batches)</span>
                        </h3>
                    </div>
                    <button type="button" onclick="addRow()" class="btn-primary text-xs py-1.5 px-3 bg-teal-600 hover:bg-teal-700 text-white rounded-lg flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        <span>Add Row</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs" id="items-table">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                                <th class="py-2.5 px-3 w-56">Medicine</th>
                                <th class="py-2.5 px-3 w-28">Batch #</th>
                                <th class="py-2.5 px-3 w-28">Expiry</th>
                                <th class="py-2.5 px-3 w-20 text-right">Qty</th>
                                <th class="py-2.5 px-3 w-16 text-right">Free</th>
                                <th class="py-2.5 px-3 w-24 text-right">Rate (₹)</th>
                                <th class="py-2.5 px-3 w-24 text-right">MRP (₹)</th>
                                <th class="py-2.5 px-3 w-24 text-right">Selling (₹)</th>
                                <th class="py-2.5 px-3 w-20 text-right">GST %</th>
                                <th class="py-2.5 px-3 w-28 text-right">Total (₹)</th>
                                <th class="py-2.5 px-2 w-10 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200" id="items-body">
                            <!-- Rows injected by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <div class="w-72 space-y-1.5 text-xs text-right">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal (Taxable):</span>
                            <span id="display-subtotal" class="font-semibold text-slate-900">₹0.00</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>GST Tax Total:</span>
                            <span id="display-gst" class="font-semibold text-slate-900">₹0.00</span>
                        </div>
                        <div class="flex justify-between text-base font-extrabold text-teal-800 pt-2 border-t border-slate-200">
                            <span>Grand Total:</span>
                            <span id="display-grand-total">₹0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment & Settlement -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <div class="border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-6 h-6 rounded bg-teal-50 text-teal-700 flex items-center justify-center text-xs font-bold">3</span>
                        <span>Payment Terms &amp; Settlement</span>
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Payment Mode</label>
                        <select name="payment_type" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                            <option value="Credit">Credit (Pay Later)</option>
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer / NEFT">Bank Transfer / NEFT</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Paid Amount (₹)</label>
                        <input type="number" step="0.01" name="paid_amount" id="paid-amount" value="0.00" oninput="calcTotals()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-slate-700 mb-1 block">Due Balance (₹)</label>
                        <input type="text" id="display-due" readonly value="₹0.00" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs sm:text-sm font-bold text-rose-600">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Consignment Notes / Verification Remarks</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Delivery verified, cold chain packs received in good order..." class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="index.php" class="btn-secondary text-xs py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Cancel</a>
                <button type="submit" class="btn-primary text-xs py-2.5 px-8 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">Save &amp; Stock Inward Consignment</button>
            </div>
        </form>

    </main>
</div>

<!-- Dynamic Row Script -->
<script>
const medicinesData = <?= json_encode($medicines) ?>;

function addRow() {
    const tbody = document.getElementById('items-body');
    const tr = document.createElement('tr');
    tr.className = 'hover:bg-slate-50/60';

    let options = '<option value="">-- Choose Medicine --</option>';
    medicinesData.forEach(m => {
        options += `<option value="${m.id}" data-rate="${m.purchase_price}" data-mrp="${m.mrp}" data-selling="${m.selling_price}" data-gst="${m.gst_rate}">${m.name} (${m.unit})</option>`;
    });

    tr.innerHTML = `
        <td class="py-2 px-3">
            <select name="medicine_id[]" required onchange="onMedSelect(this)" class="w-full border border-slate-300 rounded p-1 text-xs">
                ${options}
            </select>
        </td>
        <td class="py-2 px-3"><input type="text" name="batch_number[]" required placeholder="BATCH#" class="w-full border border-slate-300 rounded p-1 text-xs"></td>
        <td class="py-2 px-3"><input type="date" name="expiry_date[]" required class="w-full border border-slate-300 rounded p-1 text-xs"></td>
        <td class="py-2 px-3"><input type="number" name="quantity[]" required value="10" min="1" oninput="calcTotals()" class="w-full border border-slate-300 rounded p-1 text-xs text-right qty-in"></td>
        <td class="py-2 px-3"><input type="number" name="free_quantity[]" value="0" min="0" class="w-full border border-slate-300 rounded p-1 text-xs text-right"></td>
        <td class="py-2 px-3"><input type="number" step="0.01" name="purchase_price[]" required value="0.00" oninput="calcTotals()" class="w-full border border-slate-300 rounded p-1 text-xs text-right rate-in"></td>
        <td class="py-2 px-3"><input type="number" step="0.01" name="mrp[]" value="0.00" class="w-full border border-slate-300 rounded p-1 text-xs text-right mrp-in"></td>
        <td class="py-2 px-3"><input type="number" step="0.01" name="selling_price[]" value="0.00" class="w-full border border-slate-300 rounded p-1 text-xs text-right selling-in"></td>
        <td class="py-2 px-3">
            <select name="gst_rate[]" onchange="calcTotals()" class="w-full border border-slate-300 rounded p-1 text-xs text-right gst-in">
                <option value="0">0%</option>
                <option value="5">5%</option>
                <option value="12" selected>12%</option>
                <option value="18">18%</option>
                <option value="28">28%</option>
            </select>
        </td>
        <td class="py-2 px-3 text-right font-bold row-total">₹0.00</td>
        <td class="py-2 px-2 text-center">
            <button type="button" onclick="this.closest('tr').remove(); calcTotals();" class="text-slate-400 hover:text-rose-600 p-1">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function onMedSelect(select) {
    const opt = select.selectedOptions[0];
    const tr = select.closest('tr');
    if (opt && opt.dataset.rate) {
        tr.querySelector('.rate-in').value = opt.dataset.rate;
        tr.querySelector('.mrp-in').value = opt.dataset.mrp;
        tr.querySelector('.selling-in').value = opt.dataset.selling;
        tr.querySelector('.gst-in').value = parseFloat(opt.dataset.gst) || 12;
    }
    calcTotals();
}

function calcTotals() {
    let subtotal = 0;
    let totalGst = 0;

    document.querySelectorAll('#items-body tr').forEach(tr => {
        const qty = parseFloat(tr.querySelector('.qty-in')?.value) || 0;
        const rate = parseFloat(tr.querySelector('.rate-in')?.value) || 0;
        const gst = parseFloat(tr.querySelector('.gst-in')?.value) || 0;

        const rowBase = qty * rate;
        const rowGst = rowBase * (gst / 100);
        const rowTotal = rowBase + rowGst;

        subtotal += rowBase;
        totalGst += rowGst;

        const rowTotalEl = tr.querySelector('.row-total');
        if (rowTotalEl) rowTotalEl.textContent = '₹' + rowTotal.toFixed(2);
    });

    const grandTotal = subtotal + totalGst;
    document.getElementById('display-subtotal').textContent = '₹' + subtotal.toFixed(2);
    document.getElementById('display-gst').textContent = '₹' + totalGst.toFixed(2);
    document.getElementById('display-grand-total').textContent = '₹' + grandTotal.toFixed(2);

    const paid = parseFloat(document.getElementById('paid-amount')?.value) || 0;
    const due = Math.max(0, grandTotal - paid);
    document.getElementById('display-due').value = '₹' + due.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    addRow(); // Start with one row
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
