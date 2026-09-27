<?php
/**
 * Expiry Tracking Dashboard (<30d, <60d, <90d & Expired)
 */
$page_title = "Expiry Tracking";
$current_page = "inventory";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

require_login();
$db = get_db();

$tab = $_GET['tab'] ?? '30';

$where = "b.quantity > 0";
if ($tab === 'expired') {
    $where .= " AND b.expiry_date < CURDATE()";
} elseif ($tab === '30') {
    $where .= " AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($tab === '60') {
    $where .= " AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)";
} elseif ($tab === '90') {
    $where .= " AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
}

$stmt = $db->query("
    SELECT b.*, m.name as medicine_name, m.medicine_code, m.rack_number, m.unit
    FROM medicine_batches b
    JOIN medicines m ON b.medicine_id = m.id
    WHERE {$where}
    ORDER BY b.expiry_date ASC
");
$batches = $stmt->fetchAll();

// Counts for tabs
$cnt_expired = (int)$db->query("SELECT COUNT(*) FROM medicine_batches WHERE quantity > 0 AND expiry_date < CURDATE()")->fetchColumn();
$cnt_30 = (int)$db->query("SELECT COUNT(*) FROM medicine_batches WHERE quantity > 0 AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
$cnt_60 = (int)$db->query("SELECT COUNT(*) FROM medicine_batches WHERE quantity > 0 AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetchColumn();
$cnt_90 = (int)$db->query("SELECT COUNT(*) FROM medicine_batches WHERE quantity > 0 AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Medicine Expiry Monitoring</h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5">Track near-expiry stock to prioritize dispensing or quarantine for vendor return.</p>
            </div>
            <a href="index.php" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg">
                &larr; Stock Overview
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
            <a href="expiry.php?tab=30" class="text-xs font-bold py-1.5 px-3 rounded-lg transition-colors <?= $tab === '30' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                Expiring in &lt; 30 Days (<?= $cnt_30 ?>)
            </a>
            <a href="expiry.php?tab=60" class="text-xs font-bold py-1.5 px-3 rounded-lg transition-colors <?= $tab === '60' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                &lt; 60 Days (<?= $cnt_60 ?>)
            </a>
            <a href="expiry.php?tab=90" class="text-xs font-bold py-1.5 px-3 rounded-lg transition-colors <?= $tab === '90' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                &lt; 90 Days (<?= $cnt_90 ?>)
            </a>
            <a href="expiry.php?tab=expired" class="text-xs font-bold py-1.5 px-3 rounded-lg transition-colors <?= $tab === 'expired' ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' ?>">
                Already Expired (<?= $cnt_expired ?>)
            </a>
        </div>

        <!-- Table -->
        <div class="app-card bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Medicine</th>
                            <th class="py-3 px-4">Batch Number</th>
                            <th class="py-3 px-4">Expiry Date</th>
                            <th class="py-3 px-4 text-center">Days Status</th>
                            <th class="py-3 px-4 text-right">Available Qty</th>
                            <th class="py-3 px-4 font-mono">Location</th>
                            <th class="py-3 px-4 text-right">At-Risk Value (Cost)</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400">No batches match this expiry window.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): ?>
                                <?php
                                    $exp_time = strtotime($b['expiry_date']);
                                    $days_left = floor(($exp_time - time()) / 86400);
                                    $cost = (float)$b['quantity'] * (float)$b['purchase_price'];
                                ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-4 font-bold text-slate-900"><?= e($b['medicine_name']) ?></td>
                                    <td class="py-3 px-4 font-mono font-bold text-slate-800"><?= e($b['batch_number']) ?></td>
                                    <td class="py-3 px-4 font-medium text-slate-700"><?= format_date($b['expiry_date']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if ($days_left < 0): ?>
                                            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px]">Expired (<?= abs($days_left) ?>d ago)</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]"><?= $days_left ?> days left</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= (int)$b['quantity'] ?> <?= e($b['unit']) ?></td>
                                    <td class="py-3 px-4 font-mono text-slate-600"><?= e($b['rack_number'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-right font-bold text-rose-600"><?= format_currency($cost) ?></td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="stock-adjustment.php?medicine_id=<?= $b['medicine_id'] ?>&batch_id=<?= $b['id'] ?>&type=EXPIRED" class="btn-primary text-[11px] py-1 px-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded">
                                            Quarantine
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
