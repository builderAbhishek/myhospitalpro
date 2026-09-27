<?php
/**
 * Supplier / Distributor Directory
 */
$page_title = "Suppliers";
$current_page = "suppliers";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

$search = trim($_GET['search'] ?? '');
$where = ["s.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where[] = "(s.name LIKE ? OR s.phone LIKE ? OR s.gstin LIKE ? OR s.supplier_code LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
}

$where_sql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.*, 
           COUNT(p.id) as total_purchases,
           COALESCE(SUM(p.grand_total), 0) as total_purchased
    FROM suppliers s
    LEFT JOIN purchases p ON s.id = p.supplier_id AND p.status != 'cancelled'
    WHERE {$where_sql}
    GROUP BY s.id
    ORDER BY s.name ASC
");
$stmt->execute($params);
$suppliers = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Suppliers &amp; Distributors</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Manage pharma stockists, drug licenses, procurement ledgers, and payables.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="add.php" class="btn-primary text-xs py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    <span>Register Supplier</span>
                </a>
            </div>
        </div>

        <div class="app-card p-4 bg-white border border-slate-200 rounded-xl shadow-xs">
            <form method="GET" action="index.php" class="flex gap-3">
                <div class="flex-1 relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </span>
                    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by distributor name, GSTIN, or phone..." 
                           class="w-full border border-slate-300 rounded-lg pl-9 pr-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-teal-600">
                </div>
                <button type="submit" class="btn-primary text-xs py-2 px-4 bg-slate-800 hover:bg-slate-900 text-white rounded-lg">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn-secondary text-xs py-2 px-3 bg-slate-100 text-slate-700 rounded-lg">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Distributor Agency</th>
                            <th class="py-3 px-4">Contact Person</th>
                            <th class="py-3 px-4">Contact &amp; Location</th>
                            <th class="py-3 px-4">GSTIN &amp; DL</th>
                            <th class="py-3 px-4 text-center">Bills</th>
                            <th class="py-3 px-4 text-right">Total Inward</th>
                            <th class="py-3 px-4 text-right">Payable Balance</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($suppliers)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">No suppliers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suppliers as $s): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900">
                                            <a href="view.php?id=<?= $s['id'] ?>" class="text-teal-700 hover:underline"><?= e($s['name']) ?></a>
                                        </div>
                                        <div class="text-[11px] font-mono text-slate-400"><?= e($s['supplier_code']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-slate-800"><?= e($s['contact_person']) ?></td>
                                    <td class="py-3 px-4 text-slate-600">
                                        <div><?= e($s['phone']) ?></div>
                                        <div class="text-[11px] text-slate-400"><?= e($s['city']) ?>, <?= e($s['state']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-slate-600">
                                        <div>GST: <?= e($s['gstin']) ?></div>
                                        <div class="text-[10px] text-slate-400">DL: <?= e($s['drug_license'] ?: 'N/A') ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 font-semibold text-[11px] text-slate-700"><?= (int)$s['total_purchases'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= format_currency($s['total_purchased']) ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold <?= (float)$s['current_balance'] > 0 ? 'text-rose-600' : 'text-slate-500' ?>">
                                        <?= format_currency($s['current_balance']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="view.php?id=<?= $s['id'] ?>" title="View Ledger" class="p-1.5 text-slate-500 hover:text-teal-600 rounded hover:bg-slate-100">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                            </a>
                                            <a href="edit.php?id=<?= $s['id'] ?>" title="Edit Supplier" class="p-1.5 text-slate-500 hover:text-blue-600 rounded hover:bg-slate-100">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                            </a>
                                            <a href="payment.php?supplier_id=<?= $s['id'] ?>" title="Pay Supplier" class="p-1.5 text-teal-600 hover:text-teal-800 rounded hover:bg-teal-50">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
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
