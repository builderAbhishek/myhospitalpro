<?php
/**
 * Master Inventory Valuation & Stock Summary
 */
$page_title = "Inventory Management";
$current_page = "inventory";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

// Metrics
$val_row = $db->query("
    SELECT COALESCE(SUM(quantity * purchase_price), 0) as cost_val,
           COALESCE(SUM(quantity * selling_price), 0) as retail_val,
           COALESCE(SUM(quantity), 0) as total_units,
           COUNT(*) as active_batches
    FROM medicine_batches 
    WHERE status = 'active'
")->fetch();

$total_cost = (float)$val_row['cost_val'];
$total_retail = (float)$val_row['retail_val'];
$total_units = (int)$val_row['total_units'];
$active_batches = (int)$val_row['active_batches'];

// Low stock count
$low_stock_count = $db->query("
    SELECT m.id
    FROM medicines m
    LEFT JOIN medicine_batches b ON m.id = b.medicine_id AND b.status = 'active'
    WHERE m.status = 'active'
    GROUP BY m.id, m.reorder_level
    HAVING COALESCE(SUM(b.quantity), 0) <= m.reorder_level
")->rowCount();

// Expiring in 30 days
$expiring_count = (int)$db->query("
    SELECT COUNT(*) FROM medicine_batches 
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
      AND quantity > 0 AND status = 'active'
")->fetchColumn();

// Expired count
$expired_count = (int)$db->query("
    SELECT COUNT(*) FROM medicine_batches 
    WHERE expiry_date < CURDATE() AND quantity > 0
")->fetchColumn();

// Search
$search = trim($_GET['search'] ?? '');
$where = ["m.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where[] = "(m.name LIKE ? OR m.generic_name LIKE ? OR m.rack_number LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}

$where_sql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT m.*, c.name as category_name,
           COALESCE(SUM(b.quantity), 0) as on_hand_stock,
           COALESCE(SUM(b.quantity * b.purchase_price), 0) as stock_cost_value,
           COALESCE(SUM(b.quantity * b.selling_price), 0) as stock_retail_value,
           COUNT(DISTINCT b.id) as batch_count
    FROM medicines m
    LEFT JOIN medicine_categories c ON m.category_id = c.id
    LEFT JOIN medicine_batches b ON m.id = b.medicine_id AND b.status = 'active'
    WHERE {$where_sql}
    GROUP BY m.id
    ORDER BY on_hand_stock ASC, m.name ASC
");
$stmt->execute($params);
$inventory = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header Strip -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Inventory Management</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Real-time batch tracking, stock valuation, and reorder levels.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="batches.php" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg flex items-center gap-1.5 shadow-xs">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.97 12.92A2 2 0 0 0 2 14.63v3.24a2 2 0 0 0 .97 1.71l6 3.43a2 2 0 0 0 1.97 0l6-3.43a2 2 0 0 0 .97-1.71v-3.24a2 2 0 0 0-.97-1.71L12 9.5a2 2 0 0 0-1.97 0Z"/></svg>
                    <span>All Batches (FEFO)</span>
                </a>
                <a href="low-stock.php" class="btn-secondary text-xs py-2 px-3 bg-amber-50 border border-amber-300 text-amber-800 hover:bg-amber-100 rounded-lg flex items-center gap-1.5 shadow-xs">
                    <span>Low Stock (<?= $low_stock_count ?>)</span>
                </a>
                <a href="expiry.php" class="btn-secondary text-xs py-2 px-3 bg-rose-50 border border-rose-300 text-rose-800 hover:bg-rose-100 rounded-lg flex items-center gap-1.5 shadow-xs">
                    <span>Expiry Tracking (<?= $expiring_count + $expired_count ?>)</span>
                </a>
                <a href="stock-adjustment.php" class="btn-primary text-xs py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    <span>Stock Adjustment</span>
                </a>
            </div>
        </div>

        <!-- 4 Valuation & Status KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Stock Cost Value</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1"><?= format_currency($total_cost) ?></div>
                <div class="text-xs text-slate-400 mt-1"><?= $total_units ?> total on-hand units in store</div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Estimated Retail Worth</div>
                <div class="text-2xl font-extrabold text-teal-700 mt-1"><?= format_currency($total_retail) ?></div>
                <div class="text-xs text-emerald-700 font-semibold mt-1">
                    Potential Margin: <?= ($total_retail > 0) ? format_currency($total_retail - $total_cost) : '₹0.00' ?>
                </div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Batches</div>
                <div class="text-2xl font-extrabold text-indigo-700 mt-1"><?= $active_batches ?></div>
                <div class="text-xs text-slate-400 mt-1">Monitored under FEFO queue</div>
            </div>

            <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Critical Alerts</div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-extrabold text-amber-600"><?= $low_stock_count ?></span>
                    <span class="text-xs text-slate-500">Low Stock</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-2xl font-extrabold text-rose-600"><?= $expired_count ?></span>
                    <span class="text-xs text-slate-500">Expired</span>
                </div>
                <div class="text-xs text-slate-400 mt-1">Requires immediate replenishment</div>
            </div>
        </div>

        <!-- Inventory Master Stock Table -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs space-y-3">
            <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Formulary Stock Balance</h2>
                    <p class="text-xs text-slate-500">Derived in real-time from active batch allocations</p>
                </div>
                <form method="GET" action="index.php" class="flex items-center gap-2">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search medicine or rack..." 
                           class="border border-slate-300 rounded-lg px-3 py-1.5 text-xs focus:ring-2 focus:ring-teal-600">
                    <button type="submit" class="btn-primary text-xs py-1.5 px-3 bg-slate-800 text-white rounded-lg">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="index.php" class="btn-secondary text-xs py-1.5 px-2.5 bg-slate-100 text-slate-600 rounded-lg">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Medicine Item</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Shelf / Rack</th>
                            <th class="py-3 px-4 text-center">Active Batches</th>
                            <th class="py-3 px-4 text-right">On-Hand Stock</th>
                            <th class="py-3 px-4 text-right">Reorder Level</th>
                            <th class="py-3 px-4 text-right">Stock Valuation (Cost)</th>
                            <th class="py-3 px-4 text-center">Stock Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($inventory)): ?>
                            <tr>
                                <td colspan="9" class="py-8 text-center text-slate-400">No inventory matches found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventory as $row): ?>
                                <?php
                                    $qty = (int)$row['on_hand_stock'];
                                    $reorder = (int)$row['reorder_level'];
                                    $badge = '<span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">In Stock</span>';
                                    if ($qty === 0) {
                                        $badge = '<span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-50 text-rose-700 border border-rose-200">Out of Stock</span>';
                                    } elseif ($qty <= $reorder) {
                                        $badge = '<span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-50 text-amber-700 border border-amber-200">Low Stock</span>';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4">
                                        <a href="../medicines/view.php?id=<?= $row['id'] ?>" class="font-bold text-slate-900 hover:text-teal-700"><?= e($row['name']) ?></a>
                                        <div class="text-[11px] text-slate-400 font-mono"><?= e($row['medicine_code']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600"><?= e($row['category_name']) ?></td>
                                    <td class="py-3 px-4 font-mono font-medium text-slate-800"><?= e($row['rack_number'] ?: 'Unassigned') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 font-semibold text-[11px] text-slate-700"><?= (int)$row['batch_count'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= $qty ?> <span class="font-normal text-[11px] text-slate-500"><?= e($row['unit']) ?></span></td>
                                    <td class="py-3 px-4 text-right text-slate-500"><?= $reorder ?> <?= e($row['unit']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= format_currency($row['stock_cost_value']) ?></td>
                                    <td class="py-3 px-4 text-center"><?= $badge ?></td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="stock-adjustment.php?medicine_id=<?= $row['id'] ?>" class="text-xs font-semibold text-teal-700 hover:underline">Adjust</a>
                                            <span class="text-slate-300">•</span>
                                            <a href="../medicines/view.php?id=<?= $row['id'] ?>" class="text-xs font-semibold text-slate-600 hover:underline">Batches</a>
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
