<?php
/**
 * Record Payment to Supplier / Distributor
 */
$page_title = "Record Vendor Payment";
$current_page = "suppliers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$supplier_id = (int)($_GET['supplier_id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supp = $stmt->fetch();

if (!$supp) {
    set_flash('error', "Supplier not found.");
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $amount = (float)($_POST['amount'] ?? 0.0);
    $payment_method = trim($_POST['payment_method'] ?? 'Bank Transfer / NEFT');
    $reference_number = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0) {
        $error = "Payment amount must be greater than zero.";
    } else {
        try {
            $db->beginTransaction();

            $payment_number = 'SPAY-' . date('Y') . '-' . str_pad($supplier_id, 4, '0', STR_PAD_LEFT) . '-' . time() % 10000;
            $user_id = $_SESSION['user_id'] ?? null;

            $in = $db->prepare("
                INSERT INTO supplier_payments (supplier_id, payment_number, payment_date, amount, payment_method, reference_number, notes, created_by)
                VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?)
            ");
            $in->execute([$supplier_id, $payment_number, $amount, $payment_method, $reference_number, $notes, $user_id]);

            // Deduct supplier balance
            $db->prepare("UPDATE suppliers SET current_balance = GREATEST(0, current_balance - ?) WHERE id = ?")->execute([$amount, $supplier_id]);

            record_audit_log($db, 'SUPPLIER_PAYMENT', 'suppliers', $supplier_id, null, ['amount' => $amount, 'voucher' => $payment_number]);
            $db->commit();

            set_flash('success', "Payment voucher {$payment_number} of " . format_currency($amount) . " recorded.");
            header("Location: view.php?id={$supplier_id}");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="view.php?id=<?= $supplier_id ?>" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Record Supplier Disbursement</h1>
                    <p class="text-xs text-slate-500">Distributor: <?= e($supp['name']) ?> • Current Payable: <strong class="text-rose-600"><?= format_currency($supp['current_balance']) ?></strong></p>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-medium">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="max-w-xl">
            <form method="POST" action="payment.php?supplier_id=<?= $supplier_id ?>" class="app-card p-6 bg-white border border-slate-200 rounded-xl shadow-xs space-y-4">
                <?= csrf_field() ?>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Disbursement Amount (₹) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" name="amount" required value="<?= e($supp['current_balance']) ?>" max="<?= e($supp['current_balance']) ?>" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm font-bold text-slate-900 focus:ring-2 focus:ring-teal-600">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Payment Mode</label>
                    <select name="payment_method" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                        <option value="Bank Transfer / NEFT">Bank Transfer / NEFT / RTGS</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Cheque # or Bank UTR Reference</label>
                    <input type="text" name="reference_number" placeholder="e.g. NEFT-HDFC-99120" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                </div>

                <div>
                    <label class="text-xs font-semibold text-slate-700 mb-1 block">Payment Remarks / Bill Settlement Notes</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Full clearance of September consignments" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <a href="view.php?id=<?= $supplier_id ?>" class="btn-secondary text-xs py-2 px-4 bg-slate-100 text-slate-700 rounded-lg">Cancel</a>
                    <button type="submit" class="btn-primary text-xs py-2 px-6 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-sm">
                        Confirm Disbursement
                    </button>
                </div>
            </form>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
