<?php
require_once __DIR__ . '/../includes/app.php';
require_login();
has_permission('view_reports');

$page_title = 'Financial & Analytical Reports';
$active_menu = 'reports';

$db = db();

// Period Filter
$period = sanitize($_GET['period'] ?? 'this_month');
$start_date = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitize($_GET['end_date'] ?? date('Y-m-d'));

if ($period === 'today') {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d');
} elseif ($period === 'yesterday') {
    $start_date = date('Y-m-d', strtotime('-1 day'));
    $end_date = date('Y-m-d', strtotime('-1 day'));
} elseif ($period === 'this_week') {
    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d');
} elseif ($period === 'this_month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-d');
} elseif ($period === 'this_year') {
    $start_date = date('Y-01-01');
    $end_date = date('Y-m-d');
}

// 1. Sales metrics for period
$sales_stmt = $db->prepare("
    SELECT 
        COUNT(id) AS total_bills,
        COALESCE(SUM(total_amount), 0) AS gross_sales,
        COALESCE(SUM(discount_amount), 0) AS total_discount,
        COALESCE(SUM(tax_amount), 0) AS total_tax,
        COALESCE(SUM(paid_amount), 0) AS total_collected,
        COALESCE(SUM(balance_amount), 0) AS total_outstanding
    FROM sales 
    WHERE DATE(sale_date) BETWEEN :start_date AND :end_date 
      AND status != 'cancelled'
");
$sales_stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$sales_data = $sales_stmt->fetch();

// 2. Purchases metrics for period
$purch_stmt = $db->prepare("
    SELECT 
        COUNT(id) AS total_bills,
        COALESCE(SUM(total_amount), 0) AS gross_purchases,
        COALESCE(SUM(paid_amount), 0) AS total_paid,
        COALESCE(SUM(balance_amount), 0) AS total_due
    FROM purchases 
    WHERE purchase_date BETWEEN :start_date AND :end_date 
      AND status != 'cancelled'
");
$purch_stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$purch_data = $purch_stmt->fetch();

// 3. Profit calculation for period
// Revenue = sales total_amount
// COGS = sum of (sale_items.quantity * medicine_batches.purchase_price)
$cogs_stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(si.quantity * b.purchase_price), 0) AS total_cogs
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN medicine_batches b ON si.batch_id = b.id
    WHERE DATE(s.sale_date) BETWEEN :start_date AND :end_date 
      AND s.status != 'cancelled'
");
$cogs_stmt->execute(['start_date' => $start_date, 'end_date' => $end_date]);
$cogs_val = (float)($cogs_stmt->fetch()['total_cogs'] ?? 0);

$gross_revenue = (float)$sales_data['gross_sales'];
$gross_profit = $gross_revenue - $cogs_val;
$margin_pct = $gross_revenue > 0 ? ($gross_profit / $gross_revenue) * 100 : 0;

// 4. Current Inventory Valuation (Global current stock)
$val_stmt = $db->query("
    SELECT 
        COALESCE(SUM(current_stock * purchase_price), 0) AS cost_valuation,
        COALESCE(SUM(current_stock * mrp), 0) AS retail_valuation,
        COALESCE(SUM(current_stock), 0) AS total_units
    FROM medicine_batches 
    WHERE current_stock > 0 AND status = 'active'
");
$inventory_val = $val_stmt->fetch();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Financial & Management Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Audit-ready ledgers, profitability analytics, and live stock valuation</p>
        </div>
        
        <!-- Period Selector Form -->
        <form method="GET" action="index.php" class="flex flex-wrap items-center gap-2 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
            <select name="period" onchange="this.form.submit()" class="text-xs font-semibold px-3 py-1.5 border border-gray-200 rounded-lg text-gray-700 bg-gray-50 focus:bg-white focus:outline-none">
                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="yesterday" <?= $period === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                <option value="this_week" <?= $period === 'this_week' ? 'selected' : '' ?>>This Week</option>
                <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
                <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
                <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
            
            <input type="date" name="start_date" value="<?= e($start_date) ?>" class="text-xs px-2 py-1.5 border border-gray-200 rounded-lg text-gray-700">
            <span class="text-xs text-gray-400">to</span>
            <input type="date" name="end_date" value="<?= e($end_date) ?>" class="text-xs px-2 py-1.5 border border-gray-200 rounded-lg text-gray-700">
            
            <button type="submit" class="px-3 py-1.5 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition">
                Apply
            </button>
        </form>
    </div>

    <!-- Executive Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Sales Total -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Gross Sales</span>
                <span class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900"><?= format_currency($sales_data['gross_sales']) ?></div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span><?= number_format($sales_data['total_bills']) ?> Completed Invoices</span>
                <span class="text-emerald-600 font-semibold"><?= format_currency($sales_data['total_collected']) ?> Recv</span>
            </div>
        </div>

        <!-- Purchases Total -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Procurement Cost</span>
                <span class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900"><?= format_currency($purch_data['gross_purchases']) ?></div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span><?= number_format($purch_data['total_bills']) ?> Vendor Bills</span>
                <span class="text-amber-600 font-semibold"><?= format_currency($purch_data['total_due']) ?> Due</span>
            </div>
        </div>

        <!-- Estimated Gross Profit -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Period Gross Profit</span>
                <span class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-emerald-600"><?= format_currency($gross_profit) ?></div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span>Margin: <strong class="text-emerald-700"><?= number_format($margin_pct, 1) ?>%</strong></span>
                <span>COGS: <?= format_currency($cogs_val) ?></span>
            </div>
        </div>

        <!-- Current Stock Valuation -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200/80 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stock Assets</span>
                <span class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="text-2xl font-black text-gray-900"><?= format_currency($inventory_val['cost_valuation']) ?></div>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                <span>Retail: <?= format_currency($inventory_val['retail_valuation']) ?></span>
                <span><?= number_format($inventory_val['total_units']) ?> Units</span>
            </div>
        </div>
    </div>

    <!-- Navigation Hub to Detail Reports -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Sales Ledger Card -->
        <a href="sales.php" class="bg-white p-6 rounded-2xl border border-gray-200/80 hover:border-primary-400 hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">Sales Ledger & Invoices</h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Tax invoice records, customer breakdowns, payment method analysis, and CSV export.</p>
            <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 group-hover:translate-x-1 transition">
                Open Sales Report &rarr;
            </div>
        </a>

        <!-- Purchases Ledger Card -->
        <a href="purchases.php" class="bg-white p-6 rounded-2xl border border-gray-200/80 hover:border-primary-400 hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">Procurement & Purchases</h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Vendor bills, incoming shipments, supplier ledger, and unpaid liabilities.</p>
            <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 group-hover:translate-x-1 transition">
                Open Purchases Report &rarr;
            </div>
        </a>

        <!-- Inventory Valuation Card -->
        <a href="inventory.php" class="bg-white p-6 rounded-2xl border border-gray-200/80 hover:border-primary-400 hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">Stock Valuation & Health</h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">Inventory cost vs retail value, batch expiration timeline, and low stock items.</p>
            <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 group-hover:translate-x-1 transition">
                Open Inventory Report &rarr;
            </div>
        </a>

        <!-- Profit & Loss Card -->
        <a href="profit.php" class="bg-white p-6 rounded-2xl border border-gray-200/80 hover:border-primary-400 hover:shadow-md transition group">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-base font-bold text-gray-900 group-hover:text-primary-600 transition">Profit & Margin Analysis</h3>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">True batch-level COGS deduction, net profitability trends, and margin ratios.</p>
            <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 group-hover:translate-x-1 transition">
                Open Profit Analytics &rarr;
            </div>
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
