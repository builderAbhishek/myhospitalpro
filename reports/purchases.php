<?php
require_once __DIR__ . '/../includes/app.php';
require_login();
has_permission('view_reports');

$page_title = 'Purchases & Procurement Report';
$active_menu = 'reports';

$db = db();

$start_date = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$end_date = sanitize($_GET['end_date'] ?? date('Y-m-d'));
$supplier_id = (int)($_GET['supplier_id'] ?? 0);
$payment_status = sanitize($_GET['payment_status'] ?? '');
$export = sanitize($_GET['export'] ?? '');

$where_clauses = ["p.purchase_date BETWEEN :start_date AND :end_date", "p.status != 'cancelled'"];
$params = ['start_date' => $start_date, 'end_date' => $end_date];

if ($supplier_id > 0) {
    $where_clauses[] = "p.supplier_id = :supplier_id";
    $params['supplier_id'] = $supplier_id;
}

if (!empty($payment_status)) {
    $where_clauses[] = "p.payment_status = :payment_status";
    $params['payment_status'] = $payment_status;
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT 
        p.*,
        s.name AS supplier_name,
        s.phone AS supplier_phone,
        (SELECT COUNT(id) FROM purchase_items WHERE purchase_id = p.id) AS item_count
    FROM purchases p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE {$where_sql}
    ORDER BY p.purchase_date DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=MHP_Purchases_Report_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Purchase #', 'Supplier Bill #', 'Date', 'Supplier', 'Items', 'Total Cost', 'Paid', 'Due Balance', 'Status']);
    foreach ($purchases as $row) {
        fputcsv($out, [
            $row['purchase_number'],
            $row['invoice_number'],
            $row['purchase_date'],
            $row['supplier_name'],
            $row['item_count'],
            $row['total_amount'],
            $row['paid_amount'],
            $row['balance_amount'],
            strtoupper($row['payment_status'])
        ]);
    }
    fclose($out);
    exit;
}

$total_bills = count($purchases);
$total_spend = 0;
$total_paid = 0;
$total_outstanding = 0;

foreach ($purchases as $p) {
    $total_spend += (float)$p['total_amount'];
    $total_paid += (float)$p['paid_amount'];
    $total_outstanding += (float)$p['balance_amount'];
}

$suppliers = $db->query("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name ASC")->fetchAll();

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
                <span class="text-xs font-semibold text-primary-600">Procurement Report</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight mt-1">Purchases & Procurement</h1>
            <p class="text-sm text-gray-500">Inventory acquisition costs, vendor invoices, and outstanding accounts payable</p>
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
    <form method="GET" action="purchases.php" class="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-sm grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
            <input type="date" name="start_date" value="<?= e($start_date) ?>" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">End Date</label>
            <input type="date" name="end_date" value="<?= e($end_date) ?>" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Supplier</label>
            <select name="supplier_id" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="0">All Suppliers</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $supplier_id === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Status</label>
            <select name="payment_status" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-lg">
                <option value="">All Statuses</option>
                <option value="paid" <?= $payment_status === 'paid' ? 'selected' : '' ?>>Fully Paid</option>
                <option value="partial" <?= $payment_status === 'partial' ? 'selected' : '' ?>>Partially Paid</option>
                <option value="pending" <?= $payment_status === 'pending' ? 'selected' : '' ?>>Pending / Unpaid</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 py-2 bg-primary-600 text-white text-xs font-semibold rounded-lg hover:bg-primary-700 transition">Filter</button>
            <a href="purchases.php" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">Reset</a>
        </div>
    </form>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Total Bills</span>
            <span class="text-xl font-black text-gray-900"><?= number_format($total_bills) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Total Purchase Cost</span>
            <span class="text-xl font-black text-purple-600"><?= format_currency($total_spend) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Disbursed Payments</span>
            <span class="text-xl font-black text-emerald-600"><?= format_currency($total_paid) ?></span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
            <span class="text-xs text-gray-500 block">Accounts Payable (Due)</span>
            <span class="text-xl font-black text-amber-600"><?= format_currency($total_outstanding) ?></span>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Purchase #</th>
                        <th class="py-3 px-4">Vendor Bill #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Supplier</th>
                        <th class="py-3 px-4 text-center">Items</th>
                        <th class="py-3 px-4 text-right">Total Cost</th>
                        <th class="py-3 px-4 text-right">Paid</th>
                        <th class="py-3 px-4 text-right">Balance Due</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($purchases)): ?>
                        <tr><td colspan="10" class="py-12 text-center text-gray-400">No purchase records found for this period.</td></tr>
                    <?php else: ?>
                        <?php foreach ($purchases as $row): ?>
                            <tr class="hover:bg-gray-50/60 transition">
                                <td class="py-3 px-4 font-mono font-bold text-gray-900"><?= e($row['purchase_number']) ?></td>
                                <td class="py-3 px-4 font-mono text-gray-600"><?= e($row['invoice_number'] ?: '—') ?></td>
                                <td class="py-3 px-4 text-gray-600 whitespace-nowrap"><?= date('d M Y', strtotime($row['purchase_date'])) ?></td>
                                <td class="py-3 px-4 font-medium text-gray-900"><?= e($row['supplier_name']) ?></td>
                                <td class="py-3 px-4 text-center"><?= $row['item_count'] ?></td>
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
                                    <a href="../purchases/view.php?id=<?= $row['id'] ?>" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded text-[11px] transition">Details</a>
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
