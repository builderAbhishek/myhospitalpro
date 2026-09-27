<?php
/**
 * Master Dashboard Controller
 * Real MySQL Analytics, KPIs, Chart.js dataset, and Quick POS shortcuts
 */
$page_title = "Executive Dashboard";
$current_page = "dashboard";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

require_login();
$user = current_user();
$db = get_db();

// 1. Today's Sales
$stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as total, COUNT(*) as count 
                    FROM sales 
                    WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
$today_sales_row = $stmt->fetch();
$today_sales = (float)$today_sales_row['total'];
$today_sales_count = (int)$today_sales_row['count'];

// Yesterday's Sales
$stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as total 
                    FROM sales 
                    WHERE DATE(created_at) = SUBDATE(CURDATE(), 1) AND status != 'cancelled'");
$yesterday_sales = (float)$stmt->fetchColumn();

// Sales % growth
$sales_growth = 0;
if ($yesterday_sales > 0) {
    $sales_growth = round((($today_sales - $yesterday_sales) / $yesterday_sales) * 100, 1);
}

// 2. Today's Purchases
$stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as total, COUNT(*) as count 
                    FROM purchases 
                    WHERE invoice_date = CURDATE() AND status != 'cancelled'");
$today_purchases_row = $stmt->fetch();
$today_purchases = (float)$today_purchases_row['total'];
$today_purchases_count = (int)$today_purchases_row['count'];

