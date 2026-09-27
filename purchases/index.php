<?php
/**
 * Inward Purchases Consignments Directory
 */
$page_title = "Purchases";
$current_page = "purchases";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.invoice_number LIKE ? OR p.purchase_number LIKE ? OR s.name LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like]);
}

if (!empty($status)) {
    $where[] = "p.payment_status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

$query = "
    SELECT p.*, s.name as supplier_name, s.phone as supplier_phone,
           COUNT(pi.id) as item_count
    FROM purchases p
    JOIN suppliers s ON p.supplier_id = s.id
    LEFT JOIN purchase_items pi ON p.id = pi.purchase_id
    WHERE {$where_sql}
    GROUP BY p.id
    ORDER BY p.invoice_date DESC, p.id DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$purchases = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Purchase Consignments</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Manage inward distributor bills, inventory batch receipts and vendor payables.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="create.php" class="btn-primary text-xs py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    <span>New Inward Purchase</span>
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
            <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-6 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by Invoice #, Purchase Ref, or Supplier Name..." 
                           class="w-full border border-slate-300 rounded-lg pl-9 pr-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                </div>

                <div class="sm:col-span-3">
                    <select name="status" onchange="this.form.submit()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                        <option value="">All Payment Statuses</option>
                        <option value="Paid" <?= ($status === 'Paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="Pending" <?= ($status === 'Pending') ? 'selected' : '' ?>>Pending / Unpaid</option>
                        <option value="Partial" <?= ($status === 'Partial') ? 'selected' : '' ?>>Partial</option>
                    </select>
                </div>

                <div class="sm:col-span-3 flex items-center gap-2">
                    <button type="submit" class="btn-primary w-full text-xs py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg">Filter</button>
                    <?php if (!empty($search) || !empty($status)): ?>
                        <a href="index.php" class="btn-secondary text-xs py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table Card -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Purchase Ref</th>
                            <th class="py-3 px-4">Supplier Invoice #</th>
                            <th class="py-3 px-4">Distributor / Supplier</th>
                            <th class="py-3 px-4">Invoice Date</th>
                            <th class="py-3 px-4 text-center">Items</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-right">Paid</th>
                            <th class="py-3 px-4 text-right">Due Balance</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($purchases)): ?>
                            <tr>
                                <td colspan="10" class="py-12 text-center text-slate-400">
                                    <p class="font-medium text-slate-600">No purchase bills found</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Click "New Inward Purchase" to record supplier consignments.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($purchases as $p): ?>
                                <?php
                                    $is_cancelled = ($p['status'] === 'cancelled');
                                    $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($p['payment_status'] === 'Paid') $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($is_cancelled) $badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors <?= $is_cancelled ? 'opacity-60 bg-slate-50/40' : '' ?>">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        <a href="view.php?id=<?= $p['id'] ?>" class="text-teal-700 hover:underline"><?= e($p['purchase_number']) ?></a>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-slate-800"><?= e($p['invoice_number']) ?></td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-slate-800"><?= e($p['supplier_name']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= e($p['supplier_phone']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600"><?= format_date($p['invoice_date']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 font-semibold text-[11px] text-slate-700"><?= (int)$p['item_count'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= format_currency($p['grand_total']) ?></td>
                                    <td class="py-3 px-4 text-right text-emerald-700 font-bold"><?= format_currency($p['paid_amount']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold <?= (float)$p['due_amount'] > 0 ? 'text-rose-600' : 'text-slate-400' ?>">
                                        <?= format_currency($p['due_amount']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase border <?= $badge ?>">
                                            <?= $is_cancelled ? 'Cancelled' : e($p['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="view.php?id=<?= $p['id'] ?>" title="View Bill" class="text-slate-600 hover:text-teal-700 font-semibold p-1 inline-flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                                <span>View</span>
                                            </a>
                                            <?php if (!$is_cancelled): ?>
                                                <a href="cancel.php?id=<?= $p['id'] ?>&token=<?= csrf_token() ?>" onclick="return confirm('Cancel this purchase consignment? Stock will be safely reversed if not dispensed.');" title="Cancel Purchase" class="text-slate-400 hover:text-rose-600 p-1">
                                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
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
