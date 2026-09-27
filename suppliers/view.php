<?php
/**
 * View Supplier Profile, Inward Bills & Payment Ledger
 */
$page_title = "Supplier Profile";
$current_page = "suppliers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$supp = $stmt->fetch();

if (!$supp) {
    set_flash('error', "Supplier not found.");
    header("Location: index.php");
    exit;
}

// Purchases
$purch_stmt = $db->prepare("SELECT * FROM purchases WHERE supplier_id = ? ORDER BY invoice_date DESC");
$purch_stmt->execute([$id]);
$purchases = $purch_stmt->fetchAll();

// Payments made
$pay_stmt = $db->prepare("SELECT * FROM supplier_payments WHERE supplier_id = ? ORDER BY payment_date DESC");
$pay_stmt->execute([$id]);
$payments = $pay_stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight"><?= e($supp['name']) ?></h1>
                        <span class="px-2 py-0.5 rounded-full font-mono text-xs bg-slate-100 font-bold"><?= e($supp['supplier_code']) ?></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Contact: <?= e($supp['contact_person']) ?> • Phone: <?= e($supp['phone']) ?></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="payment.php?supplier_id=<?= $id ?>" class="btn-primary text-xs py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span>Record Vendor Payment</span>
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg">
                    Edit Profile
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Outstanding Payable Balance</div>
                <div class="text-2xl font-extrabold mt-1 <?= (float)$supp['current_balance'] > 0 ? 'text-rose-600' : 'text-emerald-700' ?>">
                    <?= format_currency($supp['current_balance']) ?>
                </div>
                <div class="text-xs text-slate-400 mt-1">Terms: <?= e($supp['payment_terms']) ?></div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inward Consignments</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= count($purchases) ?></div>
                <div class="text-xs text-slate-400 mt-1">Vendor bills delivered</div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Statutory Details</div>
                <div class="text-xs text-slate-700 mt-1 font-mono">GSTIN: <strong><?= e($supp['gstin']) ?></strong></div>
                <div class="text-xs text-slate-500 mt-0.5 font-mono">DL No: <?= e($supp['drug_license'] ?: 'Not recorded') ?></div>
            </div>
        </div>

        <!-- Purchase Bills -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200">
                <h2 class="text-sm font-bold text-slate-900">Inward Purchase Consignments</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Purchase Ref</th>
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-right">Paid</th>
                            <th class="py-3 px-4 text-right">Due Balance</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($purchases)): ?>
                            <tr><td colspan="8" class="py-6 text-center text-slate-400">No purchases recorded from this distributor.</td></tr>
                        <?php else: ?>
                            <?php foreach ($purchases as $p): ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($p['purchase_number']) ?></td>
                                    <td class="py-3 px-4 font-semibold text-slate-800"><?= e($p['invoice_number']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= format_date($p['invoice_date']) ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= format_currency($p['grand_total']) ?></td>
                                    <td class="py-3 px-4 text-right text-emerald-700"><?= format_currency($p['paid_amount']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-rose-600"><?= format_currency($p['due_amount']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase <?= $p['status'] === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                                            <?= e($p['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="../purchases/view.php?id=<?= $p['id'] ?>" class="text-xs font-semibold text-teal-700 hover:underline">View &rarr;</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments Ledger -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200">
                <h2 class="text-sm font-bold text-slate-900">Vendor Disbursements / Payments Ledger</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Payment Voucher #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Method</th>
                            <th class="py-3 px-4 text-right">Amount Paid</th>
                            <th class="py-3 px-4">Reference / Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">No payment records on file.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($pay['payment_number']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= format_date($pay['payment_date']) ?></td>
                                    <td class="py-3 px-4"><?= e($pay['payment_method']) ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold text-teal-700"><?= format_currency($pay['amount']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= e($pay['notes'] ?: $pay['reference_number'] ?: '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
