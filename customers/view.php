<?php
/**
 * View Customer Dossier, Invoices & Payment Ledger
 */
$page_title = "Customer Profile";
$current_page = "customers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$cust = $stmt->fetch();

if (!$cust) {
    set_flash('error', "Customer not found.");
    header("Location: index.php");
    exit;
}

// Invoices
$sales_stmt = $db->prepare("SELECT * FROM sales WHERE customer_id = ? ORDER BY created_at DESC");
$sales_stmt->execute([$id]);
$invoices = $sales_stmt->fetchAll();

// Payments
$pay_stmt = $db->prepare("SELECT * FROM customer_payments WHERE customer_id = ? ORDER BY payment_date DESC");
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
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight"><?= e($cust['name']) ?></h1>
                        <span class="px-2 py-0.5 rounded-full font-mono text-xs bg-slate-100 font-bold"><?= e($cust['customer_code']) ?></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5"><?= e($cust['customer_type']) ?> • Mobile: <?= e($cust['mobile'] ?: 'None') ?></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="payment.php?customer_id=<?= $id ?>" class="btn-primary text-xs py-2 px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span>Record Payment Collection</span>
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg">
                    Edit Profile
                </a>
            </div>
        </div>

        <!-- Ledger KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Outstanding Dues</div>
                <div class="text-2xl font-extrabold mt-1 <?= (float)$cust['current_balance'] > 0 ? 'text-rose-600' : 'text-emerald-700' ?>">
                    <?= format_currency($cust['current_balance']) ?>
                </div>
                <div class="text-xs text-slate-400 mt-1">Credit Limit: <?= format_currency($cust['credit_limit']) ?></div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sales Invoices</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= count($invoices) ?></div>
                <div class="text-xs text-slate-400 mt-1">Dispensed counter bills</div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Demographic Profile</div>
                <div class="text-xs text-slate-700 mt-1"><?= e($cust['address'] ?: 'No address specified') ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= e($cust['city']) ?>, <?= e($cust['state']) ?> - <?= e($cust['pincode']) ?></div>
            </div>
        </div>

        <!-- Invoices History -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200">
                <h2 class="text-sm font-bold text-slate-900">Purchase Invoices History</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Payment Method</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-right">Paid</th>
                            <th class="py-3 px-4 text-right">Due</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="8" class="py-6 text-center text-slate-400">No invoices recorded for this customer.</td></tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($inv['invoice_number']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= format_datetime($inv['created_at']) ?></td>
                                    <td class="py-3 px-4"><?= e($inv['payment_method']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= format_currency($inv['grand_total']) ?></td>
                                    <td class="py-3 px-4 text-right text-emerald-700"><?= format_currency($inv['paid_amount']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-rose-600"><?= format_currency($inv['due_amount']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase <?= $inv['payment_status'] === 'Paid' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' ?>">
                                            <?= e($inv['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="../billing/invoice.php?id=<?= $inv['id'] ?>" class="text-xs font-semibold text-teal-700 hover:underline">View &rarr;</a>
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
                <h2 class="text-sm font-bold text-slate-900">Payment Collection Ledger</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Receipt #</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Mode</th>
                            <th class="py-3 px-4 text-right">Amount Received</th>
                            <th class="py-3 px-4">Reference / Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="5" class="py-6 text-center text-slate-400">No payment receipts on file.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($p['receipt_number']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= format_date($p['payment_date']) ?></td>
                                    <td class="py-3 px-4 font-medium"><?= e($p['payment_method']) ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold text-emerald-700"><?= format_currency($p['amount']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= e($p['notes'] ?: $p['reference_number'] ?: '-') ?></td>
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
