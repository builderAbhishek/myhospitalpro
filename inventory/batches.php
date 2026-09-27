<?php
/**
 * Physical Inventory Batches Directory (FEFO Queue)
 */
$page_title = "Inventory Batches (FEFO)";
$current_page = "inventory";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(b.batch_number LIKE ? OR m.name LIKE ? OR m.generic_name LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
}

if (!empty($status)) {
    $where[] = "b.status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT b.*, m.name as medicine_name, m.medicine_code, m.unit, m.rack_number
    FROM medicine_batches b
    JOIN medicines m ON b.medicine_id = m.id
    WHERE {$where_sql}
    ORDER BY b.expiry_date ASC
");
$stmt->execute($params);
$batches = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Active Inventory Batches</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Physical batch-level stock sorted strictly by First-Expiry-First-Out (FEFO).</p>
            </div>
            <a href="index.php" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg">
                &larr; Stock Overview
            </a>
        </div>

        <!-- Filter -->
        <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
            <form method="GET" action="batches.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div class="sm:col-span-7">
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by batch number or medicine..." 
                           class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                </div>
                <div class="sm:col-span-3">
                    <select name="status" onchange="this.form.submit()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                        <option value="">All Batch Statuses</option>
                        <option value="active" <?= ($status === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="quarantined" <?= ($status === 'quarantined') ? 'selected' : '' ?>>Quarantined</option>
                        <option value="depleted" <?= ($status === 'depleted') ? 'selected' : '' ?>>Depleted</option>
                    </select>
                </div>
                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit" class="btn-primary w-full text-xs py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg">Filter</button>
                    <?php if (!empty($search) || !empty($status)): ?>
                        <a href="batches.php" class="btn-secondary text-xs py-2 px-3 bg-slate-100 text-slate-700 rounded-lg">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Medicine Item</th>
                            <th class="py-3 px-4">Batch Number</th>
                            <th class="py-3 px-4">Expiry Date</th>
                            <th class="py-3 px-4 text-center">Days Remaining</th>
                            <th class="py-3 px-4 text-right">Available Qty</th>
                            <th class="py-3 px-4 text-right">Purchase Price</th>
                            <th class="py-3 px-4 text-right">Selling Price</th>
                            <th class="py-3 px-4 text-right">Total Batch Worth</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="10" class="py-10 text-center text-slate-400">No batches found matching criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): ?>
                                <?php
                                    $exp_time = strtotime($b['expiry_date']);
                                    $days_left = floor(($exp_time - time()) / 86400);
                                    $days_badge = '<span class="text-slate-600 font-semibold">' . $days_left . ' days</span>';
                                    if ($days_left < 0) {
                                        $days_badge = '<span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px]">Expired</span>';
                                    } elseif ($days_left <= 30) {
                                        $days_badge = '<span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">' . $days_left . ' days</span>';
                                    }
                                    $worth = (float)$b['quantity'] * (float)$b['purchase_price'];
                                ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 font-bold text-slate-900"><?= e($b['medicine_name']) ?></td>
                                    <td class="py-3 px-4 font-mono font-bold text-slate-800"><?= e($b['batch_number']) ?></td>
                                    <td class="py-3 px-4"><?= format_date($b['expiry_date']) ?></td>
                                    <td class="py-3 px-4 text-center"><?= $days_badge ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= (int)$b['quantity'] ?></td>
                                    <td class="py-3 px-4 text-right text-slate-600"><?= format_currency($b['purchase_price']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-teal-700"><?= format_currency($b['selling_price']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= format_currency($worth) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?= $b['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= e($b['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="stock-adjustment.php?medicine_id=<?= $b['medicine_id'] ?>&batch_id=<?= $b['id'] ?>" class="text-xs font-semibold text-teal-700 hover:underline">
                                            Adjust
                                        </a>
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
