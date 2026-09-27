<?php
/**
 * Detailed Medicine Dossier View
 * Displays Formulary Master, Active Batches (FEFO), and Stock Movements Ledger
 */
$page_title = "Medicine Details";
$current_page = "medicines";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT m.*, c.name as category_name, man.name as manufacturer_name
    FROM medicines m
    LEFT JOIN medicine_categories c ON m.category_id = c.id
    LEFT JOIN manufacturers man ON m.manufacturer_id = man.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$med = $stmt->fetch();

if (!$med) {
    set_flash('error', "Medicine not found.");
    header("Location: index.php");
    exit;
}

// Fetch all batches sorted by FEFO (earliest expiry first)
$batch_stmt = $db->prepare("SELECT * FROM medicine_batches WHERE medicine_id = ? ORDER BY expiry_date ASC");
$batch_stmt->execute([$id]);
$batches = $batch_stmt->fetchAll();
$total_stock = array_sum(array_column($batches, 'quantity'));

// Fetch stock movements ledger
$movements_stmt = $db->prepare("
    SELECT sm.*, b.batch_number, u.name as staff_name 
    FROM stock_movements sm 
    JOIN medicine_batches b ON sm.batch_id = b.id 
    LEFT JOIN users u ON sm.created_by = u.id 
    WHERE sm.medicine_id = ? 
    ORDER BY sm.created_at DESC 
    LIMIT 30
");
$movements_stmt->execute([$id]);
$movements = $movements_stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header Strip -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="index.php" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight"><?= e($med['name']) ?></h1>
                        <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700"><?= e($med['medicine_code']) ?></span>
                        <?php if ($med['prescription_required']): ?>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-50 text-rose-700 border border-rose-200">Rx Required</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5"><?= e($med['generic_name']) ?> • <?= e($med['category_name']) ?> • <?= e($med['manufacturer_name'] ?? 'Generic') ?></p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="../inventory/stock-adjustment.php?medicine_id=<?= $id ?>" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg">
                    Adjust Stock
                </a>
                <a href="edit.php?id=<?= $id ?>" class="btn-primary text-xs py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                    <span>Edit Medicine</span>
                </a>
            </div>
        </div>

        <!-- 4 Key Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total On-Hand Stock</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= $total_stock ?> <span class="text-sm font-normal text-slate-500"><?= e($med['unit']) ?></span></div>
                <div class="text-xs text-slate-400 mt-1">Reorder threshold: <?= (int)$med['reorder_level'] ?> <?= e($med['unit']) ?></div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Selling Price &amp; MRP</div>
                <div class="text-2xl font-extrabold text-teal-700 mt-1"><?= format_currency($med['selling_price']) ?></div>
                <div class="text-xs text-slate-400 mt-1">MRP: <?= format_currency($med['mrp']) ?> (GST <?= (float)$med['gst_rate'] ?>%)</div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Purchase Cost Rate</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= format_currency($med['purchase_price']) ?></div>
                <div class="text-xs text-slate-400 mt-1">Est. Gross Margin: <?= ($med['selling_price'] > 0) ? round((($med['selling_price'] - $med['purchase_price']) / $med['selling_price']) * 100, 1) : 0 ?>%</div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Store Location</div>
                <div class="text-xl font-bold text-slate-900 mt-1"><?= e($med['rack_number'] ?: 'Unassigned') ?></div>
                <div class="text-xs text-slate-400 mt-1">Barcode: <?= e($med['barcode'] ?: 'None') ?></div>
            </div>
        </div>

        <!-- Active Batches (FEFO) Table -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 md:px-6 border-b border-slate-200 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Active Inventory Batches (FEFO Sorted)</h2>
                    <p class="text-xs text-slate-500">First-Expiry-First-Out dispensing queue</p>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700"><?= count($batches) ?> Batches</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Batch Number</th>
                            <th class="py-3 px-4">Expiry Date</th>
                            <th class="py-3 px-4 text-right">Available Qty</th>
                            <th class="py-3 px-4 text-right">Purchase Price</th>
                            <th class="py-3 px-4 text-right">MRP</th>
                            <th class="py-3 px-4 text-right">Selling Price</th>
                            <th class="py-3 px-4 text-center">Batch Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">No batch records found for this medicine. Create a purchase to inward stock.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): ?>
                                <?php
                                    $exp_time = strtotime($b['expiry_date']);
                                    $days_left = floor(($exp_time - time()) / 86400);
                                    $exp_badge = '';
                                    if ($days_left < 0) {
                                        $exp_badge = '<span class="ml-2 px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 text-[10px] font-bold">Expired</span>';
                                    } elseif ($days_left <= 30) {
                                        $exp_badge = '<span class="ml-2 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">' . $days_left . 'd left</span>';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900"><?= e($b['batch_number']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium text-slate-700"><?= format_date($b['expiry_date']) ?></span>
                                        <?= $exp_badge ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= (int)$b['quantity'] ?></td>
                                    <td class="py-3 px-4 text-right text-slate-600"><?= format_currency($b['purchase_price']) ?></td>
                                    <td class="py-3 px-4 text-right text-slate-600"><?= format_currency($b['mrp']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-teal-700"><?= format_currency($b['selling_price']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase <?= $b['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= e($b['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Stock Movements Ledger -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 md:px-6 border-b border-slate-200">
                <h2 class="text-sm font-bold text-slate-900 tracking-tight">Stock Movement Audit Trail</h2>
                <p class="text-xs text-slate-500">Historical stock additions, sales deductions, and adjustments</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Movement Type</th>
                            <th class="py-3 px-4">Batch</th>
                            <th class="py-3 px-4 text-right">Quantity</th>
                            <th class="py-3 px-4">Reference</th>
                            <th class="py-3 px-4">Notes</th>
                            <th class="py-3 px-4">Staff</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($movements)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">No stock movements recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($movements as $m): ?>
                                <?php
                                    $qty = (int)$m['quantity'];
                                    $type_color = 'bg-slate-100 text-slate-700';
                                    if ($m['movement_type'] === 'PURCHASE') $type_color = 'bg-blue-50 text-blue-700 border border-blue-200';
                                    if ($m['movement_type'] === 'SALE') $type_color = 'bg-teal-50 text-teal-700 border border-teal-200';
                                    if ($m['movement_type'] === 'DAMAGED' || $m['movement_type'] === 'EXPIRED') $type_color = 'bg-rose-50 text-rose-700 border border-rose-200';
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4 text-slate-500"><?= format_datetime($m['created_at']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase <?= $type_color ?>">
                                            <?= e($m['movement_type']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-mono font-semibold text-slate-800"><?= e($m['batch_number']) ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold <?= $qty > 0 ? 'text-emerald-700' : 'text-rose-700' ?>">
                                        <?= $qty > 0 ? '+' . $qty : $qty ?>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600"><?= e($m['reference_number'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= e($m['notes'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-slate-500"><?= e($m['staff_name'] ?? 'System') ?></td>
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
