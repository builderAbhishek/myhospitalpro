<?php
/**
 * View Purchase Consignment Bill
 */
$page_title = "Purchase Details";
$current_page = "purchases";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT p.*, s.name as supplier_name, s.company_name, s.phone as supplier_phone, 
           s.email as supplier_email, s.address as supplier_address, s.gstin as supplier_gstin,
           u.name as staff_name
    FROM purchases p
    JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$purch = $stmt->fetch();

if (!$purch) {
    set_flash('error', "Purchase consignment not found.");
    header("Location: index.php");
    exit;
}

// Fetch Items
$items_stmt = $db->prepare("
    SELECT pi.*, m.name as medicine_name, m.unit
    FROM purchase_items pi
    JOIN medicines m ON pi.medicine_id = m.id
    WHERE pi.purchase_id = ?
");
$items_stmt->execute([$id]);
$items = $items_stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Purchase: <?= e($purch['purchase_number']) ?></h1>
                        <span class="px-2 py-0.5 rounded-full font-bold text-xs uppercase <?= $purch['status'] === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' ?>">
                            <?= e($purch['status']) ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Supplier Bill # <?= e($purch['invoice_number']) ?> • Received on <?= format_date($purch['invoice_date']) ?></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                    <span>Print Bill</span>
                </button>
            </div>
        </div>

        <!-- Supplier & Bill Dossier -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Supplier Info</div>
                <div class="font-bold text-slate-900 text-sm"><?= e($purch['supplier_name']) ?></div>
                <div class="text-xs text-slate-600 mt-1"><?= e($purch['supplier_address']) ?></div>
                <div class="text-xs text-slate-500 mt-1">Phone: <?= e($purch['supplier_phone']) ?></div>
                <div class="text-xs font-mono text-slate-500 mt-1">GSTIN: <?= e($purch['supplier_gstin']) ?></div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Payment Status</div>
                <div class="text-xl font-extrabold text-slate-900"><?= format_currency($purch['grand_total']) ?></div>
                <div class="text-xs text-emerald-700 mt-1">Paid: <?= format_currency($purch['paid_amount']) ?> (<?= e($purch['payment_type']) ?>)</div>
                <div class="text-xs font-bold mt-1 <?= (float)$purch['due_amount'] > 0 ? 'text-rose-600' : 'text-slate-500' ?>">
                    Due: <?= format_currency($purch['due_amount']) ?>
                </div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Audit Info</div>
                <div class="text-xs text-slate-700">Received By: <strong><?= e($purch['staff_name'] ?? 'Admin') ?></strong></div>
                <div class="text-xs text-slate-500 mt-1">System Timestamp: <?= format_datetime($purch['created_at']) ?></div>
                <?php if (!empty($purch['notes'])): ?>
                    <div class="text-xs text-slate-600 mt-2 bg-slate-50 p-2 rounded border border-slate-100 italic">"<?= e($purch['notes']) ?>"</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Inward Items Table -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Inward Consignment Batch Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Medicine Item</th>
                            <th class="py-3 px-4">Batch Number</th>
                            <th class="py-3 px-4">Expiry Date</th>
                            <th class="py-3 px-4 text-right">Inward Qty</th>
                            <th class="py-3 px-4 text-right">Free Qty</th>
                            <th class="py-3 px-4 text-right">Purchase Rate</th>
                            <th class="py-3 px-4 text-right">MRP</th>
                            <th class="py-3 px-4 text-right">GST %</th>
                            <th class="py-3 px-4 text-right">Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach ($items as $item): ?>
                            <tr class="hover:bg-slate-50/70">
                                <td class="py-3 px-4 font-bold text-slate-900"><?= e($item['medicine_name']) ?></td>
                                <td class="py-3 px-4 font-mono font-semibold text-slate-800"><?= e($item['batch_number']) ?></td>
                                <td class="py-3 px-4 text-slate-700"><?= format_date($item['expiry_date']) ?></td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900"><?= (int)$item['quantity'] ?></td>
                                <td class="py-3 px-4 text-right text-slate-500"><?= (int)$item['free_quantity'] ?></td>
                                <td class="py-3 px-4 text-right"><?= format_currency($item['purchase_price']) ?></td>
                                <td class="py-3 px-4 text-right text-slate-500"><?= format_currency($item['mrp']) ?></td>
                                <td class="py-3 px-4 text-right"><?= (float)$item['gst_rate'] ?>%</td>
                                <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= format_currency($item['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <div class="w-64 space-y-1 text-xs text-right">
                    <div class="flex justify-between text-slate-600">
                        <span>Taxable Subtotal:</span>
                        <span class="font-bold text-slate-900"><?= format_currency($purch['subtotal']) ?></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Total GST Amount:</span>
                        <span class="font-bold text-slate-900"><?= format_currency($purch['gst_amount']) ?></span>
                    </div>
                    <div class="flex justify-between text-sm font-extrabold text-teal-800 pt-2 border-t border-slate-200">
                        <span>Grand Total:</span>
                        <span><?= format_currency($purch['grand_total']) ?></span>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
