<?php
/**
 * Medicines Formulary Directory
 */
$page_title = "Medicine Formulary";
$current_page = "medicines";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

$search = trim($_GET['search'] ?? '');
$category_id = trim($_GET['category'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// Fetch Categories for dropdown
$categories = $db->query("SELECT * FROM medicine_categories WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Build query
$where = ["m.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where[] = "(m.name LIKE ? OR m.generic_name LIKE ? OR m.brand_name LIKE ? OR m.barcode LIKE ? OR m.medicine_code LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

if (!empty($category_id)) {
    $where[] = "m.category_id = ?";
    $params[] = $category_id;
}

$where_sql = implode(' AND ', $where);

// Fetch medicines with aggregated batch stock
$query = "
    SELECT m.*, 
           c.name as category_name, 
           man.name as manufacturer_name,
           COALESCE(SUM(b.quantity), 0) as total_stock,
           COUNT(DISTINCT b.id) as batch_count
    FROM medicines m
    LEFT JOIN medicine_categories c ON m.category_id = c.id
    LEFT JOIN manufacturers man ON m.manufacturer_id = man.id
    LEFT JOIN medicine_batches b ON m.id = b.medicine_id AND b.status = 'active'
    WHERE {$where_sql}
    GROUP BY m.id
";

if ($status_filter === 'low') {
    $query .= " HAVING total_stock > 0 AND total_stock <= m.reorder_level ";
} elseif ($status_filter === 'out') {
    $query .= " HAVING total_stock = 0 ";
}

$query .= " ORDER BY m.name ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$medicines = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Header & Action Button -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Medicine Formulary</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Manage hospital pharmacy formulary catalog, dosages, prices and reorder parameters.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="add.php" class="btn-primary text-xs py-2 px-3.5 shadow-sm bg-teal-600 hover:bg-teal-700 text-white rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    <span>Add New Medicine</span>
                </a>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs space-y-3">
            <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                <div class="lg:col-span-5 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" 
                           placeholder="Search by brand name, generic salt, barcode..." 
                           class="w-full border border-slate-300 rounded-lg pl-9 pr-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                </div>

                <div class="lg:col-span-3">
                    <select name="category" onchange="this.form.submit()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <select name="status" onchange="this.form.submit()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-teal-600">
                        <option value="">All Stock Status</option>
                        <option value="low" <?= ($status_filter === 'low') ? 'selected' : '' ?>>Low Stock</option>
                        <option value="out" <?= ($status_filter === 'out') ? 'selected' : '' ?>>Out of Stock</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="btn-primary w-full text-xs py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg">Filter</button>
                    <?php if (!empty($search) || !empty($category_id) || !empty($status_filter)): ?>
                        <a href="index.php" class="btn-secondary text-xs py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-100">
                <span>Showing <strong><?= count($medicines) ?></strong> medicines</span>
                <span class="text-teal-700 font-semibold">Live Database Search</span>
            </div>
        </div>

        <!-- Table Card -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Medicine &amp; Brand</th>
                            <th class="py-3 px-4">Generic / Salt</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Manufacturer</th>
                            <th class="py-3 px-4 text-right">Current Stock</th>
                            <th class="py-3 px-4 text-right">MRP</th>
                            <th class="py-3 px-4 text-right">Selling Price</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($medicines)): ?>
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-10 h-10 text-slate-300 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                        <p class="font-medium text-slate-600">No medicines found</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Try adjusting your search criteria or add a new medicine.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($medicines as $med): ?>
                                <?php 
                                    $stock = (int)$med['total_stock'];
                                    $reorder = (int)$med['reorder_level'];
                                    $status_badge = '<span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200">In Stock</span>';
                                    if ($stock === 0) {
                                        $status_badge = '<span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-rose-50 text-rose-700 border border-rose-200">Out of Stock</span>';
                                    } elseif ($stock <= $reorder) {
                                        $status_badge = '<span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-amber-50 text-amber-700 border border-amber-200">Low Stock</span>';
                                    }
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900">
                                            <a href="view.php?id=<?= $med['id'] ?>" class="text-teal-700 hover:underline"><?= e($med['name']) ?></a>
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                                            <span><?= e($med['brand_name'] ?: 'Generic') ?></span>
                                            <span>•</span>
                                            <span class="font-mono text-slate-400"><?= e($med['medicine_code']) ?></span>
                                            <?php if ($med['prescription_required']): ?>
                                                <span class="px-1.5 py-0.2 rounded bg-rose-50 text-rose-600 font-bold text-[9px] border border-rose-200">Rx</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600">
                                        <div><?= e($med['generic_name'] ?: '-') ?></div>
                                        <div class="text-[11px] text-slate-400"><?= e($med['strength'] ?: '') ?> <?= e($med['dosage_form'] ?: '') ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-700">
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium text-[11px]"><?= e($med['category_name'] ?: 'Tablet') ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600 truncate max-w-[140px]" title="<?= e($med['manufacturer_name'] ?? '') ?>">
                                        <?= e($med['manufacturer_name'] ?: '-') ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="font-extrabold <?= ($stock <= $reorder) ? 'text-amber-600' : 'text-slate-900' ?>">
                                            <?= $stock ?> <span class="text-[11px] font-normal text-slate-500"><?= e($med['unit'] ?: 'Units') ?></span>
                                        </div>
                                        <div class="text-[10px] text-slate-400"><?= (int)$med['batch_count'] ?> active batches</div>
                                    </td>
                                    <td class="py-3 px-4 text-right text-slate-500"><?= format_currency($med['mrp']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= format_currency($med['selling_price']) ?></td>
                                    <td class="py-3 px-4 text-center"><?= $status_badge ?></td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="view.php?id=<?= $med['id'] ?>" title="View Details &amp; Batches" class="p-1.5 text-slate-500 hover:text-teal-600 rounded-md hover:bg-slate-100 transition-colors">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </a>
                                            <a href="edit.php?id=<?= $med['id'] ?>" title="Edit Medicine" class="p-1.5 text-slate-500 hover:text-blue-600 rounded-md hover:bg-slate-100 transition-colors">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                            </a>
                                            <a href="delete.php?id=<?= $med['id'] ?>&token=<?= csrf_token() ?>" onclick="return confirm('Are you sure you want to deactivate <?= e($med['name']) ?>?');" title="Deactivate" class="p-1.5 text-slate-500 hover:text-rose-600 rounded-md hover:bg-slate-100 transition-colors">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                            </a>
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
