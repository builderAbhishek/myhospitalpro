<?php
/**
 * Low Stock Alert & Reorder Dashboard
 */
$page_title = "Low Stock Alerts";
$current_page = "inventory";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

$stmt = $db->query("
    SELECT m.*, c.name as category_name, man.name as manufacturer_name,
           COALESCE(SUM(b.quantity), 0) as on_hand_stock,
           (m.reorder_level - COALESCE(SUM(b.quantity), 0)) as shortage
    FROM medicines m
    LEFT JOIN medicine_categories c ON m.category_id = c.id
    LEFT JOIN manufacturers man ON m.manufacturer_id = man.id
    LEFT JOIN medicine_batches b ON m.id = b.medicine_id AND b.status = 'active'
    WHERE m.status = 'active'
    GROUP BY m.id
    HAVING on_hand_stock <= m.reorder_level
    ORDER BY on_hand_stock ASC
");
$low_items = $stmt->fetchAll();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Low Stock Alerts &amp; Reorder List</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Medicines whose aggregate batch on-hand stock has dropped below reorder thresholds.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="../purchases/create.php" class="btn-primary text-xs py-2 px-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    <span>Create Purchase Order</span>
                </a>
            </div>
        </div>

        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Critical Stock Replenishment Required</h2>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200"><?= count($low_items) ?> Items Requiring Order</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Medicine Item</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Manufacturer</th>
                            <th class="py-3 px-4 text-right">Current On-Hand</th>
                            <th class="py-3 px-4 text-right">Reorder Threshold</th>
                            <th class="py-3 px-4 text-right">Deficit Shortage</th>
                            <th class="py-3 px-4 text-right">Estimated Cost to Replenish</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($low_items)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-10 h-10 text-emerald-400 mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <p class="font-bold text-slate-700">All Stock Levels Healthy</p>
                                        <p class="text-xs text-slate-400 mt-0.5">No medicines are currently at or below their reorder thresholds.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($low_items as $item): ?>
                                <?php
                                    $shortage = max(0, (int)$item['shortage']);
                                    $est_cost = $shortage * (float)$item['purchase_price'];
                                ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900"><?= e($item['name']) ?></div>
                                        <div class="text-[11px] text-slate-400 font-mono"><?= e($item['medicine_code']) ?></div>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600"><?= e($item['category_name']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= e($item['manufacturer_name'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-right font-extrabold text-rose-600">
                                        <?= (int)$item['on_hand_stock'] ?> <span class="font-normal text-[11px] text-slate-500"><?= e($item['unit']) ?></span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-semibold text-slate-700"><?= (int)$item['reorder_level'] ?> <?= e($item['unit']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-amber-600"><?= $shortage ?> <?= e($item['unit']) ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-slate-900"><?= format_currency($est_cost) ?></td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="../purchases/create.php" class="btn-primary text-[11px] py-1 px-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded">
                                            Reorder
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
