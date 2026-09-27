<?php
require_once __DIR__ . '/../includes/app.php';
require_login();
has_permission('view_reports');

$page_title = 'Sales Ledger Report';
$active_menu = 'reports';

$db = db();

$start_date = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitize($_GET['end_date'] ?? date('Y-m-d'));
$payment_method = sanitize($_GET['payment_method'] ?? '');
$customer_id = (int)($_GET['customer_id'] ?? 0);
$export = sanitize($_GET['export'] ?? '');

$where_clauses = ["DATE(s.sale_date) BETWEEN :start_date AND :end_date", "s.status != 'cancelled'"];
$params = ['start_date' => $start_date, 'end_date' => $end_date];

if (!empty($payment_method)) {
    $where_clauses[] = "s.payment_method = :payment_method";
    $params['payment_method'] = $payment_method;
}

if ($customer_id > 0) {
    $where_clauses[] = "s.customer_id = :customer_id";
    $params['customer_id'] = $customer_id;
}

$where_sql = implode(' AND ', $where_clauses);

// Query
$sql = "
    SELECT 
        s.*,
        c.name AS customer_name,
        c.phone AS customer_phone,
        u.name AS cashier_name,
        (SELECT COUNT(id) FROM sale_items WHERE sale_id = s.id) AS item_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE {$where_sql}
    ORDER BY s.sale_date DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// CSV Export
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=MHP_Sales_Report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Invoice #', 'Date & Time', 'Customer', 'Phone', 'Items', 'Payment Mode', 'Subtotal', 'Discount', 'GST Tax', 'Total Amount', 'Paid', 'Due', 'Cashier']);
    foreach ($sales as $row) {
        fputcsv($out, [
            $row['invoice_number'],
            $row['sale_date'],
            $row['customer_name'] ?? 'Walk-in Customer',
            $row['customer_phone'] ?? '',
            $row['item_count'],
            strtoupper($row['payment_method']),
            $row['subtotal'],
            $row['discount_amount'],
            $row['tax_amount'],
            $row['total_amount'],
            $row['paid_amount'],
            $row['balance_amount'],
            $row['cashier_name']
        ]);
    }
    fclose($out);
    exit;
}

// Summary Metrics
$total_invoices = count($sales);
$total_revenue = 0;
$total_collected = 0;
$total_due = 0;
$total_discount = 0;
$total_tax = 0;

foreach ($sales as $s) {
    $total_revenue += (float)$s['total_amount'];
    $total_collected += (float)$s['paid_amount'];
    $total_due += (float)$s['balance_amount'];
    $total_discount += (float)$s['discount_amount'];
    $total_tax += (float)$s['tax_amount'];
}

// Customers list for dropdown
$customers = $db->query("SELECT id, name, phone FROM customers WHERE status = 'active' ORDER BY name ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="index.php" class="text-xs text-gray-500 hover:text-gray-700">&larr; Reports Hub</a>
                <span class="text-gray-300">/</span>
                <span class="text-xs font-semibold text-primary-600">Sales Ledger</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Sales Ledger & Invoices</h1>
            <p class="text-sm text-gray-500">Comprehensive breakdown of all retail transactions and GST collections</p>
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
    <form method="GET" action="sales.php" class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= e($start_date) ?>" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="<?= e($end_date) ?>" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Mode</label>
            <select name="payment_method" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="">All Methods</option>
                <option value="cash" <?= $payment_method === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="card" <?= $payment_method === 'card' ? 'selected' : '' ?>>Card</option>
                <option value="upi" <?= $payment_method === 'upi' ? 'selected' : '' ?>>UPI</option>
                <option value="credit" <?= $payment_method === 'credit' ? 'selected' : '' ?>>Credit</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Customer</label>
            <select name="customer_id" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="0">All Customers</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $customer_id === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['phone']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition">Filter</button>
            <a href="sales.php" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">Reset</a>
        </div>
    </form>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Total Bills</span>
            <span class="text-xl font-black text-gray-900"><?= number_format($total_invoices) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Gross Sales</span>
            <span class="text-xl font-black text-blue-600"><?= format_currency($total_revenue) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Amount Collected</span>
            <span class="text-xl font-black text-emerald-600"><?= format_currency($total_collected) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Outstanding Due</span>
            <span class="text-xl font-black text-amber-600"><?= format_currency($total_due) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">GST Collected</span>
            <span class="text-xl font-black text-purple-600"><?= format_currency($total_tax) ?></span>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Customer</th>
                        <th class="py-3 px-4 text-center">Items</th>
                        <th class="py-3 px-4">Mode</th>
                        <th class="py-3 px-4 text-right">Tax (GST)</th>
                        <th class="py-3 px-4 text-right">Total</th>
                        <th class="py-3 px-4 text-right">Paid</th>
                        <th class="py-3 px-4 text-right">Due</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($sales)): ?>
                        <tr><td colspan="11" class="py-12 text-center text-gray-400">No sales transactions found for this date range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sales as $row): ?>
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="py-3 px-4 font-mono font-bold text-gray-900"><?= e($row['invoice_number']) ?></td>
                                <td class="py-3 px-4 text-gray-600 whitespace-nowrap"><?= date('d M Y, h:i A', strtotime($row['sale_date'])) ?></td>
                                <td class="py-3 px-4 font-medium text-gray-900">
                                    <?= e($row['customer_name'] ?? 'Walk-in Customer') ?>
                                    <?php if (!empty($row['customer_phone'])): ?>
                                        <span class="block text-gray-400 text-[10px]"><?= e($row['customer_phone']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center"><?= $row['item_count'] ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700 uppercase"><?= e($row['payment_method']) ?></span>
                                </td>
                                <td class="py-3 px-4 text-right text-gray-600"><?= format_currency($row['tax_amount']) ?></td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900"><?= format_currency($row['total_amount']) ?></td>
                                <td class="py-3 px-4 text-right text-emerald-600 font-semibold"><?= format_currency($row['paid_amount']) ?></td>
                                <td class="py-3 px-4 text-right text-amber-600 font-semibold"><?= format_currency($row['balance_amount']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <?php if ($row['payment_status'] === 'paid'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">PAID</span>
                                    <?php elseif ($row['payment_status'] === 'partial'): ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">PARTIAL</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">UNPAID</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <a href="../billing/invoice.php?id=<?= $row['id'] ?>" target="_blank" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded text-[11px] transition">Invoice</a>
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