// 3. Total Stock Valuation (Cost & Retail)
$stmt = $db->query("SELECT COALESCE(SUM(quantity * purchase_price), 0) as cost_val,
                           COALESCE(SUM(quantity * selling_price), 0) as retail_val,
                           COALESCE(SUM(quantity), 0) as total_units
                    FROM medicine_batches 
                    WHERE status = 'active'");
$stock_valuation = $stmt->fetch();
$total_stock_value = (float)$stock_valuation['cost_val'];
$total_retail_value = (float)$stock_valuation['retail_val'];

// 4. Low Stock Medicines Count
$low_stock_stmt = $db->query("
    SELECT m.id
    FROM medicines m
    LEFT JOIN medicine_batches b ON m.id = b.medicine_id AND b.status = 'active'
    WHERE m.status = 'active'
    GROUP BY m.id, m.reorder_level
    HAVING COALESCE(SUM(b.quantity), 0) <= m.reorder_level
");
$low_stock_count = $low_stock_stmt->rowCount();

// 5. Expiring in 30 Days Count
$expiring_count = (int)$db->query("
    SELECT COUNT(*) 
    FROM medicine_batches 
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
      AND quantity > 0 AND status = 'active'
")->fetchColumn();

// 6. Total Receivables (Customer Outstanding Dues)
$customer_dues = (float)$db->query("SELECT COALESCE(SUM(current_balance), 0) FROM customers WHERE current_balance > 0 AND status = 'active'")->fetchColumn();

// 7. Total Payables (Supplier Dues)
$supplier_dues = (float)$db->query("SELECT COALESCE(SUM(current_balance), 0) FROM suppliers WHERE current_balance > 0 AND status = 'active'")->fetchColumn();

// 8. Recent 5 Sales
$recent_sales = $db->query("
    SELECT s.*, u.name as staff_name 
    FROM sales s 
    LEFT JOIN users u ON s.created_by = u.id 
    WHERE s.status != 'cancelled'
    ORDER BY s.created_at DESC 
    LIMIT 5
")->fetchAll();

// 9. Top 5 Selling Medicines
$top_medicines = $db->query("
    SELECT si.medicine_name, 
           COALESCE(mc.name, 'Medicine') as category,
           SUM(si.quantity) as sold_qty, 
           SUM(si.total) as total_revenue
    FROM sale_items si
    JOIN sales s ON si.sale_id = s.id
    JOIN medicines m ON si.medicine_id = m.id
    LEFT JOIN medicine_categories mc ON m.category_id = mc.id
    WHERE s.status != 'cancelled'
    GROUP BY si.medicine_id, si.medicine_name, mc.name
    ORDER BY sold_qty DESC
    LIMIT 5
")->fetchAll();

// 10. 7-Day Sales Trend for Chart.js
$sales_trend_stmt = $db->query("
    SELECT DATE_FORMAT(d.dt, '%a, %d %b') as day_label,
           COALESCE(SUM(s.grand_total), 0) as day_total
    FROM (
        SELECT CURDATE() - INTERVAL (a.a + (10 * b.a)) DAY as dt
        FROM (SELECT 0 as a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6) as a
        CROSS JOIN (SELECT 0 as a) as b
    ) d
    LEFT JOIN sales s ON DATE(s.created_at) = d.dt AND s.status != 'cancelled'
    GROUP BY d.dt
    ORDER BY d.dt ASC
");
$chart_rows = $sales_trend_stmt->fetchAll();
$chart_labels = array_column($chart_rows, 'day_label');
$chart_values = array_map('floatval', array_column($chart_rows, 'day_total'));
?>

<div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- Navbar Header -->
    <?php require_once __DIR__ . '/includes/navbar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 space-y-6">
        
        <!-- Welcome Header & Quick Action Buttons -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 tracking-tight">Good Morning, <?= e($user['name'] ?? 'Admin') ?></h1>
                <p class="text-xs md:text-sm text-slate-500 mt-0.5 flex items-center gap-2">
                    <span><?= date('l, d F Y') ?></span>
                    <span>•</span>
                    <span class="text-teal-700 font-medium">Terminal Connected to MySQL</span>
                </p>
            </div>
            <!-- Quick Actions Strip -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="billing/index.php" class="btn-primary text-xs py-2 px-3.5 shadow-sm bg-teal-600 hover:bg-teal-700 text-white rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6"/><path d="M16 12h-6"/><path d="M14 16h-4"/></svg>
                    <span>New Bill (F2)</span>
                </a>
                <a href="medicines/add.php" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    <span>Add Medicine</span>
                </a>
                <a href="purchases/create.php" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M19 18h2a1 1 0 0 0 1-1v-5.28a2 2 0 0 0-.8-1.6L18 8h-4"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
                    <span>New Purchase</span>
                </a>
                <a href="customers/add.php" class="btn-secondary text-xs py-2 px-3 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                    <span>Add Customer</span>
                </a>
            </div>
        </div>

        <!-- KPI Cards Grid (4 Primary Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Card 1: Today's Sales -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Today's Sales</span>
                    <span class="p-2 rounded-lg bg-teal-50 text-teal-700">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900"><?= format_currency($today_sales) ?></div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <?php if ($sales_growth >= 0): ?>
                        <span class="text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m18 15-6-6-6 6"/></svg>
                            +<?= $sales_growth ?>%
                        </span>
                    <?php else: ?>
                        <span class="text-rose-700 font-bold bg-rose-50 px-1.5 py-0.5 rounded flex items-center gap-0.5">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                            <?= $sales_growth ?>%
                        </span>
                    <?php endif; ?>
                    <span class="text-slate-400"><?= $today_sales_count ?> bills counter dispensed</span>
                </div>
            </div>

            <!-- Card 2: Today's Purchase -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Today's Purchase</span>
                    <span class="p-2 rounded-lg bg-blue-50 text-blue-700">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900"><?= format_currency($today_purchases) ?></div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <span class="text-slate-600 font-medium"><?= $today_purchases_count ?> Vendor Invoices Inwarded</span>
                </div>
            </div>

            <!-- Card 3: Total Stock Value -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Stock Value</span>
                    <span class="p-2 rounded-lg bg-indigo-50 text-indigo-700">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </span>
                </div>
                <div class="text-2xl font-extrabold text-slate-900"><?= format_currency($total_stock_value) ?></div>
                <div class="flex items-center gap-1.5 mt-2 text-xs">
                    <span class="text-slate-500">Retail Worth: <strong><?= format_currency($total_retail_value) ?></strong></span>
                </div>
            </div>

            <!-- Card 4: Low Stock & Expiry Alerts -->
            <div class="app-card p-5 bg-white border border-slate-200 rounded-xl shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between text-slate-500 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider">Inventory Alerts</span>
                    <span class="p-2 rounded-lg bg-amber-50 text-amber-700">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </span>
                </div>
                <div class="flex items-baseline gap-3">
                    <div class="text-2xl font-extrabold text-amber-600"><?= $low_stock_count ?></div>
                    <span class="text-xs text-slate-500 font-medium">Low Stock</span>
                    <span class="text-slate-300">•</span>
                    <div class="text-2xl font-extrabold text-rose-600"><?= $expiring_count ?></div>
                    <span class="text-xs text-slate-500 font-medium">&lt; 30d Expiry</span>
                </div>
                <div class="mt-2 text-xs text-slate-400">
                    <a href="inventory/low-stock.php" class="text-primary hover:underline font-semibold">View Actionable Items &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Ledger Financial Summary Strip -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-surface border border-appborder rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-emerald-50 text-emerald-700">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Patient Credit Outstanding</div>
                        <div class="text-lg font-bold text-slate-900"><?= format_currency($customer_dues) ?></div>
                    </div>
                </div>
                <a href="customers/index.php" class="text-xs font-semibold text-primary hover:underline">Manage Ledgers &rarr;</a>
            </div>

            <div class="bg-surface border border-appborder rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 rounded-lg bg-rose-50 text-rose-700">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Distributor / Supplier Payables</div>
                        <div class="text-lg font-bold text-slate-900"><?= format_currency($supplier_dues) ?></div>
                    </div>
                </div>
                <a href="suppliers/index.php" class="text-xs font-semibold text-primary hover:underline">View Payables &rarr;</a>
            </div>
        </div>

        <!-- Charts & Top Movers Section (2 Columns) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- 7-Day Sales Trend Chart (8 Cols) -->
            <div class="lg:col-span-8 bg-surface border border-appborder rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 tracking-tight">7-Day Sales Revenue Trend</h2>
                        <p class="text-xs text-slate-500">Real-time daily transaction velocity in ₹</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-teal-50 text-teal-700">Live Chart</span>
                </div>
                <div class="h-64 relative">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            <!-- Top Moving Medicines (4 Cols) -->
            <div class="lg:col-span-4 bg-surface border border-appborder rounded-xl p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Top Dispensed Medicines</h2>
                    <a href="reports/sales.php" class="text-xs text-primary hover:underline font-medium">All Reports</a>
                </div>
                <div class="space-y-3">
                    <?php if (empty($top_medicines)): ?>
                        <div class="text-xs text-slate-400 py-8 text-center">No sales recorded yet.</div>
                    <?php else: ?>
                        <?php foreach ($top_medicines as $idx => $med): ?>
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                                <div class="min-w-0 pr-2">
                                    <div class="text-xs font-bold text-slate-900 truncate"><?= e($med['medicine_name']) ?></div>
                                    <div class="text-[11px] text-slate-500"><?= e($med['category']) ?> • <?= (int)$med['sold_qty'] ?> units sold</div>
                                </div>
                                <div class="text-xs font-extrabold text-teal-700 shrink-0">
                                    <?= format_currency($med['total_revenue']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Counter Transactions Table -->
        <div class="bg-surface border border-appborder rounded-xl overflow-hidden shadow-xs">
            <div class="p-4 md:px-6 border-b border-appborder flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">Recent Sales Counter Transactions</h2>
                    <p class="text-xs text-slate-500">Latest completed counter invoices</p>
                </div>
                <a href="reports/sales.php" class="text-xs text-primary font-semibold hover:underline">View All Sales Ledger &rarr;</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-appborder text-slate-500 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Date & Time</th>
                            <th class="py-3 px-4">Customer Name</th>
                            <th class="py-3 px-4">Payment Mode</th>
                            <th class="py-3 px-4 text-right">Grand Total</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-appborder">
                        <?php if (empty($recent_sales)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">No recent sales found in MySQL.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_sales as $sale): ?>
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4 font-mono font-bold text-slate-900">
                                        <a href="billing/invoice.php?id=<?= $sale['id'] ?>" class="text-primary hover:underline"><?= e($sale['invoice_number']) ?></a>
                                    </td>
                                    <td class="py-3 px-4 text-slate-600"><?= format_datetime($sale['created_at']) ?></td>
                                    <td class="py-3 px-4 font-medium text-slate-800"><?= e($sale['customer_name']) ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-0.5 rounded-full font-medium text-[11px] bg-slate-100 text-slate-700">
                                            <?= e($sale['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-extrabold text-slate-900"><?= format_currency($sale['grand_total']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wider <?= $sale['payment_status'] === 'Paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                            <?= e($sale['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <a href="billing/invoice.php?id=<?= $sale['id'] ?>" class="text-slate-600 hover:text-teal-700 font-semibold p-1 inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect width="12" height="8" x="6" y="14"/></svg>
                                            <span>Invoice</span>
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

<!-- Chart.js Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesTrendChart');
    if (!ctx) return;

    const labels = <?= json_encode($chart_labels) ?>;
    const data = <?= json_encode($chart_values) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (₹)',
                data: data,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13, 148, 136, 0.1)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#0d9488',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return ' Sales: ₹' + context.parsed.y.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        callback: function(val) { return '₹' + val.toLocaleString('en-IN'); },
                        font: { size: 10 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
