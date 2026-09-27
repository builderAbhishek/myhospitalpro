<?php
require_once __DIR__ . '/../includes/app.php';
require_login();
has_permission('view_reports');

$page_title = 'Profit & Loss Analysis';
$active_menu = 'reports';

$db = db();

$start_date = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitize($_GET['end_date'] ?? date('Y-m-d'));
$group_by = sanitize($_GET['group_by'] ?? 'day'); // day, category, medicine
$export = sanitize($_GET['export'] ?? '');

if ($group_by === 'medicine') {
    $sql = "
        SELECT 
            m.name AS label,
            m.item_code AS sub_label,
            SUM(si.quantity) AS qty_sold,
            SUM(si.total_amount) AS revenue,
            SUM(si.quantity * b.purchase_price) AS cogs,
            (SUM(si.total_amount) - SUM(si.quantity * b.purchase_price)) AS gross_profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN medicine_batches b ON si.batch_id = b.id
        JOIN medicines m ON si.medicine_id = m.id
        WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date 
          AND s.status != 'cancelled'
        GROUP BY m.id, m.name, m.item_code
        ORDER BY gross_profit DESC
    ";
} elseif ($group_by === 'category') {
    $sql = "
        SELECT 
            COALESCE(c.name, 'Uncategorized') AS label,
            '' AS sub_label,
            SUM(si.quantity) AS qty_sold,
            SUM(si.total_amount) AS revenue,
            SUM(si.quantity * b.purchase_price) AS cogs,
            (SUM(si.total_amount) - SUM(si.quantity * b.purchase_price)) AS gross_profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN medicine_batches b ON si.batch_id = b.id
        JOIN medicines m ON si.medicine_id = m.id
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date 
          AND s.status != 'cancelled'
        GROUP BY c.id, c.name
        ORDER BY gross_profit DESC
    ";
} else {
    // By day
    $sql = "
        SELECT 
            DATE(s.sale_date) AS label,
            DAYNAME(s.sale_date) AS sub_label,
            SUM(si.quantity) AS qty_sold,
            SUM(si.total_amount) AS revenue,
            SUM(si.quantity * b.purchase_price) AS cogs,
            (SUM(si.total_amount) - SUM(si.quantity * b.purchase_price)) AS gross_profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        JOIN medicine_batches b ON si.batch_id = b.id
        WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date 
          AND s.status != 'cancelled'
        GROUP BY DATE(s.sale_date), DAYNAME(s.sale_date)
        ORDER BY label DESC
    ";
}

$stmt = $db->prepare($sql);
$stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$rows = $stmt->fetchAll();

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=MHP_Profit_Report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Classification', 'Detail', 'Qty Sold', 'Net Revenue', 'COGS Cost', 'Gross Profit', 'Margin %']);
    foreach ($rows as $r) {
        $margin = $r['revenue'] > 0 ? round(($r['gross_profit'] / $r['revenue']) * 100, 2) : 0;
        fputcsv($out, [
            $r['label'],
            $r['sub_label'],
            $r['qty_sold'],
            $r['revenue'],
            $r['cogs'],
            $r['gross_profit'],
            $margin . '%'
        ]);
    }
    fclose($out);
    exit;
}

$total_rev = 0;
$total_cogs = 0;
$total_profit = 0;
$total_sold = 0;

foreach ($rows as $r) {
    $total_rev += (float)$r['revenue'];
    $total_cogs += (float)$r['cogs'];
    $total_profit += (float)$r['gross_profit'];
    $total_sold += (int)$r['qty_sold'];
}

$overall_margin = $total_rev > 0 ? ($total_profit / $total_rev) * 100 : 0;

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
                <span class="text-xs font-semibold text-primary-600">Profitability</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Gross Profit & Margin Analysis</h1>
            <p class="text-sm text-gray-500">True batch-level cost accounting and profit margins</p>
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

    <!-- Filter Form -->
    <form method="GET" action="profit.php" class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= e($start_date) ?>" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="<?= e($end_date) ?>" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Grouping Dimension</label>
            <select name="group_by" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="day" <?= $group_by === 'day' ? 'selected' : '' ?>>Breakdown by Day</option>
                <option value="category" <?= $group_by === 'category' ? 'selected' : '' ?>>Breakdown by Category</option>
                <option value="medicine" <?= $group_by === 'medicine' ? 'selected' : '' ?>>Breakdown by Medicine</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition">Analyze</button>
            <a href="profit.php" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">Reset</a>
        </div>
    </form>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Total Revenue</span>
            <span class="text-xl font-black text-gray-900"><?= format_currency($total_rev) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Cost of Goods (COGS)</span>
            <span class="text-xl font-black text-purple-600"><?= format_currency($total_cogs) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Gross Profit</span>
            <span class="text-xl font-black text-emerald-600"><?= format_currency($total_profit) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Gross Margin %</span>
            <span class="text-xl font-black text-blue-600"><?= number_format($overall_margin, 1) ?>%</span>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4"><?= $group_by === 'day' ? 'Date' : ($group_by === 'category' ? 'Category' : 'Medicine') ?></th>
                        <th class="py-3 px-4"><?= $group_by === 'medicine' ? 'Item Code' : ($group_by === 'day' ? 'Day of Week' : '') ?></th>
                        <th class="py-3 px-4 text-center">Qty Sold</th>
                        <th class="py-3 px-4 text-right">Net Revenue</th>
                        <th class="py-3 px-4 text-right">COGS Cost</th>
                        <th class="py-3 px-4 text-right">Gross Profit</th>
                        <th class="py-3 px-4 text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="py-12 text-center text-gray-400">No sales transactions available for profit analysis in this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): 
                            $rev = (float)$row['revenue'];
                            $gp = (float)$row['gross_profit'];
                            $m_pct = $rev > 0 ? ($gp / $rev) * 100 : 0;
                        ?>
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="py-3 px-4 font-bold text-gray-900"><?= e($row['label']) ?></td>
                                <td class="py-3 px-4 font-mono text-gray-500"><?= e($row['sub_label']) ?></td>
                                <td class="py-3 px-4 text-center font-semibold text-gray-700"><?= number_format($row['qty_sold']) ?></td>
                                <td class="py-3 px-4 text-right text-gray-900"><?= format_currency($rev) ?></td>
                                <td class="py-3 px-4 text-right text-gray-500"><?= format_currency($row['cogs']) ?></td>
                                <td class="py-3 px-4 text-right font-black <?= $gp >= 0 ? 'text-emerald-600' : 'text-red-600' ?>"><?= format_currency($gp) ?></td>
                                <td class="py-3 px-4 text-right font-bold <?= $m_pct >= 20 ? 'text-emerald-600' : ($m_pct >= 10 ? 'text-amber-600' : 'text-gray-600') ?>">
                                    <?= number_format($m_pct, 1) ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
