<?php
require_once __DIR__ . '/../includes/app.php';
require_login();
has_permission('view_reports');

$page_title = 'Inventory Valuation & Health Report';
$active_menu = 'reports';

$db = db();

$category_id = (int)($_GET['category_id'] ?? 0);
$stock_status = sanitize($_GET['stock_status'] ?? '');
$export = sanitize($_GET['export'] ?? '');

$where_clauses = ["b.current_stock > 0", "b.status = 'active'"];
$params = [];

if ($category_id > 0) {
    $where_clauses[] = "m.category_id = :category_id";
    $params['category_id'] = $category_id;
}

if ($stock_status === 'low_stock') {
    $where_clauses[] = "b.current_stock <= m.min_stock_level";
} elseif ($stock_status === 'expiring_soon') {
    $where_clauses[] = "b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
} elseif ($stock_status === 'expired') {
    $where_clauses[] = "b.expiry_date < CURDATE()";
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT 
        b.id AS batch_id,
        b.batch_number,
        b.expiry_date,
        b.purchase_price,
        b.selling_price,
        b.mrp,
        b.current_stock,
        m.id AS medicine_id,
        m.name AS medicine_name,
        m.item_code,
        m.dosage_form,
        m.strength,
        m.min_stock_level,
        c.name AS category_name,
        (b.current_stock * b.purchase_price) AS cost_value,
        (b.current_stock * b.mrp) AS retail_value,
        ((b.current_stock * b.mrp) - (b.current_stock * b.purchase_price)) AS potential_profit
    FROM medicine_batches b
    JOIN medicines m ON b.medicine_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE {$where_sql}
    ORDER BY m.name ASC, b.expiry_date ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$batches = $stmt->fetchAll();

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=MHP_Inventory_Valuation_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Code', 'Medicine Name', 'Category', 'Batch #', 'Expiry Date', 'Current Qty', 'Unit Cost', 'Unit MRP', 'Total Cost Valuation', 'Total Retail Valuation', 'Projected Profit']);
    foreach ($batches as $row) {
        fputcsv($out, [
            $row['item_code'],
            $row['medicine_name'] . ' ' . $row['strength'],
            $row['category_name'],
            $row['batch_number'],
            $row['expiry_date'],
            $row['current_stock'],
            $row['purchase_price'],
            $row['mrp'],
            $row['cost_value'],
            $row['retail_value'],
            $row['potential_profit']
        ]);
    }
    fclose($out);
    exit;
}

$total_units = 0;
$total_cost = 0;
$total_retail = 0;
$total_potential_profit = 0;

foreach ($batches as $b) {
    $total_units += (int)$b['current_stock'];
    $total_cost += (float)$b['cost_value'];
    $total_retail += (float)$b['retail_value'];
    $total_potential_profit += (float)$b['potential_profit'];
}

$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="p-6 space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="index.php" class="text-xs text-gray-500 hover:text-gray-700">&larr; Reports Hub</a>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-semibold text-primary-600">Stock Valuation</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Inventory Valuation & Health</h1>
            <p class="text-sm text-gray-500">Live batch-level asset valuation, shelf life tracking, and inventory capital</p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-xl hover:bg-emerald-700 transition flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-xs font-semibold rounded-xl hover:bg-gray-50 transition flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Report
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="inventory.php" class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
            <select name="category_id" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_id === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Health Filter</label>
            <select name="stock_status" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="">All Batches with Stock</option>
                <option value="low_stock" <?= $stock_status === 'low_stock' ? 'selected' : '' ?>>Low Stock Alert</option>
                <option value="expiring_soon" <?= $stock_status === 'expiring_soon' ? 'selected' : '' ?>>Expiring Soon (Within 90 Days)</option>
                <option value="expired" <?= $stock_status === 'expired' ? 'selected' : '' ?>>Expired Stock</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition">Filter</button>
            <a href="inventory.php" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">Reset</a>
        </div>
    </form>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Total Active Units</span>
            <span class="text-xl font-black text-gray-900"><?= number_format($total_units) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Capital Invested (Cost)</span>
            <span class="text-xl font-black text-blue-600"><?= format_currency($total_cost) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Retail Value (MRP)</span>
            <span class="text-xl font-black text-purple-600"><?= format_currency($total_retail) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Projected Profit</span>
            <span class="text-xl font-black text-emerald-600"><?= format_currency($total_potential_profit) ?></span>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Item Code</th>
                        <th class="py-3 px-4">Medicine Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 font-mono">Batch #</th>
                        <th class="py-3 px-4">Expiry</th>
                        <th class="py-3 px-4 text-center">Stock</th>
                        <th class="py-3 px-4 text-right">Cost Price</th>
                        <th class="py-3 px-4 text-right">MRP</th>
                        <th class="py-3 px-4 text-right">Cost Val</th>
                        <th class="py-3 px-4 text-right">Retail Val</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($batches)): ?>
                        <tr><td colspan="10" class="py-12 text-center text-gray-400">No stock batches matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($batches as $row): 
                            $is_exp = strtotime($row['expiry_date']) < time();
                            $days_exp = ceil((strtotime($row['expiry_date']) - time()) / 86400);
                        ?>
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="py-3 px-4 font-mono text-gray-500"><?= e($row['item_code']) ?></td>
                                <td class="py-3 px-4 font-medium text-gray-900">
                                    <?= e($row['medicine_name']) ?> <span class="text-gray-400"><?= e($row['strength']) ?></span>
                                </td>
                                <td class="py-3 px-4 text-gray-600"><?= e($row['category_name']) ?></td>
                                <td class="py-3 px-4 font-mono font-semibold text-gray-900"><?= e($row['batch_number']) ?></td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <?php if ($is_exp): ?>
                                        <span class="text-red-600 font-bold"><?= date('d M Y', strtotime($row['expiry_date'])) ?> (Expired)</span>
                                    <?php elseif ($days_exp <= 90): ?>
                                        <span class="text-amber-600 font-semibold"><?= date('d M Y', strtotime($row['expiry_date'])) ?> (<?= $days_exp ?>d)</span>
                                    <?php else: ?>
                                        <span class="text-gray-600"><?= date('d M Y', strtotime($row['expiry_date'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center font-bold <?= $row['current_stock'] <= $row['min_stock_level'] ? 'text-red-600' : 'text-gray-900' ?>">
                                    <?= $row['current_stock'] ?>
                                </td>
                                <td class="py-3 px-4 text-right text-gray-600"><?= format_currency($row['purchase_price']) ?></td>
                                <td class="py-3 px-4 text-right text-gray-600"><?= format_currency($row['mrp']) ?></td>
                                <td class="py-3 px-4 text-right font-semibold text-gray-900"><?= format_currency($row['cost_value']) ?></td>
                                <td class="py-3 px-4 text-right font-bold text-primary-600"><?= format_currency($row['retail_value']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
